window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["no_preview_text_available","loading"]);window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,["threadpreview"]);(function(){$(".js-tooltip[title]").qtip({style:{classes:"ui-tooltip-shadow"}});if(vBulletin.options.get("threadpreview")>0&&$(".channel-content-widget").eq(0).attr("data-canviewtopiccontent")){var A=function(){var C=$(this);if(C.data("vb-qtip-preview-initialized")=="1"){return }C.data("vb-qtip-preview-initialized","1");var E=C.closest(".topic-item"),G=E.attr("data-node-id"),F=$(".topic-info",E),B=$(".cell-count",E),D=$(".cell-lastpost",E);C.qtip({content:{text:function(I,H){$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/fetch-node-preview?nodeid="+G,dataType:"json"}).done(function(J){if($.trim(J)!=""){var K='<div class="b-topicpreview__previewtext">"'+J+'"</div>';H.set("content.text",K)}else{var K='<div class="b-topicpreview__previewtext">'+vBulletin.phrase.get("no_preview_text_available")+"</div>";H.set("content.text",K)}}).fail(function(L,J,K){H.set("content.text",J+": "+K)});return vBulletin.phrase.get("loading")}},show:{delay:500},position:{my:"top left",at:"bottom right",viewport:$(window)},style:{classes:"ui-tooltip-shadow ui-tooltip-rounded b-topicpreview"}});C.trigger("mouseover")};$(document).off("mouseover",".topic-list-container .topic-title").on("mouseover",".topic-list-container .topic-title",A)}})();