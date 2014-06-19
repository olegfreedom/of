/**
 * Use to URL:
 *  /profile/settings/index
 *  /profile/settings/index/*
 */

function input_only_digits(val) {
    return val.replace(/[^\d,]/g, '');
}

$(document).ready(function(){
    $('#tel1, #tel2, #fax').each(function() {
        $(this).mask("+38 (099) 999 99 99", {
            completed : function () {
                // console.log($(this).val());
                // $(this).val($(this).val().replace(/\D/g, "")).unmask();
            }
        });
    });

});
