var grupo_table = null

jQuery(document).ready(function () {
  grupo_table = $('#table-grupo')
  dataTablesInit(grupo_table, {
    ajax: __HOMEPAGE_PATH__ + 'grupo/index_table/',
    columnDefs: datatablesGetColDef(),
    order: [[1, 'asc']],
  })
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
      width: '30px',
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
      name: 'nombre',
    },
    {
      targets: index++,
      name: 'roles',
    },
    {
      targets: index++,
      name: 'descripcion',
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
