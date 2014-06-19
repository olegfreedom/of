/**
 * Use to URL:
 *  /admin/pages
 *  /admin/pages/*
 */

var func_admin_pages = {
    validator: function(){
        admin_pages_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    //init func
    func_admin_pages.init();
});

function admin_pages_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                content: $(form).find('textarea[name="content"]').val()
            };

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    admin_createErrors(form, data);
                }
            });
        }
    });
}

