<!DOCTYPE html> 
<html> 
<head> 
	<title><?php echo et_get_option("blogname") ; ?>  | MOBILE VERSION</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"> 
	
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.2/jquery.mobile.structure-1.3.2.css" /> 
	<link rel="stylesheet" href="<?php echo TEMPLATEURL ?>/mobile/css/main.css" />
	<script src="<?php echo TEMPLATEURL ?>/includes/core/js/lib/jquery.min.js"></script>
	<script src="<?php echo TEMPLATEURL ?>/js/libs/jquery.validate.min.js"></script>
	<script src="<?php echo TEMPLATEURL ?>/js/libs/jquery.mobile-1.3.2.min.js"></script>
	<?php 	if(isset($_COOKIE['demo_is_mobile']) && $_COOKIE['demo_is_mobile'] == 1){ ?>
	<script type="text/javascript">
		$(document).bind("mobileinit", function () {
		    $.mobile.ajaxEnabled = false;
		});
	</script>	
	<?php } ?>
	<?php echo et_get_option('et_google_analytics'); ?>
</head> 
<body> 
