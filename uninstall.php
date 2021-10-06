<?php
/**
 * Runs on removing plugin
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 't_words_per_minute' );
delete_option( 't_rounding_behavior' );
delete_option( 't_shortcode_lebel' );
delete_option( 'system_meta_options' );

$post_types = get_post_types( array(), 'objects' );
foreach ( $post_types as $key => $object ) {
	delete_post_meta( $object->ID, 't_reading_time_num' );
	delete_option( 't_supported_post_types_' . $key );
}
