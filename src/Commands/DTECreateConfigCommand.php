<?php

namespace arweb\DataTablesEditor\Commands;

use Illuminate\Console\Command;

class DTECreateConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte:config:create {config_file} {eloquent_model} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a DataTables Editor configuration file using an Eloquent model.';

    /**
     * TODO: Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Creating new config file "' . $this->argument('config_file') .
            '" from eloquent model "' . $this->argument('eloquent_model') . '"');
        $this->info('TODO: inspect model, maybe inspect the database it points to, and conclude a dte model');
        // Table can be nice to show what is transformed to what
        // if($this->option('debug') {
        //   $this->table(['a', 'b'], [[1, 2], [3, 4]]);
        // }
    }
}
