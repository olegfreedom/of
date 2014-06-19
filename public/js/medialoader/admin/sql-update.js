/**
 * Use to URL:
 *  /admin/sql-update
 *  /admin/sql-update/*
 */

var func_admin_sql_update = {
    update: function(){
        admin_sql_update_SetType();
    },
    init: function(){
        this.update();
    }
};

$(document).ready(function(){
    //init func
    func_admin_sql_update.init();
});

function admin_sql_update_SetType(){
    bodyOffOn('click', '.set-update-type', function(e){
        e.preventDefault();
        var type = $(this).attr('data-val');
        var form = $(this).parents('.update-form');
        
        if($(form).find('.checkbox.check-items').length > 0){
            if($(form).find('.checkbox.check-items:checked').length > 0){
                $(form).find('input[name="type"]').val(type);
                $(form).submit();                
            }else{
                $(form).find('.alert-box').html(admin_errorAlert('Выберите нужные обновления.'));
            }            
        }else{                
            $(form).find('.alert-box').html(admin_errorAlert('Нет доступных обновлений.'));
        }
    });
}