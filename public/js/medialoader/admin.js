/**
 * Use to URL:
 *  /admin/*
 */

var func_admin = {
    add: function(){
        admin_galleryAddItem();
        admin_phoneAddItem();
    },
    change: function(){
        admin_phoneChangeMask();
    },
    confirm: function(){
        admin_removeConfirm();
    },
    remove: function(){
        admin_galleryRemoveImage();
        admin_phoneRemoveItem();
    },
    checkbox: function(){
        admin_checkboxChecked();
    },
    validator: function(){
        admin_pagesFormValidator();
    },
    load: function(){
        admin_loadTinymce();
        admin_loadSelectpicker();
    },
    init: function(){
        this.add();
        this.change();
        this.confirm();
        this.remove();
        this.checkbox();
        this.validator();
        this.load();
    }
}

$(document).ready(function(){
    // init func
    func_admin.init();
});

function admin_galleryAddItem(){
    bodyOffOn('change', '.gallery-add-item', function(e){
        e.preventDefault();
        var el = $(this);
        var files = '';
        
        if($(el[0].files).length > 1){            
            $(el[0].files).each(function(index, elem){
                files += '<span>'+elem.name+'</span>';
            });
        }
        
        $('.gallery-upload-box').html(files); 
    });
}

function admin_phoneAddItem(){
    bodyOffOn('click', '.phone-add-item', function(e){
        e.preventDefault();
        
        var mask = $('.phone-item-data').attr('data-phone-mask');
        var placeholder = $('.phone-item-data').attr('data-phone-placeholder');
        var maskArray = $('.phone-change-mask:first').html();
        
        if($(maskArray).length > 0){
            maskArray = '&nbsp;<select class="selectpicker span2 phone-change-mask" name="mask[]">'+
                            maskArray+
                        '</select>';
        }else{
            maskArray = '';
        }
        
        var html = '<div class="control-group">'+       
                        '<div class="controls">'+
                                '<input type="text" class="phone-items bfh-phone span2" data-format="'+mask+'" name="phone[]" placeholder="'+placeholder+'" maxlength="20" value="">'+
                                maskArray+
                        '</div>'+
                    '</div>';
        
        $('.phone-box').append(html);
        bfhphone_init();
        admin_loadSelectpicker();
        $('.phone-box .phone-items:last').focus();
    });
}

function admin_phoneChangeMask(){
    bodyOffOn('change', '.phone-change-mask', function(e){
        e.preventDefault();
        
        var el = $(this);
        var input = $(el).parent('.controls').find('.phone-items');
        var option = $(el).find('option:selected');
        
        var value = $(input).val();
        
        $(input).attr('data-format', $(option).attr('data-mask'))
                .attr('placeholder', $(option).text())
                .attr('data-number', value)
                .val('');
        

        var html = $(input).outerHTML();
        
        $(input).remove();
        $(el).parent('.controls').prepend(html);
        
        bfhphone_init();
    });
}

/**
 * @description Confirm remove
 */
function admin_removeConfirm(){
    bodyOffOn('click', '.confirm-remove', function(e){
        if(confirm('Подтвердите удаление')){
            return true;
        }else{
           e.preventDefault();
            return false;
        }
    });
}

/**
 * @description Delete gallery image
 */
function admin_galleryRemoveImage(){
    bodyOffOn('click', '.gallery-remove-image', function(e){
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

/**
 * @description Delete phone item
 */
function admin_phoneRemoveItem(){
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

/**
 * @param object form
 * @param array data
 */
function admin_createErrors(form, data){
    if($(data.error).length > 0){
        for(var i in data.error){                        
            if(i == 'phoneArray' && $(data.error[i]).length > 0){                
                for(var k in data.error[i]){
                    if(data.error[i][k] == false){
                        $(form).find('.phone-box .phone-items').eq(k).parents('.control-group').addClass('error');
                    }
                };
            }else{
                if(data.error[i] == false){
                    $(form).find('[name="'+i+'"]').parents('.control-group').addClass('error');
                }
            }
        }
        
        var errorItem = $(form).find('.control-group.error:first');
        if($(errorItem).length > 0){
            var top = $(errorItem).offset().top - 45;
            $('html, body').animate({scrollTop : top},'slow');
        }
        
    }
}

/**
 * @param object form
 */
function admin_removeErrors(form){
    $(form).find('.control-group').removeClass('error');
    $(form).find('.controls .help-block').html('');
}

function admin_checkboxChecked(){
    bodyOffOn('change', '.checkbox.check-all', function(e){
        var status = $(this).is(':checked');
        
        if(status === true){
            $('.checkbox.check-items').each(function(index, elem){
                checkboxChecked(elem, true);
            });
        }else{
            $('.checkbox.check-items').each(function(index, elem){
                checkboxChecked(elem, false);
            });
        }
    });
}

/**
 * @description Bootstarap error alert
 * @param string text
 * @returns string
 */
function admin_errorAlert(text){
    return '<div class="alert alert-error">'+
                '<button type="button" class="close" data-dismiss="alert">×</button>'+
                '<strong>Ошибка!</strong> &nbsp; '+text+
            '</div>';
}

/**
 * @description Bootstarap success alert
 * @param string text
 * @returns string
 */
function admin_successAlert(text){
    return '<div class="alert alert-success">'+
                '<button type="button" class="close" data-dismiss="alert">×</button>'+
                '<strong>Поздравляем!</strong> &nbsp; '+text+
            '</div>';
}

/**
 * @description Bootstarap info alert
 * @param string text
 * @returns string
 */
function admin_infoAlert(text){
    return '<div class="alert alert-info">'+
                '<button type="button" class="close" data-dismiss="alert">×</button>'+
                '<strong>Обратите внимание!</strong> &nbsp; '+text+
            '</div>';
}

/**
 * @description Load Tinymce
 */
function admin_loadTinymce(){
    $('textarea.tinymce').tinymce({
        script_url : '/js/tinymce/tinymce.min.js',
        plugins: [
            "advlist autolink lists link charmap preview",
            "textcolor code media paste"
        ],
        toolbar: "preview | styleselect forecolor backcolor | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link media charmap hr | code | pastetext",
        width : 900,
        height : 450,
        paste_as_text: true,
        statusbar : false,
        menubar : false,
        resize: false,
        element_format : "html",
        forced_root_block : '',
        force_p_newlines : false,
        language:"ru"
    });  
}

/**
 * @description Load bootstrap select
 */
function admin_loadSelectpicker(){
    $('select.selectpicker').selectpicker();
}


/**********************/
/* Validators (start) */
/**********************/

function admin_pagesFormValidator(){
    bodyOffOn('submit', '.pages-form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                title: $(form).find('input[name="title"]').val(),
                description: $(form).find('textarea[name="description"]').val()
            };

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    admin_createErrors(form, data);
                }
            });
        }
    });
}
/********************/
/* Validators (end) */
/********************/