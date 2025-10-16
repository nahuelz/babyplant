var usuario_table = null

jQuery(document).ready(function () {
  usuario_table = $('#table-usuario')
    dataTablesInit(usuario_table, {
        ajax: {
            url: __HOMEPAGE_PATH__ + 'usuario/index_table/',
            type: 'GET',
            data: function (d) {
                // Mapear los parámetros de DataTables a los que espera el servidor
                return {
                    draw: d.draw,
                    start: d.start,
                    length: d.length,
                    search: d.search.value,
                    order: d.order,
                    columns: d.columns
                };
            },
            dataSrc: function (json) {
                // Procesar la respuesta del servidor al formato que espera DataTables
                return json.data;
            }
        },
        columnDefs: datatablesGetColDef(),
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 10000], [10, 25, 50, 100, 10000]],
        //scrollX: false,
        autoWidth: false,
        fixedHeader: false,
        //scrollCollapse: true,
        serverSide: true,
        processing: true
    });

  $(document).on('click', '.accion-habilitar', function (e) {
    e.preventDefault();
    var msg = (parseInt($(this).attr('habilitar'))) ? 'habilitar' : 'deshabilitar';
    var a_href = $(this).attr('href');
    show_confirm({
        title: 'Confirmación',
        type: 'warning',
        msg: '¿Desea '+ msg +' este usuario?',
        callbackOK: function () {
            location.href = a_href;
        }
    });
    e.stopPropagation();
  });

})

/**
 *
 * @returns {Array}
 */
function datatablesGetColDef() {
  let index = 1

  return [

    {
      targets: index++,
      name: 'email',
    },
    {
      targets: index++,
      name: 'nombre',
    },
    {
      targets: index++,
      name: 'apellido',
    },
    {
      targets: index++,
      name: 'celular',
    },
    {
      targets: index++,
      name: 'grupos',
    },
    {
      targets: -1,
      name: 'acciones',
      title: 'Acciones',
      className: "text-center dt-acciones",
      orderable: false,
      width: '90px',
      render: dataTablesActionFormatter
  }
  ]
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
  if(data.habilitar != undefined) {
          return '<a class="dropdown-item accion-habilitar" titulo="'+ full[4]+'" habilitar="1" href="' + data.habilitar + '"><i class="la la-clipboard" style="margin-right: 5px;"></i> Habilitar</a>'
  }else if(data.deshabilitar != undefined){
          return '<a class="dropdown-item accion-habilitar" titulo="'+ full[4]+'" habilitar="0" href="' + data.deshabilitar + '"><i class="la la-edit" style="margin-right: 5px;"></i> Deshabilitar</a>'
  }
  return ''
}



function show_confirm(options_in){
  var options = $.extend({
      title: 'Confirmar',
      msg: '¿Desea continuar?',
      callbackOK: function () {
      },
      callbackCancel: function () {
      }
  }, options_in);

  Swal.fire({
      title: options.title,
      text: options.msg,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: 'Confirmar',
      cancelButtonText: 'Cancelar',
  }).then(function(result) {
      if (result.value) {
          options.callbackOK();
      } else {
          options.callbackCancel();
      }
  });
  
}