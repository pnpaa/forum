<?php

class ET_ForumEngine extends ET_Base{

	// abstract function on_add_scripts();
	// abstract function on_add_styles();	

	//
	// declare post_types, scripts, styles ... which are uses in theme
	function __construct(){
		parent::__construct();
		global $current_user;

		// disable admin bar if user can not manage options
		if (!current_user_can('manage_options')){
			show_admin_bar(false);
		};

		// register tag
		$this->add_action('init', 'init_theme');
		//$this->add_action('init', 'register_tag');
		$this->add_filter('logout_url', 'logout_home', 10, 2);

		//filter email message template
		$this->add_filter('et_reset_password_link', 'reset_password_link', 10, 3);
		$this->add_filter('et_retrieve_password_message','retrieve_password_message',10,3);
		$this->add_action('et_after_register','user_register_mail', 10 , 2);
		$this->add_action('et_password_reset', 'password_reset_mail',10,2);
		$this->add_action('widgets_init', 'et_widgets_init');

		$this->add_action('after_switch_theme', 'set_default_theme');

		$this->add_action( 'fe_send_following_mail' , 'mail_to_following_users' );

		$this->add_action('after_setup_theme', 'set_static_front_page');
		
		$this->add_filter('map_meta_cap', 'map_meta_cap', 10, 4);

		//short codes
		new FE_Shortcodes();	

		// enqueue script and styles
		if (is_admin()){
			$this->add_action('admin_enqueue_scripts', 'on_add_scripts');
			$this->add_action('admin_print_styles', 'on_add_styles');
		} else {
			$this->add_action('wp_enqueue_scripts', 'on_add_scripts');
			$this->add_action('wp_print_styles', 'on_add_styles');
		}

		// remove action deregister jquery
		//remove_action( 'wp_enqueue_scripts', 'et_deregister_jquery' );
		

		//
		if(et_get_option('twitter_login', false))
			new ET_TwitterAuth();
		if(et_get_option('facebook_login', false)){
			new ET_FaceAuth();
		}
	}
 	public function set_default_theme(){
		if(did_action( 'after_switch_theme' ) === 1){			
			update_option( 'et_auto_expand_replies' , true );
			update_option( 'fe_send_following_mail' , true );
			update_option( 'avatar_default', 'identicon' );
		} 		
 	}		
	/**
	 * Email to following user when thread has new reply
	 */	
 	public function mail_to_following_users(){
		$threads = get_option( 'fe_threads_new_reply' );
		if(!empty($threads)){
			foreach ($threads as $key => $id) {
				$users_follow = explode(',',get_post_meta($id,'et_users_follow',true));
				foreach ($users_follow as $key => $value) {

					$user 		= get_user_by('id',$value);

					$headers  	= 'MIME-Version: 1.0' . "\r\n";
					$headers 	.= 'Content-type: text/html; charset=utf-8' . "\r\n";
					$headers 	.= "From: ".get_option('blogname')." < ".get_option('admin_email') ."> \r\n";
					$subject 	= __("[New Reply]Your thread has a new reply.",ET_DOMAIN);

					$FE_MailTemplate    = new FE_MailTemplate();
					$message	=	$FE_MailTemplate->get_following_thread_mail();

					/* ============ filter placeholder ============ */
					$message  	=	str_ireplace('[display_name]', $user->display_name, $message);
					$message  	=	str_ireplace('[thread_title]', get_the_title($id), $message);
					$message  	=	str_ireplace('[thread_link]', get_permalink($id), $message);
					$message  	=	str_ireplace('[blogname]', get_option('blogname'), $message);
					/* ============ filter placeholder ============ */

					if($user->user_email)
						wp_mail($user->user_email, $subject , $message, $headers);	
				}
			}
			update_option( 'fe_threads_new_reply' , array() );
		}
	}
	public function set_static_front_page(){
		$front_id  = get_option('page_on_front');

		if ( empty($front_id) ){
			$front = wp_insert_post(array(
				'post_status' => "publish",
				'post_type'   => 'page',
				'post_title'  => __('Threads Index',ET_DOMAIN)
			));
			update_option( 'page_on_front' , $front );
		}

		$posts_id  = get_option('page_for_posts');

		if (empty( $posts_id )){
			$post = wp_insert_post(array(
				'post_status' => "publish",
				'post_type'   => 'page',
				'post_title'  => __('Blog',ET_DOMAIN)
			));
			update_option( 'page_for_posts' , $post );
		}

		update_option( 'show_on_front' , "page" );  
	}

