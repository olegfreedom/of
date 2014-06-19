/**
 * Use to URL:
 *  /admin/notification
 *  /admin/notification/*
 */

var func_admin_notification = {
    validator: function(){
        admin_notification_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    //init func
    func_admin_notification.init();
});

function admin_notification_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                title: $(form).find('input[name="title"]').val(),
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

