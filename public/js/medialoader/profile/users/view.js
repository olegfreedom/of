/**
 * Use to URL:
 *  /profile/users/view
 *  /profile/users/view/*
 */

var func_users_view = {
    submit: function(){
        users_view_submitCommentForm();
        // users_view_submitSMSForm();
    },
    get: function(){
        // users_view_getPhoneNumber();
    },
    show: function() {
        users_view_initPopupForm();
    },
    edit: function() {
        users_view_editComment();
    },
    rate: function() {
        users_view_upComment();
        users_view_downComment();
    },
    slider: function() {
        users_view_initSlider();
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
    func_users_view.init();
});

function users_view_initSlider()
{
    $('input.slider-input').slider({
        formater: function(value) {
            return 'Оценка: '+value;
        },
        value: 0
    });
}

function users_view_submitCommentForm(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="user-comment-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            application_removeErrors(form);
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                comment_full: $(form).find('textarea[name="comment_full"]').val()
            }

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    application_createErrors(form, data);
                }
            });
        }
    });
}

function users_view_editComment(){
    bodyOffOn('click', '.comment-edit', function (e){
        e.preventDefault();

        var url = $(this).attr('href');
        $.post(url, {}, function(data){
            if(data.comment_full){
                $('#popup-user-comment-add form.user-comment-form').find('input[name="id"]').val(data.id);
                $('#popup-user-comment-add form.user-comment-form').find('input[name="parent_id"]').val(data.parent_id);
                $('#popup-user-comment-add form.user-comment-form').find('textarea[name="comment_full"]').text(data.comment_full);

                $('#popup-user-comment-add').modal('show');
            }
        });
    });
}

function users_view_upComment(){
    bodyOffOn('click', '.comment-rating-up', function (e){
        e.preventDefault();

        var url = $(this).attr('href');
        $.post(url, {}, function(data){
        });
    });
}

function users_view_downComment(){
    bodyOffOn('click', '.comment-rating-up', function (e){
        e.preventDefault();

        var url = $(this).attr('href');
        $.post(url, {}, function(data){
        });
    });
}



function users_view_initPopupForm(){
    bodyOffOn('click', '.reply-comment', function (e){
        e.preventDefault();

        $('#popup-user-comment-add form.user-comment-form').find('input[name="id"]').val(0);
        $('#popup-user-comment-add form.user-comment-form').find('textarea[name="comment_full"]').text('');

        $('#popup-user-comment-add form.user-comment-form').find('input[name="parent_id"]').val($(this).attr('rel'));

        $('#popup-user-comment-add').modal('show');
    });
}
