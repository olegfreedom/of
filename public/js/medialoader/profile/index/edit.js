/**
 * Use to URL:
 *  /profile/index/edit
 *  /profile/index/edit/*
 */
var func_profile_edit = {
    init: function(){
        func_profile_index.optionLoad();
    }
};

$(document).ready(function(){
    // init func
    func_profile_edit.init();
});
