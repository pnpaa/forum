<?php

/**
 * Basic User class
 */
class ET_User extends ET_Base{

	/**
	 * Insert a member
	 */
	static $instance = null;

	public function __construct(){

	}

	static public function init(){
		$instance = self::get_instance();
	}

	// public function get_instance(){
	// 	if ( self::$instance == null){
	// 		self::$instance = new ET_User();
	// 	} 
	// 	return self::$instance;
	// }

	public function _insert($data){

		$args = $this->_filter_meta($data);

		$result = wp_insert_user( $args['data'] );

		if ($result != false && !is_wp_error( $result )){
			if ( isset($args['meta']) ) {
				foreach ($args['meta'] as $key => $value) {
					update_user_meta( $result, $key, $value );
				}
			}

			// people can modify here
			do_action('et_insert_user', $result);
		}

		return $result;
	}

	public function _update($data){
		try {
			if (empty($data['ID']))
				throw new Exception(__('Member not found', ET_DOMAIN), 404);

			// filter meta and default data
			$args = $this->_filter_meta($data);

			// update database
			$result = wp_update_user( $args['data'] );
			if ($result != false || !is_wp_error( $result ) ){
				if ( isset($args['meta']) ){
					foreach ((array)$args['meta'] as $key => $value) {
						update_user_meta( $result, $key, $value );
					}
				}

				// people can modify here
				do_action('et_update_user', $result);
			}

			return $result;
		} catch (Exception $e) {
			return new WP_Error($e->getCode(), $e->getMessage());
		}
	}

	protected function _delete($id, $reassign = 'novalue'){
		if ( wp_delete_user( $id, $reassign ) ){
			do_action( 'et_delete_user' );
		}
	}

	// add more meta data into default userdata
	protected function _convert($data){
		
		if(empty($data))
			return false;

		$result = clone $data->data;

		if (!empty($result)){
			foreach ($this->meta_data as $key) {
				$result->$key = get_user_meta( $data->ID, $key, true );
			}
		}

		return $result;
	}

	protected function _filter_meta($data){
		$return = array();
		foreach ($data as $key => $value) {
			if (in_array($key, $this->meta_data))
				$return['meta'][$key] = $value;
			else 
				$return['data'][$key] = $value;
		}
		return $return;
	}
	
}

/**
 * Handle member data in forum engine
 */
class FE_Member extends ET_User{

	static $instance;

	public function __construct(){
		$this->meta_data = array(
			'et_avatar',
			'et_unread_threads',
			'et_following_threads',
			'user_hide_info',
			'user_mobile',
			'user_facebook',
			'user_twitter',
			'user_gplus',
			'user_location',
			'et_thread_count',
			'et_reply_count',
			'et_like_count',
			'description',
			'et_twitter_id',
			'et_facebook_id'
			);
	}

	static public function init(){
		$instance = self::get_instance();						
	}

	/**
	 * get instance
	 */
	static public function get_instance(){
		if (self::$instance == null){
			self::$instance = new FE_Member();
		}

		return self::$instance;
	}

	private function _ban($id, $time){

	}


	static public function insert($data){
		$instance = self::get_instance();
		return $instance->_insert($data);
	}

	static public function update($data){
		$instance = self::get_instance();
		return $instance->_update($data);
	}

	static public function delete($id){
		$instance = self::get_instance();
		return $instance->_delete($id);
	}

	static public function get($id){
		$user = get_userdata( $id );
		return self::convert($user);
	}

	static public function toggle_follow($thread_id, $user_id){
		$users_follow_arr = explode(',',get_post_meta($thread_id,'et_users_follow',true));

		if(!in_array($user_id, $users_follow_arr)){
			array_push($users_follow_arr, $user_id);
		} else {
			foreach ($users_follow_arr as $key => $value) {
				if ( $user_id == $value ){
					unset($users_follow_arr[$key]);
					break;
				}
			}
		}
		$users_follow_arr = array_unique(array_filter($users_follow_arr));
		$users_follow = implode(',', $users_follow_arr);
		FE_Threads::update_field($thread_id, 'et_users_follow', $users_follow);
		et_get_user_following_threads();
		return $users_follow_arr;
	}

