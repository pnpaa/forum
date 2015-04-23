<div id="setting-social" class="inner-content et-main-main clearfix hide">
<?php 
	$twitter_key		=	et_get_option("et_twitter_key");
	$twitter_secret		=	et_get_option("et_twitter_secret");
	$facebook_key		=	et_get_option("et_facebook_key");
	$facebook_secret	=	et_get_option("et_facebook_secret");
	$validator			=	new ET_Validator();
?>	
	<!-- GENERAL -->
    <div class="title font-quicksand"><?php _e("Twitter API",ET_DOMAIN);?></div>
    <div class="desc">
        <?php _e("Enabling this will allow users to login via Twitter.",ET_DOMAIN);?>           
        <div class="inner no-border btn-left">
            <div class="payment">
                <?php et_toggle_button('twitter_login', __("Twitter Login",ET_DOMAIN), get_option('twitter_login', false) ); ?>
            </div>
        </div>                          
    </div>    
	<div class="desc">
	    <?php _e("Twitter API key",ET_DOMAIN);?>
    	<div class="form no-margin no-background">
    		<div class="form-item">
        		<div class="label"><?php _e("Twitter Consumer Key",ET_DOMAIN);?></div>
        		<input class="bg-grey-input <?php if($twitter_key == '') echo 'color-error' ?>" type="text" value="<?php echo htmlentities($twitter_key) ?>" id="twitter_account" name="et_twitter_key"/>
        		<span class="icon <?php if($twitter_key == '') echo 'color-error' ?>" data-icon="<?php data_icon($twitter_key) ?>"></span>
        	</div>
        	<div class="form-item">
        		<div class="label"><?php _e("Twitter Consumer Secret",ET_DOMAIN);?></div>
        		<input class="bg-grey-input <?php if($twitter_secret == '') echo 'color-error' ?>" type="text" value="<?php echo htmlentities($twitter_secret) ?>" id="facebook_link" name="et_twitter_secret"/>
        		<span class="icon <?php if($twitter_secret == '') echo 'color-error' ?>" data-icon="<?php data_icon($twitter_secret) ?>"></span>
        	</div>     	
    	</div>
	</div>
    <div class="title font-quicksand"><?php _e("Facebook API",ET_DOMAIN);?></div>
    <div class="desc">
        <?php _e("Enabling this will allow users to login via Facebook.",ET_DOMAIN);?>           
        <div class="inner no-border btn-left">
            <div class="payment">
                <?php et_toggle_button('facebook_login', __("Facebook Login",ET_DOMAIN), get_option('facebook_login', false) ); ?>
            </div>
        </div>                          
    </div>    
    <div class="desc">
        <?php _e("Facebook API for authentication",ET_DOMAIN);?>
        <div class="form no-margin no-background">
            <div class="form-item">
                <div class="label"><?php _e("Facebook Applicatin ID",ET_DOMAIN);?></div>
                <input class="bg-grey-input <?php if($facebook_key =="") echo 'color-error' ?>" type="text" value="<?php echo htmlentities($facebook_key) ?>" id="twitter_account" name="et_facebook_key"/>
                <span class="icon <?php if($facebook_key == '') echo 'color-error' ?>" data-icon="<?php data_icon($facebook_key) ?>"></span>
            </div>
            <?php /* <div class="form-item">
                <div class="label"><?php _e("Facebook Applicatin Secret",ET_DOMAIN);?></div>
                <input class="bg-grey-input <?php if ($facebook_secret =="") echo 'color-error' ?>" type="text" value="<?php echo htmlentities($facebook_secret) ?>" id="facebook_link" name="et_facebook_secret"/>
                <span class="icon <?php if($facebook_secret == '') echo 'color-error' ?>" data-icon="<?php data_icon($facebook_secret) ?>"></span>
            </div> */ ?>
        </div>
    </div>
</div> <!-- END #setting-social -->