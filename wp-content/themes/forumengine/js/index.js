(function($){

$(document).ready(function(){
	indexView = new ForumEngine.Views.Index();
});

ForumEngine.Views.Index = Backbone.View.extend({
	el: 'body',
	events: {
		'submit #form_thread form' : 'onCreateTopic'
	},
	initialize: function(){
		new ForumEngine.Views.ListThread({el : '#main_list_post'});

		pubsub.on('fe:auth:afterLogin', this.afterAuthorize);
		pubsub.on('fe:auth:afterRegister', this.afterAuthorize);		
	},
	afterAuthorize: function(model){
		var name = model.get("display_name"),
			avatar = model.get("et_avatar");
		$("div.profile-account span.name a").text(name);
		$("div.profile-account span.img").html(avatar);
		$("div.login").hide();
		$("div.profile-account").fadeIn("slow");
				
		var success = false;
		$.ajax({
			url: fe_globals.ajaxURL,
			type: 'POST',
			data: {
				action: 'et_get_nonce'
			},
			beforeSend: function(){},
			success:function(resp){
				success = resp.success;
				if(resp.success){
					$("#form_thread form").find('input.fe_nonce').val(resp.data.ins);
					$("#form_thread form").find("input.btn").val(fe_front.texts.create_topic);
					$("#uploadImgModal").find('span.et_ajaxnonce').attr('id', resp.data.up);
					/* update layout form upload img */
					$("#images_upload_container").removeClass('disabled').css('opacity', '1.0');
					$("#images_upload_browse_button").prop('disabled', false);
					$("p.text-danger").addClass('hide');					
				}
			},
			complete:function(){
				if(success){
					$("#form_thread form").find('div.button-event input.btn').trigger('click');
				}					
			}
		});
		
	},

	onCreateTopic: function(event){
		var view = this;
		if(ForumEngine.app.currentUser.get('id')){

			var title = $("#form_thread form input.inp-title").val(),
				category = $("#form_thread form select#thread_category").val(),
				content = tinyMCE.activeEditor.getContent();

			if(title == '' || category == '' || /^(?:\s|<br *\/?>)*$/.test(content) || content == '' || ($.trim(title)).length==0 || ($.trim(content)).length==0) {
				pubsub.trigger('fe:showNotice', fe_front.form_login.error_msg , 'warning');
				return false;
				$("#form_thread form").find('input.btn').prop('disabled', false);
			} else {
				$("#form_thread form").find('input.btn').prop('disabled', true);
			}

		} else {
			event.preventDefault();
			var modal =ForumEngine.app.getLoginModal();
			modal.open(false);
			view.listenTo(pubsub, 'fe:auth:afterLogin');
			view.listenTo(pubsub, 'fe:auth:afterRegister');
		}
	},	
});

})(jQuery);