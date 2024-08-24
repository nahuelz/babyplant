function showLoader() {
  $('body').prepend(
    '<div class="loader-container"><div class="loader"></div></div>',
  )
}

function closeLoader() {
  $('.loader-container').remove()
}
