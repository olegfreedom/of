/**
 * Use to URL:
 *  /admin/users
 *  /admin/users/*
 */

var func_admin_users = {
    edit: function(){
        admin_users_editForm();
    },
    init: function(){
        this.edit();
    }
}

$(document).ready(function(){
    //init func
    func_admin_users.init();
});

function admin_users_editForm(){
    bodyOffOn('submit', '.edit-form', function(e){
        var form = $(this);
        var edit = $(form).find('input[name="edit-form"]');
        
        if($(edit).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var phoneArray = [];
            
            $(form).find('.phone-box .phone-items').each(function(index, elem){
                phoneArray[index] = $(elem).val();
            });
            
            var vals = {
                name: $(form).find('input[name="name"]').val(),
                username: $(form).find('input[name="username"]').val(),
                password: $(form).find('input[name="password"]').val(),
                retry_password: $(form).find('input[name="retry_password"]').val(),
                star: $(form).find('input[name="star"]').val(),
                phoneArray: phoneArray
            };

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(edit).val(1);
                    $(form).submit();
                }else{
                    admin_createErrors(form, data);
                }
            });
        }
    });
}