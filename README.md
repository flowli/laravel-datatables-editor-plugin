# Laravel DataTables Editor Package

## Setup

1. Install this Laravel-compatible composer package by running `composer require arweb/laravel-datatables-editor-plugin`
   .
2. Register the service provider:
    1. open `config/app.php` of your Laravel project
    2. inside `app.php`: add `arweb\DataTablesEditor\DTEServiceProvider::class` to the config array at key `provider`
    3. in doubt check the
       Laravel's [provider documentation](https://laravel.com/docs/8.x/providers#registering-providers)

3. Because DataTables Editor is a commercial product, it cannot be bundled in this package. The recommendation is:
    1. get a valid [DataTables Editor](https://editor.datatables.net) license
    2. download a ZIP file at https://editor.datatables.net
    3. install it by running `php artisan dte:install-editor-zipfile <filename>`
    
## Usage

@TODO: describe a sample usage
