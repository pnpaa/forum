(function($){

$(document).ready(function(){
	new ForumMobile.Views.Author();
});

/**
 * Website script here
 **/
ForumMobile.Views.Author = Backbone.View.extend({
	el: 'body',
	events: {
		'tap a#more_thread_author' 			: 'loadMoreThreads'
	},
	initialize: function(){
	},
	loadMoreThreads:function(event){

		var element = $(event.currentTarget),
			paged = element.attr('data-page'),
			status  = element.attr('data-status'),
			author  = element.attr('data-author'),
			query_default = {
			action		: 'et_post_sync',
			method		: 'get',
			content : {
				paged			: paged,
				status 			: status,
				author 			: author,
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

					$('div.ui-page-active').find('a#more_thread_author').attr('data-page', current_page);
					$('div.ui-page-active').find('a#more_thread_author').hide();

					that.renderLoadMore(response.data.threads);

					if( current_page < max_page_query && max_page_query != 0){
						$('div.ui-page-active').find('a#more_thread_author').show();
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
	}
});

})(jQuery);