<?php 

class ET_AdminMember extends ET_AdminMenuItem{

	private $options;

	function __construct(){
		parent::__construct('et-members',  array(
			'menu_title'	=> __('Members', ET_DOMAIN),
			'page_title' 	=> __('MEMBERS', ET_DOMAIN),
			'callback'   	=> array($this, 'menu_view'),
			'slug'			=> 'et-members',
			'page_subtitle'	=> __('ForumEngine members', ET_DOMAIN),
			'pos' 			=> 15
		));

		$this->add_ajax('et-filter-stat', 'filter_stat');
	}

	public function on_add_scripts(){
		$this->add_existed_script( 'jquery' );
		$this->add_existed_script( 'underscore' );
		$this->add_existed_script( 'backbone' );
		//$this->add_existed_script( 'jquery-ui-datepicker' );
		?>
		<!--[if lt IE 9]> <?php $this->add_script( 'excanvas', TEMPLATEURL . '/js/libs/excanvas.min.js' ); ?> <![endif]-->
		<?php 
		$this->add_script('fe-function',  		TEMPLATEURL . '/js/functions.js', array('jquery', 'backbone', 'underscore' ));
		$this->add_script('backend-script',  	TEMPLATEURL . '/admin/js/admin.js', array('jquery', 'backbone', 'underscore' ));
		$this->add_script('backend-member',  	TEMPLATEURL . '/admin/js/members.js', array('jquery', 'backbone', 'underscore', 'backend-script' ));
	}

	public function on_add_styles(){
		$this->add_style( 'admin_styles', TEMPLATEURL . '/admin/css/admin.css', array(), false, 'all' ); 
		$this->add_style( 'admin_forum_styles', TEMPLATEURL . '/admin/css/admin-forum.css', array(), false, 'all' ); 
	}

