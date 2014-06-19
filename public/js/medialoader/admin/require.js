/**
 * Use to URL:
 *  /admin/require
 *  /admin/require/*
 */

var func_admin_options = {
    action: function(){
        admin_require_change_category();
    },
    init: function(){
        this.action();
    }
}

$(document).ready(function(){
    //init func
    func_admin_options.init();
});

function admin_require_change_category() {
    bodyOffOn('change', '.edit-require', function (e) {
        e.preventDefault();
        $('.controls').find('input[type="checkbox"]').prop("checked", false);
        var url = $('input[name="edit-require-url"]').val();
        var value = { category_id: $(this).val() };

            $.post(url, value, function (data) {
                if (data.fields.length > 0) {
                    $(data.fields).each(function (index, val) {
                        $('.controls').find('input[name="params[' + val + ']"]').prop("checked", true);
                    })
                }
            });
    });
}