	static public function convert($user){
		$instance 	= self::get_instance();
		$result 	= $instance->_convert($user);
		
		$result->id 		= $result->ID;
		$result->et_avatar 	= self::get_avatar($result->ID,64,array('class'=> 'avatar','alt' => $user->display_name));

		$excludes = array('user_email', 'user_pass');
		foreach ($excludes as $value) {
			unset($result->$value);
		}

		if ( !empty($result->et_thread_count) ) $result->et_thread_count = 0;
		if ( !empty($result->et_reply_count) ) 	$result->et_reply_count = 0;

		// additional 
		return $result;
	}

	static public function get_avatar_urls($id, $size = 64){
		$avatar = get_user_meta( $id, 'et_avatar', true );

		if ( empty($avatar) || empty($avatar['thumbnail']) ){
			$link 	= get_avatar( $id, $size );
			preg_match( '/src=(\'|")(.+?)(\'|")/i', $link, $array );
			$sizes = get_intermediate_image_sizes();
			$avatar = array();
			foreach ($sizes as $size) {
				$avatar[$size] = array($array[2]);
			}
		} else {
			$avatar = $avatar['thumbnail'][0];
		}
		return $avatar;
	}

	static public function get_avatar($id, $size = 64 ,$params = array('class'=> 'avatar' , 'title' => '', 'alt' => '')){
		extract($params);
		$avatar = get_user_meta( $id, 'et_avatar', true );

		if ( !empty($avatar) && isset($avatar['thumbnail'][0])){
			$avatar = '<img src="'.$avatar['thumbnail'][0].'" class="'.$class.'" alt="'.$alt.'" />';
		} else {
			$link 	= get_avatar( $id, $size );
			preg_match( '/src=(\'|")(.+?)(\'|")/i', $link, $array );
			$sizes = get_intermediate_image_sizes();
			$avatar = array();
			foreach ($sizes as $size) {
				$avatar[$size] = $array[2];
			}
			$avatar = '<img src="'.$avatar['thumbnail'].'" class="'.$class.'" alt="'.$alt.'" />';			
		}
		return $avatar;
	}
	static public function get_unread(){
		global $user_ID,$wpdb;
		
		$userdata 	 =  get_user_meta( $user_ID, 'et_unread_threads',true);
		$current_time = current_time( 'mysql' );

		if($userdata){
			$last_access = $userdata['last_access'];
		} else {
			$last_access = $current_time;
		}	
			
		$sql = "SELECT et_p.ID FROM $wpdb->posts AS et_p INNER JOIN $wpdb->postmeta AS et_mt ON (et_p.ID = et_mt.post_id) WHERE et_p.post_type = 'thread' AND et_mt.meta_key = 'et_updated_date' AND et_mt.meta_value > '{$last_access}' AND (et_p.post_status = 'publish') GROUP BY et_p.ID ORDER BY et_mt.meta_value DESC, et_p.post_date DESC";

		$results = $wpdb->get_results($sql);

		if($results){
			foreach ($results as $result) {
				if(!in_array($result->ID , $userdata['data'])){
					array_push($userdata['data'], $result->ID);
				}
			}
		}

		$user = array(
			'ID' => $user_ID,
			'et_unread_threads' => array(
				'data' => $userdata['data'],
				'last_access' => $userdata['last_access'],
			));

		FE_Member::update($user);
	}
	static public function update_unread($thread_id = 0){
		global $user_ID,$post;
		$ID = ($thread_id) ? $thread_id : $post->ID;
		$userdata  	 =  get_user_meta($user_ID,'et_unread_threads',true);

		if(!empty($userdata)){
			$threads_arr = ($userdata['data']) ? $userdata['data'] : array();					
		} else {
			$threads_arr = array();
		}

		if(($key = array_search($ID, $threads_arr)) !== false) {
		    unset($threads_arr[$key]);
		}

		$user = array(
			'ID' => $user_ID,
			'et_unread_threads' => array(
					'data' => $threads_arr,
					'last_access' => current_time( 'mysql' )
				)
			);
		FE_Member::update($user);
	}	
	/**
	 * Update thread count and reply count for user
	 */
	static public function update_counter($user_id, $type = 'thread'){
		global $wpdb;

		$type 	= $type == 'thread' ? 'thread' : 'reply';
		$sql 	= " SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_author = {$user_id} AND post_status = 'publish' AND post_type = '{$type}' ";
		$number = $wpdb->get_var($sql);

		if ( $type == 'thread' )
			update_user_meta( $user_id, 'et_thread_count', $number );
		else 
			update_user_meta( $user_id, 'et_reply_count', $number );
	}

