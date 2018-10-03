<?php

if ( ! defined( 'WPSEO_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

function wpseo_ajax_json_echo_die( $results ) {
	echo wp_json_encode( $results );
	die();
}

function wpseo_set_option() {
	if ( ! current_user_can( 'manage_options' ) ) {
		die( '-1' );
	}
	check_ajax_referer( 'wpseo-setoption' );
	$option = sanitize_text_field( filter_input( INPUT_POST, 'option' ) );
	if ( $option !== 'page_comments' ) {
		die( '-1' );
	}

	update_option( $option, 0 );
	die( '1' );
}
add_action( 'wp_ajax_wpseo_set_option', 'wpseo_set_option' );
add_action( 'wp_ajax_yoast_dismiss_notification', array( 'Yoast_Notification_Center', 'ajax_dismiss_notification' ) );

function wpseo_set_ignore() {
	if ( ! current_user_can( 'manage_options' ) ) {
		die( '-1' );
	}
	check_ajax_referer( 'wpseo-ignore' );
	$ignore_key = sanitize_text_field( filter_input( INPUT_POST, 'option' ) );
	WPSEO_Options::set( 'ignore_' . $ignore_key, true );
	die( '1' );
}
add_action( 'wp_ajax_wpseo_set_ignore', 'wpseo_set_ignore' );


function wpseo_dismiss_tagline_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		die( '-1' );
	}
	check_ajax_referer( 'wpseo-dismiss-tagline-notice' );
	update_user_meta( get_current_user_id(), 'wpseo_seen_tagline_notice', 'seen' );
	die( '1' );
}
add_action( 'wp_ajax_wpseo_dismiss_tagline_notice', 'wpseo_dismiss_tagline_notice' );

function wpseo_ajax_replace_vars() {
	global $post;
	check_ajax_referer( 'wpseo-replace-vars' );
	$post = get_post( intval( filter_input( INPUT_POST, 'post_id' ) ) );
	global $wp_query;
	$wp_query->queried_object    = $post;
	$wp_query->queried_object_id = $post->ID;
	$omit = array( 'excerpt', 'excerpt_only', 'title' );
	echo wpseo_replace_vars( stripslashes( filter_input( INPUT_POST, 'string' ) ), $post, $omit );
	die;
}
add_action( 'wp_ajax_wpseo_replace_vars', 'wpseo_ajax_replace_vars' );

function wpseo_save_title() {
	wpseo_save_what( 'title' );
}
add_action( 'wp_ajax_wpseo_save_title', 'wpseo_save_title' );


function wpseo_save_description() {
	wpseo_save_what( 'metadesc' );
}
add_action( 'wp_ajax_wpseo_save_metadesc', 'wpseo_save_description' );

function wpseo_save_what( $what ) {
	check_ajax_referer( 'wpseo-bulk-editor' );
	$new      = filter_input( INPUT_POST, 'new_value' );
	$post_id  = intval( filter_input( INPUT_POST, 'wpseo_post_id' ) );
	$original = filter_input( INPUT_POST, 'existing_value' );
	$results = wpseo_upsert_new( $what, $post_id, $new, $original );
	wpseo_ajax_json_echo_die( $results );
}

function wpseo_upsert_meta( $post_id, $new_meta_value, $orig_meta_value, $meta_key, $return_key ) {
	$post_id                  = intval( $post_id );
	$sanitized_new_meta_value = wp_strip_all_tags( $new_meta_value );
	$orig_meta_value          = wp_strip_all_tags( $orig_meta_value );
	$upsert_results = array(
		'status'                 => 'success',
		'post_id'                => $post_id,
		"new_{$return_key}"      => $sanitized_new_meta_value,
		"original_{$return_key}" => $orig_meta_value,
	);

	$the_post = get_post( $post_id );
	if ( empty( $the_post ) ) {

		$upsert_results['status']  = 'failure';
		$upsert_results['results'] = __( 'Post doesn\'t exist.', 'wordpress-seo' );

		return $upsert_results;
	}

	$post_type_object = get_post_type_object( $the_post->post_type );
	if ( ! $post_type_object ) {
		$upsert_results['status']  = 'failure';
		$upsert_results['results'] = sprintf(
			__( 'Post has an invalid Content Type: %s.', 'wordpress-seo' ),
			$the_post->post_type
		);
		return $upsert_results;
	}

	if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
		$upsert_results['status']  = 'failure';
		$upsert_results['results'] = sprintf(
			__( 'You can\'t edit %s.', 'wordpress-seo' ),
			$post_type_object->label
		);
		return $upsert_results;
	}

	if ( ! current_user_can( $post_type_object->cap->edit_others_posts ) && (int) $the_post->post_author !== get_current_user_id() ) {
		$upsert_results['status']  = 'failure';
		$upsert_results['results'] = sprintf(
			__( 'You can\'t edit %s that aren\'t yours.', 'wordpress-seo' ),
			$post_type_object->label
		);
		return $upsert_results;

	}

	if ( $sanitized_new_meta_value === $orig_meta_value && $sanitized_new_meta_value !== $new_meta_value ) {
		$upsert_results['status']  = 'failure';
		$upsert_results['results'] = __( 'You have used HTML in your value which is not allowed.', 'wordpress-seo' );
		return $upsert_results;
	}

	$res = update_post_meta( $post_id, $meta_key, $sanitized_new_meta_value );
	$upsert_results['status']  = ( $res !== false ) ? 'success' : 'failure';
	$upsert_results['results'] = $res;
	return $upsert_results;
}

