/**
 * Use to URL:
 *  /admin/testimonials
 *  /admin/testimonials/*
 */

var func_admin_testimonials = {
    edit: function(){
        admin_testimonials_editForm();
    },
    init: function(){
        this.edit();
    }
}

$(document).ready(function(){
    //init func
    func_admin_testimonials.init();
});

function admin_testimonials_editForm(){
    bodyOffOn('submit', '.testimonials-edit-form', function(e){
        var form = $(this);
        var edit = $(form).find('input[name="edit-form"]');
        
        if($(edit).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val(),
                email: $(form).find('input[name="email"]').val(),
                message: $(form).find('textarea[name="message"]').val()
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