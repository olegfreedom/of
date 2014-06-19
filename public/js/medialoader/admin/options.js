/**
 * Use to URL:
 *  /admin/options
 *  /admin/options/*
 */

var func_admin_options = {
    validator: function(){
        admin_options_formValidator();
    },
    action: function(){
        admin_options_add_select_button();
        admin_options_delete_select_button();
        admin_options_change_select();
        admin_options_checked_option();
    },
    init: function(){
        this.validator();
        this.action();
    },
    optionElementID: 0
}

$(document).ready(function(){
    //init func
    func_admin_options.init();
});

function admin_options_formValidator(){
    bodyOffOn('submit', '.form-validator', function(e){
        var form = $(this);
        var status = $(form).find('input[name="'+($(form).hasClass('edit') ? 'edit' : 'add')+'-form"]');

        if($(status).val() == 0){
            e.preventDefault();
            admin_removeErrors(form);

            var valueArray = [];

            $(form).find('.row-fluid').each(function(index, elem){
                valueArray[index] = {
                    name: $(elem).find('.controls-row .option_name').val(),
                    val: $(elem).find('.controls-row .option_val').val()
                };
            });

            var url = $(form).find('input[name="validator"]').val();
            var vals = {
                name: $(form).find('input[name="name"]').val(),
                type: $(form).find('select[name="type"]').val(),
                valueArray: valueArray
            };

            $.post(url, vals, function(data){
                if(data.status == true){
                    $(status).val(1);
                    $(form).submit();
                }else{
                    $(form).find('.controls-row').removeClass('error');
                    admin_createErrors(form, data);
                    if ($(data.error).length > 0) {
                        for (var i in data.error) {
                            if (i == 'valueArray' && $(data.error[i]).length > 0) {
                                for (var k in data.error[i]) {
                                    for (var d in data.error[i][k]) {
                                        if (data.error[i][k][d] == false) {
                                            $(form).find('.row-fluid').eq(k).find('.controls-row .option_'+d).parent('.controls-row').addClass('error');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            });
        }
    });
}
    function admin_options_change_select(){
        bodyOffOn('change', '.type-option', function(e){
            //e.preventDefault();
            var value = $(this).val();
            var html = '';
            var box = $('.control-group .controls.change');
            var button = $('.cont .controls-add a.add-select');
                $('.control-group .controls.change .new').remove();
            box.empty();
            button.hide();
            func_admin_options.optionElementID = 0;
            if (value == 'select'){
                html += admin_options_add_select_html();
                button.show();
            }else if (value == 'text') {
                html +='<div class="row-fluid change">'+
                            '<div class="col span3">'+
                                 '<div class="controls-row">'+
                                    '<label class="control-label">По умолчанию</label>'+
                                    '<input type="text" class="span12" name="default" value="">'+
                                '</div>'+
                            '</div>'+
                        '</div>';

            }else if (value == 'radio') {
                html += admin_options_add_select_html();
                button.show();

            }else if (value == 'multi') {
                html += admin_options_add_select_html();
                button.show();

            }else if (value == 'checkbox'){
                html += '<div class="row-fluid change">'+
                            '<div class="col span3">'+
                                '<div class="controls-row">'+
                                    '<input type="checkbox" value="y" name="default">&nbsp;По умолчанию'+
                                '</div>'+
                            '</div>'+
                        '</div>';
            }
            box.append(html);

        });
}

function admin_options_add_select_button(){
    bodyOffOn('click', '.add-select', function(e){
        e.preventDefault();
        var controls = $(this).closest('div.cont').find('.control-group .controls.change');
        var size = $(controls).find('.row-fluid.change').length;
        
        if(size > func_admin_options.optionElementID){
            func_admin_options.optionElementID = size;
        }

        $(controls).append(admin_options_add_select_html());
    });
}

function admin_options_delete_select_button(){
    bodyOffOn('click', '.icon-remove.options', function(e){
        e.preventDefault();
        var controls = $(this).closest('.controls.change');
        if($(controls).find('.row-fluid.change').length > 1){
            $(this).closest('.row-fluid.change').remove();
        }
    });
}

function admin_options_checked_option(){
    bodyOffOn('change', '.option_selected', function(){
        var type = $('.type-option.selectpicker').val();
        if (type != 'multi') {
            checkboxChecked($('.option_selected'), false);
            checkboxChecked($(this), true);
        }

    });
}

function admin_options_add_select_html(){
    var html = '';
    html += '<div class="row-fluid change">'+
                '<div class="col span3">'+
                    '<div class="controls-row">'+
                        '<label class="control-label">Название</label>'+
                        '<input type="text" class="option_name span12" name="option['+func_admin_options.optionElementID+'][name]">'+
                    '</div>'+
                '</div>'+
                '<div class="col span1 select">'+
                    '<div class="controls-row">'+
                        '<label class="control-label">&nbsp;</label>'+
                        '<input type="checkbox" value="y" class="option_selected" name="option['+func_admin_options.optionElementID+'][selected]">'+
                    '</div>'+
                '</div>'+
                '<div class="col span1">'+
                    '<div class="controls-row">'+
                    '<label class="control-label">&nbsp;</label>'+
                    '<a class="icon-remove options" title="удалить"></a>'+
                    '</div>'+
                '</div>'+
            '</div>';

    func_admin_options.optionElementID++;
    return html;
}