	static public function get_current_member(){
		$user = wp_get_current_user();
		if ( !$user->ID ) return $user;
		else {
			return FE_Member::convert($user);
		}
	}
}

function et_get_avatar($id, $size = 64,$params = array('class'=> 'avatar','alt' => '')){
	return FE_Member::get_avatar($id, $size, $params);
}

class ET_UserAjax extends ET_Base{

	public function __construct(){
		$this->add_ajax('et_user_sync', 'user_sync');
	}

	public function user_sync(){
		$this->ajax_header();

		switch ($_POST['method']){
			case 'login': 
				$resp = $this->login();
				break;

			case 'register': 
				$resp = $this->register();
				break;

			case 'logout': 
				$resp = $this->logout();
				break;

			case 'follow':
				$resp = $this->follow();
				break;

			case 'inbox':
				$resp = $this->inbox();
				break;

			case 'forgot':
				$resp = $this->forgot(); 
				break;

			case 'change_pass': 
				$resp = $this->change_pass();
				break;

			case 'reset': 
				$resp = $this->reset_password(); 
				break;

			case 'change_logo': 
				$resp = $this->change_logo();
				break;

			case 'get_members':
				$resp = $this->get_members();
				break;

			case 'update_role':
				$resp = $this->update_role();

			default:
				break;
		}

		echo json_encode($resp);
		exit;
	}
	public function change_pass(){
		global $current_user;		
		$user_email	= $current_user->data->user_email;

		try{
			if( !isset( $_REQUEST['content']['old_pass'] ) || !isset( $_REQUEST['content']['new_pass'] ) ){
				throw new Exception(__('Please enter all required information to reset your password.', ET_DOMAIN ), 400 );
			}
			if( $_REQUEST['content']['new_pass'] !== $_REQUEST['content']['re_pass'] ){
				throw new Exception(__('Confirmed password does not matched', ET_DOMAIN ), 400 );
			}

			// check old password
			$pass_check = wp_check_password( $_REQUEST['content']['old_pass'], $current_user->data->user_pass, $current_user->data->ID );

			if ( !$pass_check ) {
				throw new Exception(__('Old password is not correct.', ET_DOMAIN), 401);
			}

			if ( empty($_REQUEST['content']['new_pass']) ) 
				throw new Exception(__('Your new password cannot be empty.', ET_DOMAIN), 400);

			// set new password
			wp_set_password( $_REQUEST['content']['new_pass'], $current_user->data->ID );

			// relogin the user automatically
			$user = et_login_by_email( $user_email, $_REQUEST['content']['new_pass'] );
			if( !is_wp_error($user) ){
				$resp = array(
					'success' 	=> true,
					'msg' 		=> __('Your password was changed! Please login again!',ET_DOMAIN ),
					'redirect_url' => get_author_posts_url($current_user->data->ID)
				);				
			};

		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		return $resp;
	}
	/**
	 * Perform login ajax request
	 */
	public function login(){
		$args = $_POST['content'];

		$user 	= et_login($args['user_name'], $args['user_pass'], true);
		if( is_wp_error($user) ) {
			// apply login by email here
			$user 	= et_login_by_email($args['user_name'], $args['user_pass'], true);
		}

		// get new data of user
		if(!is_wp_error($user)) $userdata  	= FE_Member::convert($user);

		// generate new nonces
		$nonce 		= array(
			'reply_thread' => wp_create_nonce( 'insert_reply' ),
			'upload_img'   => wp_create_nonce( 'et_upload_images' ),
			);

		if ( !is_wp_error($user) ){
			$resp = array(
				'success' => true,
				'code' => 200,
				'msg' => __('You have logged in successfully', ET_DOMAIN),
				'redirect' => home_url(),
				'data' => array(
					'user' 		=> $userdata,
					'nonce' 	=> $nonce
				)
			);
		}
		else {
			$resp = array(
				'success' => false,
				'code' => 401,
				'msg' => __('Your login information was incorrect. Please try again.', ET_DOMAIN),
			);
		}

		return $resp;	
	}

	public function logout(){
		wp_logout();
		$resp = array(
			'success' 	=> true,
			'msg' 		=> __('You have logged out', ET_DOMAIN),
		);
		return $resp;
	}
	public function change_logo(){
		$res	= array(
			'success'	=> false,
			'msg'		=> __('There is an error occurred', ET_DOMAIN ),
			'code'		=> 400,
		);
		
		// check fileID
		if(!isset($_POST['fileID']) || empty($_POST['fileID']) ){
			$res['msg']	= __('Missing image ID', ET_DOMAIN );
		}
		else {
			$fileID	= $_POST["fileID"];

			// check author
			if(!isset($_POST['author']) || empty($_POST['author']) || !is_numeric($_POST['author']) ){
				$res['msg']	= __('Missing user data', ET_DOMAIN );
			}
			else{
				$author	= $_POST['author'];
				
				// check ajax nonce
				if ( !check_ajax_referer( 'user_logo_et_uploader', '_ajax_nonce', false ) ){
					$res['msg']	= __('Security error!', ET_DOMAIN );
				}
				elseif(isset($_FILES[$fileID])){

					// handle file upload				
					$attach_id	= et_process_file_upload( $_FILES[$fileID], $author, 0, array(
							'jpg|jpeg|jpe'	=> 'image/jpeg',
							'gif'			=> 'image/gif',
							'png'			=> 'image/png',
							'bmp'			=> 'image/bmp',
							'tif|tiff'		=> 'image/tiff'
						) );

					if ( !is_wp_error($attach_id) ){
						
						// Update the author meta with this logo
						try {
							$user_avatar	= et_get_attachment_data($attach_id);
							/**
							 * get old logo and delete it
							 */
							$old_logo  = get_user_meta( $author, 'et_avatar', true );
							if(isset($old_logo['attach_id'])) {
								$old_logo_id = $old_logo['attach_id'];
								wp_delete_attachment( $old_logo_id, true);
							}
							/**
							 * update new user logo
							*/
							FE_Member::update(array(
									'ID' => $author,
									'et_avatar' => $user_avatar
								));

							$res	= array(
								'success'	=> true,
								'msg'		=> __('User logo has been uploaded successfully!', ET_DOMAIN ),
								'data'		=> $user_avatar
							);
						}
						catch (Exception $e) {
							$res['msg']	= __( 'Problem occurred while updating user field', ET_DOMAIN );
						}
					}
					else{
						$res['msg']	= $attach_id->get_error_message();
					}
				}
				else {
					$res['msg']	= __('Uploaded file not found', ET_DOMAIN);
				}
			}
		}
		return $res;
	}
	public function register(){
		$param = $_REQUEST['content'];
		$args = array( 
			'user_email' => $param['user_email'],
			'user_pass'  => $param['user_pass'],
			'user_login' => $param['user_name'], 
			'display_name' => isset($param['display_name']) ? $param['display_name'] : $param['user_name']
		);

		// validate here, later 
		try {
			if(isset($param['role']) ) {
				$role	=	$param['role'];
			} else {
				$role	=	'subscriber';
			}
			do_action ('je_before_user_register', $args);
			// apply register & log the user in 
			$user_id = et_register( $args , $role, true );
			
			if ( is_wp_error($user_id) ){
				throw new Exception($user_id->get_error_message() , 401);
			}

			$data 	= get_userdata( $user_id );
			$userdata 	= FE_Member::convert($data);
			// generate new nonces
			$nonce 		= array(
				'reply_thread' => wp_create_nonce( 'insert_reply' ),
				'upload_img'   => wp_create_nonce( 'et_upload_images' ),
			);			
			$response = array(
				'success' 		=> true,
				'code' 			=> 200,
				'msg' 			=> __('You are registered and logged in successfully.', ET_DOMAIN),
				'data' => array(
					'user' => $userdata,
					'nonce' => $nonce
				),
				'redirect_url'	=> apply_filters( 'fe_filter_redirect_link_after_register', home_url() )
			);
			
		} catch (Exception $e) {
			$response = array(
				'success' => false,
				'code' => $e->getCode(),
				'msg' => $e->getMessage()
			);
		}

		header( 'HTTP/1.0 200 OK' );
		header( 'Content-type: application/json' );
		echo json_encode($response);
		exit;
	}

	public function forgot(){

		// call the retrieve password request
		$result = et_retrieve_password();

		if ( is_wp_error($result) ){
			$response = array(
				'success' 	=> false,
				'msg' 		=> $result->get_error_message(),
				);
		}
		else {
			$response = array(
				'success' 	=> true,
				'msg' 		=> __('Please check your email inbox to reset password.', ET_DOMAIN),
				);
		}
		return $response;
	}

	function reset_password(){
		try {
			if ( empty($_REQUEST['content']['user_login']) )
				throw new Exception( __("This user is not found.", ET_DOMAIN) );
			if ( empty($_REQUEST['content']['user_key']) )
				throw new Exception( __("Invalid Key", ET_DOMAIN) );
			if ( empty($_REQUEST['content']['new_pass']) )
				throw new Exception( __("Please enter your new password", ET_DOMAIN) );

			// validate activation key
			$validate_result = et_check_password_reset_key($_REQUEST['content']['user_key'], $_REQUEST['content']['user_login']);
			if ( is_wp_error($validate_result) ){
				throw new Exception( $validate_result->get_error_message() );
			}

			// do reset password
			$user = get_user_by('login', $_REQUEST['content']['user_login']);
			$reset_result = et_reset_password($user, $_REQUEST['content']['new_pass']);

			if ( is_wp_error($reset_result) ){
				throw new Exception( $reset_result->get_error_message() );
			}
			else {
				$response = array(
					'success' 	=> true,
					'code' 		=> 200,
					'msg' 		=> __('Your password has been changed. Please log in again.', ET_DOMAIN),
					'data' 		=> array(
						'redirect_url' => home_url()
						)
				);
			}
		} catch (Exception $e) {
			$response = array(
				'success' 	=> false,
				'code' 		=> 400,
				'msg' 		=> $e->getMessage(),
				'data' 		=> array(
					'redirect_url' => home_url()
					)
				);
		}
		return $response;
	}
	public function inbox(){
		$args 		= $_POST['content'];

		global $current_user;

		try {
			if ( !$current_user->ID ){
				throw new Exception(__('Login required', ET_DOMAIN));
			}

			$author 	= get_user_by( 'id', $args["user_id"] );
			$to_email 	= $author->user_email;
			$from_email = $current_user->user_email;

			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
			$headers .= "From: $current_user->display_name <$from_email>" . "\r\n";
			$headers .= 'Reply-To: '.$current_user->display_name.' <'.$from_email.'>' . "\r\n";

			$subject  	=	apply_filters('fe_inbox_subject',__('New Private Message From ', ET_DOMAIN).get_bloginfo('blogname' ));	
			$new_message  = stripslashes(str_replace("\n", "<br>", $args['message'])) ;
			$blogname 	= get_bloginfo('blogname');
			$home   	= home_url();
			$sender 	= get_author_posts_url($current_user->ID);
			$message 	= <<<HTML
	Hi {$author->display_name},

	<p>You have just received the following message from user: <a href="{$sender}">{$current_user->display_name}</a> in <a href="{$home}">{$blogname}</a>:</p>
	<br>
	{$new_message}
	<br><br>
	<p>Sincerely,</p>
	{$blogname}
HTML;
			$send     = wp_mail($to_email, $subject , $message, $headers);

			if(!($send)){
				throw new Exception(__('Email sending failed.', ET_DOMAIN));
			}

			$resp = array(
				'success' 	=> true,
				'msg' 		=> 'Message was sent successfully.',
				//'send'		=> $current_user->display_name.'--'.$from_email//$to_email .'-'. $subject .'-'. $message.'-'.$headers
			);
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}

		return $resp;
	}
	/**
	 * 
	 */
	public function follow(){
		$args 		= $_POST['content'];
		$thread_id 	= $args['post_id'];

		global $current_user;

		try {
			if ( !$current_user->ID ){
				throw new Exception(__('Login required', ET_DOMAIN));
			}

			$result = FE_Member::toggle_follow($thread_id, $current_user->ID);

			if (!is_array($result))
				throw new Exception(__('Error occurred', ET_DOMAIN));

			if(in_array($current_user->ID, $result)){
				$msg = __( 'You have started following this thread.', ET_DOMAIN );
			} else {
				$msg = __( 'You have stopped following this thread.', ET_DOMAIN );
			}
			
			$resp = array(
				'success' 	=> true,
				'msg' => $msg,
				'data' 		=> array(
					'isFollow' 	=> in_array($current_user->ID, $result),
					'following' => $result
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

	/**
	 * Query member
	 *
	 */
	public function get_members(){
		try {
			$query_vars = wp_parse_args( $_POST['content']['query_vars'], array('search_columns' => array('user_nicename', 'user_login')));

			if ( !empty($query_vars['search']) )
				$query_vars['search'] = "*" . $query_vars['search'] . "*";

			$query = new WP_User_Query($query_vars);

			if ( !empty($query->results) ){
				$result = array();

				foreach ($query->results as $user) {
					$info = (array)$user->data + array(
						'id' 				=> $user->ID,
						'thread_count' 		=> get_user_meta($user->ID, 'et_thread_count',true) ? get_user_meta($user->ID, 'et_thread_count',true) : 0,
						'reply_count' 		=> get_user_meta($user->ID, 'et_reply_count', true) ? get_user_meta($user->ID, 'et_reply_count',true) : 0,
						'user_location' 	=> get_user_meta($user->ID, 'user_location', true) ? get_user_meta($user->ID, 'user_location', true) : 'NA',
						'date_text' 		=> sprintf( __('Join on %s', ET_DOMAIN), date('jS M, Y', strtotime($user->user_registered)) ),
						'role' 				=> $user->roles[0],
						'avatar' 			=> et_get_avatar($user->ID)
					);

					$result[] = $info;
				}

			} else {
				throw new Exception(__('No result found', ET_DOMAIN));
			}

			$resp = array(
				'success' 	=> true,
				'msg' 		=> '',
				'data' 		=> array(
					'users' => $result,
					'total' => (int)$query->total_users,
					'offset' => (int)$query_vars['offset'],
					'number' => (int)$query_vars['number']
				)
			);
			
		} catch (Exception $e) {
			$resp = array(
				'success' 	=> false,
				'msg' 		=> $e->getMessage()
			);
		}
		// $this->ajax_header();
		// echo json_encode($resp);
		return $resp;
	}

	/**
	 * 
	 */
	public function update_role(){
		try {
			// validate
			if (!current_user_can('manage_options'))
				throw new Exception( __("You don't have permission", ET_DOMAIN) );

			$data = $_POST['content'];

			$result = wp_update_user( array(
				'ID' 		=> $data['ID'],
				'role' 		=> $data['role']
			) );

			if ( is_wp_error( $result ) )
				throw new Exception( $result->get_message() );
			else if ( !$result )
				throw new Exception( __("Fail to update user permission", ET_DOMAIN) );



			$resp = array(
				'success' 	=> true,
				'data' 		=> array(
					'ID' 	=> $result
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
}



/**
 * Additional functions
 */
function et_count_user_posts($user_id,$post_type = "thread"){
	global $wpdb;
	$sql = "SELECT COUNT(post.ID) 
				FROM {$wpdb->posts} as post
				WHERE post.post_type = '".$post_type."' 
					AND post.post_status = 'publish'
					AND post.post_author = ".$user_id;
	return $wpdb->get_var( $sql );
}
function et_count_follow_threads($user_id){
	if(get_user_meta( $user_id, 'et_following_threads', true ))
		return count(get_user_meta( $user_id, 'et_following_threads', true ));
	else 
		return 0;
}
function et_add_user_group($role_id, $display_name, $permission){
	add_role( $role_id, $display_name, $permission );
}

function et_remove_user_group($role){
	// check if role has
	$users = get_users(array(
		'roles' => $role
	));

	// if at least there is a user in role, return error
	if (!empty($users)) return false;

	// if 
	remove_role( $role );

	return true;	
}
function fe_update_user_likes($thread_id, $method = "like", $delete = 0){

	$thread = get_post( $thread_id );
	$user_id  = $thread->post_author;
	$likes = get_user_meta( $user_id, 'et_like_count', true ) ? (int) get_user_meta( $user_id, 'et_like_count', true ) : 0;
	
	if($method == "like"){
		$count = $likes + 1;
		update_user_meta( $user_id, 'et_like_count', $count);
	}
	elseif ($method == "delete") {
		$count= $likes - $delete;
		update_user_meta( $user_id, 'et_like_count', $count > 0 ? $count : 0);
	}
	elseif ($method == "undo") {
		$count= $likes + $delete;
		update_user_meta( $user_id, 'et_like_count', $count);
	}
	else {
		$count= $likes > 1 ? $likes-1 : 0;
		update_user_meta( $user_id, 'et_like_count', $count);
	}
}

function get_user_role( $user_id ){

  $user_data = get_userdata( $user_id );

  if(!empty( $user_data->roles ))
      return $user_data->roles[0];

  return false; 

}