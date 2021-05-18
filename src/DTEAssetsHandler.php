<?php

namespace arweb\DataTablesEditor;

use Exception;

class DTEAssetsHandler
{
    public static function determineRequiredAssets(DTEController $instance)
    {
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
                $conditionResult = self::evaluateAssetCondition($conditionExpression, $instance);
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
        return $applyingAssets;
    }

    /**
     * Checks if a condition for including assets is true or not
     * @param $conditionExpression
     * @param Controller $instance
     * @return bool
     * @throws Exception
     */
    public static function evaluateAssetCondition($conditionExpression, DTEController $instance)
    {
        if (empty($conditionExpression)) {
            return true;
        }
        $conditionParts = explode(':', $conditionExpression);
        $conditionType = strtolower(array_shift($conditionParts));
        $conditionValue = count($conditionParts) > 0 ? join(':', $conditionParts) : '';
        switch ($conditionType) {
            case 'feature':
                if (empty($instance->editorConfig['features']) || !is_array($instance->editorConfig['features'])) {
                    return false;
                }
                return in_array($conditionValue, $instance->editorConfig['features']);
                break;
            default:
                throw new Exception('Unknown conditional asset condition type - cannot render DTE.');
        }
    }

    /**
     * Turns local paths and CDN urls into asset()'ables and provides full urls
     * @param $resourcePaths Array of either local url path or full external URL
     * @return array
     */
    public static function turnURLsIntoAssets($resourcePaths)
    {
        // ensure the asset paths exist
        $assetUrlPath = 'dte2-assets';
        $assetFilePath = public_path($assetUrlPath);
        @mkdir($assetFilePath);

        // build a list of asset urls
        $assets = [];
        foreach ($resourcePaths as $resourcePath) {
            // rewrites (to facilitate 1:1 path copies from editor.datatables.net examples)
            $resourcePath = preg_replace('|^//|', 'https://', $resourcePath);
            $resourcePath = preg_replace('|^../../extensions/Editor/|i', 'dte2/', $resourcePath);

            // make full, accessable urls out of all paths
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
