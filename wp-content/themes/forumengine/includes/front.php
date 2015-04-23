<?php 

class ET_ForumFront extends ET_ForumEngine{

	/**
	 * Init 
	 */
	public function __construct(){
		parent::__construct();
		// posts
		new ET_ForumFrontPost();
		$time_send_mail = apply_filters( 'fe_time_send_mail' , 120 );

		if(get_option( 'fe_send_following_mail' )){
			wp_schedule_single_event( time() + $time_send_mail, 'fe_send_following_mail' );
		} else {
			wp_clear_scheduled_hook( 'fe_send_following_mail' );
		}

		//$this->add_action( 'wp', 'prefix_setup_schedule' );

		$this->add_filter( 'query_vars', 'query_vars' );

		$this->add_filter('author_link', 'modify_author_link', 10, 3);

		$this->add_filter('the_editor_content','custom_editor_style');

		$this->add_filter('mce_external_plugins','tinymce_add_plugins');

		$this->add_filter("request", "filter_request_feed");

		$this->add_action('pre_get_posts', 'forum_main_query');

		$this->add_action('init', 'frontend_init');

		$this->add_action('template_redirect', 'handle_unread_threads');

		$this->add_action('wp_footer', 'footer');

		$this->add_action('wp_footer', 'script_templates');

		$this->add_action('et_get_breadcrumb', 'edit_breadcrumbs', 10, 2);

		//$this->add_filter('cron_schedules','addCronMinutes');
		$this->add_action( 'fe_user_badge', 'add_user_badges' );		
		$this->add_filter( 'fe_user_badge_2', 'modify_user_badge', 10, 2 );		
	}		

	public function add_user_badges($post_author){

		if(!get_option( 'user_badges' ))
			return false;

		$badges = get_option( 'fe_user_badges' );
		$user_role = get_user_role($post_author);
			if(isset($badges[$user_role]) && $user_role){

		?>
		<span class="user-badge"><?php echo $badges[$user_role] ?></span>
		<?php
		}
	}

	public function modify_user_badge($badge, $author){

		if(!get_option( 'user_badges' ))
			return false;

		$badges = get_option( 'fe_user_badges' );
		$user_role = get_user_role($author);
		if(isset($badges[$user_role]) && $user_role){
			return '<span class="user-badge">'.$badges[$user_role].'</span>';
		}
	}
	public function filter_request_feed($request) {
	    if (isset($request['feed']) && !isset($request['post_type'])):
	        $request['post_type'] = array("thread");
	    endif;

	    return $request;
	}

	public function frontend_init($wp_rewrite){
		global $wp_rewrite;
		add_rewrite_rule( 'member/([^/]+)/?$', 'index.php?pagename=member&member=$matches[1]', 'top' );
		add_rewrite_rule( 'member/([^/]+)/page/([0-9]{1,})/?$', 'index.php?pagename=member&member=$matches[1]&paged=$matches[2]', 'top' );

		// modify the "search threads" link
		$search_slug = apply_filters( 'search_thread_slug', 'search-threads' );
		add_rewrite_rule( $search_slug . '/([^/]+)/?$', 'index.php?s=$matches[1]&post_type=thread', 'top' );
		add_rewrite_rule( $search_slug . '/([^/]+)/page/([0-9]{1,})/?$', 'index.php?s=$matches[1]&post_type=thread&paged=$matches[2]', 'top' );

		$rules = get_option( 'rewrite_rules' );
		if ( !isset($rules['member/([^/]+)/?$']) ){
			$wp_rewrite->flush_rules();
		}
		
		if( !get_option( 'fe_pre_theme' )){
			$theme = wp_get_theme();
			update_option( 'fe_pre_theme' , $theme->get( 'Version' ));
		}		
	}
	public function query_vars($vars){
		$vars[] = 'member';
		return $vars;
	}

	public function modify_author_link($link, $author_id, $author_nicename){
		$return = str_replace('/'.'author/', '/'.'member/', $link);
		return $return;
	}

	public function footer(){
		global $current_user;
		?>
		<script type="text/javascript" id="current_user">
		 	currentUser = <?php 
		 	if ($current_user->ID)
		 		echo json_encode(FE_Member::convert($current_user)); 
		 	else 
		 		echo json_encode(array('id' => 0, 'ID' => 0)); 
		 	?>
		</script>
		<?php
	}

	public function edit_breadcrumbs( $breadcrumb, $args){
		extract($args);
		if ( $class != '' ) $class = 'class="' . $class . '"';
		if ( $id != '' ) $id = 'id="' . $id . '"';
		if ( $item_class != '') $item_class = 'class="' . $item_class . '"';

		if ( is_tax( 'thread_category' ) || is_singular( 'thread' ) ){
			$breadcrumb = '';
			$breadcrumb .= '<ul class="breadcrumbs">';
			$breadcrumb .= "<li class='icon'><a href='" . home_url() .  "'>" . __('Home', ET_DOMAIN) ."</a></li>";
			global $post;
			$terms = get_the_terms( $post->ID, 'thread_category' );

			if(!empty($terms)){
				foreach ($terms as $term) {
					$breadcrumb .= "<li $item_class><a href='" . get_term_link( $term, 'thread_category' ) . "'> $term->name</a></li>";
					break;
				}
			} else {
				$breadcrumb .= "<li $item_class>" . __( 'No Category', ET_DOMAIN ) . '</li>';
			}
			
			if ( is_singular( 'thread' ) ){
				$breadcrumb .= '<li ' . $item_class .' >';
				$breadcrumb .= get_the_title();
				$breadcrumb .= '</li>';
			}
			$breadcrumb .= '</ul>';
			return $breadcrumb;
		}

		if (is_home()){
			$breadcrumb = '';
			$breadcrumb .= '<ul class="breadcrumbs">';
			$breadcrumb .= '<li class="icon"><a href="'. home_url() .'">' . __('Home', ET_DOMAIN) . '</a></li>';
			$breadcrumb .= '<li class="icon">' . __('Blog', ET_DOMAIN) . '</li>';
			$breadcrumb .= '</ul>';
			return $breadcrumb;
		}
		return $breadcrumb;
	}

	/**
	 * Front end editor styling
	 */
	public function custom_editor_style($content){
    	// This is for front-end tinymce customization
	    if ( ! is_admin() ) {
	        global $editor_styles;
	        $editor_styles = (array) $editor_styles;
	        $stylesheet    = array();

	        $stylesheet[] = 'css/editor.css';

	        $editor_styles = array_merge( $editor_styles, $stylesheet );
	    }
	    return $content;
	}

	public function on_add_scripts(){
		parent::on_add_scripts();

		// default scripts: jquery, backbone, underscore
		$this->add_existed_script('jquery');
		$this->add_script('bootstrap', TEMPLATEURL . '/js/libs/bootstrap.min.js');
		$this->add_existed_script('underscore');
		$this->add_existed_script('backbone');
		$this->add_script('modernizr', TEMPLATEURL . '/js/libs/modernizr.js', array('jquery'));

		$this->add_script('jquery-validator', TEMPLATEURL . '/js/libs/jquery.validate.min.js','jquery');
		$this->add_script('site-script', TEMPLATEURL . '/js/script.js', 'jquery');
		$this->add_script('site-functions', TEMPLATEURL . '/js/functions.js',array('jquery', 'backbone', 'underscore'));
		$this->add_script('site-front', TEMPLATEURL . '/js/front.js', array('jquery', 'underscore', 'backbone', 'site-functions'));
		
		//localize scripts
		$front_texts = array(
			'form_login'	=> array(
				'error_msg'					=> __("Please fill out all fields required.", ET_DOMAIN),
				'error_user'				=> __("Please enter your user name.", ET_DOMAIN),
				'error_email'				=> __("Please enter a valid email address.", ET_DOMAIN),
				'error_username'			=> __("Please enter a valid username.", ET_DOMAIN),
				'error_repass'				=> __("Please enter the same password as above.", ET_DOMAIN),
				'error_url'					=> __("Please enter a valid URL.", ET_DOMAIN),
				'error_cb'					=> __("You must accept the term & privacy.", ET_DOMAIN),
			),
			'form_thread'	=> array(
				'close_tab' => __("You have made some changes which you might want to save.", ET_DOMAIN),
				'delete_thread' => __("Are you sure want to delete this thread?", ET_DOMAIN),
				'login_2_follow' => __("You must log in to follow this thread", ET_DOMAIN),
			),
			'texts' => array(
				'hide_preview' => __("Hide Preview", ET_DOMAIN),
				'show_preview' => __("Show Preview", ET_DOMAIN),
				'create_topic' => __("Create Topic", ET_DOMAIN),
				'upload_images' => __("Upload Images", ET_DOMAIN),
				'insert_codes' => __("Insert Codes", ET_DOMAIN),
				'no_file_choose' => __("No file chosen.", ET_DOMAIN),
				)
		);		
		wp_localize_script( 'site-front', 'fe_front', $front_texts );

		if (is_front_page() || is_singular( 'thread' ) || is_tax()){
			$this->add_script('fe-upload-images', TEMPLATEURL . '/js/upload-images.js', array('jquery', 'backbone', 'underscore'));
			wp_deregister_script('wplink');
			$this->add_script('tinymce-link', TEMPLATEURL . '/js/plugins/felink/wplink_clone.js');				
			wp_enqueue_script( 'plupload-all' );
		}		

		if(is_page_template( 'page-edit-profile.php' ) || is_page_template( 'page-change-pass.php' ))	{
			$this->add_script('fe-upload-images', TEMPLATEURL . '/js/upload-images.js');
			$this->add_script('edit-profile', TEMPLATEURL . '/js/edit-profile.js');
			wp_enqueue_script( 'plupload-all' );
		}
		
		// enqueue page javasript
		if (is_front_page() || is_tax()){
			$this->add_script('fe-index', TEMPLATEURL . '/js/index.js', array('jquery', 'backbone', 'underscore'));
		}
		else if (is_home() || is_category()){
			$this->add_script('fe-blog', TEMPLATEURL . '/js/blog.js', array('jquery', 'backbone', 'underscore'));
		}
		else if (is_singular( 'thread' )){

			$this->add_script('fe-shcore', TEMPLATEURL . '/js/libs/syntaxhighlighter/shCore.js', array('jquery'));
			$this->add_script('fe-brush-js', TEMPLATEURL . '/js/libs/syntaxhighlighter/shBrushJScript.js', array('jquery', 'fe-shcore'));
			$this->add_script('fe-brush-php', TEMPLATEURL . '/js/libs/syntaxhighlighter/shBrushPhp.js', array('jquery', 'fe-shcore'));
			$this->add_script('fe-brush-css', TEMPLATEURL . '/js/libs/syntaxhighlighter/shBrushCss.js', array('jquery', 'fe-shcore'));
			
			$this->add_script('fe-single-thread', TEMPLATEURL . '/js/single-thread.js', array('jquery', 'backbone', 'underscore'));

		} 
		else if (is_page_template( 'page-pending.php' )){
			$this->add_script('fe-page-pending', TEMPLATEURL . '/js/page-pending.js');
		}
		else if (is_author() || is_page_template('page-member.php' )){
			$this->add_script('fe-author', TEMPLATEURL . '/js/author.js');
		}	
		else if (is_search()){
			$this->add_script('fe-search', TEMPLATEURL . '/js/search.js');
		}				
		else if ( is_page_template( 'page-following.php' ) ){
			$this->add_script('fe-page-following', TEMPLATEURL . '/js/page-following.js');
		}
		else if ( is_page_template( 'page-reset-password.php' ) ){
			$this->add_script('fe-page-reset-password', TEMPLATEURL . '/js/reset-password.js');
		} 
		else if ( is_single() ){
			$this->add_script('fe-single-blog', TEMPLATEURL . '/js/single.js', array('jquery', 'underscore', 'backbone'));
		}
	}

