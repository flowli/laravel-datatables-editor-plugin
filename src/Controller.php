<?php

namespace arweb\DataTablesEditor;

use \App\Http\Controllers\Controller as LaravelController;
use Exception;

abstract class Controller extends LaravelController
{
    protected $editor;
    protected $editorConfigKey;
    protected $editorViewFile;

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
        $this->editor = new Generator($this->editorConfigKey);
    }

    public function editorView()
    {
        $assets = [
            'css' => [
                'https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css',
                'https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css',
                'https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css',
                'https://cdn.datatables.net/datetime/1.0.3/css/dataTables.dateTime.min.css',
                'dte2/css/editor.dataTables.min.css',
            ],
            'js' => [
                //'https://code.jquery.com/jquery-3.5.1.js', // is loaded in some project's default layout
                'https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js',
                'https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js',
                'https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js',
                'https://cdn.datatables.net/datetime/1.0.3/js/dataTables.dateTime.min.js',
                'dte2/js/dataTables.editor.min.js',
            ],
        ];
        foreach ($assets as $type => $urls) {
            $assets[$type] = $this->turnURLsIntoAssets($assets[$type]);
        }
        return $this->editor->view($this->editorViewFile, ['assets' => $assets]);
    }

    public function editorAPI()
    {
        $this->editor->endpoint();
    }

    /**
     * TODO: Turns local paths and CDN urls into asset()'ables
     * @param $resourcePaths Array of either local url path or full external URL
     * @return array
     */
    protected function turnURLsIntoAssets($resourcePaths)
    {
        // ensure the asset paths exist
        $assetUrlPath = 'dte2-assets';
        $assetFilePath = public_path($assetUrlPath);
        @mkdir($assetFilePath);

        // build a list of asset urls
        $assets = [];
        foreach ($resourcePaths as $resourcePath) {
            $isExternalUrl = preg_match('/^http(s)?\:/', $resourcePath);
            if ($isExternalUrl) {
                $parsedUrl = parse_url($resourcePath, PHP_URL_PATH);
                $urlPathParts = explode('/', $parsedUrl);
                $urlFilename = array_pop($urlPathParts);
                // cache in respective asset path
                $localFilePath = $assetFilePath . '/' . $urlFilename;
                $localFileIsMissing = !file_exists($localFilePath);
                if ($localFileIsMissing) {
                    $data = @file_get_contents($resourcePath);
                    if ($data === false) {
                        throw new \Exception('Error: could not download DTE2 file "' . $resourcePath . '" to "' . $localFilePath . '".');
                    }
                    $success = @file_put_contents($localFilePath, $data);
                    if ($success === false) {
                        throw new \Exception('Error: could not write DTE2 file "' . $localFilePath . '".');
                    }
                }
                $assets[] = asset($assetUrlPath . '/' . $urlFilename);
            } else {
                $assets[] = asset($resourcePath);
            }

        }
        return $assets;
    }
}
