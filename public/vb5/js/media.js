window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["saving"]);window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,["postminchars","commentminchars"]);var galleryData={mediaTypeFilter:false,currentMediaPage:Number($("#mediaCurrentPage").val())||1,currentGalleryPage:1,currentNodeId:0,currentUserId:0,profileMediaDetailContainer:false,currentDateFilter:"time_lastmonth"};var $mediaTab=$("#media-tab"),allowHistory,filterHistory,hash;window.vBulletin.media=window.vBulletin.media||{};(function(A){var B=["#media-tab"];if(!vBulletin.pageHasSelectors(B)){return false}window.vBulletin.media.calculatePhotosPerPage=function(D){D=D||50;var C=A("#profileMediaDetailContainer").is(":visible");if(!C){A("#profileMediaDetailContainer").removeClass("h-hide")}var E=Math.floor(A("#profileMediaDetailContainer").width()/128);if(!C){A("#profileMediaDetailContainer").addClass("h-hide")}return(D%E==0||D<=E)?D:D-(D%E)};A(document).ready(function(){A(document).off("click","#mediaList .albumLink").on("click","#mediaList .albumLink",loadGallery);var C=false;$mediaTab.off("click",".profile-media-createvideo, .profile-media-upload").on("click",".profile-media-createvideo, .profile-media-upload",function(E){var D=A(this).hasClass("profile-media-upload")?"gallery":"video";if(!C){C=A(E.target).closest(".ui-widget-content").find(".profileMediaEditContainer").dialog({modal:true,autoOpen:false,width:800,title:vBulletin.phrase.get("profile_media"),resizable:false,closeOnEscape:false,showCloseButton:false,dialogClass:"l-small dialog-container dialog-box edit-media-upload-dialog js-profile-media",close:function(){A(document).data("gallery-container",null)}});vBulletin.upload.initializePhotoUpload(C)}C.off("dialogopen").on("dialogopen",function(F,G){C.find('.b-toolbar__item[data-panel="b-content-entry-panel__content--{0}"]:not(.b-toolbar__item--active)'.format(D)).trigger("click");vBulletin.ckeditor.initEditorComponents(C);A(document).data("gallery-container",A(this).find(".b-content-entry-panel__content--"+D))}).dialog("open")});$mediaTab.off("click","#profileMediaDetailContainer .more-gallery").on("click","#profileMediaDetailContainer .more-gallery",getMorePhotos);$mediaTab.off("click",".profile-media-backbtn").on("click",".profile-media-backbtn",function(E){A("#profileMediaDetailContainer").addClass("h-hide");A("#mediaPreviousPage").closest(".pagenav-controls-container").removeClass("h-hide");A("#profileMediaContainer").removeClass("h-hide");A(".media-tab .profile-toolbar .toolset-left > li").addClass("h-hide").has(".profile-media-upload, .profile-media-createvideo").removeClass("h-hide");galleryData.currentDateFilter=A(this).closest(".conversation-toolbar-wrapper").find(".toolbar-filter-overlay input[name=filter_time]:checked").val();A(".profile-toolbar .media-toolbar-filter").addClass("h-hide");A(this).closest(".conversation-toolbar-wrapper").find(".filtered-by").addClass("h-hide").find(".filter-text-wrapper").empty();var D={totalpages:Number(A("#mediaList").data("totalpages")),totalcount:Number(A("#mediaList").data("totalcount")),currentpage:galleryData.currentMediaPage};setPagination(D)});$mediaTab.off("change","#mediaCurrentPage").on("change","#mediaCurrentPage",goMediaPage);$mediaTab.off("click","#mediaPreviousPage, #mediaNextPage").on("click","#mediaPreviousPage, #mediaNextPage",goMediaPage);galleryData.profileMediaDetailContainer=A("#profileMediaDetailContainer").clone();A(document).off("afterSave",".js-album-detail .edit-post.content-entry-box .js-content-entry form").on("afterSave",".js-album-detail .edit-post.content-entry-box .js-content-entry form",function(E,D){loadGalleryById(galleryData.currentFilter);return false});A(document).off("afterCancel",".js-album-detail .edit-post.content-entry-box .js-content-entry form").on("afterSave",".js-album-detail .edit-post.content-entry-box .js-content-entry form",function(D){loadMediaPage(galleryData.currentMediaPage);return true});A(document).off("afterSave",".js-profile-media .js-content-entry form").on("afterSave",".js-profile-media  .js-content-entry form",function(G,F){var I=A(".b-toolbar__item--active",this).data("panel");if(I=="b-content-entry-panel__content--gallery"){var D=A("#mediaList"),H=Number(D.data("totalpages")),J=Number(D.data("totalcount")),E=(J%vBulletin.media.ALBUMS_PERPAGE)==0?H+1:H;if(F.alert){openAlertDialog({title:vBulletin.phrase.get("media"),message:vBulletin.phrase.get(F.alert),iconType:"alert"})}loadMediaPage(E)}else{loadMediaPage(1)}return false});if($mediaTab.length){allowHistory=$mediaTab.find(".conversation-toolbar-wrapper").data("allow-history")=="1";filterHistory=new vBulletin.history.instance(allowHistory);hash=$mediaTab.closest(".canvas-widget").find(".js-module-top-anchor").attr("id");$mediaTab.off("click",".media-toolbar-filter").on("click",".media-toolbar-filter",function(D){A(".filter-wrapper",this).toggleClass("selected");A(".arrow .vb-icon",this).toggleClass("vb-icon-triangle-down-wide vb-icon-triangle-up-wide");A(D.target).closest(".conversation-toolbar-wrapper").find(".toolbar-filter-overlay").slideToggle("slow",function(){var E="media_filter";if(A(this).is(":visible")){A("body").off("click."+E).on("click."+E,function(H){if(A(H.target).closest(".toolbar-filter-overlay").length==0&&A(H.target).closest(".toolbar-filter").length==0){A("body").off("click."+E);A(".media-toolbar-filter").trigger("click")}});var G={};var F=vBulletin.isScrolledIntoView(this,G);if(!F){A("html,body").animate({scrollTop:"+="+Math.abs(G.bottom)},"fast")}}else{A("body").off("click."+E)}})});A("form.media-filter-overlay",$mediaTab).trigger("reset");$mediaTab.off("change",".media-filter-overlay input[type=radio]").on("change",".media-filter-overlay input[type=radio]",function(K,I){var E;if(!filterHistory.isEnabled()&&allowHistory){E=vBulletin.makePaginatedUrl(location.href,1);location.href=vBulletin.makeFilterUrl(E,this.name,this.value,H,hash);return true}galleryData.currentDateFilter=this.value;var F=fetchMediaFilter(A(K.target));var H=A(".media-tab");if(H.data("perpage")){F.perpage=H.data("perpage")}loadGalleryById(F,true,0,H.data("callbacks"));if(!A(this).data("bypass-filter-display")){vBulletin.conversation.displaySelectedFilterText(this,this.value)}if(filterHistory.isEnabled()&&!I){var J=vBulletin.getSelectedFilters(A("form.toolbar-filter-overlay",H)),N=this.name,D=this.value,M=A(".conversation-toolbar-wrapper .filtered-by",H),G=H.data("url-path")?H.data("url-path"):"#"+H.attr("id"),L={from:"filter",page:1,tab:G,filters:J,filtervalue:D,filtername:N};if(!M.data("reset")){E=vBulletin.makePaginatedUrl(location.href,1);E=vBulletin.makeFilterUrl(E,N,D,H)}else{E=location.pathname.replace(/\/page[0-9]+/,"")}filterHistory.pushState(L,document.title,E);M.data("reset",null)}});$mediaTab.off("click",".filtered-by .x").on("click",".filtered-by .x",function(F){var D=A(this).closest(".filtered-by"),H=A(this).closest(".filter-text"),E=H.data("filter-name");if(!filterHistory.isEnabled()&&allowHistory){location.href=vBulletin.makeFilterUrl(location.href,E,H.data("filter-value"),$mediaTab,hash);return false}$defaultSelectedFilter=A(".toolbar-filter-overlay .filter-options input[name={0}]".format(E),$mediaTab).prop("checked",false).filter(".js-default-checked");H.remove();var G=D.find(".filter-text").length;if(G==0){D.addClass("h-hide");D.data("reset",true)}else{if(G==1){D.find(".clear-all").addClass("h-hide")}}if($defaultSelectedFilter.length==1){$defaultSelectedFilter.data("bypass-filter-display",true);$defaultSelectedFilter.trigger("click");$defaultSelectedFilter.data("bypass-filter-display",null)}})}})})(jQuery);doMediaFilters=function(A){$(".profMediaFilterRow").removeClass("filterSelected");$(A.target).addClass("filterSelected");if($(A.target).hasClass("profMediaFilterAllTypes")){galleryData.mediaTypeFilter=false}else{if($(A.target).hasClass("profMediaFilterGallery")){galleryData.mediaTypeFilter="gallery"}else{if($(A.target).hasClass("profMediaFilterVideo")){galleryData.mediaTypeFilter="video"}}}loadMediaPage(1)};loadMediaPage=function(A){$("body").css("cursor","wait");var B=$(".js-profile-media-container",$mediaTab);$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/profile_media_content",data:({userid:B.data("user-id"),pageno:A,perpage:B.data("perpage")}),dataType:"json",success:function(C){if(C.errors){openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get(C.errors[0][0]),iconType:"error"});console.log("/ajax/render/profile_media_content failed, error: "+JSON.stringify(C))}else{console.log("/ajax/render/profile_media_content successful");$("#mediacontent").html(C);$("#profileMediaDetailContainer").empty();$("#mediacontent").removeClass("h-hide");$("#profileMediaContainer").removeClass("h-hide");$(".media-tab .profile-toolbar .toolset-left > li").addClass("h-hide").has(".profile-media-upload, .profile-media-createvideo").removeClass("h-hide");$("#mediaPreviousPage").closest("pagenav-controls").removeClass("h-hide");$("#profileMediaDetailContainer").addClass("h-hide");$("#profileMediaContainer").removeClass("h-hide");galleryData.currentMediaPage=A;var D={totalpages:Number($("#mediaList").data("totalpages")),totalcount:Number($("#mediaList").data("totalcount")),currentpage:A};setPagination(D);if(!vBulletin.isScrolledIntoView($("#profileTabs .profile-tabs-nav"))){$("html,body").animate({scrollTop:$("#profileTabs .profile-tabs-nav").offset().top},"slow")}}},error:function(E,D,C){console.log("/ajax/render/profile_media_content failed, error: "+C);console.log("response:"+E.responseText);console.log("status:"+D);console.log("code:"+E.status);openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error"})},complete:function(){$("body").css("cursor","auto")}})};goMediaPage=function(C){var B=this.id;if($("#profileMediaDetailContainer").is(":visible")){gotoGalleryPage(C);return false}if(B=="mediaCurrentPage"){var D=parseInt($("#mediaCurrentPage").val());if(D>0&&D<=parseInt($("#mediaPageCount").html())){loadMediaPage(D);pushHistoryState(D)}else{$("#mediaCurrentPage").val(galleryData.currentMediaPage)}}else{if(B=="mediaPreviousPage"){if(galleryData.currentMediaPage>1){var A=galleryData.currentMediaPage-1;loadMediaPage(A);pushHistoryState(A)}else{$("#mediaPreviousPage").addClass("h-disabled")}}else{if(B=="mediaNextPage"){if(galleryData.currentMediaPage<parseInt($("#mediaPageCount").html())){var A=galleryData.currentMediaPage+1;loadMediaPage(A);pushHistoryState(A)}else{$("#mediaNextPage").addClass("h-disabled")}}}}return false};var pushHistoryState=function(A){var C=vBulletin.makePaginatedUrl(location.href,A);if(filterHistory.isEnabled()){var B={from:"media_filter",page:A||1,tab:$mediaTab.data("url-path")?$mediaTab.data("url-path"):"#"+$mediaTab.attr("id")};filterHistory.pushState(B,document.title,C)}else{if(allowHistory){location.href=C}}};vBulletin.media.setHistoryStateChange=function(A){if(A){allowHistory=$mediaTab.find(".conversation-toolbar-wrapper").data("allow-history")=="1";filterHistory=new vBulletin.history.instance(allowHistory)}if(filterHistory.isEnabled()){filterHistory.setStateChange(function(F){var E=filterHistory.getState();if(E.data.from=="media_filter"){filterHistory.log(E.data,E.title,E.url);var B=$mediaTab.closest(".ui-tabs"),C=B.find(".ui-tabs-nav > li").filter('li:has(a[href*="#{0}"])'.format($mediaTab.attr("id")));if(C.hasClass("ui-tabs-selected")){loadMediaPage(E.data.page)}else{var D=C.index();vBulletin.selectTabByIndex.call(B,D)}}},"media_filter")}};gotoGalleryPage=function(C){if(!galleryData.currentNodeId&&!galleryData.currentUserId){return false}var A=C.target.id;if(A=="mediaCurrentPage"){targetPage=parseInt($("#mediaCurrentPage").val());if(targetPage>0&&targetPage<=parseInt($("#mediaPageCount").html())){var B={nodeid:galleryData.currentNodeId,userid:galleryData.currentUserId,pageno:targetPage,perpage:vBulletin.media.calculatePhotosPerPage(vBulletin.media.TARGET_PHOTOS_PERPAGE),datefilter:galleryData.currentDateFilter};loadGalleryById(B)}else{$("#mediaCurrentPage").val(galleryData.currentGalleryPage)}}else{if(A=="mediaPreviousPage"){if(galleryData.currentGalleryPage>1){var B={nodeid:galleryData.currentNodeId,userid:galleryData.currentUserId,pageno:galleryData.currentGalleryPage-1,perpage:vBulletin.media.calculatePhotosPerPage(vBulletin.media.TARGET_PHOTOS_PERPAGE),datefilter:galleryData.currentDateFilter};loadGalleryById(B)}else{$("#mediaPreviousPage ").addClass("h-disabled")}}else{if(A=="mediaNextPage"){if(galleryData.currentGalleryPage<parseInt($("#mediaPageCount").html())){var B={nodeid:galleryData.currentNodeId,userid:galleryData.currentUserId,pageno:galleryData.currentGalleryPage+1,perpage:vBulletin.media.calculatePhotosPerPage(vBulletin.media.TARGET_PHOTOS_PERPAGE),datefilter:galleryData.currentDateFilter};loadGalleryById(B)}else{$("#mediaNextPage").addClass("h-disabled")}}}}return false};setPagination=function(A){var B=$(".media-tab .pagenav-controls-container")[(A.totalpages>1)?"removeClass":"addClass"]("h-hide");if(B.is(":visible")){$("#mediaPreviousPage")[(A.currentpage<=1)?"addClass":"removeClass"]("h-disabled");$("#mediaNextPage")[(A.currentpage>=A.totalpages)?"addClass":"removeClass"]("h-disabled");$("#mediaCurrentPage").val(A.currentpage);$("#mediaPageCount").text(A.totalpages)}};loadGalleryById=function(B,E,F,H){var C=$("#profileMediaDetailContainer"),D={nodeid:galleryData.currentNodeId,userid:galleryData.currentUserId,channelid:0,pageno:1,dateFilter:galleryData.currentDateFilter,albumid:0};B=$.extend({},D,B);if(!isNaN(B.nodeid)){$("body").css("cursor","wait");var I;if(B.nodeid==-1){I="profile_media_videolist"}else{I="profile_textphotodetail"}if(C.closest(".media-tab").length==0){$(".media-tab").append(galleryData.profileMediaDetailContainer)}$("#profileMediaContainer").closest(".tab").find("li.list-item-gallery").remove();var A=vBulletin.getAjaxBaseurl()+"/ajax/render/"+I;var G={nodeid:B.nodeid,userid:B.userid,channelid:B.channelid,pageno:B.pageno,albumid:B.albumid,viewMore:F};if(B.dateFilter){G.dateFilter=B.dateFilter}if(B.perpage){G.perpage=B.perpage}$.ajax({url:A,type:"GET",data:G,dataType:"json",success:function(J){if(J.errors){openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get(J.errors[0][0]),iconType:"error"});if(H&&typeof H.error=="function"){H.error()}}else{galleryData.currentNodeId=B.nodeid;galleryData.currentUserId=B.userid;galleryData.currentGalleryPage=B.pageno;if(F){$(".album-detail .photo-preview").append(J);$(".more-gallery",C)[(galleryData.currentGalleryPage<$(".album-detail").data("totalpages"))?"removeClass":"addClass"]("h-hide-imp")}else{C.html(J);C.addClass("list-item").attr("data-nodeid",B.nodeid);$("#profileMediaContainer").addClass("h-hide");$(".media-tab .profile-toolbar .toolset-left > li").removeClass("h-hide").has(".profile-media-upload, .profile-media-createvideo").addClass("h-hide");C.removeClass("h-hide");$("#mediaPreviousPage").closest(".pagenav-controls-container").addClass("h-hide");$(".profile-media-uploadphotos",C).click(loadPhotoUploader);if(B.perpage){$(".media-tab").data("perpage",B.perpage).data("callbacks",H)}}}if(B.nodeid==-1){var K={totalpages:Number($(".media-video-list",C).data("totalpages")),totalcount:Number($(".media-video-list",C).data("totalcount")),currentpage:galleryData.currentGalleryPage};setPagination(K)}if(!E){}$(".profile-toolbar .media-toolbar-filter").removeClass("h-hide");if(H&&typeof H.success=="function"){H.success()}},error:function(L,K,J){console.log("/ajax/render/{0} failed, error: {1}".format(I,J));openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error"});if(H&&typeof H.error=="function"){H.error()}},complete:function(){$("body").css("cursor","auto");if(H&&typeof H.complete=="function"){H.complete()}}})}};loadPhotoUploader=function(C){$(document).data("gallery-container",$(this).closest(".album-detail"));var A=vBulletin.upload.getUploadedPhotosDlg(false);var B=A.data("nodeid");if(!B||isNaN(parseInt(B))){return }$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/media_addphotos?nodeid="+B,dataType:"json",success:function(D){if(D.errors){openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get(D.errors[0][0]),iconType:"warning",onAfterClose:function(){A.dialog("close")}})}else{A.html(D);vBulletin.upload.initializePhotoUpload(A.parent());A.find(".js-save-button").off("click").on("click",saveGalleryPhotos);A.find(".js-cancel-button").off("click").on("click",function(E){A.dialog("close")});vBulletin.upload.relocateLastInRowClass(A.find(".photo-item-wrapper"));if($(".photo-display .photo-item-wrapper:not(.h-hide)",A).length>0){vBulletin.upload.changeButtonText($(".b-button--upload .js-upload-label",A),vBulletin.phrase.get("upload_more"));$(".js-save-button",A).show()}else{$(".js-save-button",A).hide()}A.dialog("open");vBulletin.upload.adjustPhotoDialogForScrollbar(A)}},error:function(F,E,D){console.log("/ajax/render/media_addphotos failed, error: "+D);openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error",onAfterClose:function(){A.dialog("close")}})}})};loadGallery=function(B){var A=fetchMediaFilter($(this));A.pageno=1;A.perpage=vBulletin.media.calculatePhotosPerPage(vBulletin.media.TARGET_PHOTOS_PERPAGE);galleryData.currentDateFilter=$(this).closest(".js-profile-media-container").find(".toolbar-filter-overlay input[name=filter_time]:checked").val();galleryData.currentFilter=A;loadGalleryById(A)};getMorePhotos=function(D){var C=$(".media-tab").data("perpage");var B=$(".media-tab").data("callbacks");var A=fetchMediaFilter($(this));A.pageno=galleryData.currentGalleryPage+1;if(C){A.perpage=C}loadGalleryById(A,false,1,B)};saveGalleryPhotos=function(D){var C=$(D.target).closest(".profile-media-photoupload-dialog"),A=$(D.target).closest("form");if(A.length>0){$(D.target).closest(".photo-display-container").find(".photo-item-wrapper:not(.h-hide)").each(function(E,G){var F=parseInt($(G).find(".filedataid").val());if(!isNaN(F)){A.append('<input type="hidden" name="filedataid[]" value="'+F+'"/>');$('<input type="hidden" name="title_'+F+'" />').val($(G).find("textarea").val()).appendTo(A)}});var B=$("button",A).prop("disabled",true);$.ajax({url:A.attr("action"),data:A.serializeArray(),type:"post",success:function(E){if(E.errors){if(typeof (E.errors[0])=="undefined"){openAlertDialog({title:vBulletin.phrase.get("media"),message:vBulletin.phrase.get(E.errors),iconType:"error"})}else{openAlertDialog({title:vBulletin.phrase.get("media"),message:vBulletin.phrase.get(E.errors[0]),iconType:"error"})}}else{C.dialog("close");loadMediaPage(galleryData.currentMediaPage)}},error:function(G,F,E){console.log(A.attr("action")+" failed, error: "+E);openAlertDialog({title:vBulletin.phrase.get("profile_media"),message:vBulletin.phrase.get("invalid_server_response_please_try_again"),iconType:"error"})},complete:function(){B.prop("disabled",false)}})}else{C.dialog("close")}};fetchMediaFilter=function(A){var C;if($("#profileMediaContainer").is(":visible")){C=$("#profileMediaContainer")}else{if($("#profileMediaDetailContainer").is(":visible")){C=$("#profileMediaDetailContainer")}}var B={nodeid:parseInt(A.data("nodeid"),10)||parseInt(C.attr("data-nodeid"),10),userid:parseInt(C.data("userid"),10),channelid:parseInt(C.data("channelid"),10),dateFilter:galleryData.currentDateFilter,albumid:parseInt(A.data("albumid"),10)};if(isNaN(B.channelid)){B.channelid=0}if(isNaN(B.nodeid)){B.nodeid=0}if(B.nodeid>0){B.userid=0}else{if(isNaN(B.userid)){B.userid=0}}if(isNaN(B.albumid)){B.albumid=0}return B};