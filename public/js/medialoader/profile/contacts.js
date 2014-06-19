/**
 * Use to URL:
 *  /profile/contacts
 *  /profile/contacts/*
 */

var func_profile_contacts = {
    actions: function(){
        profile_contacts_phoneAddItem();
        profile_contacts_phoneRemoveItem();
        profile_contacts_submitButton();
    },
    validator: function(){
        profile_contacts_formValidator();
    },
    init: function(){
        this.actions();
        this.validator();
    }
};

$(document).ready(function(){
    // init func
    func_profile_contacts.init();
});

function profile_contacts_phoneAddItem(){
    bodyOffOn('click', '.phone-add-item', function(e){
        e.preventDefault();

        var count = $('.phone-remove-item').length + $('.phone-box .phone-items').length;

        if(count > 4){
            alert('Допустимо 5 номеров');
        }else{
            var mask = $('.phone-item-data').attr('data-phone-mask');
            var placeholder = $('.phone-item-data').attr('data-phone-placeholder');
            var maskArray = $('.phone-change-mask:first').html();

            if($(maskArray).length > 0){
                maskArray = '&nbsp;<select class="phone-change-mask small" name="mask[]">'+
                                maskArray+
                            '</select>';
            }else{
                maskArray = '';
            }
            
            var html = '<div class="control-group">'+
                            '<input type="text" class="phone-items bfh-phone small" data-format="'+mask+'" name="phone[]" placeholder="'+placeholder+'" maxlength="20" value="">'+maskArray+
                       '</div>';

            $('.phone-box').append(html);
            bfhphone_init();
            profile_loadСustomForms();
            $('.phone-box .phone-items:last').focus();        
        }
    });
}

function profile_contacts_phoneRemoveItem(){
    bodyOffOn('click', '.phone-remove-item', function(e){
        e.preventDefault();
        if(confirm('Подтвердите удаление')){
            var el = $(this);
            var url = $(el).attr('href');
            
            $.post(url, null, function(data){
                if(data.status == true){
                    $(el).parent().remove();
                }
            });
        }else{
            return false;
        }
    });
}

function profile_contacts_formValidator(){
    bodyOffOn('submit', '.tab-content .contact_inf', function(e){
        var form = $(this);
        var status = $(form).find('input[name="contacts-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var phone = [];
            
            $(form).find('input.phone-items').each(function(index, elem){
                phone[index] = $(elem).val();
            });
            
            var vals = {
                name: $(form).find('input[name="person"]').val(),
                phone: phone
            }

            $(form).find('.wrap .error').removeClass('error');

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{

                    if($(data.error).length > 0){
                        for(var i in data.error){
                            if (i == 'name'){
                                $(form).find('.input-holder.person').addClass('error');
                            }else if(i == 'phone'){
                                $(form).find('.phone-box .phone-items').addClass('error');
                            }
                            if($(data.error[i]).length > 0){
                                for(var k in data.error[i]){
                                    if(data.error[i][k] == false){
                                        if(i == 'name'){
                                            $(form).find('.input-holder.person').eq(k).addClass('error');
                                        }else if(i == 'phone'){
                                            $(form).find('.phone-box .phone-items').eq(k).addClass('error');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }); 
        }
        
    });
}

function profile_contacts_submitButton(){
    bodyOffOn('click', '#tab2 .submitIt', function(e){
        e.preventDefault();
        var form = $('.tab-content .contact_inf');

        $(form).submit();
    });
}