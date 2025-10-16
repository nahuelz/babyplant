var datatable_resize = false;

var datatables_default_options = {
    responsive: false,
    // DOM Layout settings
    dom: "<'kt-hidden'B><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>",
    lengthMenu: [5, 10, 25, 50],
    pageLength: 10,
    pagingType: 'full_numbers',
    processing: false,
    serverSide: true,
    fixedHeader: true,
    scrollX: true,
    colReorder: {
        fixedColumnsLeft: 1,
        fixedColumnsRight: 1,
    },
    language: {
        "sProcessing": "Procesando...",
        "sLengthMenu": "Mostrar _MENU_ registros",
        "sZeroRecords": "No se encontraron resultados",
        "sEmptyTable": "Ningún dato disponible en esta tabla",
        "sInfo": "Mostrando del _START_ al _END_ de un total de _TOTAL_",
        "sInfoEmpty": "Mostrando del 0 al 0 de un total de 0",
        "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
        "sInfoPostFix": "",
        "sSearch": "Buscar:",
        "sUrl": "",
        "sInfoThousands": ",",
        "sLoadingRecords": "...",
        "oPaginate": {
            "sFirst": "Primero",
            "sLast": "Último",
            "sNext": "Siguiente",
            "sPrevious": "Anterior"
        },
        "oAria": {
            "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
            "sSortDescending": ": Activar para ordenar la columna de manera descendente"
        },
        "buttons": {
            "copy": "Copiar",
            "print": "Imprimir",
            "colvis": "Visibilidad"
        }
    },
    headerCallback: function (thead, data, start, end, display) {
        // Verificar si hay checkboxes en las filas
        var hasCheckboxes = $(this.api().rows().nodes()).find('input[type="checkbox"]').length > 0;

        if (hasCheckboxes) {
            thead.getElementsByTagName('th')[0].innerHTML = '\
            <label class="kt-checkbox kt-checkbox--single kt-checkbox--solid">\
                <input type="checkbox" value="" class="kt-group-checkable">\
                <span></span>\
            </label>';
        }
    },
    columnDefs: [],
    initComplete: function () {
        var thisTable = this;
        var rowFilter = $('<tr class="filter"></tr>').appendTo($(thisTable.api().table().header()));
        thisTable.api().columns().every(function (indexColumn) {

            if (thisTable.api().column(indexColumn).visible()) {
                var thElement = thisTable.api().column(indexColumn).header();
                var type = $(thElement).hasClass('not-in-filter') ? '' : $(thElement).data('type');
                var input = '<th></th>';
                switch (type) {
                    case 'string':
                        input = $('<th><input type="text" class="form-control form-control-sm form-filter datatable-filter-custom" data-col-index="' + thisTable.api().column(indexColumn).index() + '"/></th>');

                        // Si tiene el atirbuto bServerSide en false, asumo busqueda local
                        if (thisTable.api().settings()[0].oInit.bServerSide === false) {

                            var api = thisTable.api();
                            var colIdx = thisTable.api().column(indexColumn).index();
                            var cursorPosition;

                            $(input).find('input.datatable-filter-custom')
                                    .off('keyup change')
                                    .on('change', function (e) {

                                        // Get the search value
                                        $(this).attr('title', $(this).val());

                                        var regexr = '({search})';

                                        cursorPosition = this.selectionStart;

                                        // Search the column for that value
                                        api.column(colIdx).search(this.value != '' ? regexr.replace('{search}', '(((' + this.value + ')))') : '', this.value != '', this.value == '').draw();
                                    })
                                    .on('keyup', function (e) {
                                        e.stopPropagation();
                                        $(this).trigger('change');
                                        $(this).focus()[0].setSelectionRange(cursorPosition, cursorPosition);
                                    });
                        }

                        break;
                    case 'select':
                        input = $('<select class="form-control kt-selectpicker datatable-filter-custom" title="Select" data-col-index="' + thisTable.api().column(indexColumn).index() + '">');
                        var options = $(thElement).data('select');
                        options = options.split(';');
                        $.each(options, function (indexOption, valueOption) {
                            var optionData = valueOption.split(':');
                            $(input).append('<option ' + ((optionData[1] === 'Todos' || optionData[1] === 'TODOS') ? 'selected="selected"' : '') + 'value="' + optionData[0] + '">' + optionData[1] + '</option>');
                        });
                        input = $('<th><form>').append(input);
                        $(input).find('select').selectpicker({container: 'body'});

                        // Si tiene el atirbuto bServerSide en false, asumo busqueda local
                        if (thisTable.api().settings()[0].oInit.bServerSide === false) {

                            var api = thisTable.api();
                            var colIdx = thisTable.api().column(indexColumn).index();

                            $(input).find('select.datatable-filter-custom')
                                    .off('change.select2')
                                    .on('change.select2', function (e) {
                                        
                                        var value = $(this).val();
                                        
                                        // Get the search value
                                        $(this).attr('title', $(this).val());

                                        var regexr = '({search})';

                                        // Search the column for that value
                                        api.column(colIdx).search(value != '' ? regexr.replace('{search}', '(((' + value + ')))') : '', value != '', value == '').draw();
                                    });
                        }

                        break;
                    case 'search':
                        var reset = $('<a href="#" class="btn btn-danger btn-sm btn-icon" data-skin="dark" data-toggle="kt-tooltip" data-placement="top" data-original-title="Limpiar búsqueda" title="Limpiar búsqueda">\
                                                    <i class="la la-close"></i> \
                                            </a>');
                        input = $('<th class="text-center">').append(reset);
                        $(thisTable).parents(".dataTables_scroll").on('keyup', 'input.datatable-filter-custom', function (e) {
                            e.preventDefault();
                            if (e.keyCode == 13) {
                                dataTablesTriggerFilter(rowFilter, thisTable);
                            }
                        });
                        $(document).on('change', 'select.datatable-filter-custom', function (e) {
                            e.preventDefault();
                            dataTablesTriggerFilter(rowFilter, thisTable);
                        });
                        $(reset).on('click', function (e) {
                            e.preventDefault();
                            $(rowFilter).find('.datatable-filter-custom').each(function (i) {
                                $(this).val('');
                                $(this).removeClass('datatable-filter-custom-filtering');
                                thisTable.api().column($(this).data('col-index')).search('', false, false);
                            });
                            thisTable.api().table().draw();
                        });
                        break;
                    default:
                        break;
                }

                $(input).appendTo($(rowFilter));
            }
        });

        initDoubleScroll();

        KTApp.initTooltips();

        $(window).trigger('resize');

    },
    drawCallback: function (settings) {
        /* Create style document */
        var css = document.createElement('style');
        css.type = 'text/css';
        css.classList.add("dataTable_style_hack");

        let newStyles = 'table.dataTable{margin: 0 auto; width: 100%;clear: both;border-collapse: collapse;table-layout: fixed; // ***********add thisword-wrap:break-word; // ***********and this}';

        if (css.styleSheet) {
            css.styleSheet.cssText = newStyles;
        } else {
            css.appendChild(document.createTextNode(newStyles));
        }
        /* Append style to the tag name */
        document.getElementsByTagName("head")[0].appendChild(css);
    },
    preDrawCallback: function (settings) {
        let oldStyles = document.getElementsByClassName('dataTable_style_hack');
        for (let indexStyle = 0; indexStyle < oldStyles.length; indexStyle++) {
            oldStyles[indexStyle].parentNode.removeChild(oldStyles[indexStyle]); // remove these styles
        }
    },
    buttons: [
        {
            extend: 'excelHtml5',
            text: 'pagina',
            title: '',
            className: 'pagina',
            exportOptions: {
                columns: ':not(.sorting_disabled)',
                rows: ':visible'
            }
        },
        {
            extend: 'excelHtml5',
            text: 'filtrados',
            title: '',
            className: 'filtrados',
            exportOptions: {
                columns: ':not(.sorting_disabled)',
                filter: 'applied',
                page: 'all'
            }
        },
        {
            extend: 'excelHtml5',
            text: 'todos',
            title: '',
            className: 'todos',
            exportOptions: {
                columns: ':not(.sorting_disabled)',
                page: 'all',
                rows: {
                    search: 'none'
                }
            }
        }
    ]
};

