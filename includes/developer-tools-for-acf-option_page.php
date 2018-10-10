<?php
/* Settings Page */

if ( ! defined( 'ABSPATH' ) ) exit;

class Developer_Tools_For_Acf_Settings_Page {

	private $prefix;
	private $plugin_id;
	private $setting_sections = [];
	public static $setting_fields = [];
	public static $setting_field_ids = [];
	public static $instance;

	public function __construct() {
		if ( ! wp_doing_ajax() && is_admin() ) {
			add_action( 'admin_menu', [ $this, 'add_menu' ], 99 );
			add_action( 'admin_init', [ $this, 'set_init' ] );
			add_action( 'admin_init', [ $this, 'set_up' ], 99 );
			$this->prefix = developer_tools_For_Acf::get_prefix();
			$this->plugin_id = developer_tools_For_Acf::get_plugin_id();
		}
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=acf-field-group',// parent_slug
			'Developer Tools For ACF',// page_title
			'Developer Tools',// menu_title
			'administrator',// capability
			$this->plugin_id,// menu_slug
			[ $this, 'settings_content' ]// callback
		);
	}

	public function set_init() {
		$this->get_sections();
		$this->get_fields();
	}

	public function set_up() {
		$this->setup_sections();
		$this->setup_fields();
	}

	public function get_sections(){
		if( empty( $this->setting_sections ) ){
			$this->setting_sections =[
				// section_id => ['title', 'description']
				$this->plugin_id . '_section_1' => [
						'Add a display of field details in ACF form.',
						'Details will be shown in each field label and field list box which is added in the bottom of a form.'
					],
				$this->plugin_id . '_section_2' => [
						'Add extra columns in ACF field group list.',
					],
				$this->plugin_id . '_section_extra' => [
						'For developer.',
						'When you use filter \'pre_option_(option name)\' to override values above, the filtered field will be shown as a notice which tells you \'it\'s filterd\'.'
					],
			];
			foreach ( $this->setting_sections as $id => $data ) {
				if ( ! isset( $data[1] ) ) {
					$this->setting_sections[ $id ][1] = '';
				}
			}
		}
		return $this->setting_sections;
	}

	public function get_fields(){
		self::$setting_fields = [
			[
				'label' => 'Capability',
				'id' => $this->prefix . 'capability',
				'type' => 'text',
				'section' => $this->plugin_id . '_section_1',
				'desc' => 'Input capability to see it.<br />Leave null if you want to show it to \'Administrator\' only.<br />option name: ' . $this->prefix . 'capability',
			],
			[
				'label' => 'Ignore types',
				'id' => $this->prefix . 'ignore_field_type',
				'type' => 'text',
				'section' => $this->plugin_id . '_section_1',
				'desc' => 'Input types of ACF fields as CSV not to show it. (e.g. message)<br />Leave null if you want to show any.<br />option name: ' . $this->prefix . 'ignore_field_type',
			],
			[
				'label' => 'Hide details',
				'id' => $this->prefix . 'hide_field_detail',
				'type' => 'checkbox',
				'section' => $this->plugin_id . '_section_1',
				'options' => [
					'name' => 'name',
					'key' => 'key',
					'type' => 'type',
				],
				'desc' => 'Select items to hide in each field label.<br />In the field list box, this option does not work.<br />option name: ' . $this->prefix . 'hide_field_detail',
			],
			[
				'label' => 'Hide columns',
				'id' => $this->prefix . 'hide_field_group_columns',
				'type' => 'checkbox',
				'section' => $this->plugin_id . '_section_2',
				'options' => [
					'last_modified' => 'last_modified',
					'last_modified_author' => 'last_modified_author',
				],
				'desc' => 'Select columns to hide.<br />option name: ' . $this->prefix . 'hide_field_group_columns',
			],
		];
		self::get_setting_field_ids();
	}

	public static function get_setting_field_ids(){
		if( ! empty( self::$setting_field_ids ) ){
			return self::$setting_field_ids;
		}
		foreach( self::$setting_fields as $field ){
			self::$setting_field_ids[] = $field['id'];
		}
		return self::$setting_field_ids;
	}

	public function settings_content() { ?>
		<div class="wrap">
			<h1>Developer Tools For ACF</h1>
			<?php settings_errors(); ?>
			<form method="POST" action="options.php">
				<?php
					settings_fields( $this->plugin_id );
					do_settings_sections( $this->plugin_id );
					submit_button();
				?>
			</form>
		</div> <?php
	}

	public function setup_sections() {
		$sections = $this->get_sections();
		if ( isset( $sections ) && is_array( $sections ) ) {
			foreach ( $sections as $id => $data ) {
				add_settings_section( $id, $data[0], [ $this, 'section_callback' ], $this->plugin_id );
			}
		}
	}

	public function section_callback( $section ) {
		$sections = $this->get_sections();
		if ( '' !== $sections[ $section['id'] ][1] ) {
			echo '<p class="description">' . $sections[ $section['id'] ][1] . '</p>';
		}
	}

	public function setup_fields() {
		$setting_fields = self::$setting_fields;
		foreach( $setting_fields as $field ){
			if( 'description' === $field['type'] ){
				continue;
			}
			add_settings_field( $field['id'], $field['label'], [ $this, 'field_callback' ], $this->plugin_id, $field['section'], $field );
			if ( method_exists( __CLASS__, 'validate_' . str_replace( $this->prefix, '', $field['id'] ) ) ) {
				// validation exists
				register_setting( $this->plugin_id, $field['id'], [ $this, 'validate_' . str_replace( $this->prefix, '', $field['id'] ) ] );
			} else {
				// no validation
				register_setting( $this->plugin_id, $field['id'] );
			}
		}
	}

	public function field_callback( $field ) {

		if( has_filter( 'pre_option_' . $field['id'] ) ) {
			printf( '<p class="has_filter" style="color: #f33; font-weight: bold;">This option is filtered by \'%s\'.</p>', 'pre_option_' . $field['id'] );
			printf( '<p>Filtered value is %s .</p>', print_r( get_option( $field['id'] ), true ) );
			return;
		}

		$value = get_option( $field['id'] );
		switch ( $field['type'] ) {
			case 'radio':
			case 'checkbox':
				if( ! empty ( $field['options'] ) && is_array( $field['options'] ) ) {
					$options_markup = '';
					$iterator = 0;
					$value = ( is_array( $value ) ) ? $value : [];
					foreach( $field['options'] as $key => $label ) {
						$iterator++;
						$options_markup.= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>',
							$field['id'],
							$field['type'],
							$key,
							( isset( $value[ array_search( $key, $value, true) ] ) ) ? checked( $value[ array_search( $key, $value, true ) ], $key, false ) : '',
							$label,
							$iterator
						);
					}
					printf( '<fieldset>%s</fieldset>',
						$options_markup
					);
				}
				break;
			default:
				printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />',
					$field['id'],
					$field['type'],
					( isset( $field['placeholder'] ) ) ? $field['placeholder'] : '',
					$value
				);
		}
		if( isset( $field['desc'] ) ) {
			printf( '<p class="description">%s</p>', $field['desc'] );
		}
	}

	public function validate_capability( $input ) {
		// multibyte character included?
		if( strlen( $input ) !== mb_strlen( $input, 'utf8' ) ) {
			add_settings_error(
				$this->prefix . 'capability',
				$this->prefix . 'capability' . '-validation_error',
				'You can not use multibyte character in \'Capability\'.',
				'error'
			);
			return get_option( $this->prefix . 'capability' );
		}
		return $input;
	}

}

Developer_Tools_For_Acf_Settings_Page::get_instance();
