<?php
/**
 * Make sure *only* the local+licensed copy of dataTables.editor.min.js is used here (array item == 'dte2/js/dataTables.editor.min.js')
 */
return [
    null => [
        'css' => [
            'https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css',
            'https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css',
            'https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css',
            'https://cdn.datatables.net/datetime/1.0.3/css/dataTables.dateTime.min.css',
            '../../extensions/Editor/css/editor.dataTables.min.css',
        ],
        'js' => [
            'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js',
            'https://code.jquery.com/jquery-3.5.1.js',
            'https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js',
            'https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js',
            'https://cdn.datatables.net/datetime/1.0.3/js/dataTables.dateTime.min.js',
            '../../extensions/Editor/js/dataTables.editor.min.js',
            'https://raw.githubusercontent.com/flowli/laravel-datatables-editor-plugin/master/assets/js/js.cookie-2.2.1.min.js',
        ],
    ]
];
