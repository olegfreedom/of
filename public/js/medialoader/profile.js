/**
 * Use to URL:
 *  /profile/*
 */

var func_profile = {
    add: function(){
        profile_galleryAddItem();
    },
    change: function(){
        profile_createAreasSelect();
    },
    confirm: function(){
        profile_removeConfirm();
    },
    remove: function(){
        profile_galleryRemoveImage();
        // profile_phoneRemoveItem();
    },
    load: function(){
        profile_loadFormatMoney();
        profile_loadTinymce();
        profile_createHtmlSelectLocation();
    },
    helper:function(){
        view_helper();
    },
    init: function(){
        this.add();
        this.change();
        this.confirm();
        this.remove();
        this.load();
        this.helper();
    },

    keyupTimeout: 0
}

$(document).ready(function(){
    // init func
    func_profile.init();
});

function profile_loadСustomForms(){
	if(jcf.customForms != 'undefined'){
        jcf.customForms.replaceAll();
    }
}

function view_helper(){
    var helper = {
        show: function(el){
            $('.helper .helper-box').hide();
            $(el).closest('.input-holder').find('.helper-box').show();
        },
        hide: function(el){
            $(el).closest('.input-holder').find('.helper-box').hide();
        }
    }

    bodyOffOn('click', '.helper .helper-item', function(){
        helper.show(this);
    });

    bodyOffOn('focus', '.helper .helper-item', function(){
        helper.show(this);
    });

    bodyOffOn('focusout', '.helper .helper-item', function(){
        helper.hide(this);
    });

    /* TODO: Only for hover items */
    $('.helper .mouse-hover').hover(
        function(){
            helper.show(this);
        },
        function(){
            helper.hide(this);
        }
    );
}

function profile_createAreasSelect(){
    bodyOffOn('change', 'select[name="region_id"]', function(){
        profile_createHtmlSelectLocation();
    });

    bodyOffOn('change', 'select[name="area_id"]', function(){
        profile_createHtmlSelectCity();
    });

    bodyOffOn('change', 'select[name="city_id"]', function(){
        profile_createHtmlInputZip();
    });
}

function profile_createHtmlSelectLocation(area_id){
    var region_id = $('select[name="region_id"]').val();
    var flagInner = false;
    if (!area_id)
    {
        var area_id = $('[name="area_id"]').val();
        flagInner = true;
    }
    // console.log(area_id);

    if ($('input[name="zip"]').val())
    {
        $('.region-holder').show();
    }

    $('.areas-holder').hide();
    $('.cities-holder').hide();
    // $('.zip-holder').hide();

    if(region_id > 0){
        var url = $('.form-validator').find('input[name="region_url"]').val();
        var vals = {
            region_id: region_id
        };
        $.post(url, vals, function(data){
            $('div.area_id').html('');
            if (data.areasList.length > 0){
                var html = '<select name="area_id" class="selectpicker form-control">';
                html += '<option value="0">-= Выберите регион =-</option>';
                for (var i in data.areasList){
                    html += '<option '+((data.areasList[i].id == area_id)?'selected="selected"': '')+' value="'+data.areasList[i].id+'">'+data.areasList[i].name+'</option>';
                }
                html += '</select>';
                html += '<span class="errors">* Заполните обязательное поле</span>';
                $('div.area_id').append(html);
            }
            $('select[name="area_id"]').selectpicker({
                style: 'btn-info',
                size: 4
            });
            $('.areas-holder').show();
            if (flagInner)
            {
                profile_createHtmlSelectCity();
            }
        });
    }
}

