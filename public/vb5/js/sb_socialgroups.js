window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["contenttype_vbforum_socialgroup","error","invalid_page_specified","searchtype_social_groups"]);(function(A){var B=[".socialgroup-home-widget",".socialgroup-category-list-widget"];if(!vBulletin.pageHasSelectors(B)){return false}A(document).ready(function(){vBulletin.conversation=vBulletin.conversation||{};var Z=A(".socialgroup-widget"),Q=A(".activity-stream-widget"),G=A("#activity-stream-tab",Q),M=A(".conversation-list",G),D=A(".groups-tab",Z),I,c,U,V,C,E,L,T,a,W={},O=Q.find(".js-module-top-anchor").attr("id");if(Z.hasClass("socialgroup-home-widget")){function P(e){var f=(Q.offset().top+(Q.outerHeight()-parseFloat(Q.css("border-bottom-width")))-e.height());return f}var H=Q.find(".widget-tabs-nav .ui-tabs-nav > li"),S=H.filter(".ui-tabs-selected"),R=S.index(),d,Y=false,N=H.parent().data("allow-history")=="1",F=new vBulletin.history.instance(N);if(R==-1){R=0;S=H.first()}d=S.find("> a").attr("href");var b=function(e){e=e||d;return H.filter('li:has(a[href*="'+e+'"])').first().index()};H.removeClass("ui-state-disabled");vBulletin.tabify.call(Q,{tabHistory:F,getTabIndexByHash:b,allowHistory:N,tabParamAsQueryString:true,hash:O,tabOptions:{selected:R,select:function(g,h){if(U){U.hideFilterOverlay()}var f=Q.find(".widget-tabs-panel .ui-tabs-panel:visible");var e=f.find(".list-item-body-wrapper.edit-post .edit-conversation-container");if(e.length>0){openAlertDialog({title:vBulletin.phrase.get("edit_conversation"),message:vBulletin.phrase.get("you_have_a_pending_edit_unsaved"),iconType:"warning",onAfterClose:function(){vBulletin.animateScrollTop(e.closest(".list-item").offset().top,{duration:"slow"})}});return false}},show:function(g,h){var f=h.panel.id;if(typeof W[f]=="undefined"){W[f]=A(".conversation-toolbar-wrapper",h.panel).data("allow-history")=="1"}if(h.tab.hash=="#activity-stream-tab"){if(!U){I=A(".conversation-toolbar-wrapper.scrolltofixed-floating",G);c=new vBulletin.scrollToFixed({element:I,limit:P(I)});U=new vBulletin.conversation.filter({context:G,autoCheck:A(".toolbar-filter-overlay input[type=radio][value=conversations_on]",Q).is(":checked"),scrollToTop:Q,allowHistory:W[f],onContentLoad:function(){c.updateLimit(P(I));vBulletin.truncatePostContent(M);vBulletin.conversation.processPostContent(M)}});if(d==h.tab.hash){vBulletin.truncatePostContent(M);vBulletin.conversation.processPostContent(M);U.lastFilters={filters:U.getSelectedFilters(A(".toolbar-filter-overlay",G))};A(this).data("noPushState",true)}}else{U.setOption("context",G);if(typeof U.lastFilters!="undefined"&&A(".conversation-empty:not(.h-hide)",h.panel).length>0){delete U.lastFilters}}U.applyFilters(false,true)}else{if(h.tab.hash=="#groups-tab"){if(U){U.toggleNewConversations(false)}if(!a){a=vBulletin.history.instance(W[f]);if(d==h.tab.hash){K(true)}X()}var e=A(".conversation-empty",h.panel);if(e.length>0){if(d==h.tab.hash&&!L){L=true;return false}e.addClass("h-hide");J({},!A(this).data("noPushState"))}}}}}});Q.off("click",".list-item-poll .view-more-ctrl").on("click",".list-item-poll .view-more-ctrl",function(g){var f=A(this).closest("form.poll");var h=f.find("ul.poll");A(this).addClass("h-hide");h.css("max-height","none").find("li.h-hide").slideDown(100,function(){f.find(".action-buttons").removeClass("h-hide").next(".view-less-ctrl").removeClass("h-hide");vBulletin.animateScrollTop(f.offset().top,{duration:"fast"})});return false});Q.off("click",".list-item-poll .view-less-ctrl").on("click",".list-item-poll .view-less-ctrl",function(g){var f=A(this).closest("form.poll");vBulletin.conversation.limitVisiblePollOptionsInAPost(f,3);f.find("ul.poll").css("max-height","").find("li.h-hide").slideUp(100);return false});Q.off("click",".js-post-control__ip-address").on("click",".js-post-control__ip-address",vBulletin.conversation.showIp);Q.off("click",".js-post-control__edit").on("click",".js-post-control__edit",function(f){vBulletin.conversation.editPost.apply(this,[f,U])});Q.off("click",".js-post-control__vote").on("click",".js-post-control__vote",function(f){if(A(f.target).closest(".bubble-flyout").length==1){vBulletin.conversation.showWhoVoted.apply(f.target,[f])}else{vBulletin.conversation.votePost.apply(this,[f])}return false});Q.off("click",".js-post-control__flag").on("click",".js-post-control__flag",vBulletin.conversation.flagPost);Q.off("click",".js-post-control__comment").on("click",".js-post-control__comment",vBulletin.conversation.toggleCommentBox);Q.off("click",".js-comment-entry__post").on("click",".js-comment-entry__post",function(f){vBulletin.conversation.postComment.apply(this,[f,function(){U.updatePageNumber(1).applyFilters(false,true)}])});vBulletin.conversation.bindEditFormEventHandlers("all")}function K(h){if(a.isEnabled()){var i=a.getState();if(!i||A.isEmptyObject(i.data)){var f={from:"filter_groups",page:Number(A(".pagenav-form .defaultpage",D).val())||1,filters:{filter_groups:A(".js-button-filter.js-checked",D).data("filter-value")}};h?(f.tab="#groups-tab"):null;var e=vBulletin.parseQueryString(location.search),g;if(h&&typeof e.tab=="undefined"&&d&&d!="#groups-tab"){e.tab="groups-tab";g=location.pathname+"?"+A.param(e)}else{g=location.href}a.setDefaultState(f,document.title,g)}}}function X(){if(a.isEnabled()){a.setStateChange(function(j,m,l){var f=a.getState();if(f.data.from=="filter_groups"||m=="pagination"){a.log(f.data,f.title,f.url);var k=Z.hasClass("ui-tabs")&&Z,h=k&&k.tabs("option","selected"),n=k&&k.find(".ui-tabs-nav > li").eq(h).find("a").attr("href");if(n!==false&&n!=f.data.tab){var i=k.find(".ui-tabs-nav > li").filter('li:has(a[href*="{0}"])'.format(f.data.tab)).index();vBulletin.selectTabByIndex.call(k,i)}else{var g={page:f.data.page,my:m=="pagination"&&l?(l.filter_groups||"show_all"):f.data.filters.filter_groups};J(g,false)}}},"filter")}}A(document).off("click",".add-sg").on("click",".add-sg",function(f){A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/content_channel/canAddChannel",data:{nodeid:pageData.channelid},type:"POST",dataType:"json",async:false,success:function(e){if(e.can===true){location.href=pageData.baseurl+"/sgadmin/create/settings"}else{if(e.exceeded>0){openAlertDialog({title:vBulletin.phrase.get("social_groups"),message:vBulletin.phrase.get("you_can_only_create_x_groups_delete",e.exceeded),iconType:"warning"})}else{openAlertDialog({title:vBulletin.phrase.get("social_groups"),message:vBulletin.phrase.get(e.error),iconType:"warning"})}}}})});W["groups-tab"]=W["groups-tab"]||A(".conversation-toolbar-wrapper",D).data("allow-history")=="1";D.off("click",".js-button-filter").on("click",".js-button-filter",function(f){var g={my:A(this).data("filter-value"),page:1};J(g,true)});T=new vBulletin.pagination({context:D,allowHistory:W["groups-tab"],onPageChanged:function(i,h,f,g){if(!h){var e=typeof g!="undefined"?vBulletin.parseQueryString(e):null,j={my:e&&typeof e.filter_groups=="undefined"?"show_all":A(".js-button-filter.js-checked",D).data("filter-value"),page:i};J(j,h)}}});if(!Z.hasClass("ui-tabs")){a=vBulletin.history.instance(W["groups-tab"]);K(false);X()}function J(l,h){var i=A(".conversation-list",D),k=A(".sg-groups-list",i),f=A(".sg-groups-pagination",k);l.sgparent=l.sgparent||A(".js-category",f).val();l.page=l.page||A(".js-pagenum",f).val();l.perpage=l.perpage||A(".js-perpage",f).val();l.my=l.my||D.find(".js-button-filters .js-button-filter.js-checked").data("filter-value");l.routeInfo=l.routeInfo||k.find(".sg-groups-list-container").data("route");A(".js-button-filters .js-button-filter",D).removeClass("js-checked").filter('[data-filter-value="{0}"]'.format(l.my)).addClass("js-checked");A.ajax({type:"post",url:vBulletin.getAjaxBaseurl()+"/ajax/render/socialgroup_nodes",data:l,dataType:"json",success:function(m){if(m.errors){openAlertDialog({title:vBulletin.phrase.get("contenttype_vbforum_socialgroup"),message:vBulletin.phrase.get(m.errors[0]),iconType:"error"})}else{i.replaceWith(m)}},fail:function(){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error"})}}).done(function(){var q=A(".conversation-toolbar-wrapper .pagenav-controls .pagenav-form",D),o=A(".arrow",q).removeClass("h-disabled"),m=D.find(".sg-groups-list .sg-groups-pagination"),n=m.find(".js-prevpage").val(),p=m.find(".js-nextpage").val();A(".js-pagenum",q).val(m.find(".js-pagenum").val());A(".pagetotal",q).text(m.find(".js-totalpages").val());if(n){o.filter("[rel=prev]").removeClass("h-disabled").attr("href",n)}else{o.filter("[rel=prev]").addClass("h-disabled").removeAttr("href")}if(p){o.filter("[rel=next]").removeClass("h-disabled").attr("href",p)}else{o.filter("[rel=next]").addClass("h-disabled").removeAttr("href")}});if(h){var g=location.pathname;if(d&&d!="#groups-tab"){g+="?tab=groups-tab"}g=vBulletin.makePaginatedUrl(g,l.page);g=vBulletin.makeFilterUrl(g,"filter_groups",l.my,D);if(a&&a.isEnabled()){var j=a.getState(),e={from:"filter_groups",page:l.page,tab:D.hasClass("ui-tabs-panel")?"#"+D.attr("id"):undefined,filters:{filter_groups:l.my}};a.pushState(e,document.title,g)}else{if(W["groups-tab"]){g=vBulletin.makeFilterUrl(g,"filter_groups",filterValue,D,O);location.href=g;return false}}}}})})(jQuery);