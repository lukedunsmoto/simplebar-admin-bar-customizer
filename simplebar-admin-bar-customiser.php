<?php
/**
 * Plugin Name:       SimpleBar – Admin Bar Customiser
 * Plugin URI:        https://wordpress.org/plugins/simplebar-admin-bar-customiser/
 * Description:       Customise the Admin Bar and Admin Menu colours, and (per role) add/hide/reorder Admin Bar items safely.
 * Version:           2.0.0
 * Author:            Luke Dunsmore
 * Author URI:        https://lukedunsmore.com
 * Text Domain:       simplebar-admin-bar-customiser
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SBABC_VERSION', '2.0.0' );
define( 'SBABC_FILE', __FILE__ );
define( 'SBABC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SBABC_URL', plugin_dir_url( __FILE__ ) );
define( 'SBABC_OPT_KEY', 'sbabc_options' );
define( 'SBABC_ROLES_KEY', 'sbabc_roles' );

require_once SBABC_DIR . 'includes/class-simplebar-settings.php';
require_once SBABC_DIR . 'includes/class-simplebar-css.php';
require_once SBABC_DIR . 'includes/class-simplebar-roles.php';

final class SimpleBar_Admin_Bar_Customiser {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'add_menu' ] );

		// Output CSS where needed.
		add_action( 'admin_enqueue_scripts', [ $this, 'print_css_admin' ], 20 );
		add_action( 'wp_enqueue_scripts', [ $this, 'print_css_front' ], 20 );

		// Apply role-based admin bar changes.
		add_action( 'admin_bar_menu', [ $this, 'apply_role_bar' ], 999 );
	}

	public function enqueue_admin( $hook ) {
		if ( 'settings_page_sbabc' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'sbabc-admin', SBABC_URL . 'assets/admin.css', [], SBABC_VERSION );
		wp_enqueue_script( 'sbabc-admin', SBABC_URL . 'assets/admin.js', [ 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ], SBABC_VERSION, true );
		wp_localize_script( 'sbabc-admin', 'SBABC', [
			'nonce' => wp_create_nonce('sbabc_admin'),
		]);
	}

	public function register_settings() {
		SimpleBar_Settings::register();
		SimpleBar_Roles::register();
	}

	public function add_menu() {
		add_options_page(
			__( 'SimpleBar', 'simplebar-admin-bar-customiser' ),
			__( 'SimpleBar', 'simplebar-admin-bar-customiser' ),
			'manage_options',
			'sbabc',
			[ $this, 'render_page' ]
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'simplebar-admin-bar-customiser' ) );
		}

		// Safe: View-only logic (tab switching), no state change.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['appearance','roles'], true) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'appearance';
		?>
		<div class="wrap sbabc-wrap">
			<h1><?php echo esc_html__( 'SimpleBar – Admin Bar Customiser', 'simplebar-admin-bar-customiser' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab <?php echo $active_tab==='appearance'?'nav-tab-active':'';?>" href="<?php echo esc_url( admin_url('options-general.php?page=sbabc&tab=appearance') ); ?>"><?php esc_html_e('Appearance', 'simplebar-admin-bar-customiser'); ?></a>
				<a class="nav-tab <?php echo $active_tab==='roles'?'nav-tab-active':'';?>" href="<?php echo esc_url( admin_url('options-general.php?page=sbabc&tab=roles') ); ?>"><?php esc_html_e('Roles & Links', 'simplebar-admin-bar-customiser'); ?></a>
			</h2>

			<?php if ( $active_tab === 'appearance' ) : ?>
				<form action="options.php" method="post">
					<?php
						settings_fields( 'sbabc_settings' );
						do_settings_sections( 'sbabc' );
						submit_button( __( 'Save changes', 'simplebar-admin-bar-customiser' ) );
					?>
				</form>
			<?php else: ?>
				<form action="options.php" method="post" id="sbabc-roles-form">
					<?php
						settings_fields( 'sbabc_roles' );
						SimpleBar_Roles::render_roles_ui();
						submit_button( __( 'Save role settings', 'simplebar-admin-bar-customiser' ) );
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	// Inline CSS for wp-admin.
	public function print_css_admin() {
		$opts = SimpleBar_Settings::get_options();
		if ( empty( $opts ) ) return;
		$css = SimpleBar_CSS::build_css( $opts, true );
		if ( $css ) {
			wp_register_style( 'sbabc-inline-admin', false, [], SBABC_VERSION );
			wp_enqueue_style( 'sbabc-inline-admin' );
			wp_add_inline_style( 'sbabc-inline-admin', $css );
		}
	}

	// Inline CSS for front-end admin bar (only when toolbar is showing).
	public function print_css_front() {
		if ( ! is_user_logged_in() || is_admin() ) return;
		if ( ! is_admin_bar_showing() ) return;
		$opts = SimpleBar_Settings::get_options();
		if ( empty( $opts ) || empty( $opts['apply_front'] ) ) return;
		$css = SimpleBar_CSS::build_css( $opts, false );
		if ( $css ) {
			wp_register_style( 'sbabc-inline-front', false, [], SBABC_VERSION );
			wp_enqueue_style( 'sbabc-inline-front' );
			wp_add_inline_style( 'sbabc-inline-front', $css );
		}
	}

	// Apply role-based add/hide/reorder.
	public function apply_role_bar( $wp_admin_bar ) {
		if ( ! is_user_logged_in() ) return;
		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) return;

		$roles = SimpleBar_Roles::get_roles_options();
		if ( empty( $roles ) || ! is_array( $roles ) ) return;

		// Determine the first role in the user's role list that we have settings for.
		$role_key = null;
		foreach ( $user->roles as $r ) {
			if ( isset( $roles[ $r ] ) ) { $role_key = $r; break; }
		}
		if ( ! $role_key ) return;
		$cfg = $roles[ $role_key ];

		// 1) Hide core nodes.
		if ( ! empty( $cfg['hide_core'] ) && is_array( $cfg['hide_core'] ) ) {
			foreach ( $cfg['hide_core'] as $node_id ) {
				if ( is_string( $node_id ) && $node_id !== '' ) {
					$wp_admin_bar->remove_node( $node_id );
				}
			}
		}

		// 2) Add custom links in chosen order.
		if ( ! empty( $cfg['custom'] ) && is_array( $cfg['custom'] ) ) {
			// sort by position int ascending
			usort( $cfg['custom'], function($a,$b){
				$pa = isset($a['position']) ? intval($a['position']) : 0;
				$pb = isset($b['position']) ? intval($b['position']) : 0;
				return $pa <=> $pb;
			});
			foreach ( $cfg['custom'] as $item ) {
				if ( empty( $item['visible'] ) ) continue;
				$id    = isset($item['id']) ? sanitize_key($item['id']) : ('sbabc-custom-' . wp_generate_password(6,false));
				$title = isset($item['title']) ? wp_strip_all_tags( $item['title'] ) : '';
				$url   = isset($item['url']) ? esc_url( $item['url'] ) : '';
				if ( $title === '' || $url === '' ) continue;
				
				// Parent removed
				$meta = ['class'=>'sbabc-custom'];
				if ( ! empty( $item['target'] ) && in_array( $item['target'], ['_self','_blank'], true ) ) {
					$meta['target'] = $item['target'];
				}
				$wp_admin_bar->add_node([
					'id'     => $id,
					'title'  => esc_html( $title ),
					'href'   => $url,
					'parent' => false, // Always top level
					'meta'   => $meta,
				]);
			}
		}

		// 3) Reorder supported top-level nodes (best-effort): custom order list.
		if ( ! empty( $cfg['order'] ) && is_array( $cfg['order'] ) ) {
			foreach ( $cfg['order'] as $node_id ) {
				$node_id = is_string($node_id) ? $node_id : '';
				if ( $node_id === '' ) continue;
				$node = $wp_admin_bar->get_node( $node_id );
				if ( ! $node ) continue;
				// Re-append in our sequence to push it to the end in this order.
				$wp_admin_bar->remove_node( $node_id );
				$wp_admin_bar->add_node([
					'id'     => $node->id,
					'title'  => $node->title,
					'href'   => $node->href,
					'parent' => $node->parent ?: false,
					'meta'   => (array) $node->meta,
				]);
			}
		}
	}
}

new SimpleBar_Admin_Bar_Customiser();