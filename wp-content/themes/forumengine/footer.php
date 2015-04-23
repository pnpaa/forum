		<footer>
		  <div class="footer">
			<div class="row main-center">
			  <div class="col-md-3">
				<ul class="social">
					<?php $links = array(
						'fb' => et_get_option("et_facebook_link"),
						'tw' => et_get_option("et_twitter_account"),
						'rss' => get_feed_link( 'rss2' ),
						'mail' => et_get_option("admin_email")
					) ?>
					<?php if ( $links['fb'] != "http://") { ?>
						<li class="fb"><a target="_blank" href="<?php echo $links['fb']; ?>">Facebook</a></li>
					<?php }
					if ( $links['tw'] != "http://" ) { ?>
						<li class="tw"><a target="_blank" href="<?php echo $links['tw']; ?>">Twitter</a></li>
					<?php } ?>
					<li class="rss"><a target="_blank" href="<?php echo $links['rss'] ?>">Rss</a></li>
					<li class="mail"><a target="_blank" href="mailto:<?php echo $links['mail'] ?>">Mail</a></li>
				</ul>
			  </div>
			  <div class="col-md-9 row">
				<div class="nav-wrap col-sm-6">
					<ul class="nav">
						<?php
							if(has_nav_menu('et_footer')){
								wp_nav_menu(array(
										'theme_location' => 'et_footer',
										'items_wrap' => '%3$s',
										'container' => ''
									));
							}
						?>
					</ul>
				</div>
				<div class="copyright-wrap col-sm-6">
					<ul class="nav fright">
					  <li class="copyright">
					  	<?php echo et_get_option("et_copyright") ?><br>
					  	<span><a href="http://www.enginethemes.com/themes/forumengine/" target="_blank">WordPress Forum Theme</a> - Powered by WordPress</span>
					  </li>
					</ul>
				</div>
			  </div>
			</div>
		  </div>
		</footer><!-- End Footer -->
	    <?php if(is_front_page() || is_singular( 'thread' ) || is_tax()){
	    	global $user_ID;
	    ?>
		<!-- TinyMCE Upload / Insert Image Form -->
		<div class="upload-img">
			<div class="modal" id="uploadImgModal" style="display:none;" aria-hidden="true">
				<form id="modal">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
								<span class="icon" data-icon="D"></span>
							</button>
							<h4 class="modal-title"><?php _e( 'Insert an Image', ET_DOMAIN ) ?></h4>
						</div>
		                <div class="modal-body">

		                	<!-- Form Upload Images -->
		                	<?php if(!$user_ID){ ?>
		                	<p class="text-danger"><?php _e('You need to log in to upload images from your computer.', ET_DOMAIN ) ?></p>
		                	<?php } ?>
		                	<?php if(!get_option('upload_images')){ ?>
		                	<p class="text-danger"><?php _e('Admin has disabled this function.', ET_DOMAIN ) ?></p>
		                	<?php } ?>

		                  	<div  <?php if(!$user_ID || !get_option('upload_images')){ echo 'style="opacity:0.4;"';} ?> class="upload-location <?php if(!$user_ID || !get_option('upload_images')){ echo 'disabled';} ?>" id="images_upload_container">
			                    <span><?php _e( 'Upload an Image', ET_DOMAIN ) ?></span>
			                    <div class="input-file">
			                      	<input type="button" <?php if(!$user_ID || !get_option('upload_images')){ echo 'disabled="disabled"';} ?> value="<?php _e("Browse",ET_DOMAIN);?>" class="bg-button-file button" id="images_upload_browse_button">
			                      	<span class="filename"><?php _e("No file chosen",ET_DOMAIN);?></span>
			                      	<span class="et_ajaxnonce" id="<?php echo wp_create_nonce( 'et_upload_images' ); ?>"></span>
			                    </div>
		                  	</div>
		                  	<!-- Form Upload Images -->

		                  	<!-- Form Insert Link Images -->
			                <div class="upload-url">
			                    <span><?php _e( 'Add an Image by URL', ET_DOMAIN ) ?></span>
			                    <div class="input-url">
			                      	<input type="text" placeholder="https://www.images.jpg" id="external_link" class="form-control">
				                    <div class="button-event">
				                  		<button type="button" id="insert" class="btn"><?php _e( 'Insert', ET_DOMAIN ) ?></button>
				                    	<span class="btn-cancel" data-dismiss="modal"><span data-icon="D" class="icon"></span><?php _e( 'Cancel', ET_DOMAIN ) ?></span>
				                    </div>
			                    </div>
			                </div>
			                <!-- Form Insert Link Images -->
		                </div>
		            </div>
		        </div>
		    	</form>
		    </div>
		</div>

		<!-- Modal insert link -->
		<div class="upload-img modal-insert-link">
			<div class="modal" id="insertLink" style="display:none;" aria-hidden="true">
				<form id="wp-link-1" class="main-form">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
								<span class="icon" data-icon="D"></span>
							</button>
							<h4 class="modal-title"><?php _e( 'Insert your link.', ET_DOMAIN ) ?></h4>
						</div>
		                <div class="modal-body">
			                <div class="insert-link">
			                    <div class="input-url">
			                    	<div class="controls">
				                    	<label for="url-field"><?php _e( 'Copy URL here', ET_DOMAIN ) ?></label>
				                      	<span class="line-correct collapse"></span>
				                      	<input type="text" placeholder="https://www.images.jpg" id="url-field" class="form-control">
			                      	</div>
			                      	<div class="controls">
				                    	<label for="link-title-field"><?php _e( 'Title', ET_DOMAIN ) ?></label>
				                      	<span class="line-correct collapse"></span>
				                      	<input type="text" placeholder="" id="link-title-field" class="form-control">
			                      	</div>
				                    <div class="button-event">
				                    	<button type="button" id="wp-link-submit" class="btn"><?php _e( 'Insert', ET_DOMAIN ) ?></button>
				                    	<span class="btn-cancel" data-dismiss="modal"><span data-icon="D" class="icon"></span><?php _e( 'Cancel', ET_DOMAIN ) ?></span>
				                    </div>
			                    </div>
			                </div>
		                </div>
		            </div>
		        </div>
		    	</form>
		    </div>
		</div>
		<!-- TinyMCE Upload / Insert Image Form -->
		<?php } //end modal login & upload images ?>
		<!-- Modal Login -->
		<div class="modal" id="modal_login" style="display:none;">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="login-modal">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
								<span class="icon" data-icon="D"></span>
							</button>
							<h4 class="modal-title"><?php _e( 'Login or Join', ET_DOMAIN ) ?></h4>
						</div>
						<div class="modal-body">
							<div class="login-fr">
								<ul class="social-icon clearfix">
								<?php if(et_get_option('twitter_login', false)){?>
									<li class="tw"><a href="<?php echo add_query_arg('action', 'twitterauth', home_url()) ?>">Twitter</a></li>
								<?php } ?>
								<?php if(et_get_option('facebook_login', false)){?>
									<li class="fb"><a href="#" id="facebook_auth_btn">facebook</a></li>
								<?php } ?>
								</ul>
								<form id="form_login" class="form-horizontal">
									<div class="form-group">
										<div class="col-lg-10">
											<span class="line-correct collapse"></span>
											<input type="text" name="user_name" class="form-control" id="user_name" title="<?php _e( 'Enter your username or email', ET_DOMAIN ) ?>" placeholder="<?php _e( 'Enter your username or email', ET_DOMAIN ) ?>">
											<span class="icon collapse" data-icon="D"></span>
										</div>
									</div>
									<div class="form-group">
										<div class="col-lg-10">
											<span class="line-correct collapse"></span>
											<input type="password" name="user_pass" class="form-control" id="user_pass" title="<?php _e( 'Password', ET_DOMAIN ) ?>" placeholder="<?php _e( 'Password', ET_DOMAIN ) ?>">
											<span class="icon  collapse" data-icon="D"></span>
										</div>
									</div>
							  		<div class="form-group">
										<div class="col-lg-10">
								  			<div class="btn-submit">
												<a href="#" class="bnt_forget"><?php _e( 'Forgotten password?', ET_DOMAIN ) ?></a>
												<button type="submit" class="btn"><?php _e( 'Login', ET_DOMAIN ) ?></button>
								  			</div>
										</div>
							  		</div>
								</form>
						  	</div> <!--form login -->
						  	<div class="join">
								<form id="form_register" class="form-horizontal">
							  		<div class="form-group">
										<div class="col-lg-10">
											<span class="line-correct collapse"></span>
								  			<input type="text" name="user_name" class="form-control" id="user_name" title="<?php _e( 'Username', ET_DOMAIN ) ?>" placeholder="<?php _e( 'Username', ET_DOMAIN ) ?>">
								  			<span class="icon collapse" data-icon="D"></span>
										</div>
							  		</div>
							  		<div class="form-group">
										<div class="col-lg-10">
											<span class="line-correct collapse"></span>
								  			<input type="text" name="email" class="form-control" id="email" title="<?php _e( 'Email', ET_DOMAIN ) ?>" placeholder="<?php _e( 'Email', ET_DOMAIN ) ?>">
								  			<span class="icon collapse" data-icon="D"></span>
										</div>
							  		</div>
							  		<div class="form-group">
										<div class="col-lg-10">
											<span class="line-correct collapse"></span>
								  			<input type="password" name="user_pass" class="form-control" id="user_pass_register" title="<?php _e( 'Password', ET_DOMAIN ) ?>" placeholder="<?php _e( 'Password', ET_DOMAIN ) ?>">
								  			<span class="icon collapse" data-icon="D"></span>
										</div>
							  		</div>
							  		<div class="form-group" style="margin-bottom: 0">
										<div class="col-lg-10">
											<span class="line-correct collapse"></span>
								  			<input type="password" name="re_pass" class="form-control" id="re_pass" title="<?php _e( 'Retype password', ET_DOMAIN ) ?>" placeholder="<?php _e( 'Retype password', ET_DOMAIN ) ?>">
								  			<span class="icon collapse" data-icon="D"></span>
								  		</div>
								  	</div>
							  		<div class="form-group">
										<div class="col-lg-10">
								  			<div class="btn-submit">
												<div class="fe-checkbox-container">
													<input type="checkbox" name="agree_terms" id="agree_terms" class="fe-checkbox" onfocus="blur(this)">
													<label for="agree_terms"><span data-icon="3" class="icon"></span><?php _e( 'I agree to', ET_DOMAIN ) ?> <a href="<?php echo et_get_page_link('term-condition'); ?>"><span class="color-blue"><?php _e( 'the terms', ET_DOMAIN ) ?></span></a>.</label>
									  				<!-- <div class="skin-checkbox">
														<span data-icon="3" class="icon"></span>
														<input type="checkbox" name="agree_terms" id="agree_terms" class="checkbox-show hide">
									  				</div> -->
									  				<!-- <a href="#">I agree to <span class="color-blue">the terms</span>.</a> -->
												</div>
												<button type="submit" class="btn"><?php _e( 'Join', ET_DOMAIN ) ?></button>
								  			</div>
										</div>
							  		</div>
								</form>
						  	</div>
						</div>
					</div>
					<div class="forget-modal" style="display:none">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
								<span class="icon" data-icon="D"></span>
							</button>
							<h4 class="modal-title"><?php _e( 'Forgot your password?', ET_DOMAIN ) ?></h4>
						</div>
						<div class="modal-body">
							<span class="text"><?php _e( "Type your email and we'll send you a link to retrieve it.", ET_DOMAIN ) ?></span>
							<form id="form_forget" class="form-horizontal">
							  		<div class="form-group">
										<div class="form-field">
											<span class="line-correct  collapse"></span>
								  			<input type="text" name="user_login" class="form-control" autocomplete="off" id="user_login" placeholder="<?php _e( 'Enter your username or email', ET_DOMAIN ) ?>">
								  			<span class="icon collapse" data-icon="D"></span>
										</div>
										<button type="submit" class="btn"><?php _e( 'Send', ET_DOMAIN ) ?></button>
							  		</div>
							</form>
						</div>
					</div>
			  	</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		<!-- Modal Login -->
		<?php
			if(is_author() || is_page_template('page-member.php' )){
				$current_author = get_query_var( 'author' ) ? get_query_var( 'author' ) : get_query_var( 'member' );
				$author = get_user_by( 'id', $current_author ) ? get_user_by( 'id', $current_author ) : get_user_by( 'slug', $current_author ) ;
		?>
		<!-- Modal Contact Form -->
		<div class="upload-img modal-insert-link">
			<div class="modal" id="contactFormModal" style="display:none;" aria-hidden="true">
				<form id="contact_form" class="main-form">
					<input type="hidden" name="author_id" id="author_id" value="<?php echo $author->ID ?>" />
					<input type="hidden" name="author_email" id="author_email" value="<?php echo $author->email ?>" />
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
								<span class="icon" data-icon="D"></span>
							</button>
							<h4 class="modal-title"><?php printf(__( 'Contact %s', ET_DOMAIN ),$author->display_name) ?></h4>
						</div>
		                <div class="modal-body">
			                <div class="message">
			                    <p><?php _e( 'Your message', ET_DOMAIN ) ?></p>
			                    <textarea id="txt_contact" placeholder="<?php _e( 'Got something to say? Type your message here.', ET_DOMAIN ) ?>"></textarea>
			                </div>
			                <button type="submit" class="btn"><?php _e( 'Send', ET_DOMAIN ) ?></button>
		                </div>
		            </div>
		        </div>
		    	</form>
		    </div>
		</div>
		<!-- Modal Contact Form -->
		<?php } ?>

		<!-- Default Wordpress Editor -->
		<div class="hide">
			<?php wp_editor( '' , 'temp_content', editor_settings() ); ?>
		</div>
		<!-- Default Wordpress Editor -->

		</div>
		<div class="mobile-menu">
			<ul class="mo-cat-list">
				<?php et_the_mobile_cat_list(); ?>
			</ul>
		</div>
	</div>
	<?php wp_footer(); ?>
  </body>
</html>