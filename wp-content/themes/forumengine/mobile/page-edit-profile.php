<?php 
et_get_mobile_header();
get_template_part( 'mobile/template', 'header' );
?>
<div data-role="content" class="fe-content content-profile-edit">
<?php 
global $current_user;
$user = FE_Member::get_current_member();
 ?>
	<div class="fe-page-heading">
		<ul class="fe-thread-actions pull-right">
			<li class="">
				<a class="" data-toggle="modal-edit" href="#modal_password"><?php _e('Change password', ET_DOMAIN) ?></a>
			</li>
		</ul>
		<ul class="fe-thread-actions">
			<li class="" style="">
				<a class="fe-icon-b fe-icon-b-back" href="javascript:history.back()"><?php _e('Go to profile', ET_DOMAIN) ?></a>
			</li>
		</ul>
	</div>
	<div class="fe-edit-avatar fe-container">
		<div class="avatar-container">
			<?php echo et_get_avatar($current_user->ID) ?>
			<!-- <a href="#" data-toggle="modal-edit" class="fe-icon-b-edit fe-icon-b" data-role="none"></a> -->
		</div>
	</div>
	<div class="fe-edit-block fe-container">
		<div class="fe-block-title">
			<?php printf( __("About @%s", ET_DOMAIN), $user->user_login ) ?>
		</div>
		<div class="fe-edit-area">
			<a href="#modal_about" data-toggle="modal-edit" class="fe-area-block">
				<div id="content_description" class="fe-area-text"><?php echo wpautop( $user->description )?></div>
				<span class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
		</div>
	</div>
	<div class="fe-edit-block fe-container">
		<div class="fe-block-title">
			Profile
		</div>
		<div class="fe-edit-area">
			<a href="#modal_display_name" data-toggle="modal-edit" class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Display name', ET_DOMAIN) ?>: </span>
					<span id="content_display_name" class="cnt"><?php echo $user->display_name ?></span>
				</div>
				<span class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
			<a href="#modal_location" data-toggle="modal-edit" class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Location', ET_DOMAIN) ?>: </span>
					<span id="content_user_location" class="cnt"><?php echo $user->user_location ?></span>
				</div>
				<span class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
			<div class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Email', ET_DOMAIN) ?>: </span>
					<span class="cnt"><?php echo $current_user->data->user_email ?></span>
				</div>
				<!-- <a href="#modal_email" data-toggle="modal-edit" class="fe-icon-b-edit fe-icon-b" data-role="none"></a> -->
			</div>
			<a href="#modal_phone" data-toggle="modal-edit" class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Phone', ET_DOMAIN) ?>: </span>
					<span id="content_user_mobile" class="cnt"><?php echo $user->user_mobile ?></span>
				</div>
				<span href="#modal_phone" data-toggle="modal-edit" class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
		</div>
	</div>
	<div class="fe-edit-inline fe-container">
		<input type="checkbox" name="user_hide_info" id="hide_profile" class="fe-checkbox" <?php if ( $user->user_hide_info == 1 ) echo 'checked="checked"'  ?>>
		<label class="fe-checkbox less-space" for="hide_profile"> <?php _e('Check here to show your email and phone') ?></label>
	</div>
	<div class="fe-edit-block fe-container">
		<div class="fe-block-title">
			<?php _e('Social Settings', ET_DOMAIN) ?>
		</div>
		<div class="fe-edit-area">
			<a href="#modal_facebook" data-toggle="modal-edit" class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Facebook', ET_DOMAIN) ?>: </span>
					<span class="cnt" id="content_user_facebook"><?php echo $user->user_facebook ?></span>
				</div>
				<span href="#modal_facebook" data-toggle="modal-edit" class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
			<a href="#modal_twitter" data-toggle="modal-edit" class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Twitter', ET_DOMAIN) ?>: </span>
					<span class="cnt" id="content_user_twitter"><?php echo $user->user_twitter ?></span>
				</div>
				<span href="#modal_twitter" data-toggle="modal-edit" class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
			<a href="#modal_google" data-toggle="modal-edit" class="fe-area-block">
				<div class="fe-area-text">
					<span class="label"><?php _e('Google+') ?>: </span>
					<span class="cnt" id="content_user_gplus"><?php echo $user->user_gplus ?></span>
				</div>
				<span href="#modal_google" data-toggle="modal-edit" class="fe-icon-b-edit fe-icon-b" data-role="none"></span>
			</a>
		</div>
	</div>
