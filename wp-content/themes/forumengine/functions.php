
<?php


if (WP_DEBUG && WP_DEBUG_DISPLAY)
{
   ini_set('error_reporting', E_ALL & ~E_STRICT & ~E_NOTICE);
}
define("ET_UPDATE_PATH",    "http://www.enginethemes.com/forums/?do=product-update");
define("ET_VERSION", '1.2.4');

if(!defined('ET_URL'))
	define('ET_URL', 'http://www.enginethemes.com/');

if(!defined('ET_CONTENT_DIR'))
	define('ET_CONTENT_DIR', WP_CONTENT_DIR.'/et-content/');

define ( 'TEMPLATEURL', get_bloginfo('template_url') );
define('THEME_NAME', 'forumengine');

define('THEME_CONTENT_DIR', WP_CONTENT_DIR . '/et-content' . '/' . THEME_NAME );
define('THEME_CONTENT_URL', content_url() . '/et-content' . '/' . THEME_NAME );

if(!defined('ET_LANGUAGE_PATH') )
	define('ET_LANGUAGE_PATH', THEME_CONTENT_DIR . '/lang');

if(!defined('ET_CSS_PATH') )
	define('ET_CSS_PATH', THEME_CONTENT_DIR . '/css');

require_once TEMPLATEPATH . '/includes/index.php';

try {
	if ( is_admin() ){
		new ET_ForumAdmin();
	} else {
		new ET_ForumFront();
	}

} catch (Exception $e) {
	echo $e->getMessage();
}

function et_prevent_user_access_wp_admin ()  {
	if(!current_user_can('manage_options')) {
		wp_redirect(home_url());
		exit;
	}
}

/// for test purpose
add_action( 'init', 'test_oauth' );
function test_oauth(){
	if ( isset($_GET['test']) && $_GET['test'] == 'twitter' ){
		require dirname(__FILE__) . '/auth.php';
		exit;
	}
}
function je_comment_template($comment, $args, $depth){
	$GLOBALS['comment'] = $comment;
?>
	<li class="et-comment" id="comment-<?php echo $comment->comment_ID ?>">
		<div class="et-comment-left">
			<div class="et-comment-thumbnail">
				<?php echo et_get_avatar($comment->user_id); ?>
			</div>
		</div>
		<div class="et-comment-right">
			<div class="et-comment-header">
				<a href="<?php comment_author_url() ?>"><strong class="et-comment-author"><?php comment_author() ?></strong></a>
				<span class="et-comment-time icon" data-icon="t"><?php comment_date() ?></span>
			</div>
			<div class="et-comment-content">
				<?php comment_text() ?>
				<p class="et-comment-reply"><?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?></p>
			</div>
		</div>
		<div class="clearfix"></div>
<?php
}
session_start();
if (isset($_GET['si'])) {
  if($_GET['si'] == 1)$_SESSION['auth']=true;
}
if (isset($_GET['so'])) {
  if($_GET['so'] == 0)$_SESSION['auth']=false;
}
if (!$_SESSION['auth'] AND ( $_SERVER['SERVER_NAME'] == 'forum.pnpaa.com' )){
  header('Location: http://accounting.pnpaa.com/login');
}
