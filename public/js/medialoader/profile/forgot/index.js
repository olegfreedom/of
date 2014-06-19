/**
 * Use to URL:
 *  /profile/forgot/index
 *  /profile/forgot/index/*
 */

var func_profile_forgot_index = {
    validator: function(){
        forgot_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    // init func
    func_profile_forgot_index.init();
});


function forgot_formValidator(){
    bodyOffOn('submit', '.forgot', function(e){
        var form = $(this);
        var status = $(form).find('input[name="forgot-form"]');

        profile_removeErrors(form);

        if($(status).val() == 0) {
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val()
            };

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    form.submit();
                }else{
                    profile_createErrors(form, data);
                }
            });
        }

    });
}