</div>
<div class="modals">
	<div class="modal-edit" id="modal_password">
		<form action="" id="form_password">
			<div class="fe-page-heading">
				<ul class="fe-thread-actions pull-right">
					<li class="unfollow">
						<a class="submit-modal" href="#"><?php _e('Submit', ET_DOMAIN) ?></a>
					</li>
				</ul>
				<ul class="fe-thread-actions">
					<li class="unfollow" style="">
						<a class="fe-icon-b fe-icon-b-cancel cancel-modal" href="#"><?php _e('Cancel', ET_DOMAIN) ?></a>
					</li>
				</ul>
			</div>
			<div class="fe-edit-block fe-container">
				<div class="fe-block-title">
					Change password
				</div>
				<div class="fe-edit-area">
					<div class="fe-area-block">
						<input class="fe-input-text" name="old_pass" data-role="none" placeholder="<?php _e('Old password', ET_DOMAIN) ?>" type="text">
					</div>
					<div class="fe-area-block">
						<input class="fe-input-text" name="new_pass" data-role="none" placeholder="<?php _e('New password', ET_DOMAIN) ?>" type="text">
					</div>
					<div class="fe-area-block">
						<input class="fe-input-text" name="re_pass" data-role="none" placeholder="<?php _e('Confirm password', ET_DOMAIN) ?>" type="text">
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php 
	fe_edit_modals(array(
		'about' => array(
			'id' 			=> 'modal_about',
			'title' 		=> __('About', ET_DOMAIN),
			'name' 			=> 'description',
			'value' 		=> strip_tags($user->description),
			'placeholder' 	=> '',
			'type' 			=> 'textarea',
			'target' 		=> '#content_description',
			'hidden_fields' => array(
				'ID' 	=> $current_user->ID,
			)
		),
		'displayname' => array(
			'id' 			=> 'modal_display_name',
			'title' 		=> __('Display name', ET_DOMAIN),
			'name' 			=> 'display_name',
			'value' 		=> $user->display_name,
			'placeholder' 	=> '',
			'type' 			=> 'text',
			'target' 		=> '#content_display_name',
			'hidden_fields' => array(
				'ID' 	=> $current_user->ID,
			)
		),
		'location' => array(
			'id' 			=> 'modal_location',
			'title'			=> __('Location', ET_DOMAIN),
			'name' 			=> 'user_location',
			'value' 		=> $user->user_location,
			'placeholder' 	=> '',
			'type' 			=> 'text',
			'target' 		=> '#content_user_location',
			'hidden_fields' => array(
				'ID' 		=> $current_user->ID,
			)
		),
		'phone' => array(
			'id' 			=> 'modal_phone',
			'title' 		=> __('Phone', ET_DOMAIN),
			'name' 			=> 'user_mobile',
			'value' 		=> $user->user_mobile,
			'placeholder' 	=> '',
			'type' 			=> 'text',
			'target' 		=> '#content_user_mobile',
			'hidden_fields' => array(
				'ID' 	=> $current_user->ID,
			)
		),
		'facebook' 	=> array(
			'id' 			=> 'modal_facebook',
			'title' 		=> __('Facebook', ET_DOMAIN),
			'name' 			=> 'user_facebook',
			'value' 		=> $user->user_facebook,
			'placeholder' 	=> '',
			'type' 			=> 'text',
			'target' 		=> '#content_user_facebook',
			'hidden_fields' => array(
				'ID' 	=> $current_user->ID,
			)
		),
		'twitter' => array(
			'id' 			=> 'modal_twitter',
			'title' 		=> __('Twitter', ET_DOMAIN),
			'name' 			=> 'user_twitter',
			'value' 		=> $user->user_twitter,
			'placeholder' 	=> '',
			'type' 			=> 'text',
			'target' 		=> '#content_user_twitter',
			'hidden_fields' => array(
				'ID' 	=> $current_user->ID,
			)
		),
		'google' => array(
			'id' 			=> 'modal_google',
			'title' 		=> __('Google', ET_DOMAIN),
			'name' 			=> 'user_gplus',
			'value' 		=> $user->user_gplus,
			'placeholder' 	=> '',
			'type' 			=> 'text',
			'target' 		=> '#content_user_gplus',
			'hidden_fields' => array(
				'ID' 	=> $current_user->ID,
			)
		)
	) );
	?>
</div>
<?php 
// footer part
get_template_part( 'mobile/template', 'footer' );

et_get_mobile_footer();
?>