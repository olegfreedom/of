/**
 * Use to URL:
 *  /*
 */

var func_main = {
    scrollToSearch: function(){
        main_scrollToSearch();
    },
    scrollToTestimonialsForm: function(){
        main_scrollToForm();
    },
    link: function(){
        adverts_searchButton();
        vehicles_favoritesButton();
    },
    validator: function(){
        profile_login_formValidator();
        profile_registration_formValidator();
        profile_forgot_formValidator();
        application_search_formValidator();
        testimonial_popupformValidator();
    },
    rating: function(){
        rating_star();
        rating_testimonial();
    },
    confirm: function(){
        application_removeConfirm();
    },
    load: function(){
        load_scrollToTestimonialForm();
		testimonials_TypeChkbox();
    },
    init: function(){
        this.link();
        this.validator();
        this.rating();
        this.load();
        this.confirm();
    }
}

$(document).ready(function(){
    //init func
    func_main.init();
});


/**
 * @description Confirm remove
 */
function application_removeConfirm(){
    bodyOffOn('click', '.confirm-remove', function(e){
        if(confirm('Подтвердите удаление')){
            return true;
        }else{
            e.preventDefault();
            return false;
        }
    });
}


function testimonials_TypeChkbox(){
	var holder = $('.testimonial-main-form .req_type');

	bodyOffOn('click', '.testimonial-main-form .req_type li', function(e){
		e.preventDefault();

		// unchecked all
		$(holder).find('li').removeClass('active');
		$(holder).find('li .jcf-unselectable.chk-area').removeClass('chk-checked').addClass('chk-unchecked');
		checkboxChecked($(holder).find('li input[name="type"]'), false);

		// checked this
		$(this).addClass('active');
		$(this).find('.jcf-unselectable.chk-area').removeClass('chk-unchecked').addClass('chk-checked');
		checkboxChecked($(this).find('input[name="type"]'), true);
	});
}

function rating_star(){
    $('.rating-static').rating({
                fx: 'full',
                readOnly: true,
                image: '/img/stars.png',
        	    loader: '/img/ajax-loader.gif',
                stars: 5
    	});
}

function rating_testimonial(){
    $('.rating-testimonial').rating({
        fx: 'full',
        image: '/img/stars.png',
        loader: '/img/ajax-loader.gif',
        stars: 5,
        click: function(vote){
            rating_reload(vote);
        }
    });
}

function rating_voteSet(vote){
    if($('.rating-vote-score').length > 0){
        $('.rating-vote-score').val(vote);
    }
}

function rating_reload(vote){
    $('.rating-testimonial').remove();
    rating_voteSet(vote);
    $('.rating-testimonial-box').prepend(
        '<div class="rating-testimonial">' +
            '<input name="val" value="'+vote+'" type="hidden">' +
        '</div>'
    );
    rating_testimonial();
}

function main_scrollToSearch(){
    var top = $('#search-result').offset().top;
    $('html, body').animate({scrollTop : top},'slow');
}

function main_scrollToForm(){
    var top = $('#testimonialsForm').offset().top;
    $('html, body').animate({scrollTop : top},'slow');
}

function load_scrollToTestimonialForm(){
    if($('#scroll-to-testimonials-form').length > 0){
        main_scrollToForm();
    }
}

/**
 * Add to favorites
 */
function vehicles_favoritesButton(){
    bodyOffOn('click', '.favorites-button', function(e){
        e.preventDefault();
        var el = $(this);
        var url = $(el).attr('data-url');

        $.post(url, null, function(data){
            if(data.status == true){
                $(el).attr('data-url', data.url).attr('title', data.text);

                if(data.addClass == true){
                    $(el).addClass('full');
                }else{
                    $(el).removeClass('full');
                }
            }
        });
    });
}

