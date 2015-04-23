<?php
get_header();
global $wp_query, $wp_rewrite, $post,$current_user , $user_ID;

$data = et_get_unread_follow();
$term = get_term_by( 'slug' , get_query_var( "term" ), 'thread_category') ;

?>

<div class="header-bottom header-filter">
	<div class="main-center container">
		<ul class="nav-link">
			<li <?php if(is_front_page()){ ?> class="active" <?php }?>>
				<a href="<?php echo home_url() ?>">
					<span class="icon" data-icon="W"></span>
					<span class="text"><?php _e('ALL POSTS',ET_DOMAIN) ?></span>
					<?php 
						if(!empty($data) && isset($data['unread']['data']) && count($data['unread']['data']) > 0){
					?>
					<span class="number"><?php echo count($data['unread']['data']) ?></span>
					<?php } ?>
				</a>
			</li>
			<li>
				<a
			<?php 
				if($user_ID){
					echo 'href="'.et_get_page_link("following").'"';	
				} else {
					echo 'id="open_login" data-toggle="modal" href="#modal_login"';
				}
			?>>
				<span class="icon" data-icon="&"></span>
				<span class="text"><?php _e('Following',ET_DOMAIN) ?></span>
				<?php if($user_ID && count($data['follow']) > 0){ ?>
				<span class="number"><?php echo count($data['follow']) ;?></span>
				<?php } ?>
				</a>
			</li>
			<?php if ( et_get_option("pending_thread") && (et_get_counter('pending') > 0) &&(current_user_can("manage_threads") || current_user_can( 'trash_threads' )) ) {?>
			<li>
				<a href="<?php echo et_get_page_link("pending");?>">
					<span class="icon" data-icon="N"></span>
					<span class="text"><?php _e('PENDING POSTS',ET_DOMAIN) ?></span>
				</a>
			</li>
			<?php } ?>
			<?php if(is_tax()) { ?>
			<li class="tax-<?php echo FE_ThreadCategory::get_category_color($term->term_id); ?> active">
				<a href="<?php echo get_term_link( $term, 'thread_category')?>">
					<span class="text"><?php echo $term->name; ?></span>
				</a>
			</li>			
			<?php } ?>
		</ul>
	</div>
	<div class="mo-menu-toggle visible-sm visible-xs">
		<a class="icon-menu-tablet" href="#"><?php _e('open', ET_DOMAIN ) ?></a>
	</div>
