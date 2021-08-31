<?php

namespace arweb\DataTablesEditor;

use Exception,
    stdClass,
    Config,
    DataTables\Editor,
    DataTables\Editor\Field,
    DataTables\Editor\Format,
    DataTables\Editor\Mjoin,
    DataTables\Editor\Options,
    DataTables\Editor\Upload,
    DataTables\Editor\Validate,
    DataTables\Editor\ValidateOptions;

class DTEGenerator
{
    protected $config;
    protected $assetsHandler;

    public function __construct($config)
    {
        $this->config = $config;
        if (empty($this->config)) {
            throw new Exception('No editor config found - go create it.');
        }
        $this->assetsHandler = new DTEAssetsHandler($this);
    }

    public function view($viewFile, $viewParams)
    {
        $this->config['editorFieldsJSON'] = $this->editorFieldsJSON();
        $this->config['dataTableColumnsJSON'] = $this->dataTableColumnsJSON();
        $this->config['editorButtonsJSON'] = $this->editorButtonsJSON();
        return view($viewFile, $this->config + $viewParams);
    }

    public static function JSLiteral($literal)
    {
        $o = new stdClass();
        $o->value = $literal;
        return $o;
    }

    protected function editorButtonsJSON()
    {
        // default: 3 buttons
        if (empty($this->config['buttons'])) {
            $this->config['buttons'] = [
                'extend|create',
                'extend|edit',
                'extend|remove',
            ];
        }

        $buttons = [];
        foreach ($this->config['buttons'] as $buttonDefinition) {
            $buttonDefinitionParts = explode('|', $buttonDefinition);
            $buttonType = $buttonDefinitionParts[0];
            switch ($buttonType) {
                case 'extend':
                    $extendType = $buttonDefinitionParts[1];
                    $buttons[] = [
                        'extend' => $extendType,
                        'editor' => self::JSLiteral('editor'),
                    ];
                    break;
                case 'custom':
                    $customButtonText = $buttonDefinitionParts[1];
                    $customButtonFunctionName = $buttonDefinitionParts[2];
                    $buttons[] = [
                        'text' => $customButtonText,
                        'action' => self::JSLiteral($customButtonFunctionName),
                    ];
                    break;
            }
        }
        return $this->jsonWithLiterals($buttons);
    }

    protected function dataTableColumnsJSON()
    {
        $columns = [];
        foreach ($this->config['fields'] as $columnName => $columnConfig) {
            $column = [];
            $readFromDatabaseTableColumn = !isset($columnConfig['use_table_column']) || $columnConfig['use_table_column'] !== false;
            if ($readFromDatabaseTableColumn) {
                if (isset($columnConfig['optionJoin']['foreignLabel'])) {
                    $columnName = $columnConfig['optionJoin']['table'] . '.' . $columnConfig['optionJoin']['foreignLabel'];
                }
                $column['data'] = $columnName;
            }

            // mjoin array-type field using […]-syntax? render label field separated by ', '.
            if (preg_match('/\[/', $columnName) && !empty($columnConfig['optionCrossJoin'])) {
                $join = $columnConfig['optionCrossJoin'];
                $column['render'] = '[, ].' . $join['targetLabelField'];
                $column['data'] = $join['targetTable'];
                // DTE does currently not support Mjoin server-side search & sort.
                //   Hence, disable those features for this column.
                $column['sortable'] = false;
                $column['searchable'] = false;
            }

            if (isset($columnConfig['renderer'])) {
                $column['render'] = self::JSLiteral($columnConfig['renderer']);
            }
            $columns[] = $column;
        }

        $columns = $this->jsonWithLiterals($columns);
        return $columns;
    }

