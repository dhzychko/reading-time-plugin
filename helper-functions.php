<?php
/**
 * Functions to use in theme
 */

function get_reading_time( $post_id = null ) {
	if ( ! $post_id ) {
		$the_slug = basename( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		global $wpdb;
		$db_result = $wpdb->get_results( "SELECT ID FROM wp_posts WHERE post_name = '" . $the_slug . "'" );
		$post_id   = 0;
		foreach ( $db_result as $postid ) {
			if ( get_permalink( $postid->ID == site_url( $_SERVER['REQUEST_URI'] ) ) ) {
				$post_id = (int) $postid->ID;
			}
		}
	}

	$curr_post_type      = get_post_type( $post_id );
	$post_types          = get_post_types();
	$selected_post_types = array();
	foreach ( $post_types as $post_type ) {
		if ( '1' == get_option( 't_supported_post_types_' . $post_type ) ) {
			$selected_post_types[] = $post_type;
		}
	}

	if ( ! in_array( $curr_post_type, $selected_post_types ) ) {
		return;
	}

	if ( ! empty( get_post_meta( $post_id, 't_reading_time_num' ) ) ) {
		$reading_time = get_post_meta( $post_id, 't_reading_time_num', true );
	} else {
		$curr_post_words_count = str_word_count( strip_tags( get_post( $post_id )->post_content ) );
		$words_per_min         = get_option( 't_words_per_minute', 200 );
		$reading_time          = 60 * ( $curr_post_words_count / $words_per_min );
		update_post_meta( $post_id, 't_reading_time_num', $reading_time );
	}

	$round_type = get_option( 't_rounding_behavior', '1' );
	if ( '1' == $round_type ) {
		return ceil( $reading_time );
	}
	return floor( $reading_time );
}

function the_reading_time( $post_id = null ) {
	echo get_reading_time( $post_id );
}
