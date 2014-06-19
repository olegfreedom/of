/**
 * Use to URL:
 *  /admin/catalog/add
 *  /admin/catalog/add/*
 */

var func_admin_catalog_add = {
    init: function(){
        func_admin_catalog.optionLoad();
    }
};

$(document).ready(function(){
    // init func
    func_admin_catalog_add.init();
});