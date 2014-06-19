var func_general = {
    popup: function(){
        popup_registerRequestInit();
    },
    init: function(){
        this.popup();
    }
}


$(document).ready(function(){
    //init func
    func_general.init();
});


function popup_registerRequestInit(){
    bodyOffOn('click', '.register-request', function(e){
        e.preventDefault();

        if ( $('#popup-register-request').length )
        {
            $('#popup-register-request').modal('show');
        }
    });
}


(function($) {
    $(function(){
        if($('.carousel').length>0){
            $('.carousel').carousel()
        }


        if($('input.customStyled').length>0){
            jQuery('input.customStyled').each(function(){
                $(this).prettyCheckable();
            });
        }

        if($('.selectpicker').length>0){
            $('.selectpicker').selectpicker({
                style: 'btn-info',
                size: 4
            });
        }
/*
        if( $(".calendar").length>0){
            $(".calendar").calendar();
        }
*/
        if( $('.slider-rating').length>0){
            $('.slider-rating').slider({
                formater: function(value) {
                    return 'Current value: '+value;
                }
            });
        }

        if(typeof($('textarea')) !== 'undefined'){
            $('textarea').autoResize();
        }


        if($('input[type=file]').length>0){
            $('input[type=file]').bootstrapFileInput();
        }

        $('.datepicker').datepicker({
            format: 'dd.mm.yyyy',
            language: 'ru'
        }).on('changeDate', function(e){
            (e.viewMode=='days') ? $(this).datepicker('hide') : '';
        });

/*
        var nowTemp = new Date();
        var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);

        var checkin = $('#dpd1').datepicker({
            onRender: function(date) {
                return date.valueOf() < now.valueOf() ? 'disabled' : '';
            }
        }).on('changeDate', function(ev) {
            if (ev.date.valueOf() > checkout.date.valueOf()) {
                var newDate = new Date(ev.date)
                newDate.setDate(newDate.getDate() + 1);
                checkout.setValue(newDate);
            }
            checkin.hide();
            $('#dpd2')[0].focus();
        }).data('datepicker');
        var checkout = $('#dpd2').datepicker({
            onRender: function(date) {
                return date.valueOf() <= checkin.date.valueOf() ? 'disabled' : '';
            }
        }).on('changeDate', function(ev) {
            checkout.hide();
        }).data('datepicker');
*/

        /*This area from init Function*/
    });



    /*This area from declaration plugins*/
})(jQuery);


