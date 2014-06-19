/**
 * Use to URL:
 *  /profile/settings
 *  /profile/settings/*
 */

var func_profile_settings = {
    validator: function(){
        profile_settings_formValidator();
    },
    submit: function(){
        profile_setting_submitButton();
    },
    init: function(){
        this.validator();
        this.submit();
    }
};

$(document).ready(function(){
    // init func
    func_profile_settings.init();
});

function profile_settings_formValidator(){
    bodyOffOn('submit', '.tab-content .profile', function(e){
        var form = $(this);
        var status = $(form).find('input[name="settings-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val(),
                lastname: $(form).find('input[name="lastname"]').val(),
                firstname: $(form).find('input[name="firstname"]').val(),
                secondname: $(form).find('input[name="secondname"]').val(),
                address: $(form).find('input[name="address"]').val(),
                old_password: $(form).find('input[name="old_password"]').val(),
                password: $(form).find('input[name="password"]').val(),
                retry_password: $(form).find('input[name="retry_password"]').val(),
                // phone1: $(form).find('input[name="phone1"]').val(),
                region_id:$(form).find('select[name="region_id"]').val(),
                area_id:$(form).find('select[name="area_id"]').val(),
                city_id:$(form).find('select[name="city_id"]').val(),
                zip: $(form).find('input[name="zip"]').val()
            }

            profile_removeErrors(form);

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    profile_createErrors(form, data);
                }
            }); 
        }
        
    });
}

function profile_setting_submitButton(){
    bodyOffOn('click', '#tab3 .submitIt', function(e){
        e.preventDefault();
        var form = $('.tab-content .profile');

        $(form).submit();
    });
}