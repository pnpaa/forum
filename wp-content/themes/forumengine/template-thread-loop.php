<?php 



global $user_ID;
$thread 			= FE_Threads::convert($post);
$et_updated_date 	= et_the_time(strtotime($thread->et_updated_date));
$sticky 			= et_get_option('et_sticky_threads');

?>
	<li class="<?php echo et_is_highlight($thread->ID); ?> thread-item <?php echo in_array($post->ID, $sticky) ? 'sticky' : '' ?>" data-id="<?php echo $post->ID ?>">
		<?php do_action('forumengine_before_thread_item', $thread) ?>
		<?php if(!is_author() && !is_page_template( 'page-member.php' )) {?>
		<a href="<?php the_permalink() ?>">
			<span class="thumb avatar">
				<?php echo  et_get_avatar($post->post_author);?>
				<?php do_action( 'fe_user_badge', $post->post_author ); ?>
			</span>
		</a>
		<?php } ?>
		<div class="f-floatright">
			<?php do_action('forumengine_before_thread_item_infomation', $thread) ?>
			<span class="title">
				<a href="<?php the_permalink() ?>">
					<?php the_title() ?> 
					<?php if ( $post->post_status == 'closed' ) { echo '<span class="icon" data-icon="("></span>'; } ?>
				</a>
			</span>
			<div class="post-information">
				<span class="times-create"><?php printf( __( 'Updated %s in', ET_DOMAIN ),$et_updated_date); ?></span>
				<span class="type-category">
					<?php 
					if ( !empty($thread->thread_category[0]) )
						$color = FE_ThreadCategory::get_category_color($thread->thread_category[0]->term_id);
					else 
						$color = 0;
					?>
					<a href="<?php if($thread->thread_category){ echo get_term_link( $thread->thread_category[0]->slug, 'thread_category' );}else{echo '#';} ?>">
						<span class="flags color-<?php echo $color ?>"></span>
						<?php 
						if($thread->thread_category) { 
							echo $thread->thread_category[0]->name; 
						} else {
							_e('No category', ET_DOMAIN);
						}
						?>
					</a>.
				</span>
				<span class="author">
				<?php if ( $thread->et_last_author == false ){
						_e( 'No reply yet', ET_DOMAIN );
					} else {
				?>
					<span class="last-reply"><a href="<?php echo et_get_last_page($thread->ID) ?>"><?php _e('Last reply',ET_DOMAIN);?></a></span> <?php _e('by',ET_DOMAIN);?> <?php echo '<span class="semibold"><a href="'.get_author_posts_url($thread->et_last_author->ID).'">'. $thread->et_last_author->display_name .'</a></span>.' ?>
				<?php
					} 
				?>
				</span>
				<span class="user-action">
					<span class="comment <?php if($thread->replied) echo 'active';?>"><span class="icon" data-icon="w"></span><?php echo $thread->et_replies_count ?></span>
					<span class="like <?php if($thread->liked) echo 'active';?>"><span class="icon" data-icon="k"></span><?php echo $thread->et_likes_count ?></span>
				</span>
				<span class="undo-action hide">
					<?php printf( __('Want to %s ?') , '<a href="#" class="act-undo">' . __('undo', ET_DOMAIN) . '</a>' ); ?>
				</span>
			</div>
			<?php if(current_user_can("manage_threads")) {?>
			<div class="control-thread-group">
				<?php if ( $thread->post_status == 'pending' ){ ?>
					<a href="#" data="<?php echo $thread->ID; ?>" class="approve-thread" data-toggle="tooltip" title="<?php _e('Approve', ET_DOMAIN) ?>"><span class="icon" data-icon="3"></span></a>
					<a href="#" data="<?php echo $thread->ID; ?>" class="delete-thread" data-toggle="tooltip" title="<?php _e('Delete', ET_DOMAIN) ?>"><span class="icon" data-icon="#"></span></a>				
				<?php } else {  ?>
					<a href="#" class="close-thread <?php if ( $thread->post_status == 'closed' ) echo 'collapse' ?>" data-toggle="tooltip" title="<?php _e('Close', ET_DOMAIN) ?>"><span class="icon" data-icon="("></span></a>
					<a href="#" class="unclose-thread <?php if ( $thread->post_status != 'closed' ) echo 'collapse' ?>" data-toggle="tooltip" title="<?php _e('Unclose', ET_DOMAIN) ?>"><span class="icon" data-icon=")"></span></a>
					
					<a href="#" class="delete-thread" data-toggle="tooltip" title="<?php _e('Delete', ET_DOMAIN) ?>"><span class="icon" data-icon="#"></span></a>
				<?php } ?>
			</div>
			<?php } ?>
			<?php do_action('forumengine_after_thread_item_infomation', $thread) ?>
		</div>
		<?php do_action('forumengine_after_thread_item', $thread) ?>
	</li>