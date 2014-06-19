/**
 * Use to URL:
 *  /profile/settings/success
 *  /profile/settings/success/*
 */
var func_profile_settings_success = {
    redirect: function () {
        redirect_to_contact();
    },
    init: function () {
        this.redirect();
    }
}

$(document).ready(function () {
    // init func
    func_profile_settings_success.init();
});

function redirect_to_contact() {
    setTimeout(function () {
        var url = $('.settings-url').val();
        self.location.href = url;
    }, 3000);
}
