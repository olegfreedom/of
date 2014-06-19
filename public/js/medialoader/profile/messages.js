/**
 * Use to URL:
 *  /profile/messages
 *  /profile/messages/*
 */

var func_profile_messages = {
    actions: function(){
        profile_message_remove();
        profile_add_messages_submitButton();
        profile_message_getContacts();
        profile_message_setContacts();
        profile_message_add_submitButton();
    },
    validator: function(){
        profile_add_messages_formValidator();
    },
    init: function(){
        this.actions();
        this.validator();
    }
};

$(document).ready(function(){
    // init func
    func_profile_messages.init();
});

function profile_message_remove(){
    bodyOffOn('click', 'a.remove-message', function(e){
        e.preventDefault();
        if(confirm('Подтвердите удаление')){
            var el = $(this).parent('td');
            var url = $('input[name="delete_url"]').val();
            var vals = {
                action : $('input[name="action"]').val(),
                messageId : $(el).parent('tr').find('input[name="message_id"]').val(),
                type : $(el).parent('tr').find('input[name="type"]').val()
            }
            $.post(url, vals, function(data){
                if(data.deleteResult == true){
                    $(el).parent('tr').remove();
                }
            });
        }else{
            return false;
        }
    });
}

function profile_message_getContacts() {
    var countKey = 0;
    bodyOffOn('keyup', 'input[name="username"]', function (e) {
        e.preventDefault();
        countKey++;
        setTimeout(function () {
            countKey--;
            if (countKey == 0) {
                var url = $('input[name="username_url"]').val();
                var vals = {
                    username : $('input[name="username"]').val()
                }
                $.post(url, vals, function(data){
                    $('.wrap.userList .input-holder').find('.user-list').remove();
                    if(data.usersList.length > 0){
                        var html = '';
                        html += '<div class="user-list">';
                                    $(data.usersList).each(function(x, y){
                                        html += '<p class="user-item">'+y+'</p>';
                                    })
                        html +='</div>';
                        $('.wrap.userList .input-holder').append(html);
                    }
                });
            }
        }, 500)
    });

    bodyOffOn('blur', '.wrap input[name="username"]', function () {
        if ($('.wrap.userList .input-holder .user-list p:hover').length > 0){
            return false;
        }else{
            $('.wrap.userList .input-holder').find('.user-list').remove();
        }
    });
}

function profile_message_add_submitButton(){
    bodyOffOn('click', '.sms-adding .submitIt', function(e){
        e.preventDefault();
        var form = $('.wrapper.sms-page .form-validator');

        $(form).submit();
    });
}

function profile_message_setContacts() {
    bodyOffOn('click', 'p.user-item', function (e) {
        e.preventDefault();
        var text = $(this).text();
        $('.input-holder input[name="username"]').val(text);
        $('.wrap.userList .input-holder').find('.user-list').remove();
    });
}

function profile_add_messages_submitButton(){
    bodyOffOn('click', '.form-validator .submitIt', function(e){
        e.preventDefault();
        var form = $('.container .form-validator');

        $(form).submit();
    });
}

function profile_add_messages_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            profile_removeErrors(form);
            var url = $(form).find('input[name="validator"]').val();

            var vals = {
                username: $(form).find('input[name="username"]').val(),
                title: $(form).find('input[name="title"]').val(),
                text: $(form).find('textarea[name="text"]').val()
            };
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