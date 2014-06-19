/**
 * Use to URL:
 *  /admin/locations
 *  /admin/locations/*
 */

var func_admin_locations = {
    validator: function(){
        admin_location_formValidator();
    },
    init: function(){
        this.validator();
    }
};

$(document).ready(function(){
    // init func
    func_admin_locations.init();
});

function admin_location_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val()
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

