<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SimpleBar_CSS {
	public static function build_css( $o, $is_admin = true ) {
		$imp = ! empty( $o['important'] ) ? ' !important' : '';
		$css = '';

		if ( ( $is_admin && ! empty( $o['apply_admin'] ) ) || ( ! $is_admin && ! empty( $o['apply_front'] ) ) ) {
			$css .= "
			/* SimpleBar: Admin Bar */
			#wpadminbar { background: {$o['bar_bg']}{$imp}; color: {$o['bar_text']}{$imp}; }
			#wpadminbar .ab-item, #wpadminbar .ab-label, #wpadminbar a.ab-item, #wpadminbar .ab-icon { color: {$o['bar_text']}{$imp}; fill: {$o['bar_text']}{$imp}; }
			#wpadminbar .ab-item:hover, #wpadminbar li:hover > .ab-item { background: {$o['bar_hover_bg']}{$imp}; color: {$o['bar_hover_tx']}{$imp}; }
			#wpadminbar .ab-sub-wrapper, #wpadminbar .ab-submenu { background: {$o['bar_hover_bg']}{$imp}; }
			#wpadminbar .ab-submenu .ab-item { color: {$o['bar_hover_tx']}{$imp}; }
			#wpadminbar .ab-submenu .ab-item:hover { background: {$o['bar_bg']}{$imp}; color: {$o['bar_text']}{$imp}; }
			";
		}

		if ( $is_admin && ! empty( $o['apply_menu'] ) ) {
			$css .= "
			/* SimpleBar: Admin Menu */
			#adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap { background: {$o['menu_bg']}{$imp}; }
			#adminmenu a, #adminmenu .wp-submenu a { color: {$o['menu_tx']}{$imp}; }
			#adminmenu li.menu-top:hover, #adminmenu li.opensub > a.menu-top { background: {$o['menu_current_bg']}{$imp}; color: {$o['menu_current_tx']}{$imp}; }
			#adminmenu .wp-has-current-submenu > a.menu-top, #adminmenu .current a.menu-top { background: {$o['menu_current_bg']}{$imp}; color: {$o['menu_current_tx']}{$imp}; }
			#adminmenu .opensub > a.menu-top, #adminmenu .wp-has-current-submenu > a.menu-top { background: {$o['menu_open_bg']}{$imp}; color: {$o['menu_open_tx']}{$imp}; }
			#adminmenu .wp-submenu .wp-submenu-head { background: {$o['menu_open_bg']}{$imp}; color: {$o['menu_open_tx']}{$imp}; }
			#adminmenu div.wp-menu-image:before, #adminmenu .wp-menu-image svg { color: {$o['menu_tx']}{$imp}; fill: {$o['menu_tx']}{$imp}; }
			";
		}

		$css = trim( preg_replace( '/\s+/', ' ', $css ) );
		return $css ?: '';
	}
}
