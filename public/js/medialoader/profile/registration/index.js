/**
 * Use to URL:
 *  /profile/registration/index
 *  /profile/registration/index/*
 */

var func_profile_registration_index = {
    validator: function(){
        registration_formValidator();
    },
    init: function(){
        this.validator();
    }
}

$(document).ready(function(){
    // init func
    func_profile_registration_index.init();
});


function registration_formValidator(){
    bodyOffOn('submit', '.registration', function(e){
        var form = $(this);
        var status = $(form).find('input[name="registration-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val(),
                lastname: $(form).find('input[name="lastname"]').val(),
                firstname: $(form).find('input[name="firstname"]').val(),
                secondname: $(form).find('input[name="secondname"]').val(),
                address: $(form).find('input[name="address"]').val(),
                password: $(form).find('input[name="password"]').val(),
                retry_password: $(form).find('input[name="retry_password"]').val(),
                // type: $(form).find('input[name="type"]:checked').val(),
                type: $(form).find('input[name="type"]').val(),
                region_id:$(form).find('select[name="region_id"]').val(),
                area_id:$(form).find('select[name="area_id"]').val(),
                city_id:$(form).find('select[name="city_id"]').val(),
                zip: $(form).find('input[name="zip"]').val(),
                agree: $(form).find('input[name="agree"]:checked').val(),
                captcha:{
                   id : $(form).find('input[name="captcha[id]"]').val(),
                   input : $(form).find('input[name="captcha[input]"]').val()
                }
            }

            $('#zip-error').hide();
            $('div.region-holder').hide();
            profile_removeErrors(form);

            $.post(url, vals, function(data){
                if(data.status == true && data.error.captcha == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    profile_createErrors(form, data);
                    profile_captcha_createError(form , data);

                    var $errorParentElm = $(form).find('[name="zip"]').parent();
                    $('div.region-holder').show();
                    if ( !$errorParentElm.hasClass('error') )
                    {
                        // Выдаём соответствующий индексу область-регион-город
                        $(form).find('select[name="region_id"]').val(data.location.region_id).selectpicker('refresh');
                        var $area = $(form).find('[name="area_id"]');
                        if ( $area.is('input') )
                        {
                            $area.val(data.location.area_id);
                            $(form).find('[name="city_id"]').val(data.location.city_id);
                            profile_createHtmlSelectLocation();
                        }
                        else
                        {
                            $(form).find('select[name="region_id"]').val(data.location.region_id).selectpicker('refresh');
                            profile_createHtmlSelectLocation(data.location.area_id);
                            profile_createHtmlSelectCity(data.location.area_id, data.location.city_id);
                        }
                    }

                }
            });
        }

    });
}
