/**
 * Use to URL:
 *  /admin/faq
 *  /admin/faq/*
 */

var func_admin_faq = {
    validator: function(){
        admin_faq_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    //init func
    func_admin_faq.init();
});

function admin_faq_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                title: $(form).find('input[name="title"]').val(),
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