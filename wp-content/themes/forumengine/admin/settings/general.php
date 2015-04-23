<div id="setting-general" class="inner-content et-main-main clearfix">
<?php 
	$site_title	=	et_get_option("blogname");
	$site_desc	=	et_get_option("blogdescription");
	$copyright	=	et_get_option("et_copyright");
	$twitter	=	et_get_option("et_twitter_account");
	$facebook	=	et_get_option("et_facebook_link");
	$google		=	et_get_option("et_google_plus");
	$google_analytics	=	et_get_option("et_google_analytics");
	$validator	=	new ET_Validator();
?>	
	<!-- BRANDING -->
	<div class="title font-quicksand"><?php _e('Upload Logo', ET_DOMAIN );?></div>
	<div class="desc">
		<?php _e('Your logo should be in PNG, GIF or JPG format, within <strong>120x70px</strong>  and less than <strong>1500Kb</strong>.', ET_DOMAIN);?>
		<div class="customization-info">
			<?php $uploaderID = 'website_logo';?>
			<div class="input-file upload-logo" id="<?php echo $uploaderID;?>_container">
			<?php 
				$website_logo = et_get_option("et_website_logo");
			?>
					<div class="left clearfix">
						<div class="image" id="<?php echo $uploaderID;?>_thumbnail">
							<img src="<?php echo fe_get_logo();?>"/>
						</div>
					</div>
				
				<span class="et_ajaxnonce" id="<?php echo wp_create_nonce( $uploaderID . '_et_uploader' ); ?>"></span>
				<span class="bg-grey-button button btn-button" id="<?php echo $uploaderID;?>_browse_button">
					<?php _e('Browse', ET_DOMAIN);?>
					<span class="icon" data-icon="o"></span>
				</span>

			</div>
		</div>
		<div style="clear:left"></div>
	</div>

	<div class="title font-quicksand margin-top30"><?php _e('Upload Mobile Icon', ET_DOMAIN);?></div>
	<div class="desc">
		<?php _e('This icon will be used as a launcher icon for iPhone and Android smartphones and also as the website favicon. The image dimensions should be <strong>57x57px</strong>.', ET_DOMAIN);?>
		<div class="customization-info">
			<?php $uploaderID = 'mobile_icon';?>
			<div class="input-file  mobile-logo" id="<?php echo $uploaderID;?>_container">
				<?php 
				$mobile_icon = et_get_option("et_mobile_icon");
				
					?>
					<div class="left clearfix">
						<div class="image" id="<?php echo $uploaderID;?>_thumbnail">
						<?php if ($mobile_icon){ ?>
							<img src="<?php echo $mobile_icon;?>"/>
						<?php } else { ?>
							<img src="<?php echo TEMPLATEURL . '/img/fe-favicon.png' ?>"/>
						<?php } ?>
						</div>
					</div>
				
				<span class="et_ajaxnonce" id="<?php echo wp_create_nonce( $uploaderID . '_et_uploader' ); ?>"></span>
				<span class="bg-grey-button button btn-button" id="<?php echo $uploaderID;?>_browse_button">
					<?php _e('Browse', ET_DOMAIN);?>
					<span class="icon" data-icon="o"></span>
				</span>
			</div>
		</div>
		<div style="clear:left"></div>
	</div>			
	<!-- BRANDING -->
	<!-- GENERAL -->
	<div class="title font-quicksand"><?php _e("Website Title",ET_DOMAIN);?></div>
	<div class="desc">
		<?php _e("Enter your website title ",ET_DOMAIN);?>
		<div class="form no-margin no-padding no-background">
			<div class="form-item">
				<input class="bg-grey-input <?php if($site_title == '') echo 'color-error' ?>" type="text" value="<?php echo $site_title?>" id="site_title" name="blogname" />
				<span class="icon  <?php if($site_title == '') echo 'color-error' ?>" data-icon="<?php data_icon($site_title) ?>"></span>
			</div>
		</div>
	</div>
	<div class="title font-quicksand"><?php _e("Website Description",ET_DOMAIN);?></div>
	<div class="desc">
		<?php _e("This description will appear next to your website logo in the header.",ET_DOMAIN);?>
		<div class="form no-margin no-padding no-background">
			<div class="form-item">
				<input class="bg-grey-input <?php if($site_desc == '') echo 'color-error' ?>" type="text" value="<?php echo $site_desc?>" id="site_desc" name="blogdescription" />
				<span class="icon  <?php if($site_desc == '') echo 'color-error' ?>" data-icon="<?php data_icon($site_desc) ?>"></span>
			</div>
		</div>
	</div>
    <div class="title font-quicksand"><?php _e("Copyright Information",ET_DOMAIN);?></div>
	<div class="desc">
		<?php _e("This copyright information will appear in the footer.",ET_DOMAIN);?>
		<div class="form no-margin no-padding no-background">
			<div class="form-item">
				<input class="bg-grey-input <?php if($copyright == '') echo 'color-error' ?>" type="text" value="<?php echo htmlentities($copyright) ?>" id="copyright" name="et_copyright" />
				<span class="icon  <?php if($copyright == '') echo 'color-error' ?>" data-icon="<?php data_icon($copyright) ?>"></span>
			</div>
		</div>
	</div>
    <div class="title font-quicksand"><?php _e("Social Links",ET_DOMAIN);?></div>
	<div class="desc">
	    <?php _e("Social links are displayed in the footer and in your blog sidebar.",ET_DOMAIN);?>
    	<div class="form no-margin no-background">
    		<div class="form-item">
        		<div class="label"><?php _e("Twitter URL",ET_DOMAIN);?></div>
        		<input class="url bg-grey-input <?php if(!$validator->validate('link', $twitter)) echo 'color-error' ?>" type="text" value="<?php echo htmlentities($twitter) ?>" id="twitter_account" name="et_twitter_account"/>
        		<span class="icon <?php if(!$validator->validate('link', $twitter) ) echo 'color-error' ?>" data-icon="<?php data_icon($twitter ,'link') ?>"></span>
        	</div>
        	<div class="form-item">
        		<div class="label"><?php _e("Facebook URL",ET_DOMAIN);?></div>
        		<input class="url bg-grey-input <?php if (!$validator->validate('link', $facebook)) echo 'color-error' ?>" type="text" value="<?php echo htmlentities($facebook) ?>" id="facebook_link" name="et_facebook_link"/>
        		<span class="icon <?php if( !$validator->validate('link', $facebook) ) echo 'color-error' ?>" data-icon="<?php data_icon($facebook, 'link') ?>"></span>
        	</div>
        	<div class="form-item">
        		<div class="label"><?php _e("Google Plus URL",ET_DOMAIN);?></div>
        		<input class="url bg-grey-input <?php if (!$validator->validate('link', $google)) echo 'color-error' ?>" type="text" value="<?php echo htmlentities($google) ?>" id="google_plus" name="et_google_plus"/>
        		<span class="icon <?php if (!$validator->validate('link', $google)) echo 'color-error' ?>" data-icon="<?php data_icon($google, 'link') ?>"></span>
        	</div>
    	</div>
	</div>

	<div class="title font-quicksand"><?php _e("Google Analytics",ET_DOMAIN);?></div>
	<div class="desc">
		<?php _e("Google analytics is a service offered by Google that generates detailed statistics about the visits to a website.",ET_DOMAIN);?>
		<div class="form no-margin no-padding no-background">
    		<div class="form-item">
        		<textarea class="autosize" row="4" style="height: auto;overflow: visible;" id="google_analytics" name="et_google_analytics" ><?php echo $google_analytics ?></textarea>
        		<span class="icon <?php if ($google_analytics == '') echo 'color-error' ?>" data-icon="<?php data_icon($google_analytics, 'text') ?>"></span>
        	</div>
        </div>
	<!-- GENERAL -->				
	</div>
</div> <!-- END #setting-general -->