function wpseo_save_all_titles() {
	wpseo_save_all( 'title' );
}
add_action( 'wp_ajax_wpseo_save_all_titles', 'wpseo_save_all_titles' );

function wpseo_save_all_descriptions() {
	wpseo_save_all( 'metadesc' );
}
add_action( 'wp_ajax_wpseo_save_all_descriptions', 'wpseo_save_all_descriptions' );


function wpseo_save_all( $what ) {
	check_ajax_referer( 'wpseo-bulk-editor' );
	$new_values      = $_POST['items'];
	$original_values = $_POST['existing_items'];
	$results = array();
	if ( is_array( $new_values ) && $new_values !== array() ) {
		foreach ( $new_values as $post_id => $new_value ) {
			$original_value = $original_values[ $post_id ];
			$results[]      = wpseo_upsert_new( $what, $post_id, $new_value, $original_value );
		}
	}
	wpseo_ajax_json_echo_die( $results );
}

function wpseo_upsert_new( $what, $post_id, $new, $original ) {
	$meta_key = WPSEO_Meta::$meta_prefix . $what;
	return wpseo_upsert_meta( $post_id, $new, $original, $meta_key, $what );
}

function ajax_get_keyword_usage() {
	$post_id = filter_input( INPUT_POST, 'post_id' );
	$keyword = filter_input( INPUT_POST, 'keyword' );
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		die( '-1' );
	}
	wp_die(
		wp_json_encode( WPSEO_Meta::keyword_usage( $keyword, $post_id ) )
	);
}
add_action( 'wp_ajax_get_focus_keyword_usage', 'ajax_get_keyword_usage' );

function ajax_get_term_keyword_usage() {
	$post_id       = filter_input( INPUT_POST, 'post_id' );
	$keyword       = filter_input( INPUT_POST, 'keyword' );
	$taxonomy_name = filter_input( INPUT_POST, 'taxonomy' );
	$taxonomy = get_taxonomy( $taxonomy_name );

	if ( ! $taxonomy ) {
		wp_die( 0 );
	}
	if ( ! current_user_can( $taxonomy->cap->edit_terms ) ) {
		wp_die( -1 );
	}
	$usage = WPSEO_Taxonomy_Meta::get_keyword_usage( $keyword, $post_id, $taxonomy_name );
	$usage = $usage[ $keyword ];
	wp_die(
		wp_json_encode( $usage )
	);
}
add_action( 'wp_ajax_get_term_keyword_usage', 'ajax_get_term_keyword_usage' );

function wpseo_register_ajax_integrations() {
	$integrations = array( new Yoast_Network_Admin() );
	foreach ( $integrations as $integration ) {
		$integration->register_ajax_hooks();
	}
}
wpseo_register_ajax_integrations();

new WPSEO_GSC_Ajax();
new WPSEO_Recalculate_Scores_Ajax();
new Yoast_OnPage_Ajax();
new WPSEO_Shortcode_Filter();
new WPSEO_Taxonomy_Columns();
new Yoast_Dismissable_Notice_Ajax( 'recalculate', Yoast_Dismissable_Notice_Ajax::FOR_SITE );

/********************** DEPRECATED METHODS **********************/

function wpseo_remove_stopwords_sample_permalink() {
	_deprecated_function( __FUNCTION__, 'WPSEO 6.3', 'This method is deprecated.' );
	wpseo_ajax_json_echo_die( '' );
}

function wpseo_kill_blocking_files() {
	_deprecated_function( __FUNCTION__, 'WPSEO 7.0', 'This method is deprecated.' );
	wpseo_ajax_json_echo_die( '' );
}

function wpseo_add_fb_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		die( '-1' );
	}
	_deprecated_function( __FUNCTION__, 'WPSEO 7.0', 'This method is deprecated.' );
	wpseo_ajax_json_echo_die( '' );
}
