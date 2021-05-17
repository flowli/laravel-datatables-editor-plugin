function csvImportieren() {
    var uploadEditor = new $.fn.dataTable.Editor({
        fields: [{
            label: 'CSV-Datei:',
            name: 'csv',
            type: 'upload',
            ajax: function (files, done) {
                // Ajax override of the upload so we can handle the file locally. Here we use Papa
                // to parse the CSV.
                Papa.parse(files[0], {
                    header: true,
                    skipEmptyLines: true,
                    complete: function (results) {
                        if (results.errors.length) {
                            uploadEditor.field('csv').error('CSV Lese-Fehler: ' + results.errors[0].message);
                        } else {
                            uploadEditor.close();
                            csvImportierenSelectColumns(editor, results.data, results.meta.fields);
                        }

                        // Tell Editor the upload is complete - the array is a list of file
                        // id's, which the value of doesn't matter in this case.
                        done([0]);
                    }
                });
            }
        }]
    });
    uploadEditor.create({
        title: 'CSV Datei-Import'
    });
}

// Display an Editor form that allows the user to pick the CSV data to apply to each column
function csvImportierenSelectColumns(editor, csv, header) {
    var selectEditor = new $.fn.dataTable.Editor();
    var fields = editor.order();

    for (var i = 0; i < fields.length; i++) {
        var field = editor.field(fields[i]);

        selectEditor.add({
            label: field.label(),
            name: field.name(),
            type: 'select',
            options: header,
            def: header[i]
        });
    }

    selectEditor.create({
        title: 'CSV-Spalten zuordnen',
        buttons: csv.length + ' Datensätze importieren',
        message: 'Wähle bitte die CSV-Spalten und das jeweils zugehörige Datenbank-Feld aus.'
    });

    selectEditor.on('submitComplete', function (e, json, data, action) {
        // Use the host Editor instance to show a multi-row create form allowing the user to submit the data.
        editor.create(csv.length, {
            title: 'Import bestätigen',
            buttons: 'Bestätigen',
            message: 'Klicke auf <i>Bestätigen</i> um ' + csv.length + ' Zeilen zu importieren. Du kannst den Wert für ein Feld überschreiben indem du hier etwas änderst:'
        });

        for (var i = 0; i < fields.length; i++) {
            var field = editor.field(fields[i]);
            var mapped = data[field.name()];

            for (var j = 0; j < csv.length; j++) {
                field.multiSet(j, csv[j][mapped]);
            }
        }
    });
}