/**
 * 
 * @returns {undefined}
 */
function initDoubleScroll() {

    // Enable THEAD scroll bars
    $('.dataTables_scrollHead').css('overflow', 'auto');

    // Sync THEAD scrolling with TBODY
    $('.dataTables_scrollHead').on('scroll', function () {
        $('.dataTables_scrollBody').scrollLeft($(this).scrollLeft());
    });
}

/**
 * 
 * @param {type} table
 * @param {type} options
 * @returns {undefined}
 */
function dataTablesInit(table, options) {

    let tableOptions = Object.assign({}, datatables_default_options);
    $.extend(tableOptions, options);

    table.on('preXhr.dt', function (e, settings) {
        $(".dataTables_scrollBody").busyLoad("show", {
            color: "white",
            background: "rgb(46 50 54 / 43%)",
            spinner: "accordion"
        }
        );
    }).on('xhr.dt', function (e, settings) {
        $(".dataTables_scrollBody").busyLoad("hide");
    }).DataTable(tableOptions);

    dataTablesTriggerSelection(table);


    $(window).resize(function () {
        if (datatable_resize) {
            clearTimeout(datatable_resize);
        }
        var oldStyles = document.getElementsByClassName('dataTable_style_hack');
        for (let indexStyle = 0; indexStyle < oldStyles.length; indexStyle++) {
            oldStyles[indexStyle].parentNode.removeChild(oldStyles[indexStyle]); // remove these styles
        }
        datatable_resize = setTimeout(function () {
            var oldStyles = document.getElementsByClassName('dataTable_style_hack');
            for (let indexStyle = 0; indexStyle < oldStyles.length; indexStyle++) {
                oldStyles[indexStyle].parentNode.removeChild(oldStyles[indexStyle]); // remove these styles
            }

            $(table).DataTable().columns.adjust();
            /* Create style document */
            var css = document.createElement('style');
            css.type = 'text/css';
            css.classList.add("dataTable_style_hack");

            let newStyles = 'table.dataTable{margin: 0 auto; width: 100%;clear: both;border-collapse: collapse;table-layout: fixed; // ***********add thisword-wrap:break-word; // ***********and this}';

            if (css.styleSheet) {
                css.styleSheet.cssText = newStyles;
            } else {
                css.appendChild(document.createTextNode(newStyles));
            }
            /* Append style to the tag name */
            document.getElementsByTagName("head")[0].appendChild(css);
        }, 350);
    });

}

