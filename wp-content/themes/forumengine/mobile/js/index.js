(function($){

$(document).ready(function(){
	new ForumMobile.Views.Index();
});

/**
 * Website script here
 **/
ForumMobile.Views.Index = Backbone.View.extend({
	el: 'body',
	events: {
		'tap a#more_thread' 			: 'loadMoreThreads',
		'tap a#more_blog' 				: 'loadMorePosts',
		'tap a#create_thread' 			: 'insertThread',
	},
	initialize: function(){
		// initialize thread view
		var view = this;
		this.threadItems = [];
		this.$('article').each(function(){
			var element = $(this);
			var model 	= { 'ID' : element.attr('data-id'), 'id' : element.attr('data-id') };
			view.threadItems.push( new ForumMobile.Views.ThreadItem({model : model, el : this }) );
		});
	},

	insertThread: function(event){
		var active 	 = $('div.ui-page-active'),
			title    = active.find("input#thread_title").val(),
			content  = active.find("textarea#thread_content").val(),
			category = active.find("select#thread_category").val();

		if(content == "" || title == "") {
			ForumMobile.app.notice('error', 'Please fill out all fields.');
			return false;
		}
		
		$.ajax({
			url : fe_globals.ajaxURL,
			type : 'post',
			data : {
				action		: 'et_post_sync',
				method		: 'create',
				content 	: {
					post_title		: title,
					post_content 	: content,
					thread_category : category,
				}				
			},
			beforeSend : function(){
				$.mobile.showPageLoadingMsg();
			},
			error : function(request){
				$.mobile.hidePageLoadingMsg();				
			},
			success : function(response){
				$.mobile.hidePageLoadingMsg();

				if( response.success ){
					if ( response.link )
						$.mobile.navigate(response.link);
					ForumMobile.app.notice('success', response.msg);
				} else {
					ForumMobile.app.notice('error', response.msg);
				} 
			}
		});
	},

	loadMorePosts:function(event){
		event.preventDefault();
		var paged 		= $('div.ui-page-active').find('a#more_blog').attr('data-page'),
			category  	= $('div.ui-page-active').find('a#more_blog').attr('data-cat'),
			query_default = {
				action		: 'fe_get_posts',
				content : {
					paged			: paged,
					category 		: category,
					}
			},
			that = this;

		$.ajax({
			url : fe_globals.ajaxURL,
			type : 'post',
			data : query_default,
			beforeSend : function(){
				$.mobile.showPageLoadingMsg();
			},
			error : function(request){
				$.mobile.hidePageLoadingMsg();
			},
			success : function(response){
				$.mobile.hidePageLoadingMsg();
				current_page = response.data.paged;
				max_page_query = response.data.total_pages;

				if(response.success){

					$('div.ui-page-active').find('a#more_blog').attr('data-page', current_page);
					$('div.ui-page-active').find('a#more_blog').hide();

					var container = $('div.ui-page-active').find('#posts_container');
					for (key in response.data.posts){
						container.append(response.data.posts[key]);
					}

					if( current_page < max_page_query && max_page_query != 0){
						$('div.ui-page-active').find('a#more_blog').show();
					}	
				}
				else alert('Query error');
			}
		});
	},

	loadMoreThreads:function(event){
		event.preventDefault();
		var paged 		= $('div.ui-page-active').find('a#more_thread').attr('data-page'),
			status  	= $('div.ui-page-active').find('a#more_thread').attr('data-status'),
			category  	= $('div.ui-page-active').find('a#more_thread').attr('data-term'),
			s  			= $('div.ui-page-active').find('a#more_thread').attr('data-s'),
			query_default = {
				action		: 'et_post_sync',
				method		: 'get',
				content : {
					paged			: paged,
					status 			: status,
					thread_category : category,
					s				: s,
					}
			},
			that = this;

		$.ajax({
			url : fe_globals.ajaxURL,
			type : 'post',
			data : query_default,
			beforeSend : function(){
				$.mobile.showPageLoadingMsg();
			},
			error : function(request){
				$.mobile.hidePageLoadingMsg();
			},
			success : function(response){
				$.mobile.hidePageLoadingMsg();
				current_page = response.data.paged;
				max_page_query = response.data.total_pages;

				if(response.success){

					$('div.ui-page-active').find('a#more_thread').attr('data-page', current_page);
					$('div.ui-page-active').find('a#more_thread').hide();

					that.renderLoadMore(response.data.threads);

					if( current_page < max_page_query && max_page_query != 0){
						$('div.ui-page-active').find('a#more_thread').show();
					}	
				}
				else alert('Query error');
			}
		});
	},

	renderLoadMore:function(threads){
		var container = $('div.ui-page-active').find('#posts_container');
		for (key in threads){
			container.append(threads[key]);
		}
	},

});

ForumMobile.Views.ThreadItem = Backbone.View.extend({
	tagName : 'article',
	events: {
		'tap a.fe-act'					: 'doAction',
		'tap a.act-undo' 				: 'onUndo'
	},
	initialize: function(){
		this.model = new ForumEngine.Models.Post(this.options.model);
		//console.log(this.model);
	},

	onUndo: function(event){
		event.preventDefault();
		var view = this;

		this.model.undoStatus({
			beforeSend: function(){
				$.mobile.showPageLoadingMsg();
			},
			success: function(resp){
				view.$el.removeClass('undo-active');
			},
			complete: function(){
				$.mobile.hidePageLoadingMsg();
			}
		})
	},

	doAction: function(event){
		var element 	= $(event.currentTarget),
			method 		= element.attr('data-act');
		var view 		= this;

		view.$el.addClass('undo-active')
					.removeClass('fe-editing'); 
			
		$.ajax({
			url : fe_globals.ajaxURL,
			type : 'post',
			data : {
				action		: 'et_post_sync',
				method		: method,
				content : {
					id: element.attr('data-id')
				}
			},
			beforeSend : function(){
				$.mobile.showPageLoadingMsg();
			},
			error : function(request){
				$.mobile.hidePageLoadingMsg();
			},
			success : function(response){
				$.mobile.hidePageLoadingMsg();

				if(response.success){
					// notification
					ForumMobile.app.notice('success', response.msg);
					//if(response.link){
						// var redirect;
						// if(method == "delete") redirect = response.link;
						// else redirect = window.location.href;
						// $.mobile.changePage(redirect, {
					 //        allowSamePageTransition: true,
					 //        transition: 'none',
					 //        showLoadMsg : false,
					 //        reloadPage: true
					 //    });
					//} else {
						view.$el.addClass('undo-active')
							.removeClass('fe-editing'); 
						//element.closest('article').fadeOut('slow');						
					//}
				}
				else {
					// notification
					ForumMobile.app.notice('error', response.msg);
				}
			}
		});		
	},
});

})(jQuery);