	public function et_widgets_init(){
		register_widget('FE_Thread_Category_Widget');
		register_widget('FE_Thread_Discuss_Widget');
		register_widget('FE_Thread_Hot_Widget');
		register_widget('FE_Statistic_Widget');
		register_widget('FE_Top_Users_Widget');
	}
	public function retrieve_password_message($message , $active_key , $user_data) {
		$user_login 	=   $user_data->user_login;
		$FE_MailTemplate    = new FE_MailTemplate();
		$forgot_message	=	$FE_MailTemplate->get_forgot_pass_mail();
		$activate_url	= apply_filters('et_reset_password_link',  network_site_url("wp-login.php?action=rp&key=$active_key&login=" . rawurlencode($user_login), 'login'), $active_key, $user_login );
		
		$forgot_message	=	et_filter_authentication_placeholder ( $forgot_message, $user_data->ID );
		$forgot_message	=	str_ireplace('[activate_url]', $activate_url, $forgot_message);

		return $forgot_message;
	}
	public function password_reset_mail ( $user, $new_pass ) {
		$FE_MailTemplate    = new FE_MailTemplate();
		$new_pass_msg	=	$FE_MailTemplate->get_reset_pass_mail();
		
		$new_pass_msg	=	et_filter_authentication_placeholder($new_pass_msg, $user->ID);
		$new_pass_msg 	=	str_ireplace('[site_url]', home_url(), $new_pass_msg);
		//$new_pass_msg 	=	str_ireplace('[user_login]', $user->user_login, $new_pass_msg);
		
		$subject 		=	apply_filters('et_reset_pass_mail_subject',__('Password updated successfully!', ET_DOMAIN));

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= "From: ".get_option('blogname')." < ".get_option('admin_email') ."> \r\n";
		
		//$new_pass_msg	=	et_get_mail_header().$new_pass_msg.et_get_mail_footer();
		wp_mail($user->user_email, $subject , $new_pass_msg, $headers);	
	}
	public function user_register_mail( $user_id, $role = false) {
		
		update_user_meta( $user_id, 'et_like_count', 0 );

		$user			=   new WP_User($user_id);
		
		$user_email		=	$user->user_email;
		$FE_MailTemplate		=	new FE_MailTemplate();
		$register_mail	=	$FE_MailTemplate->get_register_mail();
		
		$register_mail	=	et_filter_authentication_placeholder ( $register_mail, $user_id );
		$subject		=	sprintf(__("Congratulations! You have successfully registered to %s.",ET_DOMAIN),get_option('blogname'));

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= "From: ".get_option('blogname')." < ".get_option('admin_email') ."> \r\n";

		//$register_mail	=	et_get_mail_header().$register_mail.et_get_mail_footer();

		wp_mail($user_email, $subject , $register_mail, $headers) ;
		
	}

