/**
 * Use to URL:
 *  /admin/banners
 *  /admin/banners/*
 */
var func_admin_banners = {
    validator: function(){
        admin_banners_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    //init func
    func_admin_banners.init();
});

function admin_banners_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                title: $(form).find('input[name="title"]').val(),
                url: $(form).find('input[name="url"]').val(),
                img: $(form).hasClass('edit') ? 1 : $(form).find('input[name="img"]')[0].files.length
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