	public function menu_view($args){ 
		global $wp_roles;
		?>
		<div class="et-main-header">
			<div class="title font-quicksand"><?php echo $args->menu_title ?></div>
			<div class="desc"><?php echo $args->page_subtitle ?>. 
			</div>
		</div>
		<div class="et-main-content" id="overview">
			<div class="et-container et-member-search">
				<form action="">
				<?php global $wp_roles;  //var_dump($wp_roles); ?>
					<span class="et-search-role">
						<select name="role" id="" class="et-input">
							<option value=""><?php _e('All members', ET_DOMAIN) ?></option>
							<option value="administrator"><?php _e('Administrators', ET_DOMAIN) ?></option>
							<option value="moderator"><?php _e('Moderators', ET_DOMAIN) ?></option>
						</select>
					</span>
					<span class="et-search-input">
						<input type="text" class="et-input" name="keyword" placeholder="<?php _e('Search members...', ET_DOMAIN) ?>">
						<span class="et-search-ico"></span>
					</span>
				</form>
			</div>
			<div class="et-container et-members-list">
				<h3 class="title font-quicksand"><?php _e('Members', ET_DOMAIN) ?></h3>
				<?php 
				$posts_per_page = get_option( 'posts_per_page');
				$query = new WP_User_Query(array(
					'number'	=> $posts_per_page
				));
				$users = $query->results;

				if ( !empty($users) ) { ?>
					<ul id="members_list">
						<?php foreach ($users as $user) { 
							$info = array(
								'thread_count' => get_user_meta($user->ID, 'et_thread_count',true),
								'reply_count' => get_user_meta($user->ID, 'et_reply_count', true),
								'user_location' => get_user_meta($user->ID, 'user_location', true),
							);

							$info['thread_count'] 	= !empty($info['thread_count']) ? $info['thread_count'] : 0;
							$info['reply_count'] 	= !empty($info['reply_count']) ? $info['reply_count'] : 0;

							?>
							<li class="et-member" data-id="<?php echo $user->ID ?>">
								<div class="et-mem-container">
									<div class="et-mem-avatar">
										<?php echo et_get_avatar($user->ID);?>
									</div>
									<div class="et-act">
										<select name="role" id="" class="selector et-act-select" <?php if ( $user->ID == 1 ) echo 'disabled="disabled"' ?>>
											<?php foreach ($wp_roles->roles as $role => $data) {
												if ( $user->roles[0] == $role )
													echo '<option value="' . $role . '" selected="selected">' . $data['name'] . '</option>';
												else 
													echo '<option value="' . $role . '">' . $data['name'] . '</option>';
											} ?>
										</select>
										<!-- <a class="et-act-ban" href="#"><span class="icon" data-icon="("></span></a> -->
									</div>
									<div class="et-mem-detail">
										<div class="et-mem-top">
											<span class="name"><?php echo $user->display_name ?></span>
											<span class="thread icon" data-icon="w"><?php echo $info['thread_count'] ?></span>
											<span class="comment icon"  data-icon="q"><?php echo $info['reply_count'] ?></span>
										</div>
										<div class="et-mem-bottom">
											<span class="date"><?php printf( __('Join on %s', ET_DOMAIN), date('jS M, Y', strtotime($user->user_registered)) ) ?></span>
											<span class="loc icon" data-icon="@"><?php echo !empty($info['user_location']) ? $info['user_location'] : 'NA' ?></span>
										</div>
									</div>
								</div>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>				
				<button class="et-button btn-button <?php if ( $query->total_users <= $posts_per_page ) echo 'hide' ?>" id="load-more">Load more</button>

				<script type="text/template" id="member_template">
					<div class="et-mem-container">
						<div class="et-mem-avatar">
							<%= avatar %>
						</div>
						<div class="et-act">
							<select name="role" id="" class="selector et-act-select" <% if (id == 1) { %> disabled="disabled" <% } %>>
								<?php foreach ($wp_roles->roles as $role => $data) {
									echo "<option value='$role' <% if ('$role' == role) { %>selected='selected'<% }%> >{$data['name']}</option>";
								} ?>
							</select>
							<?php /*<a class="et-act-ban" href="#"><span class="icon" data-icon="("></span></a> */ ?>
						</div>
						<div class="et-mem-detail">
							<div class="et-mem-top">
								<span class="name"><%=display_name%></span>
								<span class="thread icon" data-icon="w"><%=thread_count%></span>
								<span class="comment icon"  data-icon="q"><%=reply_count%></span>
							</div>
							<div class="et-mem-bottom">
								<span class="date"><%= date_text %></span>
								<span class="loc icon" data-icon="@"><%=user_location%></span>
							</div>
						</div>
					</div>
				</script>
				<?php /* <ul>
					<li class="et-member">
						<div class="et-mem-container">
							<div class="et-mem-avatar">
								<?php echo get_avatar( 'n.minhtoan@gmail.com', 35 ); ?>
							</div>
							<div class="et-act">
								<select name="" id="" class="selector et-act-select">
									<option value="">Administrator</option>
									<option value="">Mod</option>
									<option value="">Member</option>
								</select>
								<a class="et-act-ban" href="#"><span class="icon" data-icon="("></span></a>
							</div>
							<div class="et-mem-detail">
								<div class="et-mem-top">
									<span class="name">Hoang Nguyen</span>
									<span class="thread icon" data-icon="w">5</span>
									<span class="comment icon"  data-icon="q">25</span>
								</div>
								<div class="et-mem-bottom">
									<span class="date">join on 18th Sep, 2013</span>
									<span class="loc icon" data-icon="@">Viet Nam</span>
								</div>
							</div>
						</div>
					</li>
					<li class="et-member">
						<div class="et-mem-container">
							<div class="et-mem-avatar">
								<?php echo get_avatar( 'n.minhtoan@gmail.com', 35 ); ?>
							</div>
							<div class="et-act">
								<select name="" id="" class="selector et-act-select">
									<option value="">Administrator</option>
									<option value="">Mod</option>
									<option value="">Member</option>
								</select>
								<a class="et-act-ban" href="#"><span class="icon" data-icon="("></span></a>
							</div>
							<div class="et-mem-detail">
								<div class="et-mem-top">
									<span class="name">Hoang Nguyen</span>
									<span class="thread icon" data-icon="w">5</span>
									<span class="comment icon"  data-icon="q">25</span>
								</div>
								<div class="et-mem-bottom">
									<span class="date">join on 18th Sep, 2013</span>
									<span class="loc icon" data-icon="@">Viet Nam</span>
								</div>
							</div>
						</div>
					</li>
					<li class="et-member">
						<div class="et-mem-container">
							<div class="et-mem-avatar">
								<?php echo get_avatar( 'n.minhtoan@gmail.com', 35 ); ?>
							</div>
							<div class="et-act">
								<select name="" id="" class="selector et-act-select">
									<option value="">Administrator</option>
									<option value="">Mod</option>
									<option value="">Member</option>
								</select>
								<a class="et-act-ban" href="#"><span class="icon" data-icon="("></span></a>
							</div>
							<div class="et-mem-detail">
								<div class="et-mem-top">
									<span class="name">Hoang Nguyen</span>
									<span class="thread icon" data-icon="w">5</span>
									<span class="comment icon"  data-icon="q">25</span>
								</div>
								<div class="et-mem-bottom">
									<span class="date">join on 18th Sep, 2013</span>
									<span class="loc icon" data-icon="@">Viet Nam</span>
								</div>
							</div>
						</div>
					</li>
				</ul> */?>
			</div>
		</div>
		<?php
	}
}
?>