	public function init_theme(){
		// post type
		FE_Threads::init();
		FE_Replies::init();
		FE_Member::init();

		//Register pages template
		et_register_page_template(array(
			'following' 		=> __('Following Threads', ET_DOMAIN), 
			'pending' 			=> __('Pending Threads', ET_DOMAIN),
			'edit-profile' 		=> __('Edit Profile', ET_DOMAIN),
			'change-pass' 		=> __('Change Password', ET_DOMAIN),
			'new-pass' 			=> __('Create New Password', ET_DOMAIN),
			'social-connect'	=> __('Authentication', ET_DOMAIN),
			'member' 			=> __('Member', ET_DOMAIN),
			'term-condition'	=> __('Terms & Conditions', ET_DOMAIN),
		));

		// generate predefined template pages
		et_generate_templates();
		// register footer menu
		register_nav_menus ( array (
			'et_footer'	=>	__('Menu display on the footer',ET_DOMAIN)		
		));		
		// register new post status: closed
		register_post_status( 'closed', array(
			'label' 				=> __('Closed',ET_DOMAIN),
			'public' 				=> true,
			'exclude_from_search' 	=> false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count'               =>  _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>' ),
		) );

		//sidebars
		register_sidebar( array(
			'name' 			=> __('Homepage Sidebar', ET_DOMAIN),
			'id' 			=> 'fe-homepage-sidebar',
			'description' 	=> __("Display widgets in homepage's sidebar", ET_DOMAIN)
		) );
		register_sidebar( array(
			'name' 			=> __('Single Thread Sidebar', ET_DOMAIN),
			'id' 			=> 'fe-single-thread-sidebar',
			'description' 	=> __("Display widgets in single thread's sidebar", ET_DOMAIN)
		) );	
		register_sidebar( array(
			'name' 			=> __('Single Post Sidebar', ET_DOMAIN),
			'id' 			=> 'fe-single-post-sidebar',
			'description' 	=> __("Display widgets in single post's sidebar", ET_DOMAIN)
		) );
		register_sidebar( array(
			'name' 			=> __('Blog Sidebar', ET_DOMAIN),
			'id' 			=> 'fe-blog-sidebar',
			'description' 	=> __("Display widgets in blog's sidebar", ET_DOMAIN)
		) );					
		register_sidebar( array(
			'name' 	=> __('All Pages Sidebar', ET_DOMAIN),
			'id' 	=> 'fe-allpage-sidebar',
			'description' 	=> __("Displays widgets in every page's sidebar", ET_DOMAIN)
		) );


