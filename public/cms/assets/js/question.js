$(function(){
    $('form[name="question"]').submit(function(){
        console.log('submit form question');
        var formElem = this;
        var params = $(this).serialize();
        console.log(params);
        $.ajax({
            type: 'POST',
            dataType: 'html',
            url: '/forum/question/add',
            data: params,
            success: function(data) {
                $("table.questions tbody").append(data);
            },
            error: function(xhr, str) {
                //console.log(str);
                alert('Помилка: ' + xhr.responseCode);
            }
        });
        return false;
    });
    
    $('.delete').on('click',function(){
        var trElem = $(this).closest('tr');
        var params = {};
        params['id'] = $(trElem).attr('attr-id');
            console.log(params);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: '/forum/question/delete',
            data: params,
            success: function(data) {
                $(trElem).remove();
                $(".qwHead"+params['id']).remove();
                $(".qwBody"+params['id']).remove();
                console.log(data);
            },
            error: function(xhr, str) {
                //console.log(str);
                alert('Помилка: ' + xhr.responseCode);
            }
        });

    });
    $('.edit').on('click',function(){
        var trElem = $(this).closest('tr');
        var tmp = $(trElem).attr('attr-id');
        console.log(tmp);
    });
    
//    $(document).on('click','table .forum_img_event',function(){
//        var params = [];
//        // params['type']
//        // params['id']
//        if($(this).hasClass('edit'))
//            params['type'] = 'edit';
//        if($(this).hasClass('delete'))    
//            params['type'] = 'delete';
//        params['id'] = $(this).closest('tr').attr('attr-id');
//        console.log(params);
//    });

    $(function() {
        $('#accordion').accordion({
            collapsible: true,
            heightStyle: "content"
        });
        
        $("#myDiv1").animate({scrollTop: $("#myDiv1").prop("scrollHeight")},
        3000);
        
        $('form[name="comment"]').submit(function(){
            var params = $(this).serialize();                
            var id_qvs = $(this).find('input[name="id_question"]').val();
            var commentBlock = $('.comment-question_'+id_qvs+' .comment-box');
            $.ajax({
                type: 'POST',
                dataType: 'html',
                url: '/forum/comment/add',
                data: params,
                success: function(data) {
                    $('.comment-question_'+id_qvs+' .comment-box').append(data)
                        .animate({scrollTop: $(commentBlock).prop("scrollHeight")},1500);
                },
                error: function(xhr, str) {
                    //console.log(str);
                    alert('Помилка: ' + xhr.responseCode);
                }
            });
            
//            $('.comment-question_'+id_qvs+' .comment-box').append("<table width='100%'>\n\
//                                <tr valign='top'>\n\
//                                    <td width='150px' style='font-size:8pt;text-align:center; border:1px dotted;'>\n\
//                                        <div style='font-size:7pt; text-align:right;'>+3.5 </div>\n\
//                                        <img src='http://wenet.pu.if.ua/tmp/project/vote/data/img/user.png' style='width:100px;'>\n\
//                                        <div style='font-size:8pt; padding:5px 0px 5px 5px;'>\n\
//                                            <b>Сітник Андрій</b>\n\
//                                            <!-- <div style='font-size:7pt; padding-top:3px;'> створено</div> -->\n\
//                                        </div>\n\
//                                    </td>\n\
//                                    <td style='border:1px dotted; padding:0px;'>\n\
//                                        <div style='min-height:120px; padding:5px;'>коментар</div>\n\
//                                        <div style='text-align:right; background:#f5f5f5; padding: 3px 25px 3px 25px; font-size:8pt; color:#333333; border-top:1px dotted;'>\n\
//                                            <b>Дата:</b> створено | \n\
//                                            <b>Оцінка:</b> +3.5 \n\
//                                            <!-- <a href='' style='color:#333333;'><b>Прокоментувати</b></a>  -->\n\
//                                        </div>\n\
//                                    </td>\n\
//                                </tr>\n\
//                            </table>").animate({scrollTop: $(commentBlock).prop("scrollHeight")},1500);
            
            return false;
        });
        $('form[name="vote"]').submit(function(){     
            alert('Ваш голос прийнято до уваги!');
            $(this).remove();
             console.log('vote');
             // -------------  I must finished it  ----------------
//            $.ajax({
//                type: 'POST',
//                dataType: 'html',
//                url: '/forum/comment/add',
//                data: params,
//                success: function(data) {
//                    $('.comment-question_'+id_qvs+' .comment-box').append(data)
//                        .animate({scrollTop: $(commentBlock).prop("scrollHeight")},1500);
//                },
//                error: function(xhr, str) {
//                    //console.log(str);
//                    alert('Помилка: ' + xhr.responseCode);
//                }
//            });
               
            return false;
        });
        
        
    });
})