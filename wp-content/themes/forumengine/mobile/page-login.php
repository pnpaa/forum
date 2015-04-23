<?php 
et_get_mobile_header();
// header part
get_template_part( 'mobile/template', 'header' );
?>
<div data-role="content" class="fe-content fe-content-auth">
	<div class="fe-tab">
		<ul class="fe-tab-items nav nav-tabs">
			<li class="fe-tab-item active fe-tab-item-2">
				<a href="#content_login" data-toggle="tab" class="ui-link">
					<span class="fe-tab-name"><?php _e('LOGIN', ET_DOMAIN) ?></span>
				</a>
			</li>
			<li class="fe-tab-item fe-tab-item-2">
				<a href="#content_register" data-toggle="tab" class="ui-link">
					<span class="fe-tab-name"><?php _e('REGISTER', ET_DOMAIN) ?></span>
				</a>
			</li>
		</ul>
	</div>
	<div class="tab-content">
		<div id="popup_msg" data-overlay-theme="a" data-role="popup"></div>
		<div id="content_login" class="tab-pane fade active in fe-container fe-tab-content fe-content-login">
			<form class="fe-form" method="post" data-ajax="false">
				<div class="fe-form-item">
					<label for=""><?php _e('Username', ET_DOMAIN) ?></label>
					<input type="text" name="username" id="username" class="fe-input-text" value="">
				</div>
				<div class="fe-form-item">
					<label for=""><?php _e('Password', ET_DOMAIN) ?></label>
					<input type="password" name="password" id="password" class="fe-input-text" value="">
				</div>
				<div class="fe-form-item fe-form-btns">
					<input type="submit" value="<?php _e('Login', ET_DOMAIN) ?>" data-role="none" class="fe-btn-right fe-form-btn">
				</div>
			</form>
		</div>
		<div id="content_register" class="tab-pane fade fe-container fe-tab-content fe-content-register">
			<form class="fe-form" method="post" data-ajax="false">
				<div class="fe-form-item">
					<label for=""><?php _e('Username', ET_DOMAIN) ?></label>
					<input type="text" name="username" id="username" class="fe-input-text" value="">
				</div>
				<div class="fe-form-item">
					<label for=""><?php _e('Email', ET_DOMAIN) ?></label>
					<input type="email" name="email" id="email" class="fe-input-text" value="">
				</div>
				<div class="fe-form-item">
					<label for=""><?php _e('Password', ET_DOMAIN) ?></label>
					<input type="password" name="password" id="rg_password" class="fe-input-text" value="">
				</div>
				<div class="fe-form-item">
					<label for=""><?php _e('Retype Password', ET_DOMAIN) ?></label>
					<input type="password" name="re_password" id="re_password" class="fe-input-text" value="">
				</div>
				<div class="fe-form-item fe-form-btns">
					<input type="submit" value="<?php _e('Register', ET_DOMAIN) ?>" data-role="none" class="fe-btn-right fe-form-btn">
				</div>
			</form>
		</div>
	</div>
</div>
<?php 
// footer part
get_template_part( 'mobile/template', 'footer' );

et_get_mobile_footer();
?>