		// run activation in first run
		// echo '<pre>';
		// print_r(get_role('moderator'));
		// echo '</pre>';
		// update_option( 'forumengine_activation', 0 ); // reset activation
		// self::removeDefaulyRoles();
		if ( !get_option( 'forumengine_activation' ) ){

			// run functions for theme first activation
			$this->first_activation();

			// disable first activation hook
			update_option( 'forumengine_activation', 1 );
		}
	}

	public function first_activation(){
		self::setupDefaultRoles();
	}

	public function map_meta_cap($caps, $cap, $user_id, $args){

		if ( 'edit_threads' == $cap || 'delete_threads' == $cap || 'read_threads' == $cap ) {
			if (empty($args[0])) return $caps;

			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->post_type );
			$caps = array();			
		}

		if ( 'edit_threads' == $cap ) {
			if ( $user_id == $post->post_author ){
				$caps[] = $post_type->cap->edit_posts;
				$caps[] = 'edit_threads';
			}
			else{
				$caps[] = $post_type->cap->edit_others_posts;
				$caps[] = 'edit_others_threads';
			}
			
		}

		elseif ( 'delete_threads' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}

		elseif ( 'read_threads' == $cap ) {
			if ( 'private' != $post->post_status )
				$caps[] = 'read';
			elseif ( $user_id == $post->post_author )
				$caps[] = 'read';
			else
				$caps[] = $post_type->cap->read_private_posts;
		}

		return $caps;

	}

	static function setupDefaultRoles(){
		// add role
		if ( !get_role('moderator') ){
			// get subcribers capabilities
			$subscriber 	= get_role('subscriber');
			$caps 			= $subscriber->capabilities;

			// add some special cap
			$modcaps = array(
				'manage_threads' 		=> 1,
				'trash_threads' 		=> 1,
				'trash_others_threads' 	=> 1,
				'publish_threads' 		=> 1,
				'edit_others_threads' 	=> 1,
				'edit_thread' 			=> 1,			
				'delete_threads' 		=> 1,
				'delete_others_threads' => 1,
				'delete_thread' 		=> 1,
				'read_private_threads' 	=> 1,

				'edit_reply' 			=> 1,
				'trash_others_replies' 	=> 1,
				'edit_others_replies' 	=> 1,
				'delete_other_replies' 	=> 1,
				'read_private_replies' 	=> 1,
				'close_threads' 		=> 1,
			);
			$subcaps = array(
				'edit_threads' 		=> 1,
				'edit_thread' 		=> 1,
				'edit_replies' 		=> 1,
				'edit_reply' 		=> 1,
				'trash_replies' 	=> 1,
				'delete_replies' 	=> 1,
				'delete_reply' 		=> 1,
				'read_thread' 		=> 1,
				'read_reply' 		=> 1
			);

			$caps 		= (array)$caps + $modcaps + $subcaps;
			$newcaps 	= $modcaps + $subcaps;
			add_role( 'moderator', __('Moderator'), $caps );

			// add caps to admin
			$admin_role = get_role('administrator');
			foreach ($newcaps as $cap => $value) {
				$admin_role->add_cap($cap);
			}

			// add user cap
			foreach ($subcaps as $cap => $value) {
				$subscriber->add_cap($cap);
			}
		} 
	}

	static function removeDefaulyRoles(){
		// add some special cap
		$modcaps = array(
			'manage_threads' 		=> 1,
			'trash_threads' 		=> 1,
			'trash_others_threads' 	=> 1,
			'publish_threads' 		=> 1,
			'edit_others_threads' 	=> 1,
			'edit_thread' 			=> 1,			
			'delete_threads' 		=> 1,
			'delete_others_threads' => 1,
			'delete_thread' 		=> 1,
			'read_private_threads' 	=> 1,

			'edit_reply' 			=> 1,
			'trash_others_replies' 	=> 1,
			'edit_others_replies' 	=> 1,
			'delete_other_replies' 	=> 1,
			'read_private_replies' 	=> 1,
			'close_threads' 		=> 1,
		);
		$subcaps = array(
			'edit_threads' 		=> 1,
			'edit_thread' 		=> 1,
			'edit_replies' 		=> 1,
			'edit_reply' 		=> 1,
			'trash_replies' 	=> 1,
			'delete_replies' 	=> 1,
			'delete_reply' 		=> 1,
			'read_thread' 		=> 1,
			'read_reply' 		=> 1
		);
		$allcaps = $modcaps + $subcaps;

		remove_role('moderator');

		$admin 		= get_role('administrator');
		$subscriber = get_role('subscriber');
		foreach ($allcaps as $cap => $value) {
			// remove cap in admin role
			$admin->remove_cap($cap);
			// remove cap in subscriber role
			if ( array_key_exists($cap, $subcaps) ){
				$subscriber->remove_cap($cap);
			}
		}
	}

	public function on_add_scripts(){
		global $current_user;
		// deregister javascript
		//wp_deregister_script( 'backbone' );
		//wp_register_script( 'backbone', FRAMEWORK_URL . '/js/lib/backbone-min.js' );
		//wp_deregister_script( 'underscore' );
		//wp_register_script( 'underscore', FRAMEWORK_URL . '/js/lib/underscore-min.js' );

		$isEditable = current_user_can( 'manage_threads' );
		$variables = array(
			'ajaxURL' 			=> admin_url('/admin-ajax.php'),
			'imgURL' 			=> TEMPLATEURL.'/img/',
			'posts_per_page' 	=> get_option('posts_per_page'),
			'isEditable'	 	=> $isEditable,
			'searchSummary' 	=> __('View all {{count}} results', ET_DOMAIN),
			'homeURL' 			=> home_url(),
			'plupload_config'			=> array(
				'max_file_size' 		=> '3mb',
				'url' 					=> admin_url('admin-ajax.php'),
				'flash_swf_url' 		=> includes_url('js/plupload/plupload.flash.swf'),
				'silverlight_xap_url'	=> includes_url('js/plupload/plupload.silverlight.xap'),
			)
		);
		?>
		<script type="text/javascript">
			fe_globals = <?php echo json_encode($variables) ?>
		</script>
		<?php 
	}
	public function on_add_styles(){}

	/**
	 * Write some method specified for forumengine only ...
	 */
	 
	public function logout_home($logouturl, $redir)
	{
		$redir = get_option('siteurl');
		return $logouturl . '&amp;redirect_to=' . urlencode($redir);
	}
	public function reset_password_link($link, $key, $user_login){
		return et_get_page_link('reset-password', array('user_login' => $user_login, 'key' => $key));
	}	
}