	public function on_add_styles(){
		parent::on_add_styles();
		$this->add_style( 'fe-bootstrap', TEMPLATEURL . '/css/bootstrap.css');
		$this->add_style( 'fe-mainstyle', TEMPLATEURL . '/css/custom.css', array('fe-bootstrap'));
		$this->add_style( 'fe-tablet', TEMPLATEURL . '/css/tablet.css');
		$this->add_style( 'fe-editor', TEMPLATEURL . '/css/editor-container.css');
		$this->add_style( 'fe-customizer', TEMPLATEURL . '/css/customizer.css');
		$this->add_style( 'fe-customizer-mobile', TEMPLATEURL . '/css/custom-mobile.css');
		$this->add_style( 'fe-ie', TEMPLATEURL . '/css/ie.css');
		$this->add_style( 'fe-style', get_stylesheet_uri());
		
		if(is_singular( 'thread' ))
			$this->add_style('fe-shstyle', TEMPLATEURL . '/css/shCoreDefault.css');

		global $wp_styles;
		$wp_styles->add_data('fe-ie', 'conditional', 'lt IE 9');

		do_action('fe_after_print_styles');
	}

	/**
	 * Hook into pre get posts
	 */
	public function forum_main_query($query){
		// Cancel if it is not main query
		global $wp_rewrite;

		if (!$query->is_main_query())
			return $query;

		if(is_tax()){
			$this->add_filter('posts_join', '_thread_join');
			$this->add_filter("posts_orderby", "_thread_orderby");			
		}
		
		if ( is_search() || isset($_GET['s']) ){
			
			global $wp_query;
			$wp_query->is_search = true;

			if (get_query_var( 's' )){
				global $et_query;
				$et_query['s'] = explode(' ', urldecode(get_query_var( 's' )));
				$query->set('s', '');
			}

			remove_all_filters( 'posts_join' );
			remove_all_filters( 'posts_where' );
			//remove_all_filters( 'posts_orderby' );
			add_filter('posts_distinct', array('FE_Threads', 'query_distinct'));
			add_filter('posts_join', array('FE_Threads', 'query_reply_join'));
			add_filter('posts_where', array('FE_Threads', 'query_reply_where'));
			$this->add_filter('posts_join', '_thread_join');
			$this->add_filter("posts_orderby", "_thread_orderby");
			//add_filter('posts_orderby', array('FE_Threads', 'query_reply_orderby'));

			$query->set('post_type', 'thread');
		}

		if (is_preview()){
			$query->is_preview = true;
			return $query;
		}
		return $query;

	}

	public static function _thread_join($join){
		global $wpdb;
		$join .= " LEFT JOIN {$wpdb->postmeta} as updated_date ON updated_date.post_id = {$wpdb->posts}.ID AND updated_date.meta_key = 'et_updated_date'";		
		return $join;
	}

	public static function _thread_orderby($orderby){
		global $wpdb;
		$orderby = " updated_date.meta_value DESC, {$wpdb->posts}.post_date DESC";
		return $orderby;
	}
	/**
	 * Add new plugin for TinyMCE
	 */	
	public function tinymce_add_plugins($plugin_array){
	    $feimage = TEMPLATEURL . '/js/plugins/feimage/editor_plugin.js';
	    $felink = TEMPLATEURL . '/js/plugins/felink/editor_plugin.js';
	    $fecode = TEMPLATEURL . '/js/plugins/fecode/editor_plugin.js';
	    $autoresize = TEMPLATEURL . '/js/plugins/autoresize/editor_plugin.js';
	    
	    $plugin_array['fecode'] = $fecode;
	    $plugin_array['feimage'] = $feimage;
	    $plugin_array['felink'] = $felink;
	    $plugin_array['autoresize'] = $autoresize;

	    return $plugin_array;
	}	
	/**
	 * Handle to track unread threads
	 */	
	public function handle_unread_threads(){ 
		global $user_ID,$post,$wpdb;

		//if user is logged in
		if($user_ID){

			//if user first time access site
			$userdata 	 =  get_user_meta( $user_ID, 'et_unread_threads',true);
			$current_time = current_time( 'mysql' );

			if($userdata){
				$last_access = $userdata['last_access'];
			} else {
				$last_access = $current_time;
			}

			$compare = strtotime($current_time) - strtotime($last_access);

			if(is_front_page() || is_page_template("page-pending.php") || is_page_template("page-following.php")){

				//check unread threads after 30 seconds
				if($compare >= 1){

					FE_Member::get_unread();	

				} else {

					$threads = get_posts(array(
							'post_type' => 'thread',
							'posts_per_page' => -1,
							'post_status' => 'publish'
						)
					);

					$threads_id = array();

					if($threads){
						foreach ($threads as $thread) {
							array_push($threads_id, $thread->ID);
						}
					}

					$user = array(
						'ID' => $user_ID,
						'et_unread_threads' => array(
							'data' => $threads_id,
							'last_access' => $last_access
						));

					if(empty($userdata)){
						FE_Member::update($user);
					}

				}
			} elseif (is_singular( 'thread' )) {

				FE_Member::update_unread();	

			}

		} else {
			//first time access home page
			if(is_front_page()){

				if(!isset($_COOKIE['fe_cookie_thread_viewed'])){

					$cookie_json = array(
						'unread_threads' => array(),
						'last_access' => current_time( 'mysql' )
					);	

					setcookie('fe_cookie_thread_viewed',json_encode($cookie_json),time()+60*60,'/');
					$_COOKIE['fe_cookie_thread_viewed'] = json_encode($cookie_json);

				}
			} else if(is_singular( 'thread' )){

				global $post;
				//first time access single-thread page
				if(!isset($_COOKIE['fe_cookie_thread_viewed'])){

					$cookie_json = array(
						'unread_threads' => array($post->ID),
						'last_access' => current_time( 'mysql' )
					);	

					setcookie('fe_cookie_thread_viewed',json_encode($cookie_json),time()+60*60,'/');
					$_COOKIE['fe_cookie_thread_viewed'] = json_encode($cookie_json);
				//if cookie exist insert current thread-id
				} else {

					$threads = json_decode(stripslashes($_COOKIE['fe_cookie_thread_viewed']));

					$threads->last_access = current_time( 'mysql' );

					if($threads->unread_threads == null || !in_array($post->ID, $threads->unread_threads) ){
						if ( !is_array($threads->unread_threads) )
							$threads->unread_threads = array();
						
						array_push($threads->unread_threads, $post->ID);

						setcookie('fe_cookie_thread_viewed',json_encode($threads),time()+60*60,'/');			
					}

				}			
			}			
		}

	}	//end handle unread threads

