/**
 * Use to URL:
 *  /admin/catalog
 *  /admin/catalog/*
 */

var func_admin_catalog = {
    validator: function(){
        admin_catalog_formValidator();
        admin_catalog_categoryFormValidator();
        admin_catalog_locationFormValidator();
        admin_catalog_typeFormValidator();
        admin_catalog_currencyFormValidator();
    },
    actions: function(){
        admin_catalog_change_category_options();
        admin_catalog_get_cities_by_region();
    },
    optionLoad: function(){
        load_category_options();
    },
    load: function(){
        admin_catalog_createCitiesSelect();
    },
    init: function(){
        this.validator();
        this.actions();
        this.load();
    }
};

$(document).ready(function(){
    // init func
    func_admin_catalog.init();
});


/**********************/
/* Validators (start) */
/**********************/

function admin_catalog_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var phoneArray = [];
            
            $(form).find('.phone-box .phone-items').each(function(index, elem){
                phoneArray[index] = $(elem).val();
            });
            
            var vals = {
                name: $(form).find('input[name="name"]').val(),
                phoneArray: phoneArray,
                description: $(form).find('textarea[name="description"]').val(),
                price: $(form).find('input[name="price"]').val(),
                category_id: $(form).find('select[name="category_id"]').val()
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

function admin_catalog_categoryFormValidator(){
    bodyOffOn('submit', '.category-form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val()
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

function admin_catalog_locationFormValidator(){
    bodyOffOn('submit', '.location-form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val()
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

function admin_catalog_typeFormValidator(){
    bodyOffOn('submit', '.type-form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val()
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

function admin_catalog_currencyFormValidator(){
    bodyOffOn('submit', '.currency-form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');
        
        if($(status).val() == 0){            
            e.preventDefault();
            admin_removeErrors(form);
            
            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val()
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

function admin_catalog_get_cities_by_region(){
    bodyOffOn('change', '.selectpicker.location', function(){
        admin_catalog_createCitiesSelect()
    });
}

function admin_catalog_createCitiesSelect(){
    var select = $('.selectpicker.location');
    var region = $(select).val();
    var city = $('input[name="city_id"]').val();
    if(region > 0){
        var url = $('.form-validator').find('input[name="location_url"]').val();
        var vals = {
            region: region
        };

        $.post(url, vals, function(data){
            if (data.citiesList.length > 0){
                $('.controls.cities').html('');
                var html = '<label class="control-label">Город</label>\n\
                            <select class="selectpicker" name="location">';
                for (var i in data.citiesList){
                    html += '<option '+((data.citiesList[i].id == city)?'selected="selected"': '')+' value="'+data.citiesList[i].id+'">'+data.citiesList[i].name+'</option>';
                }
                html += '</select>';
                $('.controls.cities').append(html);
                admin_loadSelectpicker();
            }
        });
    }
}

function load_category_options(){
    var form = $('.form-validator');
    var url = $(form).find('input[name="load_options"]').val();
    var advert_id = $(form).find('input[name="advert_id"]').val();
    var vals = {
        category: $(form).find('select[name="category_id"]').val(),
        advert_id: (advert_id > 0 ? advert_id : 0)
    };

    $.post(url, vals, function(data){
        if (typeof(data.optionsList) !== 'undefined' && data.optionsList.length > 0) {
            $('.control-group label.options').html('Подрубрика');
            create_options_html(data);
        } else {
            $('.control-group label.options').html('Для этой категории нет опций');
            return false;
        }

    });
}

function create_options_html(data){
    var html = '';
    var box = $('.form-validator .control-group.options');
    var separators = {
        checkbox: true,
        text: true,
        select: true,
        radio: true,
        multi: true
    };
    for(var key in data.optionsList) {
        var val = data.optionsList[key];

            if (val.type == 'checkbox'){
                if(separators.checkbox === true){
                    separators.checkbox = false;
                    html += '<hr>';
                }
                html += '<div>' +
                            '<input name="option['+val.id+'][checkbox_one][checkbox]" type="checkbox" value="y"'+(val.value == 'y' ? ' checked="checked"' : '' )+'> ' +val.name+
                            '<input name="option['+val.id+'][checkbox_one][hidden]" type="hidden" value="n">'+
                        '</div>'
            } else if (val.type == 'text'){
                if(separators.text === true){
                    separators.text = false;
                    html += '<hr>';
                }
                html += '<div>' +
                        '<label class="control-label">'+val.name+'</label>'+
                            '<input class="span4" name="option['+val.id+']" type="text" value="'+val.value+'" >' +
                        '</div>'
            } else if (val.type == 'select'){
                if(separators.select === true){
                    separators.select = false;
                    html += '<hr>';
                }
                html += '<div>' +
                            '<label class="control-label">'+val.name+'</label>'+
                            '<select class="selectpicker span3" name="option['+val.id+']">';
                                 for (var i in val.value){
                                    html += '<option value="'+val.value[i].value+'"'+(val.value[i].selected == 'y' ? ' selected="selected"' : '')+'>'+val.value[i].name+'</option>'
                                }
                html +='</select>'+
                        '</div>';
            } else if (val.type == 'radio'){
                if(separators.radio === true){
                    separators.radio = false;
                    html += '<hr>';
                }
                html += '<div>' +
                            '<label class="control-label">'+val.name+'</label>';

                for (var i in val.value){
                    html += '<label class="radio">'+
                                '<input name="option['+val.id+']" type="radio" value="'+val.value[i].value+'"'+(val.value[i].selected == 'y' ? ' checked="checked"' : '')+'> '+val.value[i].name+
                            '</label>'
                }
                html +='</div>';

            } else if (val.type == 'multi'){
                if(separators.multi === true){
                    separators.multi = false;
                    html += '<hr>';
                }
                html += '<div>' +
                    '<label class="control-label">'+val.name+'</label>';

                for (var i in val.value){
                    html += '<label class="radio">'+
                        '<input name="option['+val.id+'][checkbox_multi][]" type="checkbox" value="'+val.value[i].value+'"'+(val.value[i].selected == 'y' ? ' checked="checked"' : '')+'> '+val.value[i].name+
                        '</label>'
                }
                html +='</div>';
            }
    }

    box.append(html);
    admin_loadSelectpicker();
}


function admin_catalog_change_category_options() {
    bodyOffOn('change', '.controls .load-category-options', function () {
        $('.form-validator .control-group.options').empty();
        load_category_options()
    })
}