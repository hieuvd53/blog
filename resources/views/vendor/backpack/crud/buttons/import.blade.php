@if($crud->hasAccess('import'))
    <div class="import--group">
        <input type="file" accept=".csv" href="javascript:void(0)" onInput="sumbitSelectedFile(this)" name="import-file" id="import-file" data-route="{{ url($crud->route.'/import') }}" class="visually-hidden" data-button-type="import">
        <label class="import--label btn btn-primary" for="import-file"><i class="la la-file-import"></i> Import {{ $crud->entity_name }}</label>
    </div>

    @push("after_styles")
        <style>
            .import--group {
                display: inline-block;
            }

            .visually-hidden {
                position: absolute !important;
                clip: rect(1px 1px 1px 1px);
                /* IE6, IE7 */
                clip: rect(1px, 1px, 1px, 1px);
                padding: 0 !important;
                border: 0 !important;
                height: 1px !important;
                width: 1px !important;
                overflow: hidden;
            }

            .import--label {
                margin-bottom: 0;
                cursor: pointer;
            }

        </style>
    @endpush
    @push('after_scripts')
        <script>
            function sumbitSelectedFile(input) {
                var $input = $(input);
                var route = $input.attr('data-route');
                var excelsFile = $input.prop("files")[0];

                var importFormData = new FormData();
                importFormData.set("import", excelsFile);

                console.log("post to", route);
                $input.attr("disabled", "disabled");

                $.ajax({
                    url: route,
                    type: 'POST',
                    data: importFormData,
                    processData: false,
                    contentType: false,
                    success: function (result) {
                        // Show an alert with the result
                        console.log(result, route);
                        new Noty({
                            text: "Data Imported",
                            type: "success"
                        }).show();

                        // Hide all the modal, if any
                        $('.modal').modal('hide');

                        if (typeof crud !== 'undefined') {
                            // reload table
                            crud.table.ajax.reload();
                        }
                    },
                    error: function (result) {
                        // Show an alert with the result
                        new Noty({
                            text: result.responseJSON.message,
                            type: "warning"
                        }).show();
                        console.log(result);
                    },
                    complete: function () {
                        $input.removeAttr("disabled");
                    }
                });
            }

        </script>
    @endpush
@endif
