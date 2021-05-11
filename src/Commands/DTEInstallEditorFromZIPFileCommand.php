<?php

namespace arweb\DataTablesEditor\Commands;

use Illuminate\Console\Command;

class DTEInstallEditorFromZIPFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte:install {zip_file_or_folder} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copies DTE.zip or DTE folder into this Laravel project.';

    /**
     * TODO: Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Copying "' . $this->argument('zip_file_or_folder') . '" into this project');
        // 1. does source exist?
        // 2. is source file or directory?
        // 3a. file? try unzipping to /public
        // 4a. directory? just copy to /public
    }
}
