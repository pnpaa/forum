(function($){

$(document).ready(function(){
	new Backend.Views.Member();
});

Backend.Views.MemberItem = Backbone.View.extend({
	//template: $('#member_template'),
	tagName: 'li',
	className: 'et-member',

	events: {
		'change select[name=role]' : 'updateRole'
	},

	initialize: function(){
		this.model = new ForumEngine.Models.Member(this.options.model);
		console.log(this.model);
	},

	updateRole: function(e){
		console.log('update role');
		var newRole = $(e.currentTarget).val();
		var element = $(e.currentTarget).parent();
		this.model.updateRole(newRole, {
			beforeSend: function(){
				console.log('before update user role');
				$(element).loader('load');
				$(e.currentTarget).attr('disabled', 'disabled');
			},
			success: function(resp){
				console.log('update success');
				console.log(resp);
			},
			complete: function(resp){
				console.log('update complete');
				$(element).loader('unload');
				$(e.currentTarget).removeAttr('disabled');
			}
		});
	},

	render: function(){
		var template = _.template( $('#member_template').html() );

		// generate html
		this.$el.html(template( this.model.attributes )).attr('data-id', this.model.attributes.ID);

		// style select
		this.$('.selector').styleSelect();

		return this;
	}
});

Backend.Views.Member = Backbone.View.extend({
	el: '#engine_setting_content',
	queryVars: {
		offset: 0,
		number: parseInt(fe_globals.posts_per_page),
		search: '',
		role: ''
	},
	events: {
		'click #load-more' : 'loadMore',
		'change .et-search-role select[name=role]' : 'filterRole',
		'keyup .et-search-input input[name=keyword]' : 'filterText'
		//'change .et-search-role select[name=role]' : 'filterRole'
	},

	initialize: function(){
		var view = this;

		// generate view
		view.memberViews = [];
		$('#members_list li').each(function(){
			var userID = $(this).attr('data-id');
			view.memberViews.push(new Backend.Views.MemberItem({ el: $(this), model: {id: userID, ID: userID} }));
		})

		this.searchAction = _.debounce(function(){
			var element = view.$('.et-search-input input[name=keyword]');
			var s 		= element.val();

			if ( view.queryVars.search == s )
				return false;

			this.updateQueryVars({search: s, offset: 0});
			this.filter(element, true);
		}, 1000);
	},

	updateQueryVars: function(newValues){
		this.queryVars = _.extend(this.queryVars, newValues);
	},

	updateMemberList: function(newMembers, clear){
		var clear 	= clear ? true : false;
		var view 	= this;

		if (clear){
			view.memberViews = [];
			$('#members_list').html('');
		}
		
		$.each(newMembers, function(){
			var data 		= this;
			var memberView 	= new Backend.Views.MemberItem({ model: data });
			view.memberViews.push(memberView);

			$('#members_list').append( memberView.render().$el );
		});
	},

	filterRole: function(e){
		var element = $(e.currentTarget);
		var role 	= element.val();

		if ( !role ) role = '';

		this.updateQueryVars({role: role, offset: 0});
		this.filter(element, true);
	},

	filterText: function(e){		
		this.searchAction();
	},

	loadMore: function(e){
		this.queryVars.offset = this.queryVars.offset + this.queryVars.number;
		this.filter($(e.currentTarget), false);
	},

	filter: function(element, clearList){
		var view = this;
		var params = {
			url: fe_globals.ajaxURL,
			type: 'post',
			data: {
				action: 'et_user_sync',
				method: 'get_members',
				content: {
					query_vars: this.queryVars
				}
			},
			beforeSend:function(){
				element.loader('load');
			},
			success: function(resp){
				if ( resp.success ){
					view.updateMemberList( resp.data.users, clearList );

					//check
					console.log(resp.data.total);
					console.log(resp.data.offset + resp.data.number);
					if ( resp.data.total <= (resp.data.offset + resp.data.number) ){
						$('#load-more').hide();
					} else {
						$('#load-more').show();
					}
				}
			},
			complete: function(){
				console.log('load more complete');
				element.loader('unload');
			}
		}
		$.ajax(params);
	}
});

})(jQuery);