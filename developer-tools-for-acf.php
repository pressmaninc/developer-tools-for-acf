<?php
/*
Plugin Name: Developer Tools For ACF
Plugin URI:
Description: Provide developer tools for ACF
Version: 1.0.2
Author: PRESSMAN
Author URI: https://www.pressman.ne.jp/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class Developer_Tools_For_Acf{

	private $flag_load_js_css = false;
	private $setting = [];
	public static $instance;
	public static $prefix = 'dtfa_';
	public static $plugin_id = 'developer-tools-for-acf';

	function __construct() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { return; }
		// Add a display of ACF field details in ACF form
		add_action( 'acf/render_fields', [ $this, 'load_js_and_css_for_form' ], 10, 2 );
		// Add columns in ACF field group screen.
		add_action( 'admin_enqueue_scripts', [ $this, 'load_js_and_css_for_field_group' ] );
		add_filter( 'manage_edit-acf-field-group_columns', [ $this, 'manage_field_group_columns' ], 100 );
		add_action( 'manage_acf-field-group_posts_custom_column', [ $this, 'manage_field_group_custom_column' ], 100 );
	}

	/**
	 * Get instance
	 *
	 * @return $instance
	 */
	public static function get_instance() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { return; }
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
			include_once( plugin_dir_path( __FILE__ ) . 'includes/' . self::$plugin_id . '-option_page.php');
		}
		return self::$instance;
	}

	/**
	 * Get option name prefix
	 *
	 * @return $prefix
	 */
	public static function get_prefix() {
		return self::$prefix;
	}

	/**
	 * Get plugin_id
	 *
	 * @return $plugin_id
	 */
	public static function get_plugin_id() {
		return self::$plugin_id;
	}

	/**
	 * Get plugin setting
	 *
	 * @return $setting
	 */
	public function get_setting() {
		$field_ids = Developer_Tools_For_Acf_Settings_Page::get_setting_field_ids();
		foreach( $field_ids as $id ){
			if ( 0 === strpos( $id, self::$prefix ) ) {
				$_id = substr( $id, strlen( self::$prefix ) );
				$this->setting[ $_id ] = get_option( $id, '' );
				if( 'ignore_field_type' === $_id ){
					$this->setting[ $_id ] = explode( ',', str_replace( ' ', '', $this->setting[ $_id ] ) );
				}
			}
		}
		return $this->setting;
	}

	/**
	 * Load js and css for ACF form
	 *
	 * @param object $fields
	 * @param int $post_id
	 * @return none
	 */
	function load_js_and_css_for_form( $fields, $post_id ) {
		$capability = get_option( self::$prefix . 'capability', '' );
		$capability = ( '' === $capability ) ? 'administrator' : $capability;
		if ( ! current_user_can( $capability ) ){
			return;
		}

		// first time?
		if( ! isset( $post_id ) || true === $this->flag_load_js_css ) {
			return;
		} else {
			$this->flag_load_js_css = true;
			$this->setting = $this->get_setting();
		}

		// load js and css
		wp_register_style( self::$prefix . '-field', plugin_dir_url( __FILE__ ) . 'assets/css/' . self::$plugin_id . '-field.css' );
		wp_enqueue_style( self::$prefix . '-field');
		wp_register_script( self::$prefix . '-field-js', plugin_dir_url( __FILE__ ) . 'assets/js/' . self::$plugin_id . '-field.js', ['jquery'], false, true );
		wp_enqueue_script( self::$prefix . '-field-js' );

		// localize setting
		wp_localize_script( self::$prefix . '-field-js', self::$prefix . 'settings', $this->setting );

	}

	/**
	 * Load js and css for ACF field group list
	 *
	 * @return none
	 */
	function load_js_and_css_for_field_group() {
		$screen = get_current_screen();
		if( in_array( $screen->id, ['edit-acf-field-group', 'acf-field-group'] ) ) {
			wp_register_style( self::$prefix . '-field-group-css', plugin_dir_url( __FILE__ ) . 'assets/css/' . self::$plugin_id . '-field-group.css' );
			wp_enqueue_style( self::$prefix . '-field-group-css' );
		}
	}

	/**
	 * Add columns to ACF field group list
	 *
	 * @param array $columns
	 * @return none
	 */
	function manage_field_group_columns( $columns ) {
		$setting = $this->get_setting();
		if( isset( $setting['hide_field_group_columns'] ) && is_array( $setting['hide_field_group_columns'] ) ){
			$setting = $setting['hide_field_group_columns'];
			if ( ! in_array( 'last_modified', $setting ) ) {
				$columns['last_modified'] = 'Last modified';
			}
			if ( ! in_array( 'last_modified_author', $setting ) ) {
				$columns['last_modified_author'] = 'Last modified author';
			}
		}
		else {
			$columns['last_modified'] = 'Last modified';
			$columns['last_modified_author'] = 'Last modified author';
		}
		return $columns;
	}

	/**
	 * Show column's value in ACF field group list
	 *
	 * @param string $column
	 * @return none
	 */
	function manage_field_group_custom_column( $column ) {
		if ( 'last_modified' === $column ) {
			the_modified_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		}
		elseif ( 'last_modified_author' === $column ) {
			the_modified_author();
		}
	}

}

Developer_Tools_For_Acf::get_instance();
