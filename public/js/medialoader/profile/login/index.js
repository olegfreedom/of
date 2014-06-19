/**
 * Use to URL:
 *  /profile/login/index
 *  /profile/login/index/*
 */

$(document).ready(function(){
/*
    jQuery("#loginform").submit(function() {
        log = jQuery("#user_login").val();
        pwd = jQuery("#user_pass").val();

        jQuery.post("forum/core/login.php", { vb_login_username: log, vb_login_password: pwd, do: "login" });
        //alert forces delay giving vB a chance to process login - waiting for return value is not working.
        alert("Welcome to Example.com");
        return true;
    });
*/

    profile_login_index_formValidator();
});

function profile_login_index_formValidator(){
    bodyOffOn('submit', '.container .login-form', function(e){
        var form = $(this);
        var status = $(form).find('input[name="login-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val(),
                password: $(form).find('input[name="password"]').val(),
                captcha:{
                    id : $(form).find('input[name="captcha[id]"]').val(),
                    input : $(form).find('input[name="captcha[input]"]').val()
                }
            }

            $(form).find('.row .error').removeClass('error');
            profile_removeErrors(form);

            $.post(url, vals, function(data){
                if(data.status == true){
                    $.post('/vb5/core/login.php', {
                        vb_login_username: vals ['username'],
                        vb_login_password: vals ['password'],
                        do: 'login'
                    }, function(data){
                        if ( data.substr(0, 5) == '<?xml' )
                        {
                            $(status).val(1);
                            $(form).submit();

                            //  TODO: Сделать смену пароля синхронизированным с форумом при восстановлении пароля
                            /*
                            var xmlDoc = $.parseXML(data);
                            var error = $(xmlDoc).find('error').first().text();
                            $('#vb_errors').html(error);
                            */
                        }
                        else
                        {
                            $(status).val(1);
                            $(form).submit();
                        }
                    }, 'html');
                }else{
                    if($(data.error).length > 0){
                        for(var i in data.error){
                            if(data.error[i] == false){
                                $(form).find('[name="'+i+'"]').parent().addClass('error');
                                profile_captcha_createError(form, data)
                            }
                        }
                    }
                }
            });
        }

    });
}