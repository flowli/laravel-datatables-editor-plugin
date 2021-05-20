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
        // build a two-dimensional asset url array (form: $assets[$type] = $full_urls)
        $applyingAssets = DTEAssetsHandler::determineRequiredAssets($this);
        foreach ($applyingAssets as $assetType => $assetStrings) {
            $applyingAssets[$assetType] = DTEAssetsHandler::turnURLsIntoAssets($applyingAssets[$assetType]);
        }

        // determine dom layout
        $dom = 'Blfrtip';
        if (in_array('SearchBuilder', $this->editorConfig['features'])) {
            $dom = 'Q' . $dom;
        }

        // provide the editor view
        return $this->editor->view(
            $this->editorViewFile,
            [
                'languagePath' => $this->editorConfig['languagePath'] ?? null,
                'assets' => $applyingAssets,
                'dom' => $dom
            ]
        );
    }

    public function editorAPI()
    {
        $this->editor->endpoint();
    }
}
