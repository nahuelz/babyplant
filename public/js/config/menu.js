jQuery(document).ready(function () {

    if (typeof menu_activo !== 'undefined'){
        $.each($('.menu-nav a[href="' + menu_activo + '"]').parents('li'), function(index2,p){
            $(p).addClass('menu-item-active')
        })
    } else {
        $.each($('.menu-nav a:not([href=""]):not(.menu-toggle)'), function(index, a){
            var path = (a.href).replace(/\//g, '\\/');
            var regNew = new RegExp('^(' + path + 'new)');
            var regShow = new RegExp('^(' + path + ')\\d');
            var regEdit = new RegExp('^(' + path + ')\\d\/edit');
            if (a.href == location.href || regNew.test(location.href) || regShow.test(location.href) || regEdit.test(location.href)){
                $.each($(a).parents('li'), function(index2,p){
                    $(p).addClass('menu-item-active')
                })
                return
            }
        });
    }

});