</div>
<!--end header Bottom-->
<div class="container main-center">
	<div class="row">        
		<div class="col-md-9 col-sm-12 marginTop30">
			<div id="form_thread" class="thread-form auto-form new-thread">
				<form action="" method="post">
					<input type="hidden" name="fe_nonce" class="fe_nonce" value="<?php echo wp_create_nonce( 'insert_thread' ) ?>">
					<div class="text-search">
						<div class="input-container">
							<input class="inp-title" id="thread_title" maxlength="90" name="post_title" type="text" autocomplete="off" placeholder="<?php _e('Click here to start your new topic' , ET_DOMAIN) ?>">
						</div>
						<div class="btn-group cat-dropdown dropdown category-search-items collapse">
							<span class="line"></span>
							<button class="btn dropdown-toggle" data-toggle="dropdown">
								<span class="text-select"></span>
								<span class="caret"></span>
							</button>
							<?php 
							$categories = FE_ThreadCategory::get_categories();
							?>
							<select class="collapse" name="thread_category" id="thread_category">
								<option value=""><?php _e('Please select' , ET_DOMAIN) ?></option>
								<?php et_the_cat_select($categories) ?>
							</select>
						</div>
				  	</div>
					<div class="form-detail collapse">
						<?php //wp_editor( '' , 'post_content' , editor_settings() ); ?>
						<div id="wp-post_content-editor-container" class="wp-editor-container">
							<textarea id="post_content" name="post_content"></textarea>
						</div>
						<?php do_action( 'fe_custom_fields_form' ); ?>
						<div class="row line-bottom">
							<div class="col-md-6">
								<div class="show-preview">
									<div class="skin-checkbox">
										<span class="icon" data-icon="3"></span>
										<input type="checkbox" name="show_preview" class="checkbox-show" id="show_topic_item" style="display:none" />
									</div>
									<a href="#"><?php _e('Show preview' , ET_DOMAIN) ?></a>
								</div>
							</div>
							<div class="col-md-6">
								<div class="button-event">
									<input type="submit" value="
									<?php 
										if($user_ID){
											_e('Create Topic', ET_DOMAIN);
										} else {
											_e('Login and Create Topic', ET_DOMAIN);
										}
									?>
									" class="btn">
									<a href="#" class="cancel"><span class="btn-cancel"><span class="icon" data-icon="D"></span><?php _e('Cancel' , ET_DOMAIN) ?></span></a>
								</div>
							</div>
						</div>
					</div>
				</form>
				<div id="thread_preview">
					<div class="name-preview"><?php _e('YOUR PREVIEW' , ET_DOMAIN) ?></div>
			        <div class="reply-item items-thread clearfix preview-item">
						<div class="f-floatleft">
							<?php echo  et_get_avatar($user_ID);?>
						</div>
						<div class="f-floatright">
							<div class="post-display">
								<div class="post-information">
									<div class="name">
										<span class="post-author"><?php echo $current_user->display_name;?></span>
										<span class="comment"><span class="icon" data-icon="w"></span>0</span>
										<span class="like"><span class="icon" data-icon="k"></span>0</span>
									</div>
								</div>
								<div class="text-detail content"></div>
							</div>
						</div>
			        </div>
				</div><!-- End Preview Thread -->
			</div> <!-- End Form Thread -->
			<?php 
			$page = get_query_var('page') ? get_query_var('page') : 1;
			$thread_query = FE_Threads::get_threads(array(
				'post_type' 	=> 'thread',
				'paged' 		=> $page
			));

			if (  $thread_query->have_posts() ){ ?>
				<ul id="main_list_post" class="list-post">
					<?php 
					/**
					 * Display regular threads
					 */
					while ($thread_query->have_posts()){
						$thread_query->the_post();

						// get template file to render
						$template_file = get_template_directory() . '/template-thread-loop.php';
						if ( file_exists( get_stylesheet_directory() . '/template-thread-loop.php' ) )
							$template_file = get_stylesheet_directory() . '/template-thread-loop.php';

						$template_file = apply_filters( 'forumengine_thread_list_item', $template_file );

						load_template( $template_file, false );

						//get_template_part( 'template', 'thread-loop' );
					} // end while 
					?>
				</ul>
				<?php 
			} else { ?>
				<div class="notice-noresult">
					<span class="icon" data-icon="!"></span><?php _e('No topic has been created yet.', ET_DOMAIN) ?> <a href="#" id="create_first"><?php _e('Create the first one', ET_DOMAIN) ?></a>.
				</div>
				<?php 
			} // end if
			//wp_reset_query();
			?>
			<?php if(!get_option( 'et_infinite_scroll' )){ ?>

			<!-- Normal Paginations -->
			<div class="pagination pagination-centered" id="main_pagination">
				<?php 
					echo paginate_links( array(
						'base' 		=> str_replace('99999', '%#%', esc_url(get_pagenum_link( 99999 ))),
						'format' 	=> $wp_rewrite->using_permalinks() ? 'page/%#%' : '?paged=%#%',
						'current' 	=> max(1, $page),
						'total' 	=> $thread_query->max_num_pages,
						'prev_text' => '<',
						'next_text' => '>',
						'type' 		=> 'list'
					) ); 
				?>
			</div>
			<!-- Normal Paginations -->

			<?php } else { ?>

			<!-- Infinite Scroll -->
			<?php 
				$fetch = ($page < $thread_query->max_num_pages) ? 1 : 0 ;
				//$check = round((int) 10 / (int) get_option( 'posts_per_page' ) , 0 , PHP_ROUND_HALF_DOWN);
				$check = floor((int) 10 / (int) get_option( 'posts_per_page' ));
			?>
			<div id="loading" class="hide" data-fetch="<?php echo $fetch ?>" data-status="scroll-index" data-check="<?php echo $check ?>">
				<!-- <img src="<?php echo get_template_directory_uri(); ?>/img/ajax-loader.gif"> -->
				<div class="bubblingG">
					<span id="bubblingG_1">
					</span>
					<span id="bubblingG_2">
					</span>
					<span id="bubblingG_3">
					</span>
				</div>
				<?php _e( 'Loading more threads', ET_DOMAIN ); ?>
				<input type="hidden" value="<?php echo $page ?>" id="current_page">
				<input type="hidden" value="<?php echo $thread_query->max_num_pages ?>" id="max_page">
			</div>
			<!-- Infinite Scroll -->

			<?php } ?>
		</div>
		<div class="col-md-3 hidden-sm hidden-xs sidebar">
			<?php get_sidebar() ?>
		</div>
	</div>
</div>
 
<?php get_footer(); ?>

