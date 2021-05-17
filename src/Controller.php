<?php

namespace arweb\DataTablesEditor;

use \App\Http\Controllers\Controller as LaravelController;
use Exception;
use Illuminate\Support\Facades\Config;

abstract class Controller extends LaravelController
{
    protected $editor;
    protected $editorConfigKey;
    protected $editorViewFile;
    protected $editorConfig;

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
        $this->editor = new Generator($this->editorConfig);
    }

    public function editorView()
    {
        // load conditional asset config (which assets to load in which order under which condition)…
        // …and build a list of assets named $applyingAssets
        $assetConfigDir = __DIR__ . '/Config/Assets';
        $applyingAssets = [];
        $assetConfigFiles = scandir($assetConfigDir);
        sort($assetConfigFiles);
        $assetConfigFiles = array_filter($assetConfigFiles, function ($name) {
            return preg_match('/\.php$/i', $name);
        });
        foreach ($assetConfigFiles as $assetConfigFile) {
            $conditionalAssetsMap = require($assetConfigDir . DIRECTORY_SEPARATOR . $assetConfigFile);
            // merge applying conditional assets into $assets
            foreach ($conditionalAssetsMap as $conditionExpression => $condtionallyApplyingAssets) {
                $conditionResult = $this->evaluateConditionalAssetConditionExpression($conditionExpression);
                if ($conditionResult) {
                    foreach ($condtionallyApplyingAssets as $assetType => $condtionallyApplyingAsset) {
                        if (!isset($applyingAssets[$assetType])) {
                            $applyingAssets[$assetType] = [];
                        }
                        $applyingAssets[$assetType] = $condtionallyApplyingAsset + $applyingAssets[$assetType];
                    }
                }
            }
        }
        // convert asset strings into full urls
        foreach ($applyingAssets as $assetType => $assetStrings) {
            $applyingAssets[$assetType] = $this->turnURLsIntoAssets($applyingAssets[$assetType]);
        }

        // provide the editor view
        return $this->editor->view($this->editorViewFile, ['assets' => $applyingAssets]);
    }

    protected function evaluateConditionalAssetConditionExpression($conditionExpression)
    {
        if (empty($conditionExpression)) {
            return true;
        }
        $conditionParts = explode(':', $conditionExpression);
        $conditionType = strtolower(array_shift($conditionParts));
        $conditionValue = count($conditionParts) > 0 ? join(':', $conditionParts) : '';
        switch ($conditionType) {
            case 'buttons':
                if (empty($this->editorConfig['buttons']) || !is_array($this->editorConfig['buttons'])) {
                    return false;
                }
                return in_array($conditionValue, $this->editorConfig['buttons']);
                break;
            default:
                throw new Exception('Unknown conditional asset condition type - cannot render DTE.');
        }
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
