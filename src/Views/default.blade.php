@extends('layouts.app')

@section('head')
    @foreach(['css/dte/jquery.dataTables.min.css', 'css/dte/buttons.dataTables.min.css',
    'css/dte/select.dataTables.min.css', 'css/dte/dataTables.dateTime.min.css',
    'Editor-PHP/css/editor.dataTables.min.css'] as $dte_css_filename)
        <link rel="stylesheet" type="text/css" href="{{ asset($dte_css_filename) }}"/>
    @endforeach
    @foreach(['js/dte/jquery-3.5.1.js', 'js/dte/jquery.dataTables.min.js',
'js/dte/dataTables.buttons.min.js', 'js/dte/dataTables.select.min.js',
'js/dte/dataTables.dateTime.min.js', 'Editor-PHP/js/dataTables.editor.min.js'] as $dte_js_filename)
        <script src="{{ asset($dte_js_filename) }}"></script>
    @endforeach
@endsection

@section('content')
    @yield('above-table')

    <div>
        {{ csrf_field() }}
        <table id="{{ $routeName }}" class="display" style="width:100%">
            <thead>
            <tr>
                @foreach($fields as $fieldName => $fieldDetails)
                    <th>{{ !empty($fieldDetails['label']) ? $fieldDetails['label'] : $fieldName }}</th>
                @endforeach
            </tr>
            </thead>
            <tfoot>
            <tr>
                @foreach($fields as $fieldName => $fieldDetails)
                    <th>{{ !empty($fieldDetails['label']) ? $fieldDetails['label'] : $fieldName }}</th>
                @endforeach
            </tr>
            </tfoot>
        </table>
    </div>

    @yield('under-table')
@endsection

@section('foot')
    <script class="init">
        let editor;
        $(document).ready(function () {
            editor = new $.fn.dataTable.Editor({
                "ajax": {
                    url: "{{ route($routeName) }}",
                    data: function (d) {
                        d._token = $('meta[name=csrf-token]').attr('content');
                    }
                },
                "table": "#{{ $routeName }}",
                "fields": {!! $editorFieldsJSON !!}
            });

            $('#{{ $routeName }}').DataTable({
                dom: "Bfrtip",
                ajax: {
                    url: '{{ route($routeName) }}',
                    type: 'POST',
                    data: {
                        '_token': $('meta[name=csrf-token]').attr('content')
                    }
                },
                serverSide: true,
                select: true,
                columns: {!! $dataTableColumnsJSON !!},
                buttons: {!! $editorButtonsJSON !!},
                language: {
                    url: '{{ asset('js/dte/lang/de_DE.json') }}'
                }
            });
        });
    </script>
@endsection
