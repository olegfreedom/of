/**
 * Use to URL:
 *  /contact-us
 *  /contact-us/*
 */

var func_contact_us = {
    submit: function(){
        contact_us_formValidator();
    },
    init: function(){
        this.submit();
    }
}

$(document).ready(function(){
    //init func
    func_contact_us.init();
});

function contact_us_formValidator() {
    bodyOffOn('submit', '.contact-us-form', function(e) {
        var form = $(this);
        
        application_removeErrors(form);

        e.preventDefault();
        var url = $(form).find('input[name="validator"]').val();
        var vals = {
            name: $(form).find('input[name="name"]').val(),
            email: $(form).find('input[name="email"]').val(),
            message: $(form).find('textarea[name="message"]').val()
        };

        $.post(url, vals, function(data) {
            if(data.status == true){
                contact_us_addComment(form, vals);
            } else {
                application_createErrors(form, data);
            }
        }); 
    });
}

function contact_us_addComment(form, vals) {
    var url = $(form).attr('action');
    
    $.post(url, vals, function(data){
        if(data.status == true){
            $(form).find('input[name="name"]').val(''),
            $(form).find('input[name="email"]').val(''),
            $(form).find('textarea[name="message"]').val('')
            alert('Спасибо, Ваше сообщение принято.');
        } else {
            alert('При отправке сообщения возникли проблемы.');
        }
    });
}