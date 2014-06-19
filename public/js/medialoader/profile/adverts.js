/**
 * Use to URL:
 *  /profile/adverts
 *  /profile/adverts/*
 */

var func_profile_index = {
    actions: function(){
        profile_advertsEdit();
        profile_advertsRemove();
        profile_advertsShowUpdateBox();
        profile_add_edit_submitButton();
        profile_add_edit_addDate();
        profile_add_edit_chkDate();
        profile_add_img_preview();
        profile_add_edit_tab_change();
        profile_resetInputFile();
        profile_addInputFile();
        profile_change_category_options();
    },
    validator: function(){
        profile_adverts_add_edit_formValidator();
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

function profile_adverts_add_edit_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        if($(status).val() == 0){
            e.preventDefault();
            profile_removeErrors(form);
            var url = $(form).find('input[name="validator"]').val();

            var vals = {
                name: $(form).find('input[name="name"]').val(),
                type_id: $(form).find('[name="type_id"]').val(),
                description_full: $(form).find('textarea[name="description_full"]').val(),
                price: $(form).find('input[name="price"]').val(),
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
        $(wrap).find('#dropped-files .image.new').remove();
        $(wrap).find('#uploadbtn').val('');
    });
}
function profile_addInputFile(){
    bodyOffOn('click', '.wrap.img .add-img', function(e){
        e.preventDefault();
        var wrap = $(this).parent('.wrap.img');
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


function profile_add_edit_addDate(){
    bodyOffOn('click', 'a.more-dates', function (e) {
        e.preventDefault();

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

        newDateElm.find('input[name="date_from[]"]').datepicker().on('changeDate', function(ev) {
            var fromDate = new Date(ev.date);

            var date_to_elm = $(this).parent().parent().next().find('input[name="date_to[]"]');
            if (date_to_elm.val() != '')
            {
                var toDate = new Date( date_to_elm.val().replace( /(\d{2})\.(\d{2})\.(\d{4})/, '$2/$1/$3') );
                if ( fromDate.getTime() > toDate.getTime() )
                {
                    date_to_elm.val(('0'+fromDate.getDate()).slice(-2)+'.'+('0'+(fromDate.getMonth()+1)).slice(-2)+'.'+fromDate.getFullYear());
                }
            }

        });

        newDateElm.find('input[name="date_to[]"]').datepicker().on('changeDate', function(ev) {
            var toDate = new Date(ev.date);

            var date_from_elm = $(this).parent().parent().parent().prev().find('input[name="date_from[]"]');
            if (date_from_elm.val() != '')
            {
                var fromDate = new Date( date_from_elm.val().replace( /(\d{2})\.(\d{2})\.(\d{4})/, '$2/$1/$3') );
                if ( fromDate.getTime() > toDate.getTime() )
                {
                    date_from_elm.val(('0'+toDate.getDate()).slice(-2)+'.'+('0'+(toDate.getMonth()+1)).slice(-2)+'.'+toDate.getFullYear());
                }
            }

        });

        $('#item-accessible').append(newDateElm);


    });
}

function profile_add_edit_chkDate(){
    $('input[name="date_from[]"]').datepicker().on('changeDate', function(ev) {
        var fromDate = new Date(ev.date);

        var date_to_elm = $(this).parent().parent().next().find('input[name="date_to[]"]');
        if (date_to_elm.val() != '')
        {
            var toDate = new Date( date_to_elm.val().replace( /(\d{2})\.(\d{2})\.(\d{4})/, '$2/$1/$3') );
            if ( fromDate.getTime() > toDate.getTime() )
            {
                date_to_elm.val(('0'+fromDate.getDate()).slice(-2)+'.'+('0'+(fromDate.getMonth()+1)).slice(-2)+'.'+fromDate.getFullYear());
            }
        }

    });

    $('input[name="date_to[]"]').datepicker().on('changeDate', function(ev) {
        var toDate = new Date(ev.date);

        var date_from_elm = $(this).parent().parent().parent().prev().find('input[name="date_from[]"]');
        if (date_from_elm.val() != '')
        {
            var fromDate = new Date( date_from_elm.val().replace( /(\d{2})\.(\d{2})\.(\d{4})/, '$2/$1/$3') );
            if ( fromDate.getTime() > toDate.getTime() )
            {
                date_from_elm.val(('0'+toDate.getDate()).slice(-2)+'.'+('0'+(toDate.getMonth()+1)).slice(-2)+'.'+toDate.getFullYear());
            }
        }

    });

}

function profile_add_edit_tab_change(){
    bodyOffOn('click', 'a[data-toggle="tab"]', function (e) {
        console.log('active tab ' + e.target); // Active Tab
        // console.log('prev tab ' + e.relatedTarget); // Previous Tab
    });

        /*
    $('.tabs').bind('change', function (e) {
        // e.target is the new active tab according to docs
        // so save the reference in case it's needed later on
        window.activeTab = e.target;
        console.log(window.activeTab);
        // display the alert
        // alert("hello");
        // Load data etc
    });
    */
}