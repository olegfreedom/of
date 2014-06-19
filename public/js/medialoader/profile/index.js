/**
 * Use to URL:
 *  /profile/
 *  /profile/index
 *  /profile/index/*
 */

var func_profile_index = {
    actions: function(){
        profile_advertsEdit();
        profile_advertsRemove();
        profile_advertsShowUpdateBox();
        profile_add_edit_submitButton();
        profile_add_img_preview();
        profile_resetInputFile();
        profile_addInputFile();
        profile_change_category_options();
    },
    validator: function(){
        profile_add_edit_formValidator();
    },
    optionLoad: function(){
        // load_category_options();
    },
    dateLoad: function(){
        load_advert_dates();
    },
    init: function(){
        this.actions();
        this.validator();
    },
    maxUploadFiles: 12
};

$(document).ready(function(){
    //init func
    func_profile_index.init();
});

function load_advert_dates()
{
    var newDateElm = $('#item-accessible div:first').clone();
    newDateElm.find('label').text('');
    newDateElm.find('input').val('').datepicker({
        format: 'dd.mm.yyyy',
        language: 'ru'
    }).on('changeDate', function(e){
        (e.viewMode=='days') ? $(this).datepicker('hide') : '';
    });
    newDateElm.find('div.input-holder').append('<div class="col-sm-1"><a href="#" class="date-remove"><span class="glyphicon glyphicon-remove"></span></a></div>');
    newDateElm.find('div.input-holder span').css('display', 'block');
    newDateElm.find('a.date-remove').click(function(e){
        e.preventDefault();

        $(this).parent().parent().parent().remove();
    });

    $('#item-accessible').append(newDateElm);
}

function load_category_options(){
    var form = $('.form-validator');
    var url = $(form).find('input[name="load_options"]').val();
    var advert_id = $(form).find('input[name="advert_id"]').val();
    var vals = {
        category: $(form).find('select[name="category_id"]').val(),
        advert_id: (advert_id > 0 ? advert_id : 0)
    };

    // profile_require_stars();

    $.post(url, vals, function(data){
        if (data.optionsList.length > 0) {
            create_options_html(data);
            // jcf.customForms.replaceAll();
        } else {
            $('.wrap label.option').parent('.wrap').remove();
            return false;
        }

    });
}

function create_options_html(data){
    var html = '';
    var box = $('.form-validator .wrap.options');

    for(var key in data.optionsList) {
        var val = data.optionsList[key];

            if (val.type == 'checkbox'){

                html +='<div class="wrap">'+
                           '<label>'+val.name+'</label>'+
                           '<div class="input-holder helper">'+
                               '<input name="option['+val.id+'][checkbox_one][checkbox]" type="checkbox" value="y"'+(val.value == 'y' ? ' checked="checked"' : '' )+'> '+
                               '<input name="option['+val.id+'][checkbox_one][hidden]" type="hidden" value="n">'+
                           '</div>'+
                       '</div>';
            } else if (val.type == 'text'){
                html +='<div class="wrap">'+
                            '<label>'+val.name+'</label>'+
                            '<div class="input-holder helper">'+
                                '<input name="option['+val.id+']" type="text" value="'+val.value+'" >' +
                            '</div>'+
                       '</div>';
            } else if (val.type == 'select'){
                html +='<div class="wrap">'+
                            '<label>'+val.name+'</label>'+
                            '<div class="input-holder helper">'+
                                '<select name="option['+val.id+']">';
                                for (var i in val.value){
                                    html += '<option value="'+val.value[i].value+'"'+(val.value[i].selected == 'y' ? ' selected="selected"' : '')+'>'+val.value[i].name+'</option>'
                                }
                html +='</select>'+
                       '</div>'+
                       '</div>';
            } else if (val.type == 'radio'){
                html += '<div class="wrap">'+
                        '<label class="control-label">'+val.name+'</label>'+
                    '<div class="input-holder helper">';

                for (var i in val.value){
                    html += '<label class="radio">'+
                                '<input name="option['+val.id+']" type="radio" value="'+val.value[i].value+'"'+(val.value[i].selected == 'y' ? ' checked="checked"' : '')+'> '+val.value[i].name+
                            '</label>'
                }
                html +='</div>'+
                        '</div>';
            } else if (val.type == 'multi'){
                html += '<div class="wrap">'+
                    '<label class="control-label">'+val.name+'</label>'+
                    '<div class="input-holder helper">';
                    for (var i in val.value){
                        html += '<label class="radio option-size">'+
                            '<input name="option['+val.id+'][checkbox_multi][]" type="checkbox" value="'+val.value[i].value+'"'+(val.value[i].selected == 'y' ? ' checked="checked"' : '')+'> '+val.value[i].name+
                            '</label>'
                    }
                html +='</div>'+
                    '</div>';
            }
        }

    box.append(html);
}

