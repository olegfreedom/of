/**
 * Use to URL:
 *  /profile/index/add
 *  /profile/index/add/*
 */
var func_profile_add = {
    init: function(){
        func_profile_index.optionLoad();
    }
};

$(document).ready(function(){
    // init func
    func_profile_add.init();
});