class FE_Shortcodes{
	public function __construct(){
		$this->add_shortcode( 'img', 'img' );
		$this->add_shortcode( 'quote', 'quote' );
		$this->add_shortcode( 'code', 'code' );

		do_action('et_add_shortcodes');
	}

	function img($atts, $content = ""){
		return '<img src="' . $content . '">';
	}

	function code($atts, $content = ''){
		extract( shortcode_atts( array(
				'type' => 'php',
				'start' => 1,
				'highlight'=> ''
			), $atts ) );
		$content = preg_replace('#<br\s*/?>#i', "\n", $content);
		$content = str_replace("<br>", "\n", $content);
		$content = str_replace("<p></p>", "", $content);
		$content = str_replace("<p>", "", $content);
		$content = str_replace("</p>", "", $content);
		return '<pre class="ruler: true;brush: '.$type.';toolbar: false;highlight: ['.$highlight.'];first-line: '.$start.';">'.do_shortcode( $content ).'</pre>';
	}

	function quote($atts, $content = ''){
		extract( shortcode_atts( array(
				'author' => 'John Smith',
			), $atts ) );		
		return '<blockquote>' . do_shortcode( $content ) . '<span class="quote-author">'.__('From',ET_DOMAIN).' <strong>'.$author.'</strong></span></blockquote>';
	}

	private function add_shortcode($name, $callback){
		add_shortcode( $name, array($this, $callback) );
	}
}

/**
 * process uploaded image: save to upload_dir & create multiple sizes & generate metadata
 * @param  [type]  $file     [the $_FILES['data_name'] in request]
 * @param  [type]  $author   [ID of the author of this attachment]
 * @param  integer $parent=0 [ID of the parent post of this attachment]
 * @param  array [$mimes] [array of supported file extensions]
 * @return [int/WP_Error]	[attachment ID if successful, or WP_Error if upload failed]
 * @author anhcv
 */
function et_process_file_upload( $file, $author=0, $parent=0, $mimes=array() ){

	global $user_ID;
	$author = ( 0 == $author || !is_numeric($author) ) ? $user_ID : $author;
	//print_r($file);
	if( isset($file['name']) && $file['size'] > 0 && $file['size'] < 1024*1024){

		// setup the overrides
		$overrides['test_form']	= false;
		if( !empty($mimes) && is_array($mimes) ){
			$overrides['mimes']	= $mimes;
		}
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		// this function also check the filetype & return errors if having any
		$uploaded_file	=	wp_handle_upload( $file, $overrides );

		//if there was an error quit early
		if ( isset( $uploaded_file['error'] )) {
			return new WP_Error( 'upload_error', $uploaded_file['error'] );
		}
		elseif(isset($uploaded_file['file'])) {

			// The wp_insert_attachment function needs the literal system path, which was passed back from wp_handle_upload
			$file_name_and_location = $uploaded_file['file'];

			// Generate a title for the image that'll be used in the media library
			$file_title_for_media_library = preg_replace('/\.[^.]+$/', '', basename($file['name']));

			$wp_upload_dir = wp_upload_dir();

			// Set up options array to add this file as an attachment
			$attachment = array(
				'guid'				=> $uploaded_file['url'],
				'post_mime_type'	=> $uploaded_file['type'],
				'post_title'		=> $file_title_for_media_library,
				'post_content'		=> '',
				'post_status'		=> 'inherit',
				'post_author'		=> $author
			);

			// Run the wp_insert_attachment function. This adds the file to the media library and generates the thumbnails. If you wanted to attch this image to a post, you could pass the post id as a third param and it'd magically happen.
			$attach_id = wp_insert_attachment( $attachment, $file_name_and_location, $parent );
			
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );
			wp_update_attachment_metadata($attach_id,  $attach_data);
			return $attach_id;

		} else { // wp_handle_upload returned some kind of error. the return does contain error details, so you can use it here if you want.
			return new WP_Error( 'upload_error', __( 'There was a problem with your upload.', ET_DOMAIN ) );
		}
	}
	else { // No file was passed
		return new WP_Error( 'upload_error', __( 'Image\'s size upload must be less than 1MB!', ET_DOMAIN ) );
	}
}
/**
 * Print the content with shortcode
 */
