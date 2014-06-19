/**
 * Use to URL:
 *  /faq
 *  /faq/*
 */

function scrollPageCustom(selector, top) {
    var typical = $(selector).closest('.typical');
 
    if($(typical.next('.typical')).length > 0) {
        var item = typical.next('.typical');
    } else {
        var item = typical.closest('.faq').first('.typical');
    }

    scrollingPage(item, top);
}