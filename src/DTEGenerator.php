<?php

namespace arweb\DataTablesEditor;

use Config;
use DataTables\Editor;
use DataTables\Editor\Field;
use Exception;
use stdClass;

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
        foreach ($this->config['fields'] as $fieldName => $fieldDetails) {
            $column = [];
            $readFromDatabaseTableColumn = !isset($fieldDetails['use_table_column']) || $fieldDetails['use_table_column'] !== false;
            if ($readFromDatabaseTableColumn) {
                $column['data'] = $fieldName;
            }
            if (isset($fieldDetails['renderer'])) {
                $column['render'] = self::JSLiteral($fieldDetails['renderer']);
            }
            $columns[] = $column;
        }
        $jsonWithLiterals = $this->jsonWithLiterals($columns);
        return $jsonWithLiterals;
    }

    protected function editorFieldsJSON()
    {
        $editorFields = [];
        foreach ($this->config['fields'] as $fieldName => $fieldDetails) {
            if (empty($fieldDetails['type'])) {
                continue;
            }
            $fieldLabel = !empty($fieldDetails['label']) ? $fieldDetails['label'] : $fieldName;
            $editorField = [
                'label' => $fieldLabel,
                'name' => $fieldName,
                'type' => $fieldDetails['type'],
            ];
            if (isset($fieldDetails['options'])) {
                $editorField['options'] = $fieldDetails['options'];
            }
            $editorFields[] = $editorField;
        }
        return $this->jsonWithLiterals($editorFields);
    }

    protected function jsonWithLiterals($input)
    {
        $json = json_encode($input);
        $json = preg_replace('/\{\"value\"\:\"([^\"]+)\"\}/', '$1', $json);
        return $json;
    }

    /**
     * Builds Editor instance and processes data coming from _POST
     * @param bool $debug
     * @throws Exception
     */
    public function endpoint(bool $debug = false)
    {
        $sql_details = $this->sqlDetails(); // wird von DTE-Backend-Bibliothek benötigt
        include(public_path() . '/dte2/lib/DataTables.php');
        $editor = Editor::inst($db, $this->config['mainTable']);
        $fields = [];
        foreach ($this->config['fields'] as $fieldName => $fieldDetails) {
            if (empty($fieldDetails['type'])) {
                continue;
            }
            $fields[] = Field::inst($fieldName);
        }
        $editor->fields($fields);
        $editor->debug($debug);
        $editor->process($_POST);
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
}
