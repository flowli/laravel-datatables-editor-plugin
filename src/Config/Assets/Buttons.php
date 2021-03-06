<?php
/**
 * Make sure *only* the local+licensed copy of dataTables.editor.min.js is used here (array item == 'dte2/js/dataTables.editor.min.js')
 */
$importExportResources = [
    'js' => [
        '//cdnjs.cloudflare.com/ajax/libs/PapaParse/4.6.3/papaparse.min.js',
        'https://code.jquery.com/jquery-3.5.1.js',
        'https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js',
        '../../extensions/Editor/js/dataTables.editor.min.js',
        'https://cdn.datatables.net/datetime/1.0.3/js/dataTables.dateTime.min.js',
        'https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js',
        'https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js',
        'https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js',
        'https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js',
        // import button specific code
        'https://raw.githubusercontent.com/flowli/laravel-datatables-editor-plugin/master/assets/js/csv-import-de.js',
    ],
    'css' => [
        'https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css',
        '../../extensions/Editor/css/editor.dataTables.min.css',
        'https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css',
        'https://cdn.datatables.net/datetime/1.0.3/css/dataTables.dateTime.min.css',
        'https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css',
    ],
    'download-without-reference-in-html-code' => [
        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js.map',
    ],
];
return [
    'feature:CSVImport' => $importExportResources, // for backwards-compat.
    'feature:ImportExport' => $importExportResources,
];