/**
 * 
 * @param {type} table
 * @returns {undefined}
 */
function dataTablesTriggerSelection(table) {
    //table selection all
    $(document).on('change', '.kt-group-checkable', function () {
//                var set = $(this).closest('table').find('td:first-child .kt-checkable');
        var set = $(this).closest('.dataTables_scroll').find('.dataTables_scrollBody').find('table').find('td:first-child .kt-checkable');
        var checked = $(this).is(':checked');
        $(set).each(function () {
            if (checked) {
                $(this).prop('checked', true);
                $(this).closest('tr').addClass('active');
            } else {
                $(this).prop('checked', false);
                $(this).closest('tr').removeClass('active');
            }
        });
    });
    //table selection
    $(document).on('change', 'tbody tr .kt-checkbox', function () {
        $(this).parents('tr').toggleClass('active');
    });
}

/**
 * 
 * @param {type} rowFilter
 * @param {type} thisTable
 * @returns {undefined}
 */
function dataTablesTriggerFilter(rowFilter, thisTable) {
    var params = {};
    $(rowFilter).find('.datatable-filter-custom').each(function () {
        if ($(this).is('select')) {
            if (($(this).val() !== '') && ($(this).val() !== 'Todos')) {
                $(this).parent().addClass('datatable-filter-custom-filtering');
            }
        } else {
            if ($(this).val() !== '') {
                $(this).addClass('datatable-filter-custom-filtering');
            }
        }
        var i = $(this).data('col-index');
        if (params[i]) {
            params[i] += '|' + $(this).val();
        } else {
            params[i] = $(this).val();
        }
    });
    $.each(params, function (i, val) {
        // apply search params to datatable
        thisTable.api().column(i).search(val ? val : '', false, false);
    });
    thisTable.api().table().draw();
}



/**
 * 
 * @param {type} data
 * @param {type} type
 * @param {type} full
 * @param {type} meta
 * @returns {String}
 */
function dataTablesActionFormatter(data, type, full, meta) {

    let actions = '';

    if (jQuery.isEmptyObject(data)) {
        actions = '';

    } else {

        actions +=
                (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver</a>' : '')
                +
                (data.edit !== undefined ? '<a class="dropdown-item" href="' + data.edit + '" target="_blank"><i class="la la-edit" style="margin-right: 5px;"></i> Editar</a>' : '')
                +
                (data.delete !== undefined ? '<a class="dropdown-item accion-borrar" href="' + data.delete + '"><i class="la la-remove" style="margin-right: 5px;"></i> Borrar</a>' : '')
                ;

        //acciones adicionales
        actions += dataTablesCustomActionFormatter(data, type, full, meta);

        actions = '<div class="dropdown dropdown-inline">\
                        <button type="button" class="btn btn-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
                            <i class="ki ki-bold-more-hor"></i>\
                        </button>\
                        <div class="dropdown-menu">\
                            ' + actions +
                '</div>\
                    </div>';
    }


    return actions;

}


/**
 * 
 * @param {type} data
 * @param {type} type
 * @param {type} full
 * @param {type} meta
 * @returns {String}
 */
function dataTablesCustomActionFormatter(data, type, full, meta) {
    let actions = "";
    actions +=
            data.asignar !== undefined
            ? '<a class="dropdown-item accion-asignar" href="#" data-dispositivo="' +
            data.asignar +
            '"><i class="la la-user-plus" style="margin-right: 5px;"></i> Asignar</a>'
            : "";
    actions +=
            data.desasignar !== undefined
            ? '<a class="dropdown-item accion-desasignar" href="#" data-dispositivo="' +
            data.desasignar +
            '"><i class="la la-user-times" style="margin-right: 5px;"></i> Desasignar</a>'
            : "";
    actions += data.consumos !== undefined
            ? '<a class="dropdown-item accion-consumos" href="' +
            data.consumos +
            '"><i class="la la-dollar" style="margin-right: 5px;"></i> Ver consumos</a>'
            : "";
    actions += data.showDispositivo !== undefined
            ? '<a class="dropdown-item accion-dispositivo" href="' +
            data.showDispositivo +
            '"><i class="la la-search" style="margin-right: 5px;"></i> Ver dispositivo</a>'
            : "";
    actions += data.editDispositivo !== undefined
            ? '<a class="dropdown-item accion-dispositivo" href="' +
            data.editDispositivo +
            '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar dispositivo</a>'
            : "";

    return actions;
}

/**
 *
 * @param {type} queExportar
 * @returns {undefined}
 */
function exportarCustom(queExportar) {
    $('.' + queExportar).trigger('click');
}