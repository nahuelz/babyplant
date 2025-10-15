var usuario_table = null

jQuery(document).ready(function () {
  usuario_table = $('#table-usuario')
  dataTablesInit(usuario_table, {
    ajax: __HOMEPAGE_PATH__ + 'usuario/index_table/',
    columnDefs: datatablesGetColDef(),
    order: [[1, 'asc']],
    pageLength: 25,  // Esta línea establece 25 filas por página
    lengthMenu: [[10, 25, 50, 100, 1000], [10, 25, 50, 100, 1000]]
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
  let index = 0

  return [
    {
      targets: index++,
      name: 'id',
      width: '5px',
      className: 'dt-center',
      orderable: false,
      render: function (data, type, full, meta) {
        return '\
                    <label class="kt-checkbox kt-checkbox--single kt-checkbox--solid">\
                        <input type="checkbox" value="" class="kt-checkable">\
                        <span></span>\
                    </label>'
      },
    },
    {
      targets: index++,
      name: 'email',
      width: '50px',  // Establece un ancho fijo para la columna de email
      className: 'dt-center text-truncate'  // Opcional: corta el texto que exceda el ancho
    },
    {
      targets: index++,
      name: 'nombre',
        width: '50px',  // Establece un ancho fijo para la columna de email
        className: 'dt-center text-truncate'  // Opcional: corta el texto que exceda el ancho
    },
    {
      targets: index++,
      name: 'apellido',
        width: '50px',  // Establece un ancho fijo para la columna de email
        className: 'dt-center text-truncate'  // Opcional: corta el texto que exceda el ancho
    },
    {
      targets: index++,
      name: 'celular',
        width: '50px',  // Establece un ancho fijo para la columna de email
        className: 'dt-center text-truncate'  // Opcional: corta el texto que exceda el ancho
    },
    {
      targets: index++,
      name: 'grupos',
        width: '50px',  // Establece un ancho fijo para la columna de email
        className: 'dt-center text-truncate'  // Opcional: corta el texto que exceda el ancho
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