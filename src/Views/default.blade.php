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

        .dataTables_columnFilter_width {
            width: 100%;
        }
    </style>
    <script>
        function delay(callback, ms) {
            var timer = 0;
            return function () {
                var context = this, args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    callback.apply(context, args);
                }, ms || 0);
            };
        }

        function getParam(paramName) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(paramName);
        }
    </script>
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
                $(this).html('<input type="text" class="dataTables_columnFilter_width" placeholder="???? ' + title + '" />');
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
            const pageLength = Cookies.get('pageLengthCookieKey');
            const dataTablePageLength = pageLength || 10;

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
                @if(isset($dataTable['scrollX']))
                scrollX: {{ json_encode(boolval($dataTable['scrollX'])) }},
                @endif
                    @if(isset($dataTable['scrollY']))
                scrollY: {{ json_encode(intval($dataTable['scrollY'])) }},
                @endif
                lengthMenu: [[3, 10, 50, 100, 1000, -1, 3], [3, 10, 50, 100, 1000, 'Alle(!)']],
                pageLength: dataTablePageLength,
                columns: {!! $dataTableColumnsJSON !!},
                buttons: {!! $editorButtonsJSON !!},
                language: {
                    @if(file_exists(public_path($languagePath)))
                    url: '{{ asset($languagePath) }}'
                    @endif
                },
                search: {search: getParam('find')},
                initComplete: function () {
                    // feature 'individual column search': apply search
                    this.api().columns().every(function () {
                        var that = this;
                        $('input', this.footer()).on('keyup change clear',
                            delay(function (e) {
                                if (that.search() !== this.value) {
                                    that.search(this.value).draw();
                                }
                            }, 500)
                        );
                    });
                }
            });

            // remember page length for next datatable init
            $('#{{ $routeName }}').on('length.dt', function (e, settings, len) {
                Cookies.set('pageLengthCookieKey', len);
            });
        }
    </script>
@endsection