function profile_login_formValidator(){
    bodyOffOn('submit', '.header .login-holder .popup.login-form', function(e){
        var form = $(this);
        var status = $(form).find('input[name="login-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val(),
                password: $(form).find('input[name="password"]').val()
            }

            $(form).find('.row .error').removeClass('error');

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    if($(data.error).length > 0){
                        for(var i in data.error){
                            if(data.error[i] == false){
                                $(form).find('[name="'+i+'"]').addClass('error');
                            }
                        }
                    }
                }
            });
        }

    });
}

function profile_registration_formValidator(){
    bodyOffOn('submit', '.header .login-holder .popup.register', function(e){
        var form = $(this);
        var status = $(form).find('input[name="registration-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val(),
                password: $(form).find('input[name="password"]').val(),
                retry_password: $(form).find('input[name="retry_password"]').val()
            }

            $(form).find('.row .error').removeClass('error');

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    if($(data.error).length > 0){
                        for(var i in data.error){
                            if(data.error[i] == false){
                                $(form).find('[name="'+i+'"]').addClass('error');
                            }else if(data.error[i] == true && i == 'username'){
                                $(form).find('[name="'+i+'"]').addClass('error');
                                alert('Такой логин уже существует, выберите другой.');
                            }
                        }
                    }
                }
            });
        }

    });
}

function profile_forgot_formValidator(){
    bodyOffOn('submit', '.header .login-holder .popup.forgot', function(e){
        var form = $(this);
        var status = $(form).find('input[name="forgot-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                username: $(form).find('input[name="username"]').val()
            }

            $(form).find('.row .error').removeClass('error');

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    if($(data.error).length > 0){
                        for(var i in data.error){
                            if(data.error[i] == false){
                                $(form).find('[name="'+i+'"]').addClass('error');
                            }
                        }
                    }
                }
            });
        }

    });
}

function application_search_formValidator(){
    bodyOffOn('submit', '.srch', function(e){
        var form = $(this);
        var status = $(form).find('input[name="search-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            application_removeErrors(form);
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                search_text: $(form).find('input[name="search_text"]').val()
            };
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

/**
 * @param object form
 */
function application_removeErrors(form){
    $(form).find('.error').removeClass('error');
}

/**
 * @param object form
 * @param array data
 */
function application_createErrors(form, data) {
    if ($(data.error).length > 0) {
        for (var i in data.error) {
            if (data.error[i] == false) {
                $(form).find('[name="' + i + '"]').addClass('error');
            }else if(data.error[i] == true && i == 'subscribe_email'){
                $(form).find('[name="'+i+'"]').addClass('error');
                alert('Этот email уже подписан на рассылку.');
            }
        }
        var errorItem = $(form).find('.error:first').parent();
        if ($(errorItem).length > 0) {
            scrollingPage(errorItem, 10);
        }
    }
}

function adverts_searchButton() {
    bodyOffOn('click', 'form.srch .btn-search', function (e) {
        e.preventDefault();
        var form = $(this).parents('form.srch');
        $(form).submit();
    });
}

function testimonial_popupformValidator(){
    bodyOffOn('submit', '.testimonial-main-form', function(e){
        e.preventDefault();
        var form = $(this);

        application_removeErrors(form);

        var url = $(form).find('input[name="validator"]').val();

        var vals = {
            name: $(form).find('input[name="name"]').val(),
            email: $(form).find('input[name="email"]').val(),
            message: $(form).find('textarea[name="message"]').val(),
            rating: $(form).find('input[name="rate"]').val(),
            type: $(form).find('input[name="type"]:checked').val()
        };

        $.post(url, vals, function(data){
            if(data.status == true){
                testimonials_add(form, vals);
            }else{
                application_createErrors(form, data);
            }
        });
    });
}

function testimonials_add(form, vals) {
    var url = $(form).attr('action');
    var popup = $('.testimonial-main-form');
    var box = $(popup).parent('#call-form');
    
    $.post(url, vals, function(data){
        if(data.status == true){
            $(popup).replaceWith('<p class="testimonials-success">Спасибо, Ваш отзыв добавлен</p>');
            scrollingPage(($(box).find('.testimonials-success')), 50);
        }
    });
}