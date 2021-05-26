# Laravel DataTables Editor Package

## Installation

1. **Have or create** a Laravel project
2. **Install this package** with `composer require arweb/laravel-datatables-editor-plugin`
3. **Register the service provider** in `config/app.php`:
   add `arweb\DataTablesEditor\DTEServiceProvider::class` to the config array at key `provider`
4. **Get DataTables Editor**. Since it is not free of charge, it cannot be included in this package. You could:
    1. **Obtain a license** should be obtained at [DataTables Editor](https://editor.datatables.net)
    2. **Download** the PHP version's ZIP file at https://editor.datatables.net/download/
    3. **Install** with `php artisan dte2:install ~/Desktop/Editor-PHP-2.0.2.zip` - your ZIP file path may vary

## Usage

### Create a new Editor

1. pick a table name, it will be referred to as `[your-table]` (when dashed notation is recommended)
   or `[YourTable]` (when CapitalizedWords notation is recommended) in this example
2. create a basic config file `config/dte/[your-table].php` for your DataTables Editor

    ```php
    <?php
    return [
        'routeName' => '[your-table]-api',
        'databaseConnection' => '[your-laravel-database-connection-identifier]',
        'mainTable' => '[name-of-your-table-in-your-database-system]',
        'fields' => [
            '[text-field-name]' => ['type' => 'text', 'label' => '[text field name]'],
            '[enum-field-name]' => [
                'type' => 'select',
                'options' => [
                    'Option 1 Label' => 'Option1EnumValue',
                    'Option 2 Label' => 'Option2EnumValue',
                    'Option 3 Label' => 'Option3EnumValue',
                ],
                'label' => '[enum field name]',
            ],
        ],
    ];
    ```

3. add these two [routes](https://laravel.com/docs/routing) into `routes/web.php`:

    ```php
       Route::get('<your-table>', '[YourTable]Controller@page')
       ->name('[your-table]-page');
       Route::post('<your-table>', '[YourTable]Controller@api')
       ->name('[your-table]-api');
    ```

4. write the following [controller](https://laravel.com/docs/controllers)
   into `app/Http/Controllers/[YourTable]Controller.php`:

    ```php
    <?php
    
    namespace App\Http\Controllers;
    
    use arweb\DataTablesEditor\DTEController;
    
    class [YourTable]Controller extends DTEController
    {
        protected $editorConfigKey = 'dte.<your-table>';
        protected $editorViewFile = '[your-table]/page';
    }
    ```

5. add a [blade](https://laravel.com/docs/blade) view in `resources/views/[your-table]/page.blade.php`
   that inherits the layout `dte::default` provided by this package:

    ```blade
    @extends('dte::default')
    
    @section('above-table')
    This text appears above your table.
    @endsection
    
    @section('under-table')
    This text appears below your table.
    @endsection
    ```

6. start the server with `php artisan serve` and look at
   `https://127.0.0.1:[artisan-serve-port]/[your-table]` in your browser