	public function script_templates(){
		load_template( TEMPLATEPATH . '/script-template.php' );
		// print out google analytics
		echo get_option('et_google_analytics');
	}
}

/**
 * Handle post data
 */
class ET_ForumFrontPost extends ET_Base{

	public function __construct(){
		$this->add_action('template_redirect', 'handle_posts');
	}

	public function handle_posts(){
		
		if(is_singular( 'thread' )){
			global $post;
			if(!get_post_meta( $post->ID, 'et_like_count', true )){
				$likes = get_post_meta( $post->ID, 'et_likes', true );
				update_post_meta( $post->ID, 'et_like_count', count($likes) > 0 ? count($likes) : 0 );
				$replies = get_posts(array('post_parent' => $post->ID,'post_type' => 'reply','posts_per_page'=>-1));

				if (is_array($replies) && count($replies) > 0) {
				    foreach($replies as $reply){
				    	$likes = get_post_meta( $reply->ID, 'et_likes', true );
				    	update_post_meta( $reply->ID, 'et_like_count', count($likes) > 0 ? count($likes) : 0 );
				    }
				}
			}
		}

		if(is_page_template( 'page-change-pass.php' ) ||is_page_template( 'page-edit-profile.php' ) ||is_page_template( 'page-pending.php' ) || is_page_template( 'page-following.php' )){

			global $user_ID;

			if(!$user_ID){
				wp_redirect( home_url() );
				exit;				
			}
		}

		if(is_page_template( 'page-pending.php' )){

			global $current_user;

			if(!current_user_can( 'manage_threads' )){
				wp_redirect( home_url() );
				exit;				
			}
		}

		// posting new thread
		if ( isset($_POST['fe_nonce']) && wp_verify_nonce( $_POST['fe_nonce'], 'insert_thread' )){

			$pending = et_get_option('pending_thread') ;

			if($pending) {
				$result = FE_Threads::insert_thread($_POST['post_title'], $_POST['post_content'], $_POST['thread_category'],"pending");
			} else {
				$result = FE_Threads::insert_thread($_POST['post_title'], $_POST['post_content'], $_POST['thread_category']);	
			}

			do_action( 'fe_front_insert_thread', $result );

			if(!is_wp_error( $result )){
				et_get_user_following_threads();
				wp_redirect( get_permalink( $result ) );
				exit;				
			} else {

			}

		} 

		// reply a thread
		if ( isset($_POST['fe_nonce']) && wp_verify_nonce( $_POST['fe_nonce'], 'insert_reply' ) ){
			global $current_user;

			$result = FE_Replies::insert_reply($_POST['parent'], $_POST['post_content'], $current_user->ID, isset($_POST['reply_parent']) ? $_POST['reply_parent'] : 0);

			do_action( 'fe_front_insert_reply', $result );

			if(!is_wp_error( $result )){

				et_get_user_following_threads();

				if(!get_option( 'et_infinite_scroll' )){
					wp_redirect( et_get_last_page( $_POST['parent'] ) );
				} else {
					wp_redirect( get_permalink( $_POST['parent'] ) );
				}	

				exit;
			}
		}  
		//approve or open thread 
		if ( isset($_GET['fe_nonce']) && wp_verify_nonce( $_GET['fe_nonce'], 'approve_thread' ) && $_GET['action'] == 'approve'  ){			
			$args = array( 'ID' => $_GET['thread_id'], 'post_status' => 'publish' );

			FE_Threads::update($args);

			wp_redirect( get_permalink( $_GET['thread_id'] ) );
			exit;
		}  
		//delete thread
		if ( isset($_GET['fe_nonce']) && wp_verify_nonce( $_GET['fe_nonce'], 'delete_thread' ) && $_GET['action'] == 'delete' ){		

			FE_Threads::delete($_GET['thread_id']);

			wp_redirect( home_url() );
			exit;
		}
		//close thread
		if ( isset($_GET['fe_nonce']) && wp_verify_nonce( $_GET['fe_nonce'], 'close_thread' ) && $_GET['action'] == 'close' ){		

			$args = array( 'ID' => $_GET['thread_id'], 'post_status' => 'closed' );

			FE_Threads::update($args);

			wp_redirect( get_permalink( $_GET['thread_id'] ) );
			exit;
		}
				
		//update user profile
		if ( isset($_POST['fe_nonce']) && wp_verify_nonce( $_POST['fe_nonce'], 'update_profile' )){		
			global $user_ID;
			
			FE_Member::update($_POST);
			wp_redirect( get_author_posts_url($_POST['ID']) );
			exit;
		}

		/**
		 * Search thread
		 */
		if ( isset($_POST['s']) ){
			//$link = add_query_arg( 'post-type', 'thread' , get_search_link( $_POST['s'] ) );
			$link = fe_search_link( $_POST['s'] );
			wp_redirect( $link );
			exit;
		}
	}

	/**
	 * Handle search threads
	 */
	public function handle_search_thread(){

	}
}


class ET_ForumAjax extends ET_Base{
	public function __construct(){
		
		$this->add_ajax('et_fetch_replies', 'fetch_replies');
		$this->add_ajax('et_post_sync', 'sync_post');
		//upload image via TinyMCE
		$this->add_ajax('et_upload_images', 'upload_images', true, true);
		$this->add_ajax('et_get_nonce', 'get_nonce', true, false);

		$this->add_ajax('et_member_sync', 'sync_member', true, false);

		$this->add_ajax('et_search', 'search_threads');
	}

