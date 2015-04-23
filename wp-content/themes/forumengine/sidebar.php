<?php 
//sidebar
if ( is_active_sidebar( 'fe-allpage-sidebar' ) && !( is_page('blog') || is_single() || is_home() || is_front_page() || is_singular('thread'))){
	dynamic_sidebar( 'fe-allpage-sidebar' );
} 

if (is_active_sidebar( 'fe-homepage-sidebar' ) && is_front_page() ){
	dynamic_sidebar( 'fe-homepage-sidebar' );
}

if (is_active_sidebar( 'fe-single-thread-sidebar' ) && is_singular('thread') ){
	dynamic_sidebar( 'fe-single-thread-sidebar' );
}

if (is_active_sidebar( 'fe-single-post-sidebar' ) && is_singular('post') ){
	dynamic_sidebar( 'fe-single-post-sidebar' );
}

if (is_active_sidebar( 'fe-blog-sidebar' ) && is_home() ){
	dynamic_sidebar( 'fe-blog-sidebar' );
}

?>
<!-- end widget -->
