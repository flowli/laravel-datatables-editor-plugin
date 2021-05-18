<?php

namespace arweb\DataTablesEditor;

use App\Http\Controllers\Controller as LaravelController;
use Illuminate\Support\Facades\Config;
use Exception;

abstract class DTEController extends LaravelController
{
    protected $editor;
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

    public function editorView()
    {
        // load conditional asset config (which assets to load in which order under which condition)…
        // …and build a list of assets named $applyingAssets
        $applyingAssets = DTEAssetsHandler::determineRequiredAssets($this);
        // convert asset strings into full urls
        foreach ($applyingAssets as $assetType => $assetStrings) {
            $applyingAssets[$assetType] = DTEAssetsHandler::turnURLsIntoAssets($applyingAssets[$assetType]);
        }

        // provide the editor view
        return $this->editor->view($this->editorViewFile, ['assets' => $applyingAssets]);
    }

    public function editorAPI()
    {
        $this->editor->endpoint();
    }

}
