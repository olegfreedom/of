$(function(){
   $('input[name="photo"]').on('change',function(e){
        var reader = new FileReader();
        reader.onload = function (e) {
            $('img.change-photo').attr('src', e.target.result);
        };
        reader.readAsDataURL(this.files[0]);
    });
});