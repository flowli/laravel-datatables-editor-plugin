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
        return $this->editor->view($this->editorViewFile);
    }

    public function editorAPI()
    {
        $this->editor->endpoint();
    }
}
