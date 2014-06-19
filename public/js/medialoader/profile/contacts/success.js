/**
 * Use to URL:
 *  /profile/contacts/success
 *  /profile/contacts/success/*
 */
var func_profile_contacts_success = {
    redirect: function(){
        redirect_to_contact();
    },
    init: function(){
        this.redirect();
    }
}

$(document).ready(function(){
    // init func
    func_profile_contacts_success.init();
});

function redirect_to_contact(){
    setTimeout(function(){
        var url = $('.contact-url').val();
        self.location.href = url;
    }, 3000);
}
