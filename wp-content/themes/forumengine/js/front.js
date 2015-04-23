(function($){

	$(document).ready(function(){

	    jQuery.validator.addMethod("username", function(username) { 
	    	return username.match ('^([a-zA-Z0-9.]+@){0,1}([a-zA-Z0-9.])+$');
	    });		

		ForumEngine.app = new ForumEngine.Views.App();

		// init new 
		App.Auth = new ForumEngine.Models.User(currentUser);
	});
	
	$(window).resize(function(event) {
		$('.cnt-container').css('padding-bottom', $('footer').height() + 35 + 'px');
	});

	//========== Infinite Scoll ==========//
	$(window).scroll(function()
	{	
	    if( ($(window).scrollTop() == $(document).height() - $(window).height()) && $("#loading").attr('data-fetch') == 1 )
	    {
		    ForumEngine.app.getThreads();
		}

	});
	//========== Infinite Scoll ==========//

	/**
	 * Site app view
	 */
	ForumEngine.Views.App 	= Backbone.View.extend({		
		el: 'body',
		defaults: {
			currentUser: {},
			loginModal: false
		},
		events: {
			'click #open_login' 						: 'initModal',
			'change input[type=checkbox].fe-checkbox' 	: 'changeCheckbox',
			'keyup #search_field' 						: 'onSearch',
			//'blur #search_field' 						: 'hideSearchPreview',
			'focus #search_field' 						: 'showSearchPreview'
		},
		initialize: function(){
			this.currentUser 	= new ForumEngine.Models.User(currentUser);
			pubsub.on('fe:refreshPage', this.onRefreshPage);
			pubsub.on('fe:showNotice', this.onShowNotice);

			// toggle right menu in tablet 
			$('.mo-menu-toggle').bind('click', function(){
				var container = $('.site-container');
				var menu 		= $('.mobile-menu');
				if ( !container.hasClass('slided') ){
					menu.show();
					container.animate({ left: '-300' });
					container.addClass('slided');
				} else {
					container.animate({ left: '0' }, 'normal' , function(){ menu.hide(); });
					container.removeClass('slided');					
				}
			});

			$('.modal input[type=text], .modal input[type=password], .modal input.form-control')
				.focusin(function(){
					var line = $(this).before('<span class="line-correct"></span>');
				})
				.focusout(function(){
					var line = $(this).prev('span.line-correct').remove();
				});
			this.initAppearance();

			this.searchDebounce = _.debounce(this.searchAjax, 1000);
			//this.$('#search_field').keyup()
			//set padding for container
			$('.cnt-container').css('padding-bottom', $('footer').height() + 35 + 'px');

			//========== First Infinite Scoll ==========//
			// var loop = parseInt($("#loading").attr('data-check'));
			// if(loop > 1){
		 //    	for (var i = 1; i <= loop; i++) {
		 //    		console.log(loop);
		 //    		if(!$("#loading").hasClass('processing'))
		 //    			this.getThreads(i);
		 //    	};		
			// }
			var loop = parseInt($("#loading").attr('data-check'));
			this.timeOutId = 0;
			if(loop > 1 && $("#main_list_post li").length < 10){
				this.timeOutId = setTimeout(this.getThreads(), 1000);
			} else {
				clearTimeout(this.timeOutId);
			}
			//========== First Infinite Scoll ==========//

		},

		getThreads:function(page){
			var loading 	= $('body').find('#loading'),
				paged 		= page != null ? page : $('body').find('#current_page').val(),
				status  	= loading.attr('data-status'),
				query_default = {
					action		: 'et_post_sync',
					method		: 'get',
					content : {
						paged			: paged,
						status 			: status,
						}
				},
				that = this;

				if(loading.attr('data-term')) {
					query_default.content.thread_category = loading.attr('data-term');
				} 

				if(loading.attr('data-s')) {
					query_default.content.s = loading.attr('data-s');
				} 

				if(loading.attr('data-author')) {
					query_default.content.author = loading.attr('data-author');
				} 	

			$.ajax({
				url : fe_globals.ajaxURL,
				type : 'post',
				data : query_default,
				beforeSend : function(){
					loading.removeClass('hide');
					//$('body').find('#loading').attr('data-fetch', 0);
				},
				error : function(request){
					loading.addClass('hide').removeClass('processing');
				},
				success : function(response){
					
					var current_page = response.data.paged,
						max_page_query = response.data.total_pages;
					if(response.success){
						$('body').find('input#current_page').val(current_page);
						$('body').find('#loading').attr('data-fetch', current_page < max_page_query ? 1 : 0);
						loading.addClass('hide');
						that.renderLoadMore(response.data.threads);	

						if(current_page == max_page_query || $("#main_list_post li").length >= 10)
							clearTimeout(that.timeOutId);
						else
							that.timeOutId = setTimeout(that.getThreads(), 1000);		
												
					} else {

					}
				}
			});
		},

		renderLoadMore:function(threads){
			var container = $('body').find('#main_list_post'),
				that = this;

			for (key in threads){
				var itemView = new ForumEngine.Views.ListThreadItem({el : threads[key] });
				container.append( itemView.$el );
				pubsub.trigger( 'fe:getThreads', itemView.$el );
			}
		},

		initAppearance: function(){
			// calc min height for website
			if ( $('body').hasClass('admin-bar') ){
				$('.cnt-container').css('min-height', $(window).height() - 28);
			} else {
				$('.cnt-container').css('min-height', $(window).height());
			}
		},

		initModal : function(event){
			if ( typeof this.loginModal != 'object' ){
				this.loginModal 	= new ForumEngine.Views.LoginModal({el : $('#modal_login')});
			}
		},

		getLoginModal: function(){
			this.initModal();
			return this.loginModal;
		},

		login: function(username, password, options){
			this.currentUser.login(username, password, options);
		},

		register: function(username, email, pass, options){
			this.currentUser.register(username, email, pass, options);
		},	

		inbox: function(user_id, message, options){
			this.currentUser.inbox(user_id, message, options);
		},			

		setRefreshPage: function(value){
			this.loginModal.enableRefresh = value ? true : false;
		},

		// events
		onRefreshPage: function(value){
			window.location.reload();
		},

		onShowNotice: function(msg, type){
			var pageOffset 	= $('body').scrollTop();
			var noticeBlock = $('<div class="fe-noti">')
					.addClass('fe-' + type)
					.html('<span class="icon"></span>&nbsp;&nbsp;<span class="text">' + msg + '</span>');

			if ( pageOffset > 70 ){				
				noticeBlock
					.css('top', $('body').hasClass('admin-bar') ? '28px' : 0);
			} 

			$('.fe-noti')
				.fadeOut('fast', function(){
					$(this).remove();
				})

			$('body')
				.append(noticeBlock
					.hide()
					.fadeIn()
					.delay(2000)
					.fadeOut('fast', function(){
						$(this).remove();
					})
				);
		},

		changeCheckbox: function(event){
			var element = $(event.currentTarget);
			if ( element.is(':checked') ){
				$(element.next('label')).addClass('checked');
				element.parent().addClass('checked');
			} else {
				$(element.next('label')).removeClass('checked');
			}
		},

		onSearch:function(event){
			var element = event.currentTarget;
			var keyCode	= event.which;

			this.searchDebounce();
		},

		hideSearchPreview: function(event){
			var outputContainer = $('#search_preview');

			if ( !$.contains(outputContainer.get(0), event.currentTarget) ){
				outputContainer.hide();
			}
		},

		onShowSearchPreview: function(e){
			var outputContainer = $('#search_preview');
			var input 			= $('#search_field').get(0);
			$('body').bind('click', function(e){
				if ( !$.contains( outputContainer.get(0), e.target) && e.target != input ){
					outputContainer.hide();
					$('body').unbind('click');
				}
			});
		},

		showSearchPreview: function(event){
			var outputContainer = $('#search_preview');
			var view = this;
			if ( !outputContainer.hasClass('empty') ){

				outputContainer.show();

				view.onShowSearchPreview();
			}
		},

		searchAjax: function(){
			var input 		= $('#search_field');
			var searchValue = input.val();
			var source 		= $('#search_preview_template').html();
			var template 	= _.template(source);
			var outputContainer = $('#search_preview');
			var view 		= this;

			if ( searchValue == '' ){
				$('#search_preview').addClass('empty');
				return false;
			}

			var params 	= {
				url 	: fe_globals.ajaxURL,
				type 	: 'post',
				data 	: {
					'action'   	: 'et_search',
					'content' 	: {
						's' : searchValue
					}
				},
				beforeSend: function(){

				},
				success: function(resp){
					if ( resp.success ){
						var data = resp.data;

						//data.title_highlight = data.post_title.replace( searchValue, '<strong>' + searchValue + "</strong>" );
						//console.log(data);

						var output = template(resp.data);
						outputContainer.html(output).removeClass('empty').fadeIn();

						view.onShowSearchPreview();
					}
				},
				complete: function(){

				}
			};
			$.ajax(params);
		}

	});

	ForumEngine.Views.PostListItem  = Backbone.View.extend({
		tagName: "div",
		className : "items-thread clearfix reply-item",
		events: {
			'click .like-post' 			: 'onLike',
			'submit .ajax-reply' 		: 'ajaxReply',
			'click .show-replies' 		: 'showReplies',
			'click .btn-more-reply' 	: 'moreReplies',
			'click .open-reply' 		: 'onOpenReplyForm',
			'click .control-edit' 		: 'onOpenEdit',
			'click .control-edit-cancel' : 'onCancelEdit',
			'submit .form-post-edit' 	: 'onSubmitEdit',
			'click .control-quote'		: 'onQuote',
			'click .control-report' 	: 'onReport',
			'click span.btn-cancel' 	: 'onCancelReplyForm'
		},

		initialize: function(){
			//console.log(this.options.model);
			this.model 	= new ForumEngine.Models.Post(this.model);
			this.page 	= 1;

			// events
			this.subReplies 	= [];

			// add event login
			pubsub.on('fe:auth:afterLogin', this.afterAuthorize);
			pubsub.on('fe:auth:afterRegister', this.afterAuthorize);
		},

		isAuth : function(){
			return ForumEngine.app.currentUser.get('id');
		},

		updateReplyCount: function(count){
			this.$('.name .comment .count').html(count);
		},

		// handle changes in event "after login"
		// change avatar after login
		afterAuthorize: function(model){
			var name = model.get("display_name"),
				avatar = model.get("et_avatar");
			$("div.profile-account span.name a").text(name);
			$("div.profile-account span.img").html(avatar);
			$("div.login").hide();
			$("div.profile-account").fadeIn("slow");
		},

		afterRegister: function(model){
			console.log(model);
			var name = model.get("display_name"),
				avatar = model.get("et_avatar");
			$("div.profile-account span.name a").text(name);
			$("div.profile-account span.img").html(avatar);
			$("div.login").hide();
			$("div.profile-account").fadeIn("slow");
		},	

		like: function(){
			// check if is logged in
			// not logged in
			// logged in
			var element = this.$('.like-post').first();
			var view 	= this;
			var likeList = view.$el.find('.user-discuss'),
				likeDiv  = view.$el.find('.linke-by');

			this.model.like({
				beforeSend: function(){
					element.toggleClass('active');
				}, 
				success: function(resp, model){
					if (resp.success){					
						if (resp.data.isLike){
							// update like count
							element.addClass('active');
							element.find('.count').text(resp.data.count);
							// add avatar
							var avatar 	= ForumEngine.app.currentUser.get('et_avatar');
							// var dom 	= $('<li class="me"><img src="' + avatar.thumbnail + '" class="avatar" alt="' + ForumEngine.app.currentUser.get('display_name') + '"/></li>');
							var dom 	= $('<li class="me">' + avatar + '</li>');
							likeList.find('li:first-child').after(dom.hide().fadeIn());
						}
						else {
							element.removeClass('active');
							element.find('.count').text(resp.data.count);
							likeList.find('li.me').fadeOut();
						}

						// if no one like, hide the list
						if ( resp.data.count > 0 ){
							likeList.show();
							likeDiv.show();
							likeDiv.css('visibility', 'visible');
						} else {
							likeList.hide();
							//likeDiv.hide();
						}
						// remove 
						view.stopListening(pubsub, 'fe:auth:afterLogin', view.like);
						view.stopListening(pubsub, 'fe:auth:afterRegister', view.like);
					} else {
						//alert(resp.msg);
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
						element.toggleClass('active');
					}					
				}
			});
		},

		onLike: function(event){
			event.preventDefault();
			//event.stopPropagation();
			var element = $(event.currentTarget);
			console.log(element.attr('data-id'));
			console.log(this.model.get('id'));
			if ( element.attr('data-id') != this.model.get('id') ) return;

			// check if user logged in or not
			var view = this;

			// if user isn't logged in, open login modal
			if ( !this.isAuth() ){
				this.openLogin();

				// assign listening
				view.listenTo(pubsub, 'fe:auth:afterLogin', view.like);
				view.listenTo(pubsub, 'fe:auth:afterRegister', view.like);
				console.log('start listening event after login');
			}  // perform like
			else {
				this.like();
			}
		},

		onQuote: function(event){
			event.preventDefault();

			var quoteContent 	= this.model.get('post_content'), //this.$('.post-display .content').html(),
				element         = $(event.currentTarget),
				id 				= element.attr('data-id'),
				author          = this.$('.post-display > .name .post-author').text();
			var currentEditor 	= tinymce.activeEditor;
				currentEditor.setContent('');

			var trimQuote = quoteContent ? quoteContent : threadData.post_content;

			if(trimQuote.indexOf("[/quote]") >= 0) {
				trimQuote = trimQuote.split("[/quote]");
				newContent = trimQuote[1].replace(/(<br ?\/?>)*/g,"").replace(/[&]nbsp[;]/gi,"");;
			} else {
				newContent = quoteContent;
			}

			if(id) {
				$('.linke-by').show();
				$('.form-reply').hide();
				$("form#reply_thread").slideUp('fast', function() {
					$('.reply-overlay').show();
				});
				$("#post_"+id).find('.linke-by').hide();			
				$("#post_"+id).find('.form-reply').css({'display':'block'}).animate({'height':'277px','opacity':'1','filter':'alpha(opacity:100)'},500);
				// change active editor for reply editor
				var editorId = $("#post_"+id).find('.form-reply textarea[name=post_content]').attr('id');

				if(typeof tinyMCE !== 'undefined') {
			    	tinyMCE.execCommand("mceAddControl", false, editorId);  
			    	tinyMCE.activeEditor.execCommand('mceInsertContent', false , '[quote author="'+author+'"]'+newContent+'[/quote]<p></p>');
			    }
				$('body').animate( { scrollTop: $( tinyMCE.activeEditor.getContainer() ).parent().offset().top } );

			} else {

				var newContent = '[quote author="'+author+'"]' + threadData.post_content + '[/quote]' + "<p></p>";						
				var id = $('.thread-reply textarea[name=post_content]').attr('id');

				$('html, body').animate({ scrollTop: 60000 }, 'slow'); 

				$('.linke-by').show();
				$('.form-reply').hide();
				$("form#reply_thread").slideDown('fast', function() {
					$('.reply-overlay').hide();
				});	

				if(typeof tinyMCE !== 'undefined') {
			    	tinyMCE.execCommand("mceAddControl", false, id);  
			    	tinyMCE.activeEditor.execCommand('mceInsertContent', false , newContent);
			    }									
			}
			
		},

		onReport: function(event){
			event.preventDefault();
			console.log('abc');

			this.model.report({
				success: function(resp){
					if ( resp.success ){
						//alert( resp.msg );
						pubsub.trigger('fe:showNotice', resp.msg , 'success');
					} else {
						//alert( resp.msg );
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
					}
				}
			});
		},

		openReplyForm: function(){
			$('.linke-by').show();
			$('.form-reply').hide('fast');

			if(typeof (tinyMCE.activeEditor) !== 'undefined')
				tinyMCE.activeEditor.remove();
			
			$("form#reply_thread").slideUp('fast', function() {
				$('.reply-overlay').show();
			});				
					
			this.$('.linke-by').hide();			
			this.$('.form-reply').fadeIn('slow');
			// change active editor for reply editor
			var editorId = this.$('.form-reply textarea[name=post_content]').attr('id');
			if(typeof tinyMCE !== 'undefined') {
		    	tinyMCE.execCommand("mceAddControl", false, editorId);  
		    	tinyMCE.activeEditor.execCommand('mceInsertContent', false , '');
		    }			
		},

		onOpenReplyForm: function(event){
			event.preventDefault ? event.preventDefault() : event.returnValue = false;
			// check if user logged in or not
			var view = this;

			// if user isn't logged in, open login modal
			if ( !this.isAuth() ){
				this.openLogin();

				// assign listening
				view.listenTo(pubsub, 'fe:auth:afterLogin', view.openReplyForm);
				view.listenTo(pubsub, 'fe:auth:afterRegister', view.openReplyForm);
			}  // perform like
			else {
				this.openReplyForm();
			}
		},

		onCancelReplyForm: function(event){
			event.preventDefault();
			var id = $(this).attr('data-target');
			var view = this;
			this.$('.form-reply').fadeOut('normal', function(){
				view.$('div.linke-by').show();	
			});
		},

		// open modal login
		openLogin: function(){
			// get login modal
			var modal = ForumEngine.app.getLoginModal();
			// open modal
			modal.open(false);
		},

		ajaxReply: function(event){
			event.preventDefault();
			var element 	= $(event.currentTarget);
			var textarea 	= element.find('textarea[name=post_content]');
			var view 		= this;

			if (tinymce.get(textarea.attr('id'))){
				content = tinymce.get(textarea.attr('id')).getContent();	
			} else {
				content = textarea.val();
			}

			if(($.trim(content)).length==0 || content == '' || /^(?:\s|<br *\/?>)*$/.test(content)) {
				pubsub.trigger('fe:showNotice', fe_front.form_login.error_msg , 'warning');
				return false;
				$(this).find(':submit').prop('disabled', false);
			} else {
				$(this).find(':submit').prop('disabled', true);
			}

			this.model.reply(content, {
				beforeSend: function(){
					element.find('input.btn').prop('disabled',true);
				},
				success: function(resp, model){
					if ( resp.success ){
						var container 	= view.$('.reply-children .replies-container');
						var subView 	= new ForumEngine.Views.PostListItem({ model: resp.data.reply, el: resp.data.reply.html });
						view.subReplies.push( subView  );

						container.append(subView.$el);

						// open container
						if (container.is(':hidden'))
							container.parent().removeClass('collapse');

						// 
						if ( !resp.data.load_more ){
							view.$('.btn-more-reply').hide();
						}

						//reset current active TinyMCE after reply
						tinyMCE.activeEditor.execCommand('mceSetContent', false , '');

						// update count
						var old = parseInt(view.$('.name .comment .count').html());
						view.$('.name .comment .count').html(old + 1);
					}
				},
				complete: function(){
					element.find('input.btn').prop('disabled',false);
				}				
			});
		},

		showReplies: function(event){
			var element 	= $(event.currentTarget);
			var target 		= $(element.attr('href'));
			var view 		= this;
			var id 			= this.model.get('id');

			event.preventDefault();
			if(!element.hasClass('clicked')){
				element.addClass('clicked');
				// if no replies in container yet, fetch some
				if ( target.find('.replies-container .items-thread').length == 0 ){
					var page 		= this.page ? this.page : 1 ;
					view.fetchReplies({paged: page}, {
						beforeSend: function(){
							element.parent().toggleClass('loading');
						}, 
						success: function(resp){
							if ( resp.data.total_pages > 0 ){
								// display container
								//target.toggleClass('collapse');
							}
						},
						complete: function(resp){
							element.parent().toggleClass('loading');
						}
					}); // end fetchreplies
				} else {
					target.find('.replies-container .items-thread').slideDown();
				} 
			}else {
				target.find('.replies-container .items-thread').slideUp();
				element.toggleClass('clicked');				
			}
		},

		moreReplies : function(event){
			event.preventDefault();				
			var view = this;
			var buttonMore 	= view.$('.btn-more-reply');

			view.fetchReplies({paged: this.page + 1}, {
				beforeSend: function(){
					$(buttonMore).loader('load');
				},
				complete: function(){
					$(buttonMore).loader('unload');	
				}
			}); // end fetchreplies
		},

		fetchReplies : function(data, options){
			var view 		= this;
			var container 	= view.$('.replies-container');
			var buttonMore 	= view.$('.btn-more-reply');
			var loading 	=  $('<div class="loading">Loading...</div>');

			options = options || {};

			var success 	= options.success	|| function(){};
			var beforeSend 	= options.beforeSend || function(){};
			var complete 	= options.complete 	|| function(){};

			var params = {
				url 	: fe_globals.ajaxURL,
				type 	: 'post',
				data 	:   {
					action : 'et_fetch_replies',
					content : {
						reply_parent 	: this.model.get('id'),
						paged 			: 1
					}
				},
				beforeSend: function (){
					beforeSend();
				},
				success: function(resp){				
					if (resp.success){
						loading.remove();

						// add content
						_.each( resp.data.replies, function(element){
							//var subReplyModel 	= new ForumEngine.Models.Post(element);
							var subReplyView  	= new ForumEngine.Views.PostListItem({model: element, el: element.html});
							view.subReplies.push( subReplyView );
							container.prepend(subReplyView.$el);
						} );

						// verify pagination
						if ( resp.data.current_page < resp.data.total_pages ){
							view.page = resp.data.current_page;

							if(buttonMore.hasClass('hide')) buttonMore.removeClass('hide');

							buttonMore.show();
						} else {
							buttonMore.hide();
						}
					} else {
						console.log('fetch fail');
					}

					success(resp);
				},
				complete: function(){
					complete();
				}
			};
			var data 	= $.extend( params.data.content, data );

			return $.ajax(params);
		},
		
		onOpenEdit: function(event){
			event.preventDefault();
			var element = $(event.currentTarget);
			var view  	= this;
			var contentArea = view.$('.post-display');
			var target 	= view.$('.post-edit');
			var control  = view.$('.control-thread');
			var editorId = target.find("textarea").attr('id');

			contentArea.fadeOut('normal', function(){
				if(typeof tinyMCE !== 'undefined' && !tinyMCE.get( editorId )) {
			    	tinyMCE.execCommand("mceAddControl", false, editorId );  
			    	tinyMCE.get( editorId ).setContent(target.find("textarea").val());
			    }				
				target.fadeIn();
		    	tinyMCE.activeEditor.execCommand('mceAutoResize');
				control.addClass('hide');
				console.log(target.find("textarea").val());
			});
		},

		onCancelEdit: function(event){
			event.preventDefault();
			this.closeEdit();
		},

		closeEdit: function(){
			var view  	= this;
			var contentArea = view.$('.post-display');
			var target 	= view.$('.post-edit');
			var control  = view.$('.control-thread');

			target.fadeOut('normal', function(){
				contentArea.fadeIn(function(){
					if(typeof (tinyMCE.activeEditor) !== "undefined")
						tinyMCE.activeEditor.remove();					
				});
				control.removeClass('hide');
			});
		},

		onSubmitEdit: function(event){
			event.preventDefault();

			var view 		= this;
			var form 		= $(event.currentTarget);
			var data 		= form.serializeObject();
			var textareaId 	= form.find('textarea').attr('id');
			var button 		= form.find("input.btn");
			if (tinymce.get(textareaId)){
				data.post_content = tinymce.get(textareaId).getContent();
			}

			view.model.update( data, {
				beforeSend: function(){
					console.log(data);	
					button.prop('disabled', true);
				},
				success: function(resp, model){
					button.prop('disabled', false);
					if(resp.success){
						var contentView = view.$('.post-display .content');
						contentView.html(resp.data.posts.content_html);
		
						view.trigger('fe:post:afterEdit', resp.data.posts);
		
						$("div#thread_preview").fadeOut();
						SyntaxHighlighter.highlight();
						// fade cancel
						view.closeEdit();
						hasChange = false;
					} else {
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
					}
				}
			});
		}
	});

	/**
	 * Handle threads list in an archive page
	 */ 
	ForumEngine.Views.ListThread 	= Backbone.View.extend({
		initialize: function(){
			var elements 	= this.$el.children('li.thread-item');
			this.views 		= [];
			$(elements).each(function(){
				new ForumEngine.Views.ListThreadItem({el: this});
			});
		}
	});

	/**
	 * Handle thread list item in an archive page
	 */
	ForumEngine.Views.ListThreadItem = Backbone.View.extend({
		events: {
			'click .delete-thread' 	: 'onDeleteThread',
			'click .close-thread' 	: 'onCloseThread',
			'click .unclose-thread' : 'onCloseThread',
			'click .approve-thread' : 'onApproveThread',
			'click .act-undo' 		: 'onUndoAction'
		},
		initialize: function(){
			var id = $(this.$el).attr('data-id');
			if ( id ){
				this.thread = new ForumEngine.Models.Post({id : id});
			}
		},

		syncingParams: function(){
			var view = this;
			return {
				beforeSend: function(){
					$(view.$el).loader('load');
				},
				complete: function(){
					$(view.$el).loader('unload');
				}
			};
		},

		onDeleteThread: function(event){
			event.preventDefault();
			var view = this;
			var params = $.extend( this.syncingParams(), {
				success: function(resp, model){
					if (resp.success){
						//$(view.$el).fadeOut('normal', function(){ view.remove() });
						view.showUndoAction()
						$("span#num_pending").text(parseInt($("span#num_pending").text())-1);
						pubsub.trigger('fe:showNotice', resp.msg , 'success');
					} else {
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
					}
				}
			} );
			this.thread.onDelete(params);
		},

		onCloseThread: function(event){
			event.preventDefault();
			var view = this;
			var params = $.extend( this.syncingParams(), {
				success: function(resp, model){
					if ( resp.success ){
						if ( resp.data.new_status == 'closed' ){
							//alert('Closed successfully');
							pubsub.trigger('fe:showNotice', resp.msg , 'success');
							view.$('.title a').append('<span class="icon" data-icon="("></span>');
						}
						else {
							//alert('Unclosed successfully');
							pubsub.trigger('fe:showNotice', resp.msg , 'success');
							view.$('.title a .icon').remove();
						}
						view.$('.control-thread-group a.close-thread').toggleClass('collapse');
						view.$('.control-thread-group a.unclose-thread').toggleClass('collapse');
					}
				}
			} );
			this.thread.close(params);
		},

		// under construction
		onStickyThread: function(event){
			event.preventDefault();
			var params = $.extend( this.syncingParams(), {
				success: function(resp, model){
					console.log(resp);
					if (resp.success){
						pubsub.trigger('fe:showNotice', resp.msg , 'success');
					} else {
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
					}					
				}
			} );
			this.thread.sticky(params);
		},

		// on approve thread
		onApproveThread: function(event){
			event.preventDefault();
			var view 	= this;
			var params 	= $.extend( this.syncingParams(), {
				success: function(resp, model){
					if (resp.success){
						//$(view.$el).fadeOut('normal', function(){ view.remove() });
						// display undo action
						view.showUndoAction();

						$("span#num_pending").text(parseInt($("span#num_pending").text())-1);
						pubsub.trigger('fe:showNotice', resp.msg , 'success');
					} else {
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
					}
				}
			} );
			this.thread.approve(params);
		},

		onUndoAction: function(event){
			event.preventDefault();
			var view 	= this;
			var params 	= $.extend( this.syncingParams(), {
				success: function(resp, model){
					if (resp.success){
						//$(view.$el).fadeOut('normal', function(){ view.remove() });
						// display undo action
						view.hideUndoAction();

						$("span#num_pending").text(parseInt($("span#num_pending").text())+1);
						pubsub.trigger('fe:showNotice', resp.msg , 'success');
					} else {
						pubsub.trigger('fe:showNotice', resp.msg , 'error');
					}
				}
			} );
			this.thread.undoStatus(params);
		},

		showUndoAction: function(){
			var view = this;
			//this.$el.fadeOut('normal', function(){
				view.$('.user-action').hide();
				view.$('.control-thread-group').hide();
				view.$('.undo-action').removeClass('hide').show();
			//	view.$el.fadeIn();
			//});
		},

		hideUndoAction: function(){
			var view = this;
			//this.$el.fadeOut('normal', function(){
				view.$('.undo-action').hide();
				view.$('.control-thread-group').show();
				view.$('.user-action').removeClass('hide').show();
			//	view.$el.fadeIn();
			//});	
		}
	});

	/**
 	 * Present modal view
	 */
	if ( typeof(ForumEngine.Views.Modal) == 'undefined' ){
		ForumEngine.Views.Modal = Backbone.View.extend({	
			initialize: function(){	},
			open: function(){ this.$el.modal('show'); },
			close: function(){ this.$el.modal('hide'); }
		});
	}

	if ( typeof( ForumEngine.Views.LoginModal ) == 'undefined' ){
		ForumEngine.Views.LoginModal = ForumEngine.Views.Modal.extend({
			options: {
				enableRefresh : true
			},
			events: {
				'submit #form_login' 	: 'onLogin',
				'submit #form_register' : 'onRegister',
				'submit #form_forget' 	: 'onForgotPass',
			},
			initialize: function(){
				console.log('modal init');		
				ForumEngine.Views.Modal.prototype.initialize.call();
			},
			onLogin: function(event){
				event.preventDefault();
				var element 	= $(event.currentTarget),
					button 		= element.find("button.btn");
				var data 		= element.serializeObject();
				var view 		= this;
				var options 	= {
					beforeSend: function(){
						button.prop('disabled', true);
					},	

					success: function(resp, model){
						if(resp.success){
							view.trigger('response:login', resp);
							pubsub.trigger('fe:response:login', model);
							pubsub.trigger('fe:showNotice', resp.msg , 'success');

							view.$el.on('hidden.bs.modal', function(){
								pubsub.trigger('fe:auth:afterLogin', model);
								view.trigger('afterLogin', model);

								if ( view.options.enableRefresh == true){
									console.log('refresh page after login');
									window.location.reload(true);
								} else {
									console.log('dont refresh page')
								}

							});						
						} else {
							pubsub.trigger('fe:showNotice', resp.msg , 'error');						
						}

						view.close();
					},

					complete: function(){
						button.prop('disabled', false);
					}
				}
				this.login_validator	= $('form#form_login').validate({
					rules	: {
						user_name	: "required",
						user_pass	: "required"
					},
					messages	: {
						user_name	: fe_front.form_login.error_msg,
						user_pass	: fe_front.form_login.error_msg
					},
					highlight: function(element, errorClass) {
						$(element).parent().addClass('error');
						$(element).parent().find('span.icon').show();
					},
					unhighlight: function(element, errorClass) {
						$(element).parent().removeClass('error');
						$(element).parent().find('span.icon').hide();
						//$(element).parent().find('span.line-correct').show();
					},								
				});
				if(this.login_validator.form()){
					ForumEngine.app.login(data.user_name, data.user_pass, options);	
				}		
				
			},
			open: function(enableRefresh){ 
				//var enableRefresh = enableRefresh || true;
				if ( typeof enableRefresh == 'undefined' )
					this.options.enableRefresh = true;
				else 
					this.options.enableRefresh = enableRefresh;

				console.log('Enable refresh: ' + this.options.enableRefresh);
				this.$el.modal('show'); 
			},
			onForgotPass: function(event){
				event.preventDefault();
				var element 	= $(event.currentTarget),
					button 		= element.find("button.btn"),			
					data 		= element.serializeObject(),
					view 		= this,

					options 	= {
						dataType	: 'json',
						url			: fe_globals.ajaxURL,
						type: 'POST',
						data: {
							action: 'et_user_sync',
							method: 'forgot',
							user_login: data.user_login
						},

						beforeSend: function(){
							button.prop('disabled', true);
						},

						success: function(resp){
							console.log(resp);
							if(resp.success){							
								pubsub.trigger('fe:showNotice', resp.msg , 'success');												
								
							} else {
								pubsub.trigger('fe:showNotice', resp.msg , 'error');	
							}
							view.close();
						
							$(".login-modal").fadeIn();
							$(".forget-modal").hide();
							$("#form_forget")[0].reset();
						},

						complete: function(){
							button.prop('disabled', false);
						}
					};
				//validate forgot form
				this.forgotpass_validator	= $('form#form_forget').validate({
					rules	: {
						user_login :{
							required	: true,
							email	: true
						}
					},
					messages: {
					    user_login: {
							required	: fe_front.form_login.error_msg,
							email	: fe_front.form_login.error_email						
						}	
					},
					highlight: function(element, errorClass) {
						$(element).parent().addClass('error');
						$(element).parent().find('span.icon').show();
						//$(element).parent().find('span.line-correct').hide();
					},
					unhighlight: function(element, errorClass) {
						$(element).parent().removeClass('error');
						$(element).parent().find('span.icon').hide();
						//$(element).parent().find('span.line-correct').show();
					},				
				});

				if(this.forgotpass_validator.form()){			
					$.ajax(options);
				}

			},
			onRegister: function(event){
				event.preventDefault();
				var element 	= $(event.currentTarget),
					button 		= element.find("button.btn");
				var data 		= element.serializeObject();
				var view 		= this;
				var options 	= {
					beforeSend: function(){
						button.prop('disabled', true);
					},	

					success: function(resp, model){
						if(resp.success){
								view.trigger('response:register', resp);							
								pubsub.trigger('fe:showNotice', resp.msg , 'success');												
							view.$el.on('hidden.bs.modal', function(){	
								pubsub.trigger('fe:auth:afterRegister', model);	
								if ( view.options.enableRefresh == true){
									console.log('refresh page after login');
									window.location.reload(true);
								} else {
									console.log('dont refresh page')
								}

							});						
						} else {
							pubsub.trigger('fe:showNotice', resp.msg , 'error');	
						}

						view.close();
					},

					complete: function(){
						button.prop('disabled', false);
					}
				}

				//validate register form
				this.register_validator	= $('form#form_register').validate({
					rules	: {
						user_name :{
							required	: true,
							username	: true
						},
						email	: {
							required	: true,
							email		: true
						},
						user_pass		: 'required',
						re_pass	: {
							required	: true,
							equalTo		: "#user_pass_register"
						},
						agree_terms: {
							required	: function(element){
								return !$(element).is(':checked');
							}
						}
					},
					messages: {
					    user_name: {
							required	: fe_front.form_login.error_msg,
							username	: fe_front.form_login.error_username						
						},
						email : {
							required	: fe_front.form_login.error_msg,
							email		: fe_front.form_login.error_email						
						},
						user_pass: fe_front.form_login.error_msg,
						re_pass: {
							required	: fe_front.form_login.error_msg,
							equalTo		: fe_front.form_login.error_repass
						}	
					},
					errorPlacement : function(error, element){
						if ( $(element).attr('type') != 'checkbox' ){
							error.insertAfter( element );
						}
					},
					highlight: function(element, errorClass) {
						if ( $(element).attr('type') == 'checkbox' ){
							$(element).next('label').addClass('error-checkbox');
						} else {
							$(element).parent().addClass('error');
							$(element).siblings('span.icon').show();	
						}
						//$(element).parent().find('span.line-correct').hide();
					},
					unhighlight: function(element, errorClass) {
						if ( $(element).attr('type') == 'checkbox' ){
							$(element).next('label').removeClass('error-checkbox');
						} else {
							$(element).parent().removeClass('error');
							$(element).siblings('span.icon').hide();
						}
						//$(element).parent().find('span.line-correct').show();
					},				
				});

				// var checkbox = $("div.check-agree").find("div.skin-checkbox");

				// if(!checkbox.hasClass('checked')){
				// 	checkbox.css("border","1px solid #e74c3c");
				// }

				if(this.register_validator.form()){
					ForumEngine.app.register(data.user_name, data.email ,data.user_pass, options);				
				}

			}
		});
	}

})(jQuery);