function profile_createHtmlSelectCity(area_id, city_id){
    var flagInner = false;
    if (!area_id)
    {
        var area_id = $('select[name="area_id"]').val();
        flagInner = true;
    }
    if (!city_id)
    {
        var city_id = $('[name="city_id"]').val();
    }

    $('.cities-holder').hide();

    if(area_id > 0){
        var url = $('.form-validator').find('input[name="area_url"]').val();
        var vals = {
            area_id: area_id
        };
        $.post(url, vals, function(data){
            $('div.city_id').html('');

            if (data.citiesList.length > 0){
                var html = '<select name="city_id" class="selectpicker form-control">';
                html += '<option value="0">-= Выберите город =-</option>';
                for (var i in data.citiesList){
                    html += '<option '+((data.citiesList[i].id == city_id)?'selected="selected"': '')+' value="'+data.citiesList[i].id+'">'+data.citiesList[i].name+'</option>';
                }
                html += '</select>';
                html += '<span class="errors">* Заполните обязательное поле</span>';
                $('div.city_id').append(html);
            }
            $('select[name="city_id"]').selectpicker({
                style: 'btn-info',
                size: 4
            });
            $('.cities-holder').show();
            if (flagInner)
            {
                profile_createHtmlInputZip();
            }
        });
    }
}


function profile_createHtmlInputZip(){
    var select = $('select[name="city_id"]');
    var city_id = $(select).val();
    var zip = $('input[name="zip"]').val();

    if (city_id > 0){
        var url = $('.form-validator').find('input[name="zip_url"]').val();
        var vals = {
            city_id: city_id
        };
        $.post(url, vals, function(data){
            if (data.zipList.length > 0){
                $('input[name="zip"]').attr('placeholder', data.zipList[0].name).val(data.zipList[0].name);
            }

            $('.zip-holder').show();
        });
    }
}


function profile_loadFormatMoney(){
    Number.prototype.formatMoney = function(c, d, t){
        var n = this,
            c = isNaN(c = Math.abs(c)) ? 2 : c,
            d = d == undefined ? "." : d,
            t = t == undefined ? "," : t,
            s = n < 0 ? "-" : "",
            i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
            j = (j = i.length) > 3 ? j % 3 : 0;

        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    };
}

/**
 * @description Load Tinymce
 */
function profile_loadTinymce(){
    $('textarea.tinymce').tinymce({
        script_url : '/js/tinymce/tinymce.min.js',
        plugins: [
            "advlist autolink lists link charmap preview",
            "textcolor code media paste"
        ],
        toolbar: "preview | styleselect forecolor backcolor | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link media charmap hr",
        width : '100%',
        height : 200,
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


function profile_galleryAddItem(){
    bodyOffOn('click', '.add-file', function(e){
        e.preventDefault();

        var newInput = $('.files-upload li:first').clone();
        $('.files-upload').append(newInput);

    });

}

/**
 * @description Confirm remove
 */
function profile_removeConfirm(){
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
function profile_galleryRemoveImage(){
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
 * @param object form
 * @param array data
 */
function profile_createErrors(form, data){
    if($(data.messages).length > 0){
        for(var i in data.messages)
        {
            var $errorParentElm = $(form).find('[name="'+i+'"]').parent();

            var errorMessagesHtml = '';
            for (j in data.messages[i])
            {
                errorMessagesHtml+='<p>* ' + data.messages[i][j] + '</p>';
            }
            $errorParentElm.find('span.errors').html(errorMessagesHtml);

            $errorParentElm.addClass('error');
        }

        var errorItem = $(form).find('.error:first').parent();
        if($(errorItem).length > 0){
            var top = $(errorItem).offset().top;
            $('html, body').animate({scrollTop : top},'slow');
        }
    }
}

/**
 * Error captcha
 * @param form
 * @param data
 */
function profile_captcha_createError(form, data){
    if(data.error.captcha == false){
        var $errorElm = $(form).find('[name="captcha[input]"]').parent();
        $errorElm.addClass('error');
    }
}

/**
 * @param object form
 */
function profile_removeErrors(form){
    $(form).find('.error').removeClass('error');
}

/**
 * @description Edit advert item
 */
function profile_advertsEdit(){
    bodyOffOn('click', '.menu .item.action-edit', function(e){
        e.preventDefault();
        var el = $(this);
        var url = $(el).attr('data-url');

        window.location.href = url;
    });
}

/**
 * @description Delete advert item
 */
function profile_advertsRemove(){
    bodyOffOn('click', '.menu .item.action-remove', function(e){
        e.preventDefault();
        if(confirm('Подтвердите удаление')){
            var el = $(this);
            var url = $(el).attr('data-url');

            window.location.href = url;
        }else{
            return false;
        }
    });
}