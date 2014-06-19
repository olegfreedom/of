/**
 * Use to URL:
 *  /adverts/view
 *  /adverts/view/*
 */

var func_adverts_view = {
    submit: function(){
        adverts_view_submitCommentForm();
        // adverts_view_submitSMSForm();
    },
    get: function(){
        // adverts_view_getPhoneNumber();
    },
    show: function() {
        adverts_view_initPopupForm();
    },
    edit: function() {
        adverts_view_editComment();
    },
    rate: function() {
        adverts_view_upComment();
        adverts_view_downComment();
    },
    slider: function() {
        adverts_view_initSlider();
    },
    init: function(){
        this.submit();
        this.get();
        this.show();
        this.edit();
        this.slider();
        // this.rate();
    }
};

$(document).ready(function(){
    // init func
    func_adverts_view.init();
});

function adverts_view_initSlider()
{
    $('input.slider-input').slider({
        formater: function(value) {
            return 'Оценка: '+value;
        },
        value: 0
    });
}

function adverts_view_submitCommentForm(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="comment-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            application_removeErrors(form);
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                comment_full: $(form).find('textarea[name="comment_full"]').val(),
                captcha: {
                      id : $(form).find('input[name="captcha[id]"]').val(),
                      input :$(form).find('input[name="captcha[input]"]').val()
                }
            }

            application_removeErrors(form);

            $.post(url, vals, function(data){
                if(data.status == true && data.error.captcha == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    application_createErrors(form, data);
                    application_captcha_createError(form, data)
                }
            });
        }
    });
}

function adverts_view_editComment(){
    bodyOffOn('click', '.comment-edit', function (e){
        e.preventDefault();

        var url = $(this).attr('href');
        $.post(url, {}, function(data){
            if(data.comment_full){
                $('#popup-comment-add form.comment-form').find('input[name="id"]').val(data.id);
                $('#popup-comment-add form.comment-form').find('input[name="parent_id"]').val(data.parent_id);
                $('#popup-comment-add form.comment-form').find('textarea[name="comment_full"]').text(data.comment_full);

                $('#popup-comment-add').modal('show');
            }
        });
    });
}

function adverts_view_upComment(){
    bodyOffOn('click', '.comment-rating-up', function (e){
        e.preventDefault();

        var url = $(this).attr('href');
        $.post(url, {}, function(data){
        });
    });
}

function adverts_view_downComment(){
    bodyOffOn('click', '.comment-rating-up', function (e){
        e.preventDefault();

        var url = $(this).attr('href');
        $.post(url, {}, function(data){
        });
    });
}



function adverts_view_initPopupForm(){
    // $('#test_modal').modal();

    bodyOffOn('click', '.reply-comment', function (e){
        e.preventDefault();

        $('#popup-comment-add form.comment-form').find('input[name="id"]').val(0);
        $('#popup-comment-add form.comment-form').find('textarea[name="comment_full"]').text('');

        $('#popup-comment-add form.comment-form').find('input[name="parent_id"]').val($(this).attr('rel'));

        $('#popup-comment-add').modal('show');
    });
    /*
    $('#test_modal').modal({
        backdrop: true,
        keyboard: true,
        show: false //remove this if you don't want it to show straight away
    }).css({
        width: 'auto',
        'margin-left': function () {
            return -($(this).width() / 2);
        }
    });

    $('#test_modal').modal('show');
    */
/*
    bodyOffOn('click', '.send-sms', function(e){
        e.preventDefault();
        if(form.hasClass(activeClass)){
            form.removeClass(activeClass);
        } else {
            form.addClass(activeClass);
            adverts_view_addSMS_showForm(form);
        }
    });
*/
}
function application_captcha_createError(form, data){
    if(data.error.captcha == false){
        var $errorElm = $(form).find('input[name="captcha[input]"]').parent();
        $errorElm.addClass('error');
    }
}
