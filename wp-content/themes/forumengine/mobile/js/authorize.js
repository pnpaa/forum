(function($){

$(document).ready(function(){
	new ForumMobile.Views.Authorize();
});

/**
 * Website script here
 **/
ForumMobile.Views.Authorize = Backbone.View.extend({
	el: 'body',
	events: {
		'submit #content_login form' 	: 'doLogin',
		'submit #content_register form' : 'doRegister',
	},

	initialize: function(){
		//this.login    = $("#content_login form").validate();
		//this.register = $("#content_register form").validate();
	},

	doLogin: function(event){
		event.preventDefault();
		var form = $(event.currentTarget),
			username = form.find("input#username").val(),
			password = form.find("input#password").val(),
			login    = $("#content_login form").validate({
				rules	: {
					username: 'required',
					password: {
						required	: true,
					}
				}				
			});

		if(login.form()){
			$.ajax({
				url : fe_globals.ajaxURL,
				type : 'post',
				data : {
					action		: 'et_user_sync',
					method		: 'login',
					content 	: {
						user_name	: username,
						user_pass 	: password,
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
						ForumMobile.app.notice('success', response.msg);
						$.mobile.navigate(response.redirect);
					} else {
						ForumMobile.app.notice('error', response.msg);
						// $('div.ui-page-active').find('div#popup_msg').text(response.msg);
						// $( "#popup_msg" ).popup( "open" );
						// reset password
						form.find('input#password').val('');
					}
				}
			});				
		}
	
	},

	doRegister: function(event){
		event.preventDefault();
		var form     = $(event.currentTarget),
			username = form.find("input#username").val(),
			password = form.find("input#rg_password").val(),
			email    = form.find("input#email").val(),
			register = $("#content_register form").validate({
				rules	: {
					username: 'required',
					email: {
						required	: true,
						email		: true
					},
					password: {
						required	: true,
					},
					re_password: {
						required	: true,
						equalTo		: '#rg_password'
					}
				}				
			});

		if(register.form()){			
			$.ajax({
				url : fe_globals.ajaxURL,
				type : 'post',
				data : {
					action		: 'et_user_sync',
					method		: 'register',
					content 	: {
						user_name	: username,
						user_email	: email,
						user_pass 	: password,
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
						$.mobile.navigate(response.redirect_url);
						ForumMobile.app.notice('success', response.msg);
					} else {
						ForumMobile.app.notice('error', response.msg);
						// $('div.ui-page-active').find('div#popup_msg').text(response.msg);
						// $( "#popup_msg" ).popup( "open" );	
						// reset form
						form.find('input#username, input#password, input#email').val('');
					}
				}
			});	
		}	
	},
});

})(jQuery);