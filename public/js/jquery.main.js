/*
(function($) {
	$(function(){
        initClickablePostCheckbox();
        initClickablePost();
		initLoginPopup();
		jcf.lib.domReady(function(){
			jcf.customForms.replaceAll();
			var select = $('select')[0];
			var options = $(select).find('option');
			var btn = $('button');
			function disabledSomeOption(index){
				options.each(function(ind){
					if(ind === index){
						this.disabled = true;
					}else{
						this.disabled = false;
					}
				});
			}
			btn.on('click', function(){
				disabledSomeOption(2);
                if($(select).length > 0){
                    select.jcf.buildDropdown();
                }
			});

		});
		ui_slider();

 if($('div.galleryka').length > 0){
        $('div.galleryka').scrollGallery({
            mask: 'div.mask',
            slider: '>*',
            slides: '>*',
            activeClass:'active',
            disabledClass:'disabled',
            btnPrev: 'a.btn-prev',
            btnNext: 'a.btn-next',
            generatePagination: false,
            pagerLinks: '.pagination li',
            currentNumber: 'span.current-num',
            totalNumber: 'span.total-num',
            circularRotation: true,
            disableWhileAnimating: false,
            stretchSlideToMask: false,
            autoRotation: false,
            pauseOnHover: true,
            maskAutoSize: false,
            switchTime: 4000,
            animSpeed: 600,
            swipeGap: false,
            swipeThreshold: 50,
            handleTouch: true,
            vertical: false,
            step: false
        });
    }
		if($('div.toggle-block').length > 0){
			jQuery('div.toggle-block').openClose({
				addClassBeforeAnimation: true,
				activeClass:'active',
				opener:'.opener',
				slider:'.slide',
				animSpeed: 400,
				effect:'fade',
				event:'click'
			});
		}


		function fullscreenIt(element) {
			if(element.requestFullScreen) {
				element.requestFullScreen();
			} else if(element.mozRequestFullScreen) {
				element.mozRequestFullScreen();
			} else if(element.webkitRequestFullScreen) {
				element.webkitRequestFullScreen();
			}
		}

		var elem2 = $('.fullscreen');
		if(elem2.length){
			elem2.on('click', function(e){
				e.preventDefault();
				var activ = $('.flscrn').find('li.active img');
				fullscreenIt(activ[0]);
			});
		}
	});

    function initClickablePost(){
        var post = $('.clickable');
        if(post.length>0){
            //post.attr('data-href', 'sms-chat.html');
            post.on('click', function(){
                window.location = this.getAttribute('data-href');
            })
        }
    }
    function initClickablePostCheckbox(){
        var opener1 = $('.the-opener'),
            activeClass = 'ww';
        if(opener1.length>0){
            opener1.on('click', function(){
                var chk = $(this).find('.chk-area');
                    if(chk.hasClass(activeClass)){

                        chk.removeClass('ww');
                    } else {

                        chk.addClass('ww')}

            })
        }
    }

	function ui_slider(){
		;(function(){
            var maxPrice = ($('form.srch input.maxPrice').length > 0) ? $('form.srch input.maxPrice').val() : 10000;
            var pmin = ($('.wrap input#input-number1').length > 0) ? $('.wrap input#input-number1').val() : 0;
            var pmax = ($('.wrap input#input-number2').length > 0) && $('.wrap input#input-number2').val() > 0 ? $('.wrap input#input-number2').val() : maxPrice;
			;$('.sample-rate').noUiSlider({
				range: [0,maxPrice]
				,start: [pmin,pmax]
				,handles: 2
				,connect: true
				,step: 1
				,serialization: {
					to: [ [$('#input-number1'), handler1], [$('#input-number2'), handler2] ]
					,resolution: 1
				},
			});
			$('.sample-rate .noUi-handle').each(function(ind){
				var item = this.appendChild(document.createElement('span'));
				item.id = 'helper'+ ind;
			});
			function handler1(val){
				if(this.data('data-helper1')){
					this.data('data-helper1').html(val + ' тг');
				}else{
					var item = $('<span id="helper1"></span>').appendTo(this.find('.noUi-handle-lower'));
					item.html(val);
					this.data('data-helper1', item);
				}
			}
			function handler2(val){
				if(this.data('data-helper2')){
					this.data('data-helper2').html(val + ' тг');
				}else{
					var item = $('<span id="helper2"></span>').appendTo(this.find('.noUi-handle-upper'));
					item.html(val + ' тг');
					this.data('data-helper2', item);
				}
			}
		})();


		;(function(){
			;$('.sample-rate2').noUiSlider({
				range: [0,10]
				,start: [0,10]
				,handles: 2
				,connect: true				,connect: true
				,step: 1
				,serialization: {
					to: [ [$('#input-number3'), handler1], [$('#input-number4'), handler2] ]
					,resolution: 1
				},
			});
			$('.sample-rate .noUi-handle').each(function(ind){
				var item = this.appendChild(document.createElement('span'));
				item.id = 'helper'+ ind;
			});
			function handler1(val){
				if(this.data('data-helper1')){
					this.data('data-helper1').html(val);
				}else{
					var item = $('<span id="helper1"></span>').appendTo(this.find('.noUi-handle-lower'));
					item.html(val);
					this.data('data-helper1', item);
				}
			}
			function handler2(val){
				if(this.data('data-helper2')){
					this.data('data-helper2').html(val);
				}else{
					var item = $('<span id="helper2"></span>').appendTo(this.find('.noUi-handle-upper'));
					item.html(val);
					this.data('data-helper2', item);
				}
			}
		})();
	}
	// login popups init
	function initLoginPopup() {

		function clearClasses() {
			$('.login-holder').removeAttr('class').addClass('login-holder');
		}

		$('html').on('click', function () {
			if ( !$('.login-holder').hasClass('logged') ) {
				clearClasses();
			}
		});

		$('.login-holder').on('click', function (e) {
			e.stopPropagation();
		});

		$('.btn-login, .link-add.open-login-form').on('click', function() {
            var el = $('.login-holder .btn-login');
            
			if ( !$(el).closest('.login-holder').hasClass('logged') ) {
				if ( $(el).closest('.login-holder').hasClass('forgot-show') || $(this).closest('.login-holder').hasClass('register-show') ) {
					$(el).closest('.login-holder').removeClass('forgot-show register-show');
				}
				else {
					$(el).closest('.login-holder').removeClass('forgot-show register-show').toggleClass('login-show');
				}
				return false;
			}
		});

		$('.btn-forgot-pass').on('click', function() {
			clearClasses();
			$(this).closest('.login-holder').toggleClass('forgot-show');
			return false;
		});

		$('.btn-register').on('click', function() {
			clearClasses();
			$(this).closest('.login-holder').toggleClass('register-show');
			return false;
		});
	}
*/
	/*
	 * jQuery Tabs plugin
	 */
	;(function($){
		$.fn.contentTabs = function(o){
			// default options
			var options = $.extend({
				activeClass:'active',
				addToParent:false,
				autoHeight:false,
				autoRotate:false,
				checkHash:false,
				animSpeed:400,
				switchTime:3000,
				effect: 'none', // "fade", "slide"
				tabLinks:'a',
				attrib:'href',
				event:'click'
			},o);

			return this.each(function(){
				var tabset = $(this), tabs = $();
				var tabLinks = tabset.find(options.tabLinks);
				var tabLinksParents = tabLinks.parent();
				var prevActiveLink = tabLinks.eq(0), currentTab, animating;
				var tabHolder;

				// handle location hash
				if(options.checkHash && tabLinks.filter('[' + options.attrib + '="' + location.hash + '"]').length) {
					(options.addToParent ? tabLinksParents : tabLinks).removeClass(options.activeClass);
					setTimeout(function() {
						window.scrollTo(0,0);
					},1);
				}

				// init tabLinks
				tabLinks.each(function(){
					var link = $(this);
					var href = link.attr(options.attrib);
					var parent = link.parent();
					href = href.substr(href.lastIndexOf('#'));

					// get elements
					var tab = $(href);
					tabs = tabs.add(tab);
					link.data('cparent', parent);
					link.data('ctab', tab);

					// find tab holder
					if(!tabHolder && tab.length) {
						tabHolder = tab.parent();
					}

					// show only active tab
					var classOwner = options.addToParent ? parent : link;
					if(classOwner.hasClass(options.activeClass) || (options.checkHash && location.hash === href)) {
						classOwner.addClass(options.activeClass);
						prevActiveLink = link; currentTab = tab;
						tab.removeClass(tabHiddenClass).width('');
						contentTabsEffect[options.effect].show({tab:tab, fast:true});
					} else {
						var tabWidth = tab.width();
						if(tabWidth) {
							tab.width(tabWidth);
						}
						tab.addClass(tabHiddenClass);
					}

					// event handler
					link.bind(options.event, function(e){
						if(link != prevActiveLink && !animating) {
							switchTab(prevActiveLink, link);
							prevActiveLink = link;
						}
					});
					if(options.attrib === 'href') {
						link.bind('click', function(e){
							e.preventDefault();
						});
					}
				});

				// tab switch function
				function switchTab(oldLink, newLink) {
					animating = true;
					var oldTab = oldLink.data('ctab');
					var newTab = newLink.data('ctab');
					prevActiveLink = newLink;
					currentTab = newTab;

					// refresh pagination links
					(options.addToParent ? tabLinksParents : tabLinks).removeClass(options.activeClass);
					(options.addToParent ? newLink.data('cparent') : newLink).addClass(options.activeClass);

					// hide old tab
					resizeHolder(oldTab, true);
					contentTabsEffect[options.effect].hide({
						speed: options.animSpeed,
						tab:oldTab,
						complete: function() {
							// show current tab
							resizeHolder(newTab.removeClass(tabHiddenClass).width(''));
							contentTabsEffect[options.effect].show({
								speed: options.animSpeed,
								tab:newTab,
								complete: function() {
									if(!oldTab.is(newTab)) {
										oldTab.width(oldTab.width()).addClass(tabHiddenClass);
									}
									animating = false;
									resizeHolder(newTab, false);
									autoRotate();
								}
							});
						}
					});
				}

				// holder auto height
				function resizeHolder(block, state) {
					var curBlock = block && block.length ? block : currentTab;
					if(options.autoHeight && curBlock) {
						tabHolder.stop();
						if(state === false) {
							tabHolder.css({height:''});
						} else {
							var origStyles = curBlock.attr('style');
							curBlock.show().css({width:curBlock.width()});
							var tabHeight = curBlock.outerHeight(true);
							if(!origStyles) curBlock.removeAttr('style'); else curBlock.attr('style', origStyles);
							if(state === true) {
								tabHolder.css({height: tabHeight});
							} else {
								tabHolder.animate({height: tabHeight}, {duration: options.animSpeed});
							}
						}
					}
				}
				if(options.autoHeight) {
					$(window).bind('resize orientationchange', function(){
						tabs.not(currentTab).removeClass(tabHiddenClass).show().each(function(){
							var tab = jQuery(this), tabWidth = tab.css({width:''}).width();
							if(tabWidth) {
								tab.width(tabWidth);
							}
						}).hide().addClass(tabHiddenClass);

						resizeHolder(currentTab, false);
					});
				}

				// autorotation handling
				var rotationTimer;
				function nextTab() {
					var activeItem = (options.addToParent ? tabLinksParents : tabLinks).filter('.' + options.activeClass);
					var activeIndex = (options.addToParent ? tabLinksParents : tabLinks).index(activeItem);
					var newLink = tabLinks.eq(activeIndex < tabLinks.length - 1 ? activeIndex + 1 : 0);
					prevActiveLink = tabLinks.eq(activeIndex);
					switchTab(prevActiveLink, newLink);
				}
				function autoRotate() {
					if(options.autoRotate && tabLinks.length > 1) {
						clearTimeout(rotationTimer);
						rotationTimer = setTimeout(function() {
							if(!animating) {
								nextTab();
							} else {
								autoRotate();
							}
						}, options.switchTime);
					}
				}
				autoRotate();
			});
		};

		// add stylesheet for tabs on DOMReady
		var tabHiddenClass = 'js-tab-hidden';
		$(function() {
			var tabStyleSheet = $('<style type="text/css">')[0];
			var tabStyleRule = '.'+tabHiddenClass;
			tabStyleRule += '{position:absolute !important;left:-9999px !important;top:-9999px !important;display:block !important}';
			if (tabStyleSheet.styleSheet) {
				tabStyleSheet.styleSheet.cssText = tabStyleRule;
			} else {
				tabStyleSheet.appendChild(document.createTextNode(tabStyleRule));
			}
			$('head').append(tabStyleSheet);
		});

		// tab switch effects
		var contentTabsEffect = {
			none: {
				show: function(o) {
					o.tab.css({display:'block'});
					if(o.complete) o.complete();
				},
				hide: function(o) {
					o.tab.css({display:'none'});
					if(o.complete) o.complete();
				}
			},
			fade: {
				show: function(o) {
					if(o.fast) o.speed = 1;
					o.tab.fadeIn(o.speed);
					if(o.complete) setTimeout(o.complete, o.speed);
				},
				hide: function(o) {
					if(o.fast) o.speed = 1;
					o.tab.fadeOut(o.speed);
					if(o.complete) setTimeout(o.complete, o.speed);
				}
			},
			slide: {
				show: function(o) {
					var tabHeight = o.tab.show().css({width:o.tab.width()}).outerHeight(true);
					var tmpWrap = $('<div class="effect-div">').insertBefore(o.tab).append(o.tab);
					tmpWrap.css({width:'100%', overflow:'hidden', position:'relative'}); o.tab.css({marginTop:-tabHeight,display:'block'});
					if(o.fast) o.speed = 1;
					o.tab.animate({marginTop: 0}, {duration: o.speed, complete: function(){
						o.tab.css({marginTop: '', width: ''}).insertBefore(tmpWrap);
						tmpWrap.remove();
						if(o.complete) o.complete();
					}});
				},
				hide: function(o) {
					var tabHeight = o.tab.show().css({width:o.tab.width()}).outerHeight(true);
					var tmpWrap = $('<div class="effect-div">').insertBefore(o.tab).append(o.tab);
					tmpWrap.css({width:'100%', overflow:'hidden', position:'relative'});

					if(o.fast) o.speed = 1;
					o.tab.animate({marginTop: -tabHeight}, {duration: o.speed, complete: function(){
						o.tab.css({display:'none', marginTop:'', width:''}).insertBefore(tmpWrap);
						tmpWrap.remove();
						if(o.complete) o.complete();
					}});
				}
			}
		};
	}(jQuery));
/*
})(jQuery);
*/