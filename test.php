<?php

/**
 * Plugin Name:       Reading Time
 * Description:       Reading Time plugin
 * Version:           1.0.0
 * Author:            Volodymyr Dhzychko
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       test_plugin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Test {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_page' ) );
		add_action( 'admin_init', array( $this, 'admin_setttings' ) );
		add_action( 'save_post', array( $this, 'set_reading_time' ) );
		add_action( 'update_option_t_words_per_minute', array( $this, 'set_reading_time_for_all' ) );
		add_action( 'update_option_system_meta_options', array( $this, 'do_shortcode_on_post_meta' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_reset_reading_time', array( $this, 'recalculate_reading_time' ) );
	}

	public function initialize() {
		include_once plugin_dir_path( __FILE__ ) . 'helper-functions.php';
	}

	public function recalculate_reading_time() {
		$this->do_shortcode_on_post_meta();
		$this->set_reading_time_for_all();

		die();
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'test_plugin_script', plugin_dir_url( __FILE__ ) . '/admin.js', array( 'jquery' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'test_plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function set_reading_time_for_all() {
		$supported_post_types = $this->get_supported_post_types();

		foreach ( $supported_post_types as $post_type ) {

			$all_posts = get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => 'publish',
					'numberposts' => -1,
				)
			);

			foreach ( $all_posts as $post ) {
				$this->set_reading_time( $post->ID );
			}
		}
	}

	public function set_reading_time( $post_ID ) {
		$time_to_read = $this->time_to_read( $post_ID );
		update_post_meta( $post_ID, 't_reading_time_num', $time_to_read );
	}

	public function admin_setttings() {

		/* Words per minute section */
		add_settings_section( 'words_per_minute_section', null, null, 'reading-time-settings-page' );

		add_settings_field(
			't_words_per_minute',
			esc_html__( 'No. of Words Per Minute', 'test_plugin' ),
			array( $this, 'words_per_minute_HTML' ),
			'reading-time-settings-page',
			'words_per_minute_section'
		);
		register_setting(
			'test_plugin_group',
			't_words_per_minute',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'type'              => 'integer',
				'default'           => 200,
			)
		);
		/* END Words per minute section */

		/* Supported post types section */
		add_settings_section( 'supported_post_types', 'Supported post types:', null, 'reading-time-settings-page' );

		$post_types = get_post_types( array(), 'objects' );
		foreach ( $post_types as $key => $object ) {
			$identifier = 't_supported_post_types_' . $key;
			add_settings_field(
				$identifier,
				esc_html__( $object->labels->menu_name, 'test_plugin' ),
				array( $this, 'supported_post_types_HTML' ),
				'reading-time-settings-page',
				'supported_post_types',
				array( 'label_supported_post_types' => $identifier )
			);
			register_setting(
				'test_plugin_group',
				$identifier,
				array( 'sanitize_callback' => 'sanitize_text_field' )
			);
		}
		/* END Supported post types section */

		/* Rounding behavior section */
		add_settings_section( 'rounding_behavior_section', null, null, 'reading-time-settings-page' );

		add_settings_field(
			't_rounding_behavior',
			esc_html__( 'How to round reading time', 'test_plugin' ),
			array( $this, 'rounding_behavior_HTML' ),
			'reading-time-settings-page',
			'rounding_behavior_section',
		);
		register_setting(
			'test_plugin_group',
			't_rounding_behavior',
			array(
				// 'sanitize_callback' => 'sanitize_text_field',
				'type'    => 'string',
				'default' => '1',
			)
		);
		/* END Rounding behavior section */

		/* Shortcode lebel section */
		add_settings_section( 'shortcode_lebel_section', null, null, 'reading-time-settings-page' );

		add_settings_field(
			't_shortcode_lebel',
			esc_html__( 'Set shortcode lebel', 'test_plugin' ),
			array( $this, 'shortcode_lebel_HTML' ),
			'reading-time-settings-page',
			'shortcode_lebel_section'
		);
		register_setting(
			'test_plugin_group',
			't_shortcode_lebel',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'type'              => 'string',
				'default'           => 'Reading Time:',
			)
		);
		/* END Shortcode lebel section */

		/* Content meta fields section */
		add_settings_section(
			'system_meta_fields',
			esc_html__( 'Content meta fields:', 'test_plugin' ),
			array( $this, 'all_meta_fields_desc_HTML' ),
			'reading-time-settings-page'
		);

		add_settings_field(
			't_all_meta_field_id',
			esc_html__( 'Pick meta fields', 'test_plugin' ),
			array( $this, 'system_meta_fields_HTML' ),
			'reading-time-settings-page',
			'system_meta_fields',
			array( 'label_for' => 't_all_meta_field_id' )
		);

		register_setting(
			'test_plugin_group',
			'system_meta_options'
		);
		/* END Content meta fields section */
	}


	public function words_per_minute_HTML() {
		?>
			<input 
				type="number" 
				name="t_words_per_minute" 
				value="<?php echo esc_attr( get_option( 't_words_per_minute', 200 ) ); ?>" 
				class="small-text"
			> 
		<?php
	}

	public function supported_post_types_HTML( $args ) {
		?>
			<input 
				type="checkbox" 
				id="<?php echo esc_attr( $args['label_supported_post_types'] ); ?>" 
				name="<?php echo esc_attr( $args['label_supported_post_types'] ); ?>" 
				value="1" 
				<?php
					$is_post = '0';
				if ( 't_supported_post_types_post' == $args['label_supported_post_types'] ) {
					$is_post = '1';
				}
					checked( get_option( $args['label_supported_post_types'], $is_post ), '1' );
				?>
			/>
		<?php
	}

	public function rounding_behavior_HTML() {
		?>
			<select name="t_rounding_behavior">

				<option value="1" <?php selected( get_option( 't_rounding_behavior', '1' ), '1', true ); ?> >
					<?php esc_html_e( 'Round up', 'test_plugin' ); ?>
				</option>

				<option value="0" <?php selected( get_option( 't_rounding_behavior', '1' ), '0', true ); ?> >
					<?php esc_html_e( 'Round down', 'test_plugin' ); ?>
				</option>

			</select> 
		<?php
	}

	public function shortcode_lebel_HTML() {
		?>
			<input 
				type="text" 
				name="t_shortcode_lebel" 
				value="<?php echo esc_attr( get_option( 't_shortcode_lebel', 'Reading Time:' ) ); ?>" 
			> 
		<?php
	}

	public function all_meta_fields_desc_HTML( $args ) {
		?>
			<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Hold down the Ctrl (windows) or Command (Mac) button to select multiple options.', 'test_plugin' ); ?></p>
		<?php
	}

	public function get_all_meta_keys() {
		global $wpdb;
		$all_meta_fields = array();
		$post_types      = get_post_types();

		foreach ( $post_types as $post_type ) {
			$query = "
            SELECT DISTINCT($wpdb->postmeta.meta_key) 
            FROM $wpdb->posts 
            LEFT JOIN $wpdb->postmeta 
            ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
            WHERE $wpdb->posts.post_type = '%s' AND $wpdb->postmeta.meta_key != '' AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)'
        ";

			$post_meta_fields = $wpdb->get_col( $wpdb->prepare( $query, $post_type ) );

			foreach ( $post_meta_fields as $field ) {
				$all_meta_fields[] = $field;
			}
		}

		$all_meta_fields = array_unique( $all_meta_fields );

		return $all_meta_fields;
	}

	public function system_meta_fields_HTML( $args ) {
		$options = get_option( 'system_meta_options' );
		?>
		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="system_meta_options[<?php echo esc_attr( $args['label_for'] ); ?>][]"
			multiple="multiple">

			<?php
			$all_meta_fields = $this->get_all_meta_keys();
			foreach ( $all_meta_fields as $meta_field ) {
				?>
				<option value="<?php echo $meta_field; ?>" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], $meta_field, false ) ) : ( '' ); ?>>
					<?php esc_html_e( $meta_field, 'test_plugin' ); ?>
				</option>  
				<?php
			}
			?>
		</select>
		<?php
	}

	public function admin_page() {
		add_options_page( 'Reading Time Settings', 'Reading Time', 'manage_options', 'reading-time-settings-page', array( $this, 'admin_settings_HTML' ) );
	}

	public function admin_settings_HTML() {
		?>
			<div class="wrap">
				<h1>
					<?php echo esc_html__( 'Reading Time settings', 'test_plugin' ); ?>
				</h1>
				<form action="options.php" method="POST">
				<?php
					settings_fields( 'test_plugin_group' );
					do_settings_sections( 'reading-time-settings-page' );
					submit_button();
				?>
				</form>
				<a href="#" class="button-primary" id="clear-previous-calculations-test-plugin">
					<?php echo esc_html__( 'Clear Previous calculations', 'test_plugin' ); ?>
				</a>
			</div>
		<?php
	}

	public function time_to_read( $post_id ) {
		$curr_post_words_count = str_word_count( strip_tags( get_post( $post_id )->post_content ) );
		$words_per_min         = get_option( 't_words_per_minute', 200 );
		$reading_time          = 60 * ( $curr_post_words_count / $words_per_min );

		$round_type = get_option( 't_rounding_behavior', '1' );
		if ( $round_type == '1' ) {
			$reading_time = ceil( $reading_time );
		} else {
			$reading_time = floor( $reading_time );
		}

		return $reading_time;
	}

	public function get_supported_post_types() {
		$post_types          = get_post_types();
		$selected_post_types = array();
		foreach ( $post_types as $name ) {
			if ( '1' == get_option( 't_supported_post_types_' . $name ) ) {
				$selected_post_types[] = $name;
			}
		}

		return $selected_post_types;
	}

	public function get_current_id() {
		$curr_post_id = 0;
		if ( in_the_loop() ) {
			$curr_post_id = get_the_ID();
		} else {
			global $wp_query;
			$curr_post_id = $wp_query->get_queried_object_id();
		}

		return $curr_post_id;
	}

	public function is_supported() {
		$curr_post_type = get_post_type( $this->get_current_id() );

		if ( ! in_array( $curr_post_type, $this->get_supported_post_types() ) ) {
			return false;
		}
		return true;
	}

	public function get_reading_time() {
		if ( $this->is_supported() ) {
			$reading_time = 0;

			if ( ! empty( get_post_meta( $this->get_current_id(), 't_reading_time_num' ) ) ) {
				$reading_time = get_post_meta( $this->get_current_id(), 't_reading_time_num', true );
			} else {
				$post_id      = $this->get_current_id();
				$reading_time = $this->time_to_read( $post_id );
				update_post_meta( $post_id, 't_reading_time_num', $reading_time );
			}

			return $reading_time;
		}
	}

	public function reading_time_main() {
		$time = $this->get_reading_time();

		$shortcode_HTML  = '<h3 class="' . apply_filters( 't_shortcode_reading_heading_class', 'default-heading-class' ) . '" >' . esc_html__( get_option( 't_shortcode_lebel', 'Reading Time:' ), 'test_plugin' ) . '</h3>';
		$shortcode_HTML .= '<p class="' . apply_filters( 't_shortcode_reading_text_class', 'default-text-class' ) . '" >' . esc_html__( 'Time to read this article is ', 'test_plugin' ) . $time . esc_html__( ' seconds.', 'test_plugin' ) . '</p>';

		return $shortcode_HTML;
	}

	public function shortcode() {
		if ( $this->is_supported() ) {
			return $this->reading_time_main();
		}
	}

	public function do_shortcode_on_post_meta() {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type, $this->get_supported_post_types() ) ) {
				continue;
			}
			$selected_fields = get_option( 'system_meta_options' );

			if ( is_array( $selected_fields ) && ! empty( $selected_fields['t_all_meta_field_id'] ) ) {
				$selected_fields = $selected_fields['t_all_meta_field_id'];
				$all_posts       = get_posts(
					array(
						'post_type'   => $post_type,
						'post_status' => 'publish',
						'numberposts' => -1,
					)
				);

				foreach ( $all_posts as $post ) {

					foreach ( $selected_fields as $field ) {

						if ( get_post_meta( $post->ID, $field, true ) && ! is_array( get_post_meta( $post->ID, $field, true ) ) ) {
							$content = get_post_meta( $post->ID, $field, true );
							if ( false !== strpos( $content, '[reading_time]' ) ) {
								$curr_post_words_count = str_word_count( strip_tags( $content ) );
								$words_per_min         = get_option( 't_words_per_minute', 200 );
								$reading_time          = 60 * ( $curr_post_words_count / $words_per_min );

								$round_type = get_option( 't_rounding_behavior', '1' );
								if ( $round_type == '1' ) {
									$reading_time = ceil( $reading_time );
								} else {
									$reading_time = floor( $reading_time );
								}

								$shortcode_HTML  = '<h3 class="' . apply_filters( 't_shortcode_reading_heading_class', 'default-heading-class' ) . '" >' . esc_html__( get_option( 't_shortcode_lebel', 'Reading Time:' ), 'test_plugin' ) . '</h3>';
								$shortcode_HTML .= '<p class="' . apply_filters( 't_shortcode_reading_text_class', 'default-text-class' ) . '" >' . esc_html__( 'Time to read this article is ', 'test_plugin' ) . $reading_time . esc_html__( ' seconds.', 'test_plugin' ) . '</p>';

								$new_content = str_replace( '[reading_time]', $shortcode_HTML, $content );
								update_post_meta( $post->ID, $field, $new_content );

							}
						}
					}
				}
			}
		}
	}

}

$test = new Test();
$test->initialize();

add_shortcode( 'reading_time', array( $test, 'shortcode' ) );
