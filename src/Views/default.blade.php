@extends('layouts.app')

@section('head')
    @foreach($assets['css'] as $css_url)
        <link rel="stylesheet" type="text/css" href="{{ $css_url }}"/>
    @endforeach
    @foreach($assets['js'] as $js_url)
        <script src="{{ $js_url }}" defer></script>
    @endforeach
    <style type="text/css">
        .dataTables_length {
            margin: 6px 0 0 24px;
        }
    </style>
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

        var oldOnload = window.onload;
        window.onload = function () {
            oldOnload && oldOnload();

            // feature 'individual column search': add text fields
            $('#{{ $routeName }} tfoot th').each(function () {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            // initialize editor
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

            // initialize data table
            $('#{{ $routeName }}').DataTable({
                dom: '{{ $dom }}',
                ajax: {
                    url: '{{ route($routeName) }}',
                    type: 'POST',
                    data: {
                        '_token': $('meta[name=csrf-token]').attr('content')
                    }
                },
                serverSide: true,
                processing: true,
                select: true,
                lengthMenu: [[10, 50, 100, 1000, -1], [10, 50, 100, 1000, 'Alle(!)']],
                columns: {!! $dataTableColumnsJSON !!},
                buttons: {!! $editorButtonsJSON !!},
                language: {
                    @if(file_exists(public_path($languagePath)))
                    url: '{{ asset($languagePath) }}'
                    @endif
                },
                initComplete: function () {
                    // feature 'individual column search': apply search
                    this.api().columns().every(function () {
                        var that = this;

                        $('input', this.footer()).on('keyup change clear', function () {
                            if (that.search() !== this.value) {
                                that
                                    .search(this.value)
                                    .draw();
                            }
                        });
                    });
                }
            });
        }
    </script>
@endsection
