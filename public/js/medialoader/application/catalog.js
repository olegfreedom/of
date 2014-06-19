/**
 * Use to URL:
 *  /catalog
 *  /catalog/*
 */

var func_application_catalog = {
//    validator: function(){
//        application_search_formValidator();
//    },
    actions: function(){
        application_catalog_createCitiesSelect()
    },
    load: function(){
        application_catalog_createHtmlSelectCity();
    },
    init: function(){
//        this.validator();
        this.actions();
        this.load();
    }
};

$(document).ready(function(){
    //init func
    func_application_catalog.init();
});

function application_catalog_createCitiesSelect(){
    bodyOffOn('change', 'select[name="region"]', function(){
        application_catalog_createHtmlSelectCity();
    });
}

function application_catalog_createHtmlSelectCity(){
    var select = $('select[name="region"]');
    var region = $(select).val();
    var city = $('form.srch').find('input[name="location_city"]').val();

    if(region > 0){
        var url = $('form.srch').find('input[name="location_url"]').val();
        var vals = {
            region: region
        };
        $.post(url, vals, function(data){
            if (data.citiesList.length > 0){
                $('.cities-box').html('');
                var html = '<select id="reg" name="location">';
                    html += '<option value="0">По всему региону</option>';
                for (var i in data.citiesList){
                    html += '<option '+((data.citiesList[i].id == city)?'selected="selected"': '')+' value="'+data.citiesList[i].id+'">'+data.citiesList[i].name+'</option>';
                }
                html += '</select>';
                $('.cities-box').append(html);
                jcf.customForms.replaceAll();
            }
        });
    } else {
        $('.cities-box').html('');
        var html = '<select id="reg" name="location">';
                html += '<option value="0">Выберите регион</option>';
            html += '</select>';
        $('.cities-box').append(html);
        // jcf.customForms.replaceAll();
    }
}
//
//function application_search_formValidator(){
//    bodyOffOn('submit', '.srch', function(e){
//        var form = $(this);
//        var status = $(form).find('input[name="search-form"]');
//        if($(status).val() == 0){
//            e.preventDefault();
//            application_removeErrors(form);
//            var url = $(form).find('input[name="validator"]').val();
//            var vals = {
//                search_text: $(form).find('input[name="search_text"]').val()
//            };
//            $.post(url, vals, function(data){
//                if(data.status == true){
//                    $(status).val(1);
//                    $(form).submit();
//                }else{
//                    application_createErrors(form, data);
//                }
//            });
//        }
//    });
//}

