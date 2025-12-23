<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SimpleBar_Roles {

	public static function get_roles_options() {
		$opts = get_option( SBABC_ROLES_KEY, [] );
		return is_array($opts) ? $opts : [];
	}

	public static function roles_defaults() {
		return [
			'hide_core' => [],
			'custom'    => [], // array of items: id,title,url,target,position,visible
			'order'     => [], // array of node ids for best-effort reorder
		];
	}

	public static function register() {
		register_setting(
			'sbabc_roles',
			SBABC_ROLES_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_roles' ],
				'default'           => [],
			]
		);
	}

	public static function available_roles() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) $wp_roles = wp_roles();
		return $wp_roles->roles; // array keyed by role key
	}

	public static function known_core_nodes() {
		return [
			'wp-logo'      => __( 'WP Logo', 'simplebar-admin-bar-customiser' ),
			'site-name'    => __( 'Site Name', 'simplebar-admin-bar-customiser' ),
			'updates'      => __( 'Updates', 'simplebar-admin-bar-customiser' ),
			'comments'     => __( 'Comments', 'simplebar-admin-bar-customiser' ),
			'new-content'  => __( 'New', 'simplebar-admin-bar-customiser' ),
			'edit'         => __( 'Edit', 'simplebar-admin-bar-customiser' ),
			'my-account'   => __( 'My Account', 'simplebar-admin-bar-customiser' ),
			'search'       => __( 'Search', 'simplebar-admin-bar-customiser' ),
			'customize'    => __( 'Customize', 'simplebar-admin-bar-customiser' ),
		];
	}

	public static function render_roles_ui() {
		$all_roles = self::available_roles();
		$opts      = self::get_roles_options();

		// Safe: View-only logic (switching role context), no state change.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset($_GET['role']) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';
		
		if ( $current === '' || ! isset($all_roles[$current]) ) {
			// pick administrator by default if exists
			$current = isset($all_roles['administrator']) ? 'administrator' : array_key_first($all_roles);
		}
		$role_opts = isset($opts[$current]) && is_array($opts[$current]) ? wp_parse_args( $opts[$current], self::roles_defaults() ) : self::roles_defaults();

		$core = self::known_core_nodes();
		?>
		<input type="hidden" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[__role__]" value="<?php echo esc_attr( $current ); ?>" />
		<div class="sbabc-roles">
			<div class="sbabc-roles-header">
				<label for="sbabc-role-select"><strong><?php esc_html_e('Role:', 'simplebar-admin-bar-customiser'); ?></strong></label>
				<select id="sbabc-role-select" onchange="location.href='<?php echo esc_url( admin_url('options-general.php?page=sbabc&tab=roles&role=') ); ?>'+this.value;">
					<?php foreach ( $all_roles as $key => $def ) : ?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>><?php echo esc_html( translate_user_role( $def['name'] ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="sbabc-columns">
				<div class="sbabc-card">
					<h3><?php esc_html_e('Hide core admin bar items', 'simplebar-admin-bar-customiser'); ?></h3>
					<p class="description"><?php esc_html_e('Tick any built‑in nodes you want hidden for this role. (Third‑party nodes not listed here can still be hidden by adding their IDs manually in a future update.)', 'simplebar-admin-bar-customiser'); ?></p>
					<ul class="sbabc-checklist">
						<?php
						$hidden = is_array($role_opts['hide_core']) ? $role_opts['hide_core'] : [];
						foreach ( $core as $id => $label ) :
							$checked = in_array( $id, $hidden, true );
							?>
							<li>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][hide_core][]" value="<?php echo esc_attr($id); ?>" <?php checked($checked); ?> />
									<?php echo esc_html($label . ' (' . $id . ')'); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="sbabc-card">
					<h3><?php esc_html_e('Custom links (drag to reorder)', 'simplebar-admin-bar-customiser'); ?></h3>
					<p class="description"><?php esc_html_e('Add your own quick links. Drag the handle to change order.', 'simplebar-admin-bar-customiser'); ?></p>
					<table class="widefat fixed sbabc-links">
						<thead>
							<tr>
								<th style="width: 50px;"><?php esc_html_e('Order', 'simplebar-admin-bar-customiser'); ?></th>
								<th><?php esc_html_e('Title', 'simplebar-admin-bar-customiser'); ?></th>
								<th><?php esc_html_e('URL', 'simplebar-admin-bar-customiser'); ?></th>
								<th style="width: 100px;"><?php esc_html_e('Target', 'simplebar-admin-bar-customiser'); ?></th>
								<th style="width: 60px;"><?php esc_html_e('Visible', 'simplebar-admin-bar-customiser'); ?></th>
								<th style="width: 80px;"><?php esc_html_e('Delete', 'simplebar-admin-bar-customiser'); ?></th>
							</tr>
						</thead>
						<tbody id="sbabc-links-body">
							<?php
							$custom = is_array($role_opts['custom']) ? $role_opts['custom'] : [];
							// Sort by position
							usort($custom, function($a,$b){
								return intval($a['position'] ?? 0) <=> intval($b['position'] ?? 0);
							});
							$idx = 0;
							foreach ( $custom as $item ) :
								$idx++;
								$id      = sanitize_key( $item['id'] ?? ('sbabc-custom-' . $idx) );
								$title   = esc_attr( $item['title'] ?? '' );
								$url     = esc_url( $item['url'] ?? '' );
								$target  = in_array( ($item['target'] ?? '_self'), ['_self','_blank'], true ) ? $item['target'] : '_self';
								$pos     = intval( $item['position'] ?? $idx );
								$vis     = ! empty( $item['visible'] ) ? 1 : 0;
								?>
								<tr class="sbabc-row">
									<td class="sbabc-handle" style="cursor:move;">
										<span class="dashicons dashicons-move"></span>
										<input type="hidden" class="sbabc-position" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][custom][<?php echo esc_attr($idx); ?>][position]" value="<?php echo esc_attr($pos); ?>" />
										<input type="hidden" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][custom][<?php echo esc_attr($idx); ?>][id]" value="<?php echo esc_attr($id); ?>" />
									</td>
									<td><input type="text" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][custom][<?php echo esc_attr($idx); ?>][title]" value="<?php echo esc_attr($title); ?>" class="regular-text" /></td>
									<td><input type="url"  name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][custom][<?php echo esc_attr($idx); ?>][url]"   value="<?php echo esc_attr($url); ?>" class="regular-text" /></td>
									<td>
										<select name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][custom][<?php echo esc_attr($idx); ?>][target]">
											<option value="_self" <?php selected($target,'_self'); ?>>_self</option>
											<option value="_blank" <?php selected($target,'_blank'); ?>>_blank</option>
										</select>
									</td>
									<td><input type="checkbox" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][custom][<?php echo esc_attr($idx); ?>][visible]" value="1" <?php checked(1,$vis); ?> /></td>
									<td><button type="button" class="button sbabc-delete-row"><?php esc_html_e('Delete', 'simplebar-admin-bar-customiser'); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button button-secondary" id="sbabc-add-row"><?php esc_html_e('Add link', 'simplebar-admin-bar-customiser'); ?></button></p>
				</div>
			</div>

			<div class="sbabc-card">
				<h3><?php esc_html_e('Optional: Best‑effort reorder of top‑level items', 'simplebar-admin-bar-customiser'); ?></h3>
				<p class="description"><?php esc_html_e('Drag to set a preferred order for common top‑level nodes. WordPress may add some items after this runs, so consider this a best‑effort.', 'simplebar-admin-bar-customiser'); ?></p>
				<ul id="sbabc-order-list" class="sbabc-sortlist">
					<?php
					$order = is_array($role_opts['order']) ? $role_opts['order'] : [];
					$seen = [];
					// print saved order first
					foreach ( $order as $oid ) {
						if ( isset($core[$oid]) ) {
							$seen[$oid]=1;
							echo '<li class="sbabc-order-item" data-id="'.esc_attr($oid).'"><span class="dashicons dashicons-move"></span> '.esc_html($core[$oid]).' <code>'.esc_html($oid).'</code></li>';
						}
					}
					// then remaining known nodes not in order
					foreach ( $core as $id => $label ) {
						if ( isset($seen[$id]) ) continue;
						echo '<li class="sbabc-order-item" data-id="'.esc_attr($id).'"><span class="dashicons dashicons-move"></span> '.esc_html($label).' <code>'.esc_html($id).'</code></li>';
					}
					?>
				</ul>
				<input type="hidden" id="sbabc-order-input" name="<?php echo esc_attr( SBABC_ROLES_KEY ); ?>[<?php echo esc_attr($current); ?>][order_json]" value="<?php echo esc_attr( wp_json_encode( $order ) ); ?>" />
			</div>
		</div>
		<?php
	}

	public static function sanitize_roles( $input ) {
		$input = is_array($input) ? $input : [];
		$out = [];
		$role = isset($input['__role__']) ? sanitize_key($input['__role__']) : '';
		unset($input['__role__']);

		if ( $role && isset($input[$role]) && is_array($input[$role]) ) {
			$out[$role] = self::sanitize_role_payload( $input[$role] );
		} else {
			foreach ( $input as $rk => $payload ) {
				if ( ! is_array($payload) ) continue;
				$out[ sanitize_key($rk) ] = self::sanitize_role_payload( $payload );
			}
		}
		return $out;
	}

	private static function sanitize_role_payload( $payload ) {
		$clean = self::roles_defaults();
		$payload = is_array($payload) ? $payload : [];

		// hide_core
		$clean['hide_core'] = [];
		if ( ! empty( $payload['hide_core'] ) && is_array( $payload['hide_core'] ) ) {
			foreach ( $payload['hide_core'] as $nid ) {
				$nid = sanitize_key( $nid );
				if ( $nid !== '' ) $clean['hide_core'][] = $nid;
			}
		}

		// custom links
		$clean['custom'] = [];
		if ( ! empty( $payload['custom'] ) && is_array( $payload['custom'] ) ) {
			foreach ( $payload['custom'] as $row ) {
				if ( ! is_array($row) ) continue;
				$id     = isset($row['id']) ? sanitize_key($row['id']) : ('sbabc-custom-' . wp_generate_password(6,false));
				$title  = isset($row['title']) ? wp_strip_all_tags( $row['title'] ) : '';
				$url    = isset($row['url']) ? esc_url_raw( $row['url'] ) : '';
				// Parent ID removed
				$target = ( isset($row['target']) && in_array($row['target'], ['_self','_blank'], true) ) ? $row['target'] : '_self';
				$pos    = isset($row['position']) ? intval($row['position']) : 0;
				$vis    = ! empty( $row['visible'] ) ? 1 : 0;

				if ( $title !== '' && $url !== '' ) {
					$clean['custom'][] = [
						'id' => $id,
						'title' => $title,
						'url' => $url,
						'target' => $target,
						'position' => $pos,
						'visible' => $vis,
					];
				}
			}
		}

		// order
		$clean['order'] = [];
		if ( isset($payload['order_json']) && is_string($payload['order_json']) ) {
			$list = json_decode( $payload['order_json'], true );
			if ( is_array( $list ) ) {
				foreach ( $list as $nid ) {
					$nid = sanitize_key( $nid );
					if ( $nid !== '' ) $clean['order'][] = $nid;
				}
			}
		}

		return $clean;
	}
}