	public function get_nonce(){
		$this->ajax_header();
		global $user_ID;
		if($user_ID){
			$resp = array(
				'success' 	=> true,
				'msg' => 'success',
				'data' => array(
					'ins' => wp_create_nonce( 'insert_thread' ),
					'up'  => wp_create_nonce( 'et_upload_images' ),
					)
				);
		} else {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> 'failed'
			);
		}
		echo json_encode($resp);
		exit;		
	}

	public function sync_post(){
		$this->ajax_header();
		$method = $_POST['method'];

		switch ($method) {
			case 'fetch':
				$resp = $this->fetch_replies();
				break;

			case 'scroll':
				$resp = $this->scroll_replies();
				break;

			case 'like' :
				$resp = $this->toggle_like();
				break;

			case 'approve': 
				$resp = $this->approve_thread();
				break;

			case 'reply' : 
				$resp = $this->reply();
				break;

			case 'report': 
				$resp = $this->report();
				break;

			case 'get':
				$resp = $this->fetch_threads();
				break;

			case 'create':
				$resp = $this->create_thread();
				break;

			case 'update':
				$resp = $this->update_post();
				break;

			case 'sticky': 
				$resp = $this->sticky_thread();
				break;

			case 'delete':
				$resp = $this->trash_thread($_POST['content']);
				break;

			case 'close':
				$resp = $this->toggle_close($_POST['content']);
				break;

			case 'undo':
				$resp = $this->undo_action();
				break;

			case 'blog':
				$resp = $this->fetch_posts();
				break;

			default:
				# code...
				break;
		}

		echo json_encode($resp);
		exit;

	}

	public function create_thread(){
		try{
			global $user_ID;
			$args = $_POST['content'];
			$pending = et_get_option('pending_thread') ;

			if( !is_user_logged_in() ){
				throw new Exception("You must log in to create a thread.", 1);
			}

			if($args['post_title'] == "" || $args['post_content'] == "" || $args['thread_category'] == "" ){
				throw new Exception("Please fill out all fields required.", 1);
			}

			if($pending) {
				$result = FE_Threads::insert_thread($args['post_title'], $args['post_content'], $args['thread_category'],"pending",$user_ID);
			} else {
				$result = FE_Threads::insert_thread($args['post_title'], $args['post_content'], $args['thread_category'],"publish",$user_ID);	
			}

			if(is_wp_error( $result )){
				$resp = array(
					'success' 	=> false,
					'msg' 		=> __('An error occur when created thread.',ET_DOMAIN)
				);				
			} else {
				$resp = array(
					'success' 	=> true,
					'link'		=> get_permalink( $result ),
					'msg' 		=> __('Thread has been created successfully.',ET_DOMAIN)
				);				
			}
			
			et_get_user_following_threads();

		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		return $resp;					
	}
	public function fetch_posts(){

		try{
			global $post;

			$posts_data = array();
			$params = $_POST['content'];
			$cat = isset($params['cat']) ? $params['cat'] : '';

			$args = array(
				'paged' 	  => $params['paged']+1,  
				'post_status' => array('publish'),
				'post_type'   => 'post',
				'cat'	  => $cat
			);

			$query = new WP_Query($args);

			if($query->have_posts()){
				while($query->have_posts()){
					$query->the_post();
					$posts_data[] 	= $this->post_template($post);					
				}
			}	

			$resp = array(
				'success' 	=> true,
				'data' 		=> array(
					'posts'			=> $posts_data,
					'paged' 		=> $params['paged'] +1,
					'total_pages' 	=> $query->max_num_pages
					),
				'msg' 		=> 'successfully'
			);	

		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		return $resp;
	}
	public function post_template($post){

		//$post_class  = get_post_class('et-entry', $post->ID);
		$author 	 = get_user_by( 'id', $post->post_author );
		$author_url  = get_author_posts_url($post->post_author);
		$author_avt  = et_get_avatar($post->post_author);
		$post_time 	 = get_the_time( 'M jS Y' );
		$permalink   = get_permalink($post->ID);
		$comment_num = get_comments_number( $post->ID );
		$excerpt 	 = apply_filters( 'the_content' , $post->post_excerpt );
		$read_more   = __('Read more', ET_DOMAIN);

		$categories = get_the_category( $post->ID );
		$category = $separator = $output = '';

		if($categories){
			foreach($categories as $category) {
				$output .= '<a class="et-entry-cat" href="'.get_category_link( $category->term_id ).'" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) . '">'.$category->cat_name.'</a>'.$separator;
			}
		$category = trim($output, $separator);
		}

		$template = <<<HTML
<article id="post-{$post->ID}" class="et-entry">
	<div class="et-entry-left col-sm-2">
		<a class="et-entry-thumbnail" href="{$author_url}">
			{$author_avt}
		</a>
		<p class="et-entry-author">
			{$author->display_name}
		</p>
		<p class="et-entry-date">
			{$post_time}
		</p>
	</div>
	<div class="et-entry-right col-sm-10">
		<div class="et-entry-head">
			<div class="et-entry-meta">
				{$category}				
				<a href="{$permalink}#comments" class="et-entry-comments icon" data-icon="q">{$comment_num}</a>
			</div>
			<a href="{$permalink}"><h2 class="et-entry-title">{$post->post_title}</h2></a>
		</div>
		<div class="et-entry-content">
			{$excerpt}
			<a class="more-link" href="{$permalink}">{$read_more}&nbsp;&nbsp;<span class="icon" data-icon="]"></span></a>
		</div>
	</div>
	<div class="clearfix"></div>
</article><!-- #post -->		
HTML;
		return $template;
	}
	public function fetch_threads(){
		try{
			global $post,$user_ID;

			$threads_data = array();
			$params = $_POST['content'];
			if($params['status'] == "follow" || $params['status'] == "scroll-follow"){
				$follows = get_user_meta( $user_ID, 'et_following_threads',true);
				$posts_in = ($follows) ? $follows : array(0);					
				$args = array(
					'paged' 	  => $params['paged']+1,  
					'post_status' => array('publish','pending','closed'),
					'post__in' 	  => $posts_in
				);
			} else if($params['status'] == "pending" || $params['status'] == "scroll-pending") {
				$args = array(
					'paged' 	  => $params['paged']+1,  
					'post_status' => array('pending'),
				);
			} else if($params['status'] == "index" || $params['status'] == "scroll-index") {
				if(isset($params['thread_category'])){
					$args = array(
					'paged' 	  => $params['paged']+1,  
					'thread_category' => $params['thread_category'],
					'post_status' => array('publish','closed'),
					);
				} else {
					$args = array(
					'paged' 	  => $params['paged']+1,  
					'post_status' => array('publish','closed'),
					);				
				}

			} else if($params['status'] == "author" || $params['status'] == "scroll-author") {
				$args = array(
					'paged' 	  => $params['paged']+1,  
					'post_status' => array('publish','closed'),
					'author'	  => $params['author']
				);
			} else if($params['status'] == "search" || $params['status'] == "scroll-search") {

				global $wp_query,$et_query;

				$wp_query->is_search = true;
				$et_query['s'] = explode(' ', $params['s']);

				remove_all_filters( 'posts_join' );
				remove_all_filters( 'posts_where' );
				//remove_all_filters( 'posts_orderby' );
				add_filter('posts_distinct', array('FE_Threads', 'query_distinct'));
				add_filter('posts_join', array('FE_Threads', 'query_reply_join'));
				add_filter('posts_where', array('FE_Threads', 'query_reply_where'));
				//add_filter('posts_orderby', array('FE_Threads', 'query_reply_orderby'));

				add_filter('posts_join', 'ET_ForumFront::_thread_join');
				add_filter("posts_orderby", "ET_ForumFront::_thread_orderby");

				$args = array(
					'paged' 	  => $params['paged']+1,  
					'post_status' => array('publish','closed'),
					//'s'	  => $params['s']
				);
			} 

			$threads_query = FE_Threads::get_threads($args);

			if($params['status'] == "search" || $params['status'] == "scroll-search") {
				remove_filter('posts_distinct', array('FE_Threads', 'query_distinct'));
				remove_filter('posts_join', array('FE_Threads', 'query_reply_join'));
				remove_filter('posts_where', array('FE_Threads', 'query_reply_where'));

				remove_filter('posts_join', 'ET_ForumFront::_thread_join');
				remove_filter("posts_orderby", "ET_ForumFront::_thread_orderby");
			}

			//print_r($threads_query);
			if (strpos($params['status'], "scroll") !== false) {
				if($threads_query->have_posts()){
					while($threads_query->have_posts()){
						$threads_query->the_post(); 
						$thread 			= FE_Threads::convert($post);

						if($params['status'] == "scroll-author") {
							$thread->isAuthor = true;
						}

						$threads_data[] 	= $this->thread_template($thread);					
					}
				}
			} else {
				if($threads_query->have_posts()){
					while($threads_query->have_posts()){
						$threads_query->the_post(); 
						$thread 			= FE_Threads::convert($post);
						$threads_data[] 	= $this->thread_mobile_template($thread);					
					}
				}				
			}

			$resp = array(
				'success' 	=> true,
				'data' 		=> array(
					'threads'		=> $threads_data,
					'paged' 		=> $params['paged'] +1,
					'total_pages' 	=> $threads_query->max_num_pages
					),
				'msg' 		=> 'successfully'
			);	

		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		return $resp;
	}

	//thread template for desktop version
	private function thread_template($thread){
		$avatar 		= et_get_avatar($thread->post_author);
		$isHightLight 	= et_is_highlight($thread->ID);
		$isLike 		= $thread->liked ? 'active' : '';
		$isComment 		= $thread->replied ? 'active' : '';
		$permalink 		= get_permalink($thread->ID);
		$thread_title 	= get_the_title($thread->ID);
		$et_updated_date = sprintf( __( 'Updated %s', ET_DOMAIN ),et_the_time(strtotime($thread->et_updated_date)));
		$isAuthor = isset($thread->isAuthor) ? $thread->isAuthor : false;

		$user_badge = apply_filters('fe_user_badge_2',$thread->author_badge ? '<span class="user-badge">'.$thread->author_badge.'</span>' : '',$thread->post_author);

		$avatar_thumb = (!$isAuthor) ? '<a href="'.$permalink.'"><span class="thumb avatar">'.et_get_avatar($thread->post_author).$user_badge.'</span></a>' : '' ;
		$icon_close  = ($thread->post_status == 'closed') ? '<span class="icon" data-icon="("></span>' : '';

		$thread_category = $thread->thread_category ? $thread->thread_category[0]->name : __('No category', ET_DOMAIN);
		$thread_category_link = $thread->thread_category ? get_term_link( $thread->thread_category[0]->slug, 'thread_category' ) : '#';
		$color = (!empty($thread->thread_category[0])) ? FE_ThreadCategory::get_category_color($thread->thread_category[0]->term_id) : 0;	

		$last_reply = $thread->et_last_author ? '<span class="last-reply"><a href="'.et_get_last_page($thread->ID).'">'.__('Last reply',ET_DOMAIN).'</a></span> '.__( 'by', ET_DOMAIN ).' <span class="semibold"><a href="'.get_author_posts_url($thread->et_last_author->ID).'">'.$thread->et_last_author->display_name.'</a></span>': __( 'No reply yet', ET_DOMAIN );
		$undo  =  sprintf( __('Want to %s ?') , '<a href="#" class="act-undo">' . __('undo', ET_DOMAIN) . '</a>' );

		$pending = ($thread->post_status == 'pending') ? '<a href="#" data="'.$thread->ID.'" class="approve-thread" data-toggle="tooltip" title="'.__('Approve', ET_DOMAIN).'"><span class="icon" data-icon="3"></span></a>
					<a href="#" data="'.$thread->ID.'" class="delete-thread" data-toggle="tooltip" title="'.__('Delete', ET_DOMAIN).'"><span class="icon" data-icon="#"></span></a>' : '';
		$isClosed = ( $thread->post_status == 'closed' ) ? 'collapse' : '';
		$notClosed = ( $thread->post_status != 'closed' ) ? 'collapse' : '';
		$notpending = ($thread->post_status != 'pending') ? '<a href="#" class="close-thread '.$isClosed.'" data-toggle="tooltip" title="'.__('Close', ET_DOMAIN).'"><span class="icon" data-icon="("></span></a>
					<a href="#" class="unclose-thread '.$notClosed.'" data-toggle="tooltip" title="'.__('Unclose', ET_DOMAIN).'"><span class="icon" data-icon=")"></span></a>
					
					<a href="#" class="delete-thread" data-toggle="tooltip" title="'.__('Delete', ET_DOMAIN).'"><span class="icon" data-icon="#"></span></a>' : '';
		$control_group = (current_user_can("manage_threads")) ? '<div class="control-thread-group">'.$pending.$notpending.'</div>' : '';

		$before_threads_information = apply_filters( 'forumengine_filter_before_thread_item_infomation', '', $thread );
		$after_threads_information = apply_filters( 'forumengine_filter_after_thread_item_infomation', '', $thread );

		$template = <<<HTML
<li class="{$isHightLight} thread-item" data-id="{$thread->ID}">
	{$avatar_thumb}
	<div class="f-floatright">
		{$before_threads_information}
		<span class="title">
			<a href="{$permalink}">
				{$thread_title} 
				{$icon_close}
			</a>
		</span>
		<div class="post-information">
			<span class="times-create">{$et_updated_date}</span>
			<span class="type-category">
				<a href="{$thread_category_link}">
					<span class="flags color-{$color}"></span>
					{$thread_category}
				</a>.
			</span>
			<span class="author">
				{$last_reply}
			</span>
			<span class="user-action">
				<span class="comment {$isComment}"><span class="icon" data-icon="w"></span>{$thread->et_replies_count}</span>
				<span class="like {$isLike}"><span class="icon" data-icon="k"></span>{$thread->et_likes_count}</span>
			</span>
			<span class="undo-action hide">
				{$undo}
			</span>
		</div>
		{$control_group}
		{$after_threads_information}
	</div>
</li>		
HTML;

		return $template;
	}

	//thread template for mobile version
	private function thread_mobile_template($post){
		$avatar 		= et_get_avatar($post->post_author);
		$user_badge 	= apply_filters('fe_user_badge_2',$post->author_badge ? '<span class="user-badge">'.$post->author_badge.'</span>' : '',$post->post_author);
		$thread 		= FE_Threads::convert($post);
		$isPending 		= ($thread->post_status == "pending") ? 'fe-pending' : '';
		$isHightLight 	= et_is_highlight($thread->ID);
		$isLike 		= $thread->liked ? 'active' : '';
		$isComment 		= $thread->replied ? 'active' : '';
		$permalink 		= get_permalink($thread->ID);
		$thread_title 	= get_the_title($thread->ID);
		$et_updated_date = sprintf( __( 'Updated %s', ET_DOMAIN ),et_the_time(strtotime($thread->et_updated_date)));

		$thread_category = $thread->thread_category ? $thread->thread_category[0]->name : __('No category', ET_DOMAIN);
		$thread_category_link = $thread->thread_category ? get_term_link( $thread->thread_category[0]->slug, 'thread_category' ) : '#';
		$color = (!empty($thread->thread_category[0])) ? FE_ThreadCategory::get_category_color($thread->thread_category[0]->term_id) : 0;	

		$last_reply = $thread->et_last_author ? '<span class="last-reply"><a href="'.et_get_last_page($thread->ID).'">'.__('Last reply',ET_DOMAIN).'</a></span> '.__( 'by', ET_DOMAIN ).' <span class="semibold"><a href="'.get_author_posts_url($thread->et_last_author->ID).'">'.$thread->et_last_author->display_name.'</a></span>': __( 'No reply yet', ET_DOMAIN );
		$approve = __('APPROVE', ET_DOMAIN);
		$reject = __('REJECT', ET_DOMAIN);
		$template = <<<HTML
<article class="fe-post {$isPending} {$isHightLight}" data-id="{$thread->ID}">
	<div class="fe-post-panel">
		<div class="fe-actions fe-actions-2">
			<a href="#" class="fe-act fe-act-approve" data-act="approve">
				<span class="fe-act-icon fe-icon fe-sprite fe-icon-approve"></span>
				<span class="fe-act-text">{$approve}</span>
			</a>
			<a href="#" class="fe-act fe-act-reject" data-act="delete">
				<span class="fe-act-icon fe-icon fe-sprite fe-icon-reject"></span>
				<span class="fe-act-text">{$reject}</span>
			</a>
		</div>
	</div>
	<div class="fe-post-container">
		<a href="" class="fe-post-edit"><span class="fe-sprite fe-icon fe-icon-edit"></span></a>
		<a class="fe-post-avatar" href="{$permalink}">
			<span class="thumb avatar">
				{$avatar}
				{$user_badge}
			</span>
		</a>	
		<div class="fe-post-content">
			<div class="fe-post-title">
				<a href="{$permalink}">
					{$thread_title} 
				</a>
			</div>
			<div class="fe-post-info">
				<span class="fe-post-time">{$et_updated_date}</span>
				<span class="fe-post-cat">
					in 
					<a href="{$thread_category_link}">
						<span class="flags color-{$color}"></span>
						{$thread_category}									
					</a>.
				</span>
				<!--<span class="fe-post-author">
					{$last_reply}																
				</span>-->
				<span class="comment {$isComment}">
					<span class="fe-icon fe-icon-comment fe-sprite" data-icon="w"></span>{$thread->et_replies_count}
				</span>
				<span class="like {$isLike}">
					<span class="fe-icon fe-icon-like fe-sprite" data-icon="k"></span>{$thread->et_likes_count}
				</span>
			</div>
		</div>
	</div>
</article>
HTML;

		return $template;
	}

	public function update_post(){
		try {
			$args 		= $_POST['content'];

			// check if post type is thread or reply
			$post_type 	= 'reply';
			if ( wp_verify_nonce( $args['fe_nonce'], 'edit_thread' ) )
				$post_type = 'thread';

			// prepare post
			$post 		= get_post($args['ID']);

			// if given id is wrong, return error
			if (!$post) 
				if ( $post_type == 'thread' )
					throw new Exception(__("Thread not found", ET_DOMAIN));
				else 
					throw new Exception(__("Reply not found", ET_DOMAIN));
			if(!user_can_edit($post)){
				throw new Exception(__("You can't edit this post anymore!", ET_DOMAIN));
			}
			// update post
			unset($args['fe_nonce']);
			if ($post->post_type == 'thread'){
				$result 		= FE_Threads::update($args);
				do_action( 'fe_save_thread', $result );				
				$post 			= get_post($result);
				$return_data 	= FE_Threads::convert($post);
				//
				if ( !empty($return_data->thread_category) && !empty($return_data->thread_category[0]) ){
					$return_data->thread_category[0]->update_time_string = sprintf( __( 'Updated %s in', ET_DOMAIN ),time_elapsed_string( strtotime($post->post_date) ));
				}
			}else {
				$result 		= FE_Replies::update($args);
				do_action( 'fe_save_reply', $result );				
				$post 			= get_post($result);
				$return_data 	= FE_Replies::convert($post);
			}

			$return_data->content_html = apply_filters( 'et_the_content', $return_data->post_content );
			$resp = array(
				'success' 	=> true,
				'link'		=> get_permalink( $result ),
				'data' 		=> array(
					'posts' => $return_data
				)
			);
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		return $resp;
	}

	public function fetch_replies(){
		$args 		= wp_parse_args( $_POST['content'], array(
			'parent_type' 	=> 'reply',
			'order' 		=> 'DESC',
			'post_status' 		=> array('publish')
		) );
		$this->ajax_header();
		try {
			$posts 			= array();			
			$parent_type 	= $args['parent_type'];
			unset($args['parent_type']);

			$result = FE_Replies::get_replies($args);
			global $post;
			if ($result->have_posts()){
				while ($result->have_posts()){
					$result->the_post();
					if ( $parent_type == 'thread' ){

						$reply = FE_Replies::convert($post);
						$reply->html = $this->reply_desktop_template($reply);
						$posts[] = $reply;	

					} else {

						$reply = FE_Replies::convert($post);
						// more 
						$reply->html = $this->reply_template($reply);
						$reply->mobile_html = $this->reply_mobile_template($reply);
						$reply->mobile_child_html = $this->reply_mobile_child_template($reply);

						$posts[] = $reply;
					}
				}
			}

			$resp = array(
				'success' 	=> true,
				'data' 		=> array(
					'replies' 		=> $posts,
					'total_pages' 	=> $result->max_num_pages,
					'current_page' 	=> empty($args['paged']) ? 1 : (int)$args['paged']
				)
			);
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		echo json_encode($resp);
		exit;
	}

	public function scroll_replies(){

		$args 		= array(
			'paged' 	=> $_POST['content']['paged'] +1,
			'post_parent' => $_POST['content']['post_parent'],
			'post_type' => 'reply',
			'order'		=> 'ASC',
			'post_status' => "publish"
		);
		try {
			$posts 			= array();			
			//$result = new WP_Query($args);
			$result = FE_Replies::get_replies($args);
			// print_r($result);
			global $post;
			if ($result->have_posts()){
				while ($result->have_posts()){
					$result->the_post();

					$reply = FE_Replies::convert($post);
					$reply->html = $this->reply_desktop_template($reply);
					$posts[] = $reply;	
				}
			}

			$resp = array(
				'success' 	=> true,
				'data' 		=> array(
					'replies' 		=> $posts,
					'paged' 		=> $args['paged'],
					'total_pages' 	=> $result->max_num_pages
				)
			);
			//print_r($result);
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		wp_send_json( $resp );
	}

	private function reply_desktop_template($reply){
		global $user_ID;

		/*=== thread data ===*/
		$thread = get_post($reply->post_parent);
		$collapse = ($thread->post_status == "closed" && count($reply->et_likes) == 0 ) ? 'collapse' : '';
		$coll_discuss = count($reply->et_likes) > 0 ? '' : 'collapse' ;
		$liked_by = __('Liked by', ET_DOMAIN);
		$reply_btn = $thread->post_status != "closed" ? '<a href="#reply_'.$reply->ID.'" data-id="'.$reply->ID.'" class="btn-reply open-reply">'.__('Reply', ET_DOMAIN).'<span class="icon" data-icon="R"></span></a>' : '';
		$current_user_avt = et_get_avatar($user_ID,40);
		$insert_nonce = wp_create_nonce( 'ajax_insert_reply' );
		$edit_nonce = wp_create_nonce( 'edit_reply' );
		$reply_txt = __('Reply', ET_DOMAIN);
		$cancel_txt = __('Cancel', ET_DOMAIN);
		$edit_txt = __('Edit', ET_DOMAIN);
		$show_preview_txt  = __('Show preview', ET_DOMAIN);
		/*=== thread data ===*/

		/*=== replies child data ===*/
		if(get_option('et_auto_expand_replies')){

			$replies_child = FE_Replies::get_replies(array(
				'reply_parent' => $reply->ID,
				'order' => 'DESC',
				)) ;
			$hide_btn_more_reply = $replies_child->max_num_pages <= 1 ? 'hide' : '' ;
			$replies_child_html = '';
			$hide_replies_container  = '';
			if($replies_child->have_posts()){
				global $post;
				while ( $replies_child->have_posts() ) {
					$replies_child->the_post();
					$reply_child = FE_Replies::convert($post);
					$reply_child_author = apply_filters( 'fe_author', get_the_author(), $reply_child->post_author );
					$user_badge = apply_filters('fe_user_badge_2',$reply_child->author_badge ? '<span class="user-badge">'.$reply_child->author_badge.'</span>' : '',$reply_child->post_author);
					$isLike = $reply_child->liked ? 'active' : '';
					$replies_child_html .= '<div class="items-thread reply-item clearfix child" id="post_'.$reply_child->ID.'" data-id="'.$reply_child->ID.'">
										<div class="f-floatleft single-avatar avatar-child">
											'.et_get_avatar($reply_child->post_author).$user_badge.'
										</div>
										<div class="f-floatright clearfix">
											<div class="name">
												<a class="post-author" href="<?php echo get_author_posts_url( $reply_child->post_author ) ?>">'.$reply_child_author.'</a>
												<span class="like">
													<a href="#" class="like-post '.$isLike.'" data-id="<'.$reply_child->ID.'">
														<span data-icon="k" class="icon"></span>
														<span class="count">'.$reply_child->et_likes_count.'</span>
													</a>
												</span>
												<span class="date">'.et_the_time( strtotime( $reply_child->post_date ) ).'</span>
											</div>              
											'.apply_filters( 'et_the_content', $reply_child->post_content ).'
										</div>                
									</div>';
				}
			}

		} else {
			$replies_child_html = '';
			$hide_btn_more_reply = 'hide';
			$hide_replies_container = 'collapse';
		}
		/*=== replies child data ===*/

		/*=== like by ===*/
		$count = 0;
		$html = '';
		foreach ($reply->et_likes as $user_id) { 
			if ($count < 5) {
			$avatar = et_get_avatar($user_id);
			$user 	= FE_Member::get($user_id);
			$name 	= $user->display_name;
			$me = ( $user_id == $user_ID ) ? 'class="me"' : '';
			$html .= '<li '.$me.'><a href="'.get_author_posts_url( $user_id ).'" data-toggle="tooltip" title="'.$name.'">'.$avatar.'</a></li>';
			}
		$count++;										
		}
		$plusmore = ( $count > 5 ) ? '<li class="img-circle more-img">' . ($count - 5) . '</li>' : '';
		/*=== like by ===*/	

		/*=== control reply ===*/
		$edit   = user_can_edit($reply) ? '<li><a href="#" class="edit-topic-thread control-edit" data-toggle="tooltip" title="'.__('Edit', ET_DOMAIN).'"><span class="icon" data-icon="p"></span></a></li>' : '';
		$delete = current_user_can('manage_threads') ? '<li><a href="#" class="delete-topic-thread control-delete" data-toggle="tooltip" title="'.__('Delete', ET_DOMAIN).'"><span class="icon" data-icon="#"></span></a></li>' : '';
		$quote  = __('Quote', ET_DOMAIN);
		/*=== control reply ===*/

		$author_url = get_author_posts_url( $reply->post_author );
		$avatar 	= et_get_avatar($reply->post_author);
		$author  	= apply_filters( 'fe_author' , get_the_author_meta( 'display_name', $reply->post_author ) , $reply->post_author);
		$replied 	= ($reply->replied && $user_ID) ? 'active' : '';
		$liked 		= ( $reply->liked ) ? 'active' : '';
		$the_time 	=  et_the_time( strtotime( $reply->post_date ) );
		$the_content = apply_filters( 'et_the_content', $reply->post_content );
		$show_more_replies = __('Show more replies', ET_DOMAIN);
		$reply_nonce = wp_create_nonce( 'ajax_insert_reply' );
		$user_badge = apply_filters('fe_user_badge_2',$reply->author_badge ? '<span class="user-badge">'.$reply->author_badge.'</span>' : '',$reply->post_author);

		$template 	= <<<HTML
<div class="items-thread reply-item clearfix" id="post_{$reply->ID}" data-id="{$reply->ID}">						
	<ul class="control-thread">
		{$edit}
		{$delete}
		<li><a href="#" data-id="{$reply->ID}" class="control-quote" data-toggle="tooltip" title="{$quote}"><span class="icon" data-icon='"'></span></a></li>
	</ul>						
	<div class="f-floatleft single-avatar">           
		<a href="{$author_url}">{$avatar}{$user_badge}</a>
	</div>
	<!-- end float left -->
	<div class="f-floatright">
		<div class="post-display">
			<div class="name">
				<a class="post-author" href="{$author_url}">{$author}</a>
				<span class="comment">
					<a href="#replies_{$reply->ID}" class="show-replies {$replied}">
						<span data-icon="w" class="icon"></span>
						<span class="count">{$reply->et_replies_count}</span>
					</a>
				</span>
				<span class="like">
					<a href="#" class="like-post {$liked}" data-id="{$reply->ID}">
						<span data-icon="k" class="icon"></span>
						<span class="count">{$reply->et_likes_count}</span>
					</a>
				</span>
				<span class="date">{$the_time}</span>     
			</div>
			<div class="content">{$the_content}</div>
			<div id="replies_{$reply->ID}" data-id="{$reply->ID}" data-page="1" class="reply-children {$hide_replies_container}">
				<a class="btn-more-reply {$hide_btn_more_reply}" data-id="{$reply->ID}">{$show_more_replies}</a>
				<div class="replies-container">
					{$replies_child_html}
				</div>
			</div>
			<!-- end items threads child -->
			<div class="linke-by clearfix {$collapse}">
				<ul class="user-discuss {$coll_discuss}">
					<li class="text">{$liked_by}</li>
					{$html}
					{$plusmore}   
				</ul>
				{$reply_btn}
			</div>
			<div id="reply_{$reply->ID}" class="edit-reply form-reply items-thread clearfix child collapse">
				<div class="f-floatleft single-avatar avatar-child">
					{$current_user_avt}{$user_badge}
				</div>
				<div class="f-floatright clearfix">
					<form class="ajax-reply" action="" method="post">
						<input type="hidden" name="fe_nonce" value="{$reply_nonce}">
						<input type="hidden" name="post_parent" value="{$thread->ID}">
						<input type="hidden" name="et_reply_parent" value="{$reply->ID}">
						<div id="wp-edit_post_content{$reply->ID}-editor-container" class="wp-editor-container">
							<textarea name="post_content" id="reply_content{$reply->ID}"></textarea>
						</div>						
						<div class="button-event">
							<input class="btn" type="submit" value="{$reply_txt}">
							<span data-target="#reply_{$reply->ID}" class="btn-cancel"><span class="icon" data-icon="D"></span>{$cancel_txt}</span>
						</div>
					</form>
				</div> 
			</div>
		</div>
		<div class="post-edit collapse">
			<form class="form-post-edit" action="" method="post">
				<input type="hidden" name="fe_nonce" value="{$edit_nonce}">
				<input type="hidden" name="ID" value="{$reply->ID}">
				<div class="form-detail">
					<div id="wp-edit_post_content{$reply->ID}-editor-container" class="wp-editor-container">
						<textarea class="text-mce" name="post_content" id="post_content{$reply->ID}">{$the_content}</textarea>
					</div>					
					<div class="row line-bottom">
						<div class="col-md-6 col-sm-6">
							<div class="show-preview">
								<!--<div class="skin-checkbox">
									<span class="icon" data-icon="3"></span>
									<input type="checkbox" class="checkbox-show" id="show_topic_item" style="display:none" />
								</div>
								<a href="#">{$show_preview_txt}</a>-->
							</div>
						</div>
						<div class="col-md-6 col-sm-6">
							<div class="button-event">
								<input type="submit" value="{$edit_txt}" class="btn">
								<a href="#" class="cancel control-edit-cancel"><span class="btn-cancel"><span class="icon" data-icon="D"></span>{$cancel_txt}</span></a>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>    
	</div> <!-- end float right -->
</div>
HTML;

		return $template;
	}
	
	private function reply_template($post){
		$avatar 		= et_get_avatar($post->post_author);
		$content 		= apply_filters( 'et_the_content', $post->post_content );
		$author  		= apply_filters( 'fe_author', get_the_author_meta( 'display_name', $post->post_author ), $post->post_author );//get_the_author_meta( 'display_name', $post->post_author );
		$author_url 	= get_author_posts_url( $post->post_author );
		$reply 			= FE_Replies::convert($post);
		$isLike 		= $reply->liked ? 'active' : '';
		$the_time 		= et_the_time( strtotime( $reply->post_date ) );
		$user_badge 	= apply_filters('fe_user_badge_2',$post->author_badge ? '<span class="user-badge">'.$post->author_badge.'</span>' : '',$post->post_author);
		$template = <<<HTML
<div class="items-thread reply-item clearfix child" id="post_{$reply->ID}" data-id="{$reply->ID}">
	<div class="f-floatleft single-avatar avatar-child">
		{$avatar}
		{$user_badge}
	</div>
	<div class="f-floatright clearfix">
		<div class="name">
			<a class="post-author" href="{$author_url}">{$author}</a>
			<span class="like">
				<a href="#" class="like-post {$isLike}" data-id="$post->ID">
					<span data-icon="k" class="icon"></span>
					<span class="count">{$reply->et_likes_count}</span>
				</a>
			</span>
			<span class="date">{$the_time}</span>
		</div>              
		{$content}
	</div>                
</div>
HTML;

		return $template;
	}

	private function reply_mobile_template($post){
		$user_badge 	= apply_filters('fe_user_badge_2',$post->author_badge ? '<span class="user-badge">'.$post->author_badge.'</span>' : '',$post->post_author);
		$avatar 		= et_get_avatar($post->post_author);
		$content 		= apply_filters( 'et_the_content', $post->post_content );
		$content_edit   = strip_tags($post->post_content);
		$author  		=  apply_filters( 'fe_author', get_the_author_meta( 'display_name', $post->post_author ), $post->post_author );
		$author_url 	= get_author_posts_url( $post->post_author );
		$reply 			= FE_Replies::convert($post);
		$isLike 		= $reply->liked ? 'active' : '';
		$isComment 		= $reply->replied ? 'active' : '';
		$closed 		= (get_post_status($reply->post_parent) == "closed") ? 'hidden' : '' ;
		$et_time 		=  et_the_time( strtotime( $reply->post_modified ) );
		$fe_nonce		= wp_create_nonce( 'edit_reply' );
		$button_more 	= __('Show more replies',ET_DOMAIN);
		$btn_edit 		= (user_can_edit($post)) ? '<a href="#reply_' . $reply->ID . '" class="fe-icon fe-icon-edit"></a>' : '';
		$template = <<<HTML
<article class="fe-th-post" id="reply_{$reply->ID}">
	<a class="fe-avatar" href="{$author_url}">{$avatar}{$user_badge}</a>
	<div class="fe-th-container">
		<div class="fe-th-heading">
			<div class="fe-th-info">
				<a href="#" class="show-comment-child" data-id="{$reply->ID}">
					<span class="comment {$isComment}">
						<span class="fe-icon fe-icon-comment  fe-sprite" data-icon="w"></span>{$reply->et_replies_count}
					</span>
				</a>
				<a href="#" class="like" data-id="{$reply->ID}">
					<span class="like {$isLike}">
						<span class="fe-icon fe-icon-like fe-sprite" data-icon="k"></span><span class="count">{$reply->et_likes_count}</span>
					</span>
				</a>
				<span class="time">
					{$et_time}
				</span>
			</div>
			<span class="title">{$author}</span>
		</div>
		<div class="fe-th-content">
			{$content}
		</div>
		<!-- form edit -->
		<div class="fe-topic-form clearfix">
			<input type="hidden" name="fe_nonce" id="fe_nonce" value="{$fe_nonce}">
			<div class="fe-topic-content" style="display:block;">
				<div class="textarea">
					<textarea id="thread_content">{$content_edit}</textarea>
				</div>
				<div class="fe-form-actions pull-right">
					<a href="#reply_{$reply->ID}" class="fe-btn update-reply" data-id="{$reply->ID}" data-role="button">Save</a>
					<a href="#" class="fe-btn-cancel fe-icon-b fe-icon-b-cancel cancel-modal ui-link">Cancel</a>
				</div>				
			</div>
		</div>					
		<!-- form edit -->			
		<div class="fe-th-replies">
			<a href="#" class="btn-more-reply hidden" data-role="button">{$button_more}</a>
		</div>
		<div class="fe-th-ctrl">
			<div class="fe-th-ctrl-right">
				{$btn_edit}
				<a href="#reply_{$reply->ID}" class="fe-icon fe-icon-quote"></a>
				<!-- <a href="" class="fe-icon fe-icon-report"></a> -->
			</div>
			<div class="fe-th-ctrl-left {$closed}">
				<a href="#reply_{$reply->ID}" class="fe-reply ui-link">Reply <span class="fe-icon fe-icon-reply"></span></a>
			</div>
		</div>
		<div class="child-reply-box hidden">
			<div class="fe-reply-box expand reply-small">
				<textarea class="reply_child_content"></textarea>
				<div class="fe-reply-actions">
					<a href="#" class="reply-child fe-btn" data-id="{$reply->ID}" data-role="button">Reply</a>
					<a href="#" class="fe-btn-cancel fe-icon-b fe-icon-b-cancel cancel-modal ui-link">Cancel</a>
				</div>
			</div>
		</div>		
	</div>
</article>
HTML;

		return $template;
	}

	private function reply_mobile_child_template($post){
		$user_badge 	= $post->author_badge ? '<span class="user-badge">'.$post->author_badge.'</span>' : '';
		$avatar 		= et_get_avatar($post->post_author);
		$content 		= apply_filters( 'et_the_content', $post->post_content );
		$author  		= apply_filters( 'fe_author', get_the_author_meta( 'display_name', $post->post_author ), $post->post_author );//get_the_author_meta( 'display_name', $post->post_author );
		$author_url 	= get_author_posts_url( $post->post_author );
		$reply 			= FE_Replies::convert($post);
		$isLike 		= $reply->liked ? 'active' : '';
		$template = <<<HTML
<div class="fe-reply-item">
<a href="#" class="fe-avatar">
	{$avatar}
	{$user_badge}
</a>
<div class="fe-th-container">
	<div class="fe-th-heading">
		<div class="fe-th-info">
		<a href="#" class="like" data-id="{$reply->ID}">
			<span class="like {$isLike}">
				<span class="fe-icon fe-icon-like fe-sprite" data-icon="k"></span><span class="count">{$reply->et_likes_count}</span>
			</span>
		</a>
		</div>
		<span class="title">{$author}</span>
	</div>
	<div class="fe-th-content">
		{$content}
	</div>
</div>
</div>
HTML;

		return $template;
	}	
	/**
	 * Upload Images via TinyMCE
	 */
	public function upload_images(){
		try{
			if ( !check_ajax_referer( 'et_upload_images', '_ajax_nonce', false ) ){
				throw new Exception( __('Security error!', ET_DOMAIN ) );
			}

			// check fileID
			if(!isset($_POST['fileID']) || empty($_POST['fileID']) ){
				throw new Exception( __('Missing image ID', ET_DOMAIN ) );
			}
			else {
				$fileID	= $_POST["fileID"];
			}

			if(!isset($_FILES[$fileID])){
				throw new Exception( __('Uploaded file not found',ET_DOMAIN) );
			}

			if($_FILES[$fileID]['size'] > 1024*1024){
				throw new Exception( __('Image file size is too big.Size must be less than < 1MB.',ET_DOMAIN) );
			}			

			// handle file upload				
			$attach_id = et_process_file_upload( $_FILES[$fileID], 0 , 0, array());

			if ( is_wp_error($attach_id) ){
				throw new Exception( $attach_id->get_error_message() );
			}

			$image_link = wp_get_attachment_image_src( $attach_id , 'full');

			// no errors happened, return success response
			$res	= array(
				'success'	=> true,
				'msg'		=> __('The file was uploaded successfully', ET_DOMAIN),
				'data'		=> $image_link[0]
			);
		}
		catch(Exception $e){
			$res	= array(
				'success'	=> false,
				'msg'		=> $e->getMessage()
			);
		}	
		header( 'HTTP/1.0 200 OK' );
		header( 'Content-type: application/json' );
		echo json_encode($res);
		exit;		
	}	

	/**
	 * Like or dislike a reply or thread
	 */
	public function toggle_like(){
		//$this->ajax_header();
		global $current_user;

		try {
			if ( !is_user_logged_in() ) throw new Exception(__('You must logged in to perform this action', ET_DOMAIN));
			if ( !isset($_POST['content']['id']) ) throw new Exception(__('Missing argument', ET_DOMAIN));

			$thread_id 	= $_POST['content']['id'];

			$likes 		= FE_Threads::toggle_like($thread_id);
			$is_like 	= false;

			if ( in_array($current_user->ID, $likes) )
				$is_like = true;

			$resp 		= array(
				'success' 	=> true,
				'msg' 		=> '',
				'data' 		=> array(
					'likes' => $likes,
					'label' => count($likes) . ' Likes',
					'count' => count($likes),
					'isLike' => $is_like
				)
			);
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}

		return $resp;

		// echo json_encode($resp);
		// exit;
	}

	/**
	 * Report a thread or reply
	 */
	public function report(){
		global $current_user;

		try {
			if ( !is_user_logged_in() ) throw new Exception(__('You must logged in to perform this action', ET_DOMAIN));
			if ( !isset($_POST['content']['id']) ) throw new Exception(__('Missing argument', ET_DOMAIN));

			$result = FE_Threads::report($_POST['content']['id']);

			if ( $result ){
				$resp = array(
					'success' 	=> true,
					'msg' 		=> __( 'You have reported successfully', ET_DOMAIN )
				);
			} else {
				throw new Exception( __('You must logged in to perform this action', ET_DOMAIN) );
			}

		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		return $resp;
	}

	public function reply(){
		try {
			global $current_user;

			if ( !empty($_POST['et_reply_parent']) ) throw new Exception( __('Error occurred', ET_DOMAIN) );

			// find parent
			$args 	= $_POST['content'];

			if(isset($args['parent'])){
				$parent = $args['parent'];
				$args['et_reply_parent'] = 0;
			} else {
				$parent = wp_get_post_parent_id( $args['et_reply_parent'] );
			}

			$result = FE_Replies::insert_reply(
				$parent, 
				$args['post_content'], 
				$current_user->ID, 
				$args['et_reply_parent']);

			if ( is_wp_error( $result ) ){
				throw new Exception( $result->get_error_message() );
			}
			do_action( 'fe_save_reply', $result );
			$replyPost 		= get_post($result);
			$reply  		= FE_Replies::convert($replyPost);
			$reply->html 	= $this->reply_template($reply);
			$reply->mobile_html	= $this->reply_mobile_template($reply);
			$reply->mobile_child_html 	= $this->reply_mobile_child_template($reply);

			// return if it is the first reply
			$replies 		= FE_Replies::get_replies(array(
				'reply_parent' => $args['et_reply_parent'],
				'parent_type' 	=> 'reply',
			));

			if ( $replies->found_posts == 1 ){
				$load_more = false;
			} else {
				$load_more = true;
			}	
			et_get_user_following_threads();
			$resp = array(
				'success' => true,
				'data' => array(
					'reply' 		=> $reply,
					'load_more' 	=> $load_more,
					'found_posts'	=> $replies->found_posts
				)
			);
		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' => $e->getMessage()
			);
		}
		return $resp;
	}

	public function change_status($post_id, $new_status){
		$args = array(
			'ID' 			=> $post_id,
			'post_status' 	=> $new_status
		);
		return FE_Threads::update($args);
	}

	public function sticky_thread(){
		try {
			$data = $_POST['content'];

			if ( empty( $data['id'] ) ) throw new Exception( __("Can't find thread!" , ET_DOMAIN) );

			// get sticky threads
			$sticky = et_get_option( 'et_sticky_threads' );

			if ( in_array( $data['id'], (array)$sticky ) ) throw new Exception( __('Thread has been sticky already', ET_DOMAIN) );

			$sticky[] = $data['id'];

			et_update_option( 'et_sticky_threads', array_unique( $sticky ) );

			$resp = array(
				'success'	=> true,
				'msg' 		=> __( "Thread sticky successfully!", ET_DOMAIN ),
				'data' 		=> array(
					'sticky_threads' => $sticky
				)
			);

		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' => $e->getMessage()
			);			
		}
		return $resp;
	}

	public function undo_action(){
		try {
			$data = $_POST['content'];

			if ( empty( $data['id'] ) ) throw new Exception( __("Can't find thread!", ET_DOMAIN) );

			$args 		= array( 'ID' => $data['id'] );

			// get old status
			// 
			$thread 					= get_post($args['ID']);
			//$before_trash_status 		= FE_Threads::get_field( $data['id'], '_wp_trash_meta_status' );
			if ( $thread->post_status == 'trash' ){
				$post 	= wp_untrash_post( $args['ID'] );
				$result = $post['ID'];
			} else {
				$old_status 			= FE_Threads::get_field( $data['id'], '_et_old_status' );
				if ( empty($old_status) ) $old_status == 'pending';
				$args['post_status'] 	= $old_status;
				$result = FE_Threads::update($args);
			}

			if ( is_wp_error( $result ) ){
				throw new Exception( $result->get_error_message() );
			}

			$resp = array(
				'success' 	=> true,
				'msg' 		=> __( "Previous action was undone!", ET_DOMAIN ),
				'data' 		=> array(
					'id' 		=> $result
				)
			);

		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' => $e->getMessage()
			);
		}
		return $resp;
	}

	public function approve_thread(){
		try {
			$data = $_POST['content'];

			if ( empty( $data['id'] ) ) throw new Exception( __("Can't find thread!", ET_DOMAIN) );	

			$args = array( 'ID' => $data['id'], 'post_status' => 'publish' );

			$thread = FE_Threads::update($args);

			if ( is_wp_error( $thread ) ){
				throw new Exception( $thread->get_error_message() );
			}

			$resp = array(
				'success' 	=> true,
				'msg' 		=> __( "Thread approved successfully!", ET_DOMAIN ),
				'link' 		=> get_permalink( $thread ),
				'data' 		=> array(
					'id' 	=> $thread
				)
			);	

		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' => $e->getMessage()
			);
		}
		return $resp;
	}

	public function trash_thread($data){
		try {
			
			if ( empty($data['id']) ) throw new Exception( __('Error occurred', ET_DOMAIN) );

			if(get_post_type( $data['id'] ) == "reply") {
				$msg = __( "Reply deleted successfully!", ET_DOMAIN );
				$reply = get_post($data['id']);
				FE_Replies::delete($data['id']);
				FE_Threads::count_comments($reply->post_parent);
			} else {
				$msg = __( "Thread deleted successfully!", ET_DOMAIN );
				FE_Threads::delete($data['id']);
			}

			$resp = array(
				'success' 	=> true,
				'msg' 		=> $msg,
				'link'		=> home_url(),
				'data' 		=> array(
					'id' => $data['id']
				)
			);	

		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' => $e->getMessage()
			);			
		}
		return $resp;
	}	

	public function toggle_close($data){
		try {
			if ( empty( $data['id'] ) ) throw new Exception( __("Can't find thread", ET_DOMAIN) );

			$thread 	= get_post($data['id']);
			$old_status = $thread->post_status;
			if ( $thread->post_status != 'closed' ){
				$result 	= FE_Threads::close($data['id']);
				$new_status = 'closed';
				$msg = __( "Thread closed successfully!", ET_DOMAIN );
			}
			else {
				$result = FE_Threads::change_status($data['id'], 'publish');
				$new_status = 'publish';
				$msg = __( "Thread was reopened successfully!", ET_DOMAIN );
			}

			if ( is_wp_error( $result ) ){
				throw new Exception( $thread->get_error_message() );
			}

			$resp = array(
				'success' 	=> true,
				'msg'		=> $msg,
				'link'		=> get_permalink( $result ),
				'data'		=> array(
					'old_status' 	=> $old_status,
					'new_status' 	=> $new_status
				)
			);	

		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' => $e->getMessage()
			);			
		}
		return $resp;
	}

	public function sync_member(){
		try {
			$method = 	$_POST['method'];
			// $data 	=  	$_POST['content'];
			if ( is_array($_POST['content']) )
				$data = $_POST['content'];
			else 
				wp_parse_str( $_POST['content'], $data );

			foreach ($data as $key => $value) {
				if ( $key == 'description' )
					$data[$key] = wpautop( $value );
			}

			switch ($method) {
				case 'update':
					$result = FE_Member::update($data);
					if ( $result )
						$resp = array(
							'success' 	=> true,
							'data' 		=> $data
						);
					else throw new Exception(__('Fail!', ET_DOMAIN));
					break;
				default:
					throw new Exception(__('Unknown request', ET_DOMAIN));
					break;
			}
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		wp_send_json($resp);
	}

	/**
	 * AJAX search threads by keyword
	 * 
	 */
	public function search_threads(){
		try {
			$query =  FE_Threads::search($_POST['content']);
			$data = array();
			foreach ($query->posts as $post) {
				$thread = FE_Threads::convert($post);
				$thread->et_avatar = FE_Member::get_avatar_urls($post->post_author, 30);
				$thread->permalink = get_permalink( $post->ID );

				$data[] = $thread;
			}

			$resp = array(
				'success' 	=> true,
				'msg' 		=> '',
				'data' 		=> array(
					'threads' 		=> $data,
					'total' 		=> $query->found_posts,
					'count' 		=> $query->post_count,
					'pages' 		=> $query->max_num_pages,
					'search_link' 	=> fe_search_link( $_POST['content']['s'] ),
					'search_term' 	=> $_POST['content']['s'],
					'test' 			=> $query
				)
			);
		} catch (Exception $e) {
			$resp = array(
				'success' => false,
				'msg' 	=> $e->getMessage()
			);
		}
		wp_send_json($resp);
	}

} // end class ET_ForumAjax

?>