function et_get_unread_follow(){
	global $user_ID;
	$data = array();
	if($user_ID){
		$unreads =  get_user_meta( $user_ID, 'et_unread_threads',true);
		$follows =  get_user_meta( $user_ID, 'et_following_threads',true);
		$follows_ur = array();
		
		if(!empty($follows)){
			foreach ($follows as $key => $value) {
				if(in_array($value, $unreads['data']))
					array_push($follows_ur, $value);
			}			
		}
		$data['unread'] = (array)$unreads;
		$data['follow'] = $follows_ur;
	}	
	return $data;
}
/**
 * Print the content with shortcode
 */
function et_the_content($more_link_text = null, $stripteaser = false){
	$content = get_the_content($more_link_text, $stripteaser);
	$content = apply_filters( 'et_the_content', $content );
	$content = str_replace(']]>', ']]&gt;', $content);
	echo $content;
}

add_filter('et_the_content', 'et_the_content_filter');
function et_the_content_filter($content){
	add_filter('the_content', 'do_shortcode', 11);
	$content = apply_filters( 'the_content', $content );
	remove_filter('the_content', 'do_shortcode');	
	return $content;
}
// add_filter( 'the_content', 'cn_nf_url_parse');
// function cn_nf_url_parse( $content ) {

// 	$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
// 	if(preg_match_all("/$regexp/siU", $content, $matches, PREG_SET_ORDER)) {
// 		if( !empty($matches) ) {
			
// 			$srcUrl = get_option('siteurl');
// 			for ($i=0; $i < count($matches); $i++)
// 			{
			
// 				$tag = $matches[$i][0];
// 				$tag2 = $matches[$i][0];
// 				$url = $matches[$i][0];
				
// 				$noFollow = '';

// 				$pattern = '/target\s*=\s*"\s*_blank\s*"/';
// 				preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
// 				if( count($match) < 1 )
// 					$noFollow .= ' target="_blank" ';
					
// 				$pattern = '/rel\s*=\s*"\s*[n|d]ofollow\s*"/';
// 				preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
// 				if( count($match) < 1 )
// 					$noFollow .= ' rel="nofollow" ';
			
// 				$pos = strpos($url,$srcUrl);
// 				if ($pos === false) {
// 					$tag = rtrim ($tag,'>');
// 					$tag .= $noFollow.'>';
// 					$content = str_replace($tag2,$tag,$content);
// 				}
// 			}
// 		}
// 	}
	
// 	$content = str_replace(']]>', ']]&gt;', $content);
// 	return $content;

// }
/**
 * Get editor default settings
 * @param array $args overwrite settings
 */
