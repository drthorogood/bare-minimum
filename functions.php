<?php
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
} );

// Remove every dashboard widget (core and plugins) and the Welcome panel, for all users.
add_action( 'wp_dashboard_setup', function () {
	global $wp_meta_boxes;
	$wp_meta_boxes['dashboard'] = array();
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}, 999 );

/* ---------- Disable comments, pingbacks and trackbacks completely ---------- */

// Remove comment and trackback support from every post type.
add_action( 'init', function () {
	foreach ( get_post_types() as $type ) {
		remove_post_type_support( $type, 'comments' );
		remove_post_type_support( $type, 'trackbacks' );
	}
}, 100 );

// Close comments and pings everywhere, and hide any existing comments.
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 20 );
add_filter( 'get_comments_number', '__return_zero', 20 );

// Admin: remove menus, block the screens, remove the Discussion settings page.
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}, 999 );
add_action( 'admin_init', function () {
	global $pagenow;
	if ( in_array( $pagenow, array( 'edit-comments.php', 'comment.php', 'options-discussion.php' ), true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
} );

// Remove the comments icon from the admin bar.
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
}, 999 );

// Remove the Recent Comments widget and block.
add_action( 'widgets_init', function () {
	unregister_widget( 'WP_Widget_Recent_Comments' );
} );
add_filter( 'allowed_block_types_all', function ( $allowed ) {
	$comment_blocks = array( 'core/comments', 'core/comment-template', 'core/comments-title', 'core/comment-content', 'core/comment-author-name', 'core/comment-date', 'core/comment-edit-link', 'core/comment-reply-link', 'core/comments-pagination', 'core/comments-pagination-next', 'core/comments-pagination-previous', 'core/comments-pagination-numbers', 'core/post-comments-form', 'core/post-comments-count', 'core/post-comments-link', 'core/latest-comments', 'core/comment-author-avatar' );
	if ( true === $allowed ) {
		$allowed = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
	}
	return is_array( $allowed ) ? array_values( array_diff( $allowed, $comment_blocks ) ) : $allowed;
}, 100 );

// Front end: comment feeds return 404; comment reply script is not loaded.
add_action( 'template_redirect', function () {
	if ( is_comment_feed() ) {
		wp_die( esc_html__( 'Comments are disabled.', 'bare-minimum' ), '', array( 'response' => 404 ) );
	}
}, 9 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );
add_action( 'wp_enqueue_scripts', function () {
	wp_deregister_script( 'comment-reply' );
}, 100 );

// Block posting comments directly to wp-comments-post.php.
add_action( 'pre_comment_on_post', function () {
	wp_die( esc_html__( 'Comments are disabled.', 'bare-minimum' ), '', array( 'response' => 403 ) );
} );

// REST API: remove the comments endpoints.
add_filter( 'rest_endpoints', function ( $endpoints ) {
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/comments' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
} );

// Pingbacks: remove the X-Pingback header and the XML-RPC pingback methods.
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );
add_filter( 'xmlrpc_methods', function ( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
	return $methods;
} );
add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
