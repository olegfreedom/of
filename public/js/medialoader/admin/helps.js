/**
 * Use to URL:
 *  /admin/helps
 *  /admin/helps/*
 */

var func_admin_helps = {
    validator: function(){
        admin_helps_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    //init func
    func_admin_helps.init();
});

function admin_helps_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                text: $(form).find('textarea[name="text"]').val()
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