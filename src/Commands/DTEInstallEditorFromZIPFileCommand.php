<?php

namespace arweb\DataTablesEditor\Commands;

use Illuminate\Console\Command;

/**
 * Class DTEInstallEditorFromZIPFileCommand
 * @package arweb\DataTablesEditor\Commands
 */
class DTEInstallEditorFromZIPFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte2:install {zip_file_or_folder} {--debug}';

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
        /**
         * A) extract ZIP file
         */
        $zipFilePath = $this->argument('zip_file_or_folder');
        $targetParentPath = public_path();
        $targetPath = public_path('dte2');

        $this->info('Copying "' . $zipFilePath . '" into this project');

        // 1. does source exist?
        if (!file_exists($zipFilePath)) {
            $this->error('Could not find ZIP archive "' . $zipFilePath . '"');
        }
        if (is_dir($zipFilePath)) {
            $this->error('Your source "' . $zipFilePath . '" is a directory, not a file.');
        }

        // 2. does target not exist?
        if (file_exists($targetPath)) {
            $this->error('The target "' . $targetPath . '" already exists - doing nothing.');
            exit(1);
        }

        // 3. handle source file
        // a) open
        $zip = new \ZipArchive();
        $handle = $zip->open($zipFilePath);
        if ($handle === false) {
            $this->error('Could not open "' . $zipFilePath . '".');
        }

        // b) extract
        // TODO: determine $editorPathInsideZip based on ZIP file contents
        $editorPathInsideZip = 'Editor-PHP-2.0.2';
        $zip->extractTo($targetParentPath);

        // c) rename extracted directory to $targetPath
        rename($targetParentPath . '/' . $editorPathInsideZip, $targetPath);

        // d) close
        $zip->close();

        $this->info('The archive was extracted to ' . $targetParentPath);

        /**
         * B) download DataTables Editor images
         */
        $remoteImageDirectory = 'https://raw.githubusercontent.com/DataTables/DataTables/master/media/images';
        $localImagePath = public_path('images');
        @mkdir($localImagePath);
        $filesToDownload = [
            'sort_asc.png',
            'sort_asc_disabled.png',
            'sort_both.png',
            'sort_desc.png',
            'sort_desc_disabled.png',
        ];
        foreach ($filesToDownload as $fileName) {
            $fileData = @file_get_contents($remoteImageDirectory . '/' . $fileName);
            @file_put_contents($localImagePath . '/' . $fileName, $fileData);
        }
        $this->info('A total of ' . count($filesToDownload) . ' was downloaded to ' . $localImagePath);
    }

    public function error($message, $verbosity = null)
    {
        parent::error('[!] Error: ' . $message, $verbosity);
    }
}