    protected function editorFieldsJSON()
    {
        $fields = [];
        foreach ($this->config['fields'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['type'])) {
                continue;
            }
            $fieldLabel = !empty($fieldConfig['label']) ? $fieldConfig['label'] : $fieldName;
            $field = [
                'label' => $fieldLabel,
                'name' => $fieldName,
                'type' => $fieldConfig['type'],
            ];

            // datetime field: default format
            if ($fieldConfig['type'] === 'datetime') {
                $field['format'] = !empty($fieldConfig['format']) ? $fieldConfig['format'] : 'YYYY-MM-DD HH:mm:SS';
            }

            // option source: static list or dynamic from database?
            if (isset($fieldConfig['options'])) {
                $field['options'] = $fieldConfig['options'];
            } elseif (isset($fieldConfig['optionQuery'])) {
                $field['options'] = $this->getOptionsByQuery($fieldConfig['optionQuery']);
            }

            // enable multi-select for all cross joins
            if (isset($fieldConfig['optionCrossJoin'])) {
                $field['multiple'] = true;
            }

            // field.submit
            if (isset($fieldConfig['submit'])) {
                $field['submit'] = $fieldConfig['submit'] !== false;
            }

            $fields[] = $field;
        }
        $fields = $this->jsonWithLiterals($fields);
        return $fields;
    }

    protected function getOptionsByQuery($optionQuery)
    {
        ob_start();
        $data = $this
            ->getEditorInst($optionQuery['table'])
            ->fields(
                Field::inst($optionQuery['keyColumn']),
                Field::inst($optionQuery['labelColumn'])
            )->process([
                'draw' => 1,
                'columns' => [
                    [
                        'data' => $optionQuery['keyColumn'],
                        'name' => 'keyColumn',
                        'search' => ['value' => '']
                    ],
                    [
                        'data' => $optionQuery['labelColumn'],
                        'name' => 'labelColumn',
                        'search' => ['value' => '']
                    ]
                ],
                'order' => [
                    ['column' => 0, 'dir' => 'asc']
                ],
                'start' => 0,
                'search' => [
                    'value' => false
                ],
                'length' => 10
            ])
            ->json();
        $optionResult = json_decode(ob_get_contents());

        $options = [];
        foreach ($optionResult->data as $row) {
            $options[$row->{$optionQuery['labelColumn']}] = $row->{$optionQuery['keyColumn']};
        }
        ob_end_clean();
        return $options;
    }

    protected function jsonWithLiterals($input)
    {
        $json = json_encode($input);
        $json = preg_replace('/\{\"value\"\:\"([^\"]+)\"\}/', '$1', $json);
        return $json;
    }

    public function getEditorInst($table)
    {
        // load database connection details
        $sql_details = $this->sqlDetails();

        // bootstrap DataTables
        include_once(public_path() . '/dte2/lib/DataTables.php');

        // create editor
        $editor = Editor::inst($db, $table);
        return $editor;
    }

    protected function dteLeftJoin($config, $join)
    {
        return [
            $join['table'],
            $join['table'] . '.' . $join['tableKey'],
            '=',
            $this->config['mainTable'] . '.' . $join['foreignKey']
        ];
    }

    /**
     * Builds Editor instance and processes data coming from _POST
     * @param bool $debug
     * @throws Exception
     */
    public function endpoint(bool $debug = false)
    {
        // create editor
        $editor = $this->getEditorInst($this->config['mainTable']);

        // determine needed joins and fields
        $leftJoins = [];
        $joins = [];
        $fields = [];

        // add left joins
        if (!empty($this->config['leftJoins']) && is_array($this->config['leftJoins'])) {
            foreach ($this->config['leftJoins'] as $table => $join) {
                $leftJoins[] = $this->dteLeftJoin($this->config, $join);
            }
        }

        // add fields
        foreach ($this->config['fields'] as $configFieldId => $configField) {
            // skip fields without type
            if (empty($configField['type'])) {
                continue;
            }

            // create DTE field
            $field = Field::inst($configFieldId);

            /**
             * TODO @feature: make setting a default value configurable
             */
            // TODO: set default value (if applicable)
            /*
            if (isset($configField['insertDefault'])) {
                $field->setValue($configField['insertDefault'])->set(Field::SET_CREATE);
            }
            */

            /**
             * TODO @feature: make setting NULL if field value is empty configurable
             */
            /*
            if (isset($configField['nullIfEmpty']) && $configField['nullIfEmpty'] === true) {
                $field->setFormatter('Format::nullEmpty');
            }
            */

            // is feature 'option join' configured?
            if (isset($configField['optionJoin'])) {
                $join = $configField['optionJoin'];

                // determine join parameters
                $leftJoins[] = $this->dteLeftJoin($config, $join);

                // add join table label field to selection
                $fields[] = Field::inst($join['table'] . '.' . $join['foreignLabel']);

                // add options to field
                $field->options($join['table'], $join['tableKey'], $join['foreignLabel']);
            }

            // is feature 'cross table option join' configured?
            if (isset($configField['optionCrossJoin'])) {
                $join = $configField['optionCrossJoin'];

                // multi-join
                $local_cross_link_local_field = $this->config['mainTable'] . '.' . $join['localTableKey'];
                $local_cross_link_cross_field = $join['crossTable'] . '.' . $join['crossToLocalTableKey'];
                $cross_target_link_target_field = $join['targetTable'] . '.' . $join['targetToCrossKey'];
                $cross_target_link_cross_field = $join['crossTable'] . '.' . $join['crossToTargetTableKey'];

                $joins[] = Mjoin::inst($join['targetTable'])
                    ->link($local_cross_link_local_field, $local_cross_link_cross_field)
                    ->link($cross_target_link_target_field, $cross_target_link_cross_field)
                    ->order($join['targetLabelField'] . ' asc')
                    //->validator('roles[].id', Validate::mjoinMaxCount(4, 'No more than four selections please'))
                    ->fields(
                        Field::inst($join['targetToCrossKey'])
                            ->validator(Validate::required())
                            ->options(Options::inst()
                                ->table($join['targetTable'])
                                ->value($join['targetToCrossKey'])
                                ->label($join['targetLabelField'])
                            ),
                        Field::inst($join['targetLabelField'])
                    );
            }

            // add field to editor processor
            if (!isset($configField['optionCrossJoin'])) {
                $fields[] = $field;
            }
        }

        // TODO: add condition to database query
        /*
        if (isset($formModel['select_conditions']) && is_array($formModel['select_conditions'])) {
            foreach ($formModel['select_conditions'] as $condition) {
                list($field, $operator, $value) = $condition;
                $editor->where($field, $value, $operator);
            }
        }
        */

        // add left joins for foreign key single-option selects
        foreach ($leftJoins as $joinParams) {
            call_user_func_array(array($editor, 'leftJoin'), $joinParams);
        }

        // add joins for cross-table multi-selects
        foreach ($joins as $join) {
            $editor->join($join);
        }

        // add fields
        $editor->fields($fields);

        // debugging or not?
        $editor->debug($debug);

        // run processor
        $editor->process($_POST);

        // output response
        $editor->json();
    }

    /**
     * Provides database connection parameter
     * @return array
     * @throws Exception
     */
    protected function sqlDetails()
    {
        $connection = $this->config['databaseConnection'];
        $config = Config::get('database.connections.' . $connection);
        if (empty($config)) {
            throw new Exception('Fatal error: could not find database connection');
        }
        $databaseType = $this->getDatabaseTypeByLaravelDriverName($config['driver']);
        if (!isset($databaseType)) {
            throw new Exception(
                'Fatal error: database driver "' . $config['driver'] . '" is not supported yet.'
            );
        }
        $sqlDetails = [
            'type' => $databaseType,
            'user' => $config['username'],
            'pass' => $config['password'],
            'host' => $config['host'],
            'port' => $config['port'],
            'db' => $config['database'],
            'pdoAttr' => array()
        ];
        if (!empty($config['charset'])) {
            $sqlDetails['dsn'] = 'charset=' . $config['charset'];
        }
        return $sqlDetails;
    }

    protected function getDatabaseTypeByLaravelDriverName($driver)
    {
        $mapping = [
            'mysql' => 'Mysql',
            'pgsql' => 'Postgres',
            'sqlsrv' => null,
        ];
        return $mapping[$driver] ?? null;
    }

    public function dom()
    {
        $dom = 'Blfrtip';

        // define feature → letter mapping
        $dteFeatureToPrependedString = [
            'SearchBuilder' => 'Q',
            'SearchPanes' => 'P',
        ];

        // no feature config? → we're done.
        if (!array_key_exists('features', $this->config)) {
            return $dom;
        }

        // check for requestes features and prepend string
        foreach ($dteFeatureToPrependedString as $featureId => $prependString) {
            if (in_array($featureId, $this->config['features'])) {
                $dom = $prependString . $dom;
            }
        }

        return $dom;
    }

    public function languagePath()
    {
        return $this->config['languagePath'] ?? null;
    }
}