function editor_settings($args = array()){
	$buttons = apply_filters( 'fe_editor_buttons' , 'bold,italic,underline,|,bullist,|,felink,unlink,|,feimage,fecode' );
	return array(
	'quicktags' 	=> false,
	'media_buttons' => false,
	'tabindex' 		=> 5,
	'textarea_name' => 'post_content',
	'tinymce' 		=> array(
		'height' 		=> 150,
		'autoresize_min_height'=> 150,
		'force_p_newlines' => false,
		'force_br_newlines' => false,
		'forced_root_block' => '',
		'theme_advanced_buttons1' => $buttons,
		'theme_advanced_buttons2' => '',
		'theme_advanced_buttons3' => '',
		'theme_advanced_statusbar_location' => 'none',
		'setup' => 'function(ed) {
		  ed.onKeyUp.add(function(ed, e) {
		  	  if ( typeof hasChange == "undefined" ) {
			    hasChange = true;
			  }			  	
			  jQuery("#thread_preview div.text-detail").html(feHelper.parseBBCode(ed.getContent()));
		  });
		  ed.onPaste.add(function(ed, e) {
		  	  if ( typeof hasChange == "undefined" ) {
			    hasChange = true;
			  }			  	
			  jQuery("#thread_preview div.text-detail").html(feHelper.parseBBCode(ed.getContent()));
		  });	      	      	      	      	
	   }'
	));
}

function et_modify_breabcrumb($breadcrumb, $args){
	extract($args);
	if ( $class != '' ) $class = 'class="' . $class . '"';
	if ( $id != '' ) $id = 'id="' . $id . '"';
	if ( $item_class != '') $item_class = 'class="' . $item_class . '"';

	if ( is_tax( 'thread_category' ) || is_singular( 'thread' ) ){
		$breadcrumb = '';
		$breadcrumb .= '<ul class="breadcrumbs">';
		global $post;
		$terms = get_the_terms( $post->ID, 'thread_category' );

		if(!empty($terms)){
			foreach ($terms as $term) {
				$breadcrumb .= "<li $item_class><a href='" . get_term_link( $term, 'thread_category' ) . "'> $term->name</a></li>";
				break;
			}
			//the_terms( $post->ID, 'thread_category', '', '</li><li ' . $item_class .'> ' );	
		} else {
			$breadcrumb .= "<li $item_class>" . __( 'No Category', ET_DOMAIN ) . '</li>';
		}
		
		if ( is_singular( 'thread' ) ){
			$breadcrumb .= '<li ' . $item_class .' >';
			$breadcrumb .= get_the_title();
			$breadcrumb .= '</li>';
		}
		$breadcrumb .= '</ul>';
	}
	return $breadcrumb;
}
//add_action('et_get_breadcrumb', 'et_modify_breabcrumb', 10, 2);

function et_filter_authentication_placeholder ($content, $user_id) {
		$user 		=	new WP_User ($user_id);

		$content 	=	str_ireplace('[user_login]', $user->user_login, $content); 
		$content 	=	str_ireplace('[user_name]', $user->user_login, $content); 
		$content 	=	str_ireplace('[user_nicename]',ucfirst( $user->user_nicename ), $content);
		$content 	=	str_ireplace('[user_email]', $user->user_email, $content);
		$content 	=	str_ireplace('[blogname]', get_bloginfo( 'name' ), $content);
		$content 	=	str_ireplace('[display_name]', ucfirst( $user->display_name ), $content);
		$content 	=	str_ireplace('[company]', ucfirst( $user->display_name ) , $content);
		$content 	=	str_ireplace('[dashboard]', et_get_page_link('dashboard'), $content);

		return $content; 
}

//check user can edit thread / reply or not 
function user_can_edit($post){
	global $user_ID,$current_user;

	$now =  strtotime(current_time( 'mysql' ));
	$time_to_edit = apply_filters( 'fe_time_to_edit', 300 );

	if( current_user_can( "manage_threads" ) || ($user_ID == $post->post_author && ($now - strtotime($post->post_date) <= $time_to_edit))) return true;
	else return false;
}

?>
