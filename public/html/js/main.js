(function($) {
    $(function(){

        $('#tabs a').click(function (e) {
            e.preventDefault()
            $(this).tab('show')
        })
        $('#tabs2 a').click(function (e) {
            e.preventDefault()
            $(this).tab('show')
        })
        $('#tabs3 a').click(function (e) {
            e.preventDefault()
            $(this).tab('show')
        })
        $('#tabs3-2 a').click(function (e) {
            e.preventDefault()
            $(this).tab('show')
        })
        $('#tabs3-3 a').click(function (e) {
            e.preventDefault()
            $(this).tab('show')
        })
        $('#tabs3-4 a').click(function (e) {
            e.preventDefault()
            $(this).tab('show')
        })
        if($('.selectpicker').length>0){
            $('.selectpicker').selectpicker();
            $('.selectpicker').selectpicker({
                style: 'btn-info',
                size: 4
            });
        }

        if( $(".collapse").length>0){
            $(".collapse").collapse();
        }



        if( $(".calendar").length>0){
            $(".calendar").calendar();
        }

        if( $('#sl1').length>0){
            $('#sl1').slider({
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

        if( $('.datepicker').length>0){
            $('.datepicker').datepicker();

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
        }






        /*This area from init Function*/
    });



    /*This area from declaration plugins*/
})(jQuery);



//Different colors for positive and negative comment values

$(document).ready(function() {
    var $comments = $('.comment');
    $comments.each(function(index) {
        var comVal=parseInt($(this).text());
        if (comVal<0) {
            $(this).css('color', '#ef0c2c');
        }    else if(comVal>0) {
            $(this).css('color', '#06d489');
        }
    });

});


/*Image Gallery*/
$(document).ready(function() {

    $('.popup-gallery').magnificPopup({
        delegate: 'a', //
        type: 'image',
        gallery:{
            enabled:true
        }

    });

    $('#main-image').magnificPopup({
        items: [
            {
                src: 'images/image_big1.jpg'
            },
            {
                src: 'images/image_big2.jpg'
            },
            {
                src: 'images/image_big3.jpg'
            },
            {
                src: 'images/image_big4.jpg'
            }

        ],
        type: 'image',
        gallery:{
            enabled:true
        }

    });
});

    /*$(".fancybox-gallery").fancybox({
        prevEffect	: 'none',
        nextEffect	: 'none',
        helpers	: {
            title	: {
                type: 'outside'
            }

        }
    });*/
/*});*/

