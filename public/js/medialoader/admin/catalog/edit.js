/**
 * Use to URL:
 *  /admin/catalog/edit
 *  /admin/catalog/edit/*
 */

var func_admin_catalog_edit = {
    init: function(){
        func_admin_catalog.optionLoad();
    }
};

$(document).ready(function(){
    // init func
    func_admin_catalog_edit.init();
});

function hasExtension(inputID, exts) {
    var fileName = $('input[type=file]').val();
    return (new RegExp('(' + exts.join('|').replace(/\./g, '\\.') + ')$')).test(fileName);
}

function extFilter() {
    if (!hasExtension('add-gal-img', ['.jpg', '.jpeg', '.png'])) {
        $('input[type=file]').val('');
        alert('Возможна загрузка только PNG/JPG');
        return false;
    } else {
        return true;
    }
}