function profile_change_category_options() {
        bodyOffOn('change', '.input-holder select.jcf-hidden.category', function () {
            $('.form-validator .wrap.options').empty();
            load_category_options();
        })
}

function profile_require_stars() {
    $('.wrap').find('span.req').text('');
    var url = $('input[name="require-url"]').val();
    var value = { category_id: $('.input-holder select.jcf-hidden.category').val() };

    $.post(url, value, function (data) {
        if (data.fields) {
            $(data.fields).each(function (index, val) {
                $('.wrap').find('span.require-'+val ).text('*');
            })
        }
        return false;
    });
}

function profile_advertsShowUpdateBox(){
    $('.cards .menu .item').hover(function(){
        var el = $(this);
        var box = $(el).find('.vehicles-update-box');

        if(!$(box).hasClass('hide')){
            $(box).addClass('hide');
            $(box).prev().removeClass('title');

            initAdvertCardPopupAlign();
        }
    });

    bodyOffOn('click', '.vehicles-update-show', function(e){
        e.preventDefault();

        var el = $(this);
        var box = $(el).parent().find('.vehicles-update-box');

        if($(box).hasClass('hide')){
            $(box).removeClass('hide');
            $(box).prev().addClass('title');
        }else{
            $(box).addClass('hide');
            $(box).prev().removeClass('title');
        }

        initAdvertCardPopupAlign();
    });
};

function profile_add_edit_submitButton(){
    bodyOffOn('click', '#tab1 .submitIt', function(e){
        e.preventDefault();
        var form = $('.tab-content .form-validator');

        $(form).submit();
    });
}

function profile_add_edit_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            profile_removeErrors(form);
            var url = $(form).find('input[name="validator"]').val();
            var phoneArray = [];

            $(form).find('.phone-box .phone-items').each(function(index, elem){
                phoneArray[index] = $(elem).val();
            });
            var vals = {
                name: $(form).find('input[name="name"]').val(),
                contact_name: $(form).find('input[name="contact_name"]').val(),
                phoneArray: phoneArray,
                description: $(form).find('textarea[name="description"]').val(),
                price: $(form).find('input[name="price"]').val(),
                email: ($(form).find('input[name="email"]').length > 0) ? $(form).find('input[name="email"]').val() : '',
                category_id: $(form).find('select[name="category_id"]').val()
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

function profile_resetInputFile(){
    bodyOffOn('click', '.wrap.img .delete', function(e){
        e.preventDefault();
        var wrap = $(this).parent('.wrap.img');
console.log('reset');
        $(wrap).find('#dropped-files .image.new').remove();
        $(wrap).find('#uploadbtn').val('');
    });
}
function profile_addInputFile(){
    bodyOffOn('click', '.wrap.img .add-img', function(e){
        e.preventDefault();
        var wrap = $(this).parent('.wrap.img');
console.log(wrap);
console.log(wrap.find('#uploadbtn'));
        $(wrap).find('#uploadbtn').trigger('click');
    });
}

function profile_add_img_preview(){
    bodyOffOn('change', '.wrap.img #uploadbtn', function(e) {
        e.preventDefault();
        var wrap = $(this).parents('.wrap.img');
        var files = $(this)[0].files;
        if ((files.length + $(wrap).find('#dropped-files .image.uploaded').length) <= func_profile_index.maxUploadFiles) {
            $(wrap).find('#dropped-files .image.new').remove();
            addImgLoadInView(files);
        } else {
            alert('Вы не можете загружать больше '+func_profile_index.maxUploadFiles+' изображений!');
            files.length = 0;
            $(wrap).find('#uploadbtn').val('');
        }
    });

}

function addImgLoadInView(files) {
    var filesSize = files.length;
    for(var i = 0; i < filesSize; i++){
        // Only process image files.
        if(files[i].type.match(/^image\/.+/) && files[i].type != 'image/gif'){ //TODO: убираем возможность загружать .gif
            if((filesSize + $('.wrap.img #dropped-files .image.uploaded').length) > func_profile_index.maxUploadFiles) {
                alert('Вы не можете загружать больше '+func_profile_index.maxUploadFiles+' изображений!');
                return;
            }
            var fileReader = new FileReader();
            fileReader.onload = (function(){
                return function() {
                    console.log('loaded new (' + (filesSize + $('.wrap.img #dropped-files .image.uploaded').length) + ')');
                    $('.wrap.img #dropped-files').append('<li class="image new" style="background: url(\''+this.result+'\') no-repeat center center/cover  #fff;"></li>');
                };
            })();
            fileReader.readAsDataURL(files[i]);
        }
    }
    return false;
}

function isNumberKey(evt){
    var charCode = (evt.which) ? evt.which : evt.keyCode
    if ( charCode == 46 || charCode == 44 || charCode == 8 || (charCode > 47 && charCode < 58) )
        return true;
    return false;
}