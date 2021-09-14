<?php

namespace arweb\DataTablesEditor;

use App\Http\Controllers\Controller as LaravelController;
use Illuminate\Support\Facades\Config;
use Exception;

abstract class DTEController extends LaravelController
{
    protected $editorGenerator;
    protected $editorConfigKey;
    protected $editorViewFile;
    public $editorConfig;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (empty($this->editorConfigKey)) {
            throw new Exception('Fatal error: editor *config key* not set.');
        }
        if (empty($this->editorViewFile)) {
            throw new Exception('Fatal error: editor *view file* not set.');
        }
        $this->editorConfig = Config::get($this->editorConfigKey);
        $this->editor = new DTEGenerator($this->editorConfig);
    }

    public function data($fieldsConditions = [])
    {
        return $this->editor->data($fieldsConditions);
    }

    public function editorView()
    {
        // build a two-dimensional asset url array (form: $assets[$type] = $full_urls)
        $applyingAssets = DTEAssetsHandler::determineRequiredAssets($this);
        foreach ($applyingAssets as $assetType => $assetStrings) {
            $applyingAssets[$assetType] = DTEAssetsHandler::turnURLsIntoAssets($applyingAssets[$assetType]);
        }

        // provide the editor view
        return $this->editor->view(
            $this->editorViewFile,
            [
                'assets' => $applyingAssets,
                'dom' => $this->editor->dom(),
                'languagePath' => $this->editor->languagePath(),
            ]
        );
    }

    public function editorAPI()
    {
        $this->editor->endpoint();
    }

    /**
     * Display or provide a CSV-formatted download of DataTable columns (matching given field conditions)
     * @param array $fieldsConditions
     * @param $filename
     * @param false $download
     * @param callable $beforeOutputHook Takes $rows, can manipulate them and should return them
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function csvStream(
        $fieldsConditions = [],
        $filename,
        $beforeOutputHook = null,
        $debug = false
    ) {
        if ($debug === false) {
            $headers = [
                'Content-type' => $debug ? 'test/html' : 'text/csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Content-Disposition' => "attachment; filename=$filename",
            ];
        } else {
            $headers = [];
        }


        return response()->stream(function () use ($fieldsConditions, $beforeOutputHook) {
            $rows = $this->flattenedData($fieldsConditions);
            if (isset($beforeOutputHook) && is_callable($beforeOutputHook)) {
                $rows = call_user_func($beforeOutputHook, $rows);
            }
            $file = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Takes DataTables data() and makes it a two-dimensional array of rows and columns
     * @param array $fieldsConditions
     * @param bool $includeHeaderRow
     * @return array|array[]
     */
    protected function flattenedData($fieldsConditions = [], $includeHeaderRow = true, $limit = null)
    {
        $data = $this->data($fieldsConditions);
        if (isset($limit) && $limit > 0) {
            $data = array_slice($data, 0, $limit);
        }
        if (count($data) === 0) {
            return [];
        } else {
            $firstRow = $data[0];
        }
        $rows = [];
        if ($includeHeaderRow) {
            $rows[] = $this->getDataColumnNames($firstRow, true);
        }
        foreach ($data as $obj) {
            $rows[] = $this->getDataRowValues($obj);
        }
        return $rows;
    }

    protected function getDataColumnNames($row, $addTableName = false)
    {
        $columnNames = [];
        foreach ($row as $tableName => $table) {
            if (!is_array($table)) {
                continue;
            }
            foreach ($table as $col => $val) {
                $columnNames[] = $addTableName ? $tableName . '.' . $col : $col;
            }
        }
        return $columnNames;
    }

    protected function getDataRowValues($dataset)
    {
        $row = [];
        foreach ($dataset as $key => $table) {
            if (!is_array($table)) {
                continue;
            }
            foreach ($table as $col => $val) {
                $row[] = $val;
            }
        }
        return $row;
    }
}
