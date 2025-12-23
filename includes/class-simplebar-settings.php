<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SimpleBar_Settings {
	public static function defaults() {
		return [
			'apply_front'   => 1,
			'apply_admin'   => 1,
			'apply_menu'    => 1,
			'bar_bg'        => '#FFC300',
			'bar_text'      => '#000000',
			'bar_hover_bg'  => '#1e73be',
			'bar_hover_tx'  => '#ffffff',
			'menu_bg'       => '#23282d',
			'menu_tx'       => '#ffffff',
			'menu_current_bg' => '#1e73be',
			'menu_current_tx' => '#ffffff',
			'menu_open_bg'  => '#2c3338',
			'menu_open_tx'  => '#ffffff',
			'important'     => 1,
		];
	}

	public static function get_options() {
		$opts = get_option( SBABC_OPT_KEY, [] );
		return wp_parse_args( is_array( $opts ) ? $opts : [], self::defaults() );
	}

	public static function register() {
		register_setting(
			'sbabc_settings',
			SBABC_OPT_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize' ],
				'default'           => self::defaults(),
			]
		);

		add_settings_section(
			'sbabc_section_main',
			__( 'Admin Bar colours', 'simplebar-admin-bar-customiser' ),
			function () {
				echo '<p>' . esc_html__( 'Colours for the Admin Bar (front-end when logged in + wp-admin).', 'simplebar-admin-bar-customiser' ) . '</p>';
			},
			'sbabc'
		);

		self::add_colour( 'bar_bg',        __( 'Admin Bar background', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'bar_text',      __( 'Admin Bar text/icons', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'bar_hover_bg',  __( 'Admin Bar hover/selected background', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'bar_hover_tx',  __( 'Admin Bar hover/selected text', 'simplebar-admin-bar-customiser' ) );

		add_settings_section(
			'sbabc_section_menu',
			__( 'Admin Menu (left sidebar)', 'simplebar-admin-bar-customiser' ),
			function () {
				echo '<p>' . esc_html__( 'Colours for the wp-admin left menu.', 'simplebar-admin-bar-customiser' ) . '</p>';
			},
			'sbabc'
		);

		self::add_colour( 'menu_bg',         __( 'Menu background (default)', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'menu_tx',         __( 'Menu text/icons (default)', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'menu_current_bg', __( 'Current/hovered item background', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'menu_current_tx', __( 'Current/hovered item text', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'menu_open_bg',    __( 'Open submenu header background', 'simplebar-admin-bar-customiser' ) );
		self::add_colour( 'menu_open_tx',    __( 'Open submenu header text', 'simplebar-admin-bar-customiser' ) );

		add_settings_section(
			'sbabc_section_behaviour',
			__( 'Behaviour', 'simplebar-admin-bar-customiser' ),
			function () {
				echo '<p>' . esc_html__( 'Where should the styles apply?', 'simplebar-admin-bar-customiser' ) . '</p>';
			},
			'sbabc'
		);

		self::add_checkbox( 'apply_front', __( 'Apply on front-end (when Admin Bar is visible)', 'simplebar-admin-bar-customiser' ) );
		self::add_checkbox( 'apply_admin', __( 'Apply in wp-admin', 'simplebar-admin-bar-customiser' ) );
		self::add_checkbox( 'apply_menu',  __( 'Apply to left Admin Menu (wp-admin)', 'simplebar-admin-bar-customiser' ) );
		self::add_checkbox( 'important',   __( 'Force styles with !important', 'simplebar-admin-bar-customiser' ) );
	}

	private static function add_colour( $key, $label ) {
		add_settings_field(
			$key,
			esc_html( $label ),
			function () use ( $key ) {
				$opts  = self::get_options();
				$value = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
				echo '<input type="text" class="sbabc-colour-field" name="' . esc_attr( SBABC_OPT_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( self::defaults()[ $key ] ) . '"/>';
			},
			'sbabc',
			( strpos( $key, 'menu_' ) === 0 ? 'sbabc_section_menu' : 'sbabc_section_main' )
		);
	}

	private static function add_checkbox( $key, $label ) {
		add_settings_field(
			$key,
			esc_html( $label ),
			function () use ( $key ) {
				$opts  = self::get_options();
				$val   = ! empty( $opts[ $key ] ) ? 1 : 0;
				echo '<label><input type="checkbox" name="' . esc_attr( SBABC_OPT_KEY ) . '[' . esc_attr( $key ) . ']" value="1" ' . checked( 1, $val, false ) . '> ' . esc_html__( 'Enabled', 'simplebar-admin-bar-customiser' ) . '</label>';
			},
			'sbabc',
			'sbabc_section_behaviour'
		);
	}

	public static function sanitize( $input ) {
		$clean   = self::defaults();
		$input   = is_array( $input ) ? $input : [];
		$bools   = [ 'apply_front', 'apply_admin', 'apply_menu', 'important' ];
		$colours = [ 'bar_bg', 'bar_text', 'bar_hover_bg', 'bar_hover_tx', 'menu_bg', 'menu_tx', 'menu_current_bg', 'menu_current_tx', 'menu_open_bg', 'menu_open_tx' ];

		foreach ( $bools as $b ) {
			$clean[ $b ] = empty( $input[ $b ] ) ? 0 : 1;
		}

		foreach ( $colours as $c ) {
			if ( isset( $input[ $c ] ) && is_string( $input[ $c ] ) ) {
				$val = trim( $input[ $c ] );
				if ( preg_match( '/^#([0-9a-fA-F]{3}){1,2}$/', $val ) ) {
					$clean[ $c ] = $val;
				}
			}
		}

		return $clean;
	}
}
