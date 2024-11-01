<?php
/**
 * class-points-admin.php
 *
 * Copyright (c) Antonio Blanco http://www.eggemplo.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco
 * @package points
 * @since points 1.0.0
 */

/**
 * Points Admin class
 */
class WooPoints_Admin {

	public static function init () {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
	}

	public static function admin_notices() {
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}

	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_menu_page(
				__( 'Woocommerce Points', 'woocommerce-points' ),
				__( 'Woocommerce Points', 'woocommerce-points' ),
				'manage_options',
				'woopoints',
				array( __CLASS__, 'woopoints_menu'),
				WOO_POINTS_PLUGIN_URL . '/img/logo.png'
		);

		// Options
		$page = add_submenu_page(
				'woopoints',
				__( 'Options', 'woocommerce-points' ),
				__( 'Options', 'woocommerce-points' ),
				'manage_options',
				'woopoints-admin-options',
				array( __CLASS__, 'woopoints_admin_options')
		);

		// Notifications
		$page = add_submenu_page(
				'woopoints',
				__( 'Notifications', 'woocommerce-points' ),
				__( 'Notifications', 'woocommerce-points' ),
				'manage_options',
				'woopoints-admin-notifications',
				array( __CLASS__, 'woopoints_admin_notifications_section')
				);

	}

	public static function woopoints_menu() {
		$alert = '';
		$alert_class = 'notice-success';
		if ( isset( $_POST['save'] ) && isset( $_POST['action'] ) ) {
			if ( $_POST['action'] == 'edit' ) {
				$point_id = isset($_POST['point_id'])?intval( $_POST['point_id'] ) : null;
				$points = WooPoints::get_point( $point_id );
				$data = array();
				if ( isset( $_POST['user_id'] ) ) {
					$data['user_id'] = intval($_POST['user_id']);

					if ( isset( $_POST['datetime'] ) ) {
						$data['datetime'] = sanitize_text_field( $_POST['datetime'] );
					}
					if ( isset( $_POST['description'] ) ) {
						$data['description'] = sanitize_text_field( $_POST['description'] );
					}
					if ( isset( $_POST['status'] ) ) {
						$data['status'] = sanitize_text_field( $_POST['status'] );
					}
					if ( isset( $_POST['points'] ) ) {
						$data['points'] = intval( $_POST['points'] );
					}
					if ( isset( $_POST['ref_id'] ) && is_int( $_POST['ref_id'] ) ) {
						$data['ref_id'] = intval( $_POST['ref_id'] );
					}

					if ( $points ) {  // edit points
						WooPoints::update_points($point_id, $data);
					} else {  // add new points
						WooPoints::set_points( $data['points'], $data['user_id'], $data);
					}
					$alert = __( 'Points Updated', 'woocommerce-points' );
				} else {
					$alert = __( 'Username is not valid', 'woocommerce-points' );
					$alert_class = 'notice-error';
				}
			}
		}

		if ( isset( $_GET['action'] ) ) {
			$action = $_GET['action'];
			if ( $action !== null ) {
				switch ( $action ) {
					case 'edit' :
						if ( isset( $_GET['point_id'] ) && ( $_GET['point_id'] !== null ) ) {
							return self::woopoints_admin_points_edit( intval( $_GET['point_id'] ) );
						} else {
							return self::woopoints_admin_points_edit();
						}
						break;
					case 'delete' :
						if ( ( $_GET['point_id'] !== null ) && ( is_int( $_GET['point_id'] ) ) ) {
							if ( current_user_can( 'administrator' ) ) {
								WooPoints::remove_points( intval( $_GET['point_id'] ) );
								$alert= __( 'Points Removed', 'woocommerce-points' );
							}
						}
						break;
				}
			}
		}

		if ($alert != '') {
			echo '<div class="notice ' . $alert_class . ' " >' . $alert . '</div>';
		}

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$cancel_url  = remove_query_arg( 'point_id', remove_query_arg( 'action', $current_url ) );
		$current_url = remove_query_arg( 'point_id', $current_url );
		$current_url = remove_query_arg( 'action', $current_url );
		
		$exampleListTable = new WooPoints_List_Table();
		$exampleListTable->prepare_items();
		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2>Points</h2>
			<div class="manage add">
				<a class="add button" href="<?php echo esc_url( add_query_arg( 'action', 'edit', $current_url ) ); ?>" title="<?php echo __( 'Click to add a Points manually', 'woocommerce-points' );?>"><?php echo __( 'Add Points', 'woocommerce-points' );?></a>
			</div>

			<?php $exampleListTable->show_table(); ?>
		</div>
		<?php
	}

	/**
	 * Show Points options page.
	 */
	public static function woopoints_admin_options() {

		$section_links_array = array(
				'general' => __( 'General', 'woocommerce-points' ),
				'pages'  => __( 'Pages', 'woocommerce-points' ),
				'orders'  => __( 'Orders', 'woocommerce-points' ),
				'rewards'  => __( 'Rewards', 'woocommerce-points' )
		);

		$alert = '';

		$section = isset( $_REQUEST['section'] ) ? $_REQUEST['section'] : 'general';

		if ( isset( $_POST['submit'] ) ) {
			switch ( $section ) {
				case 'rewards' :
					update_option( 'woopoints-coupons_dollars', intval( $_POST['coupons_dollars'] ) );
					$alert= __( 'Saved Rewards subsection', 'woocommerce-points' );
					break;
				case 'orders' :
					$label = ( isset( $_POST['points_per_dollar'] ) && $_POST['points_per_dollar'] !== '' )?intval($_POST['points_per_dollar']):'0';
					update_option( 'woopoints-points_per_dollar', $label );
					update_option( 'woopoints-round', sanitize_text_field( $_POST['points_round'] ) );
					update_option( 'woopoints-points_status', sanitize_text_field( $_POST['points_status'] ) );

					$alert= __( 'Saved Order subsection', 'woocommerce-points' );
					break;
				case 'general' :
				default :
					$label = ( isset( $_POST['points_label'] ) && $_POST['points_label'] !== '' )? sanitize_text_field( $_POST['points_label'] ) : 'points';
					$slabel = ( isset( $_POST['points_singular_label'] ) && $_POST['points_singular_label'] !== '' )? sanitize_text_field( $_POST['points_singular_label'] ) : 'point';
					update_option( 'woopoints-points_label', $label );
					update_option( 'woopoints-points_singular_label', $slabel );

					$alert= __( 'Saved General subsection', 'woocommerce-points' );
					break;
			}
		}

		if ($alert != '') {
			echo '<div style="background-color: #ffffe0;border: 1px solid #993;padding: 1em;margin-right: 1em;">' . $alert . '</div>';
		}

		$section_title = $section_links_array[$section];

		?>
			<h2><?php echo __( 'Points Options', 'woocommerce-points' ); ?></h2>
			<hr>

		<?php
			$section_links = '';
			foreach( $section_links_array as $sec => $title ) {
				$section_links .= sprintf(
					'<a class="section-link nav-tab %s" href="%s">%s</a>',
					$section == $sec ? 'active nav-tab-active' : '',
					esc_url( add_query_arg( 'section', $sec, admin_url( 'admin.php?page=woopoints-admin-options' ) ) ),
					$title
				);
			}
		?>
			<div class="section-links nav-tab-wrapper">
			<?php echo $section_links; ?>
			</div>
<?php
		switch( $section ) {
			case 'pages' :
				self::section_pages();
				break;
			case 'orders' :
				self::section_orders();
				break;
			case 'rewards' :
				self::section_rewards();
				break;
			case 'general' :
			default :
				self::section_general();
				break;
		}
	}

	/**
	 * Dashboard Options section, General subsection.
	 */
	protected static function section_general () {
	?>
		<form method="post" action="">

			<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
			<h3><?php echo __( 'General', 'woocommerce-points' ); ?></h3>
			<div class="woopoints-admin-line">
				<div class="woopoints-admin-label">
					<?php echo __( 'Points plural label', 'woocommerce-points' ); ?>
				</div>
				<div class="woopoints-admin-value">
					<?php 
					$label = get_option('woopoints-points_label', 'points');
					?>
					<input type="text" name="points_label" value="<?php echo $label; ?>" class="regular-text" />
				</div>
			</div>

			<div class="woopoints-admin-line">
				<div class="woopoints-admin-label">
					<?php echo __( 'Points singular label', 'woocommerce-points' ); ?>
				</div>
				<div class="woopoints-admin-value">
					<?php 
					$label = get_option('woopoints-points_singular_label', 'point');
					?>
					<input type="text" name="points_singular_label" value="<?php echo $label; ?>" class="regular-text" />
				</div>
			</div>

		<div class="woopoints-admin-line">
			<?php submit_button( __('Save', 'woocommerce-points' ) ); ?>
		</div>

		<?php settings_fields( 'woopoints-settings' ); ?>

	</form>
<?php 
	}

	/**
	 * Dashboard Options section, Orders subsection.
	 */
	protected static function section_orders () {
	?>
		<form method="post" action="">

			<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
				<h3><?php echo __( 'Orders', 'woocommerce-points' ); ?></h3>
				<div class="woopoints-admin-line">
					<div class="woopoints-admin-label">
						<?php echo sprintf( __( 'Points every %s', 'woocommerce-points' ), get_woocommerce_currency_symbol() ); ?>
					</div>
					<div class="woopoints-admin-value">
						<?php 
						$label = get_option('woopoints-points_per_dollar', '');
						?>
						<input type="text" name="points_per_dollar" value="<?php echo $label; ?>" class="regular-text" />
						</br>
						<span class="description"><?php echo __( 'Ex. If you set 5, then the user gets 5 points for every dollar.')?></span>
					</div>
				</div>

				<div class="woopoints-admin-line">
					<div class="woopoints-admin-label">
						<?php echo __( 'Round type', 'woocommerce-points' ); ?>
					</div>
					<div class="woopoints-admin-value">
						<select name="points_round">
						<?php 
						$output = '';
						$status = get_option( 'woopoints-round', 'floor' );
						$status_descriptions = array(
								'ceil' => __( 'Ceil', 'woocommerce-points' ),
								'floor'   => __( 'Floor', 'woocommerce-points' )
						);
						foreach ( $status_descriptions as $key => $label ) {
							$selected = $key == $status ? ' selected="selected" ' : '';
							$output .= '<option ' . $selected . ' value="' . esc_attr( $key ) . '">' . $label . '</option>';
						}
						echo $output;
						?>
						</select>
					</div>
				</div>
				
				<div class="woopoints-admin-line">
					<div class="woopoints-admin-label">
						<?php echo __( 'Default points status', 'woocommerce-points' ); ?>
					</div>
					<div class="woopoints-admin-value">
						<select name="points_status">
						<?php 
						$output = '';
						$status = get_option( 'woopoints-points_status', WOO_POINTS_STATUS_ACCEPTED );
						$status_descriptions = array(
								WOO_POINTS_STATUS_ACCEPTED => __( 'Accepted', 'woocommerce-points' ),
								WOO_POINTS_STATUS_PENDING   => __( 'Pending', 'woocommerce-points' ),
								WOO_POINTS_STATUS_REJECTED => __( 'Rejected', 'woocommerce-points' ),
						);
						foreach ( $status_descriptions as $key => $label ) {
							$selected = $key == $status ? ' selected="selected" ' : '';
							$output .= '<option ' . $selected . ' value="' . esc_attr( $key ) . '">' . $label . '</option>';
						}
						echo $output;
						?>
						</select>
					</div>
				</div>
			</div>

			<div class="woopoints-admin-line">
				<?php submit_button( __('Save', 'woocommerce-points' ) ); ?>
			</div>

			<?php settings_fields( 'woopoints-settings' ); ?>

		</form>
	<?php 
	}

	/**
	 * Dashboard Options section, Rewards subsection.
	 */
	protected static function section_rewards () {
	?>
		<form method="post" action="">

			<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
				<h3><?php echo __( 'Coupons', 'woocommerce-points' ); ?></h3>
				<div class="woopoints-admin-line">
					<div class="woopoints-admin-label">
						<?php 
						echo sprintf( __( '100 %s are ', 'woocommerce-points' ), get_option('woopoints-points_label', 'points') ); 
						$label = get_option('woopoints-coupons_dollars', '');
						?>
						<input type="text" name="coupons_dollars" value="<?php echo $label; ?>" class="" size="3" />
						<?php echo sprintf( '%s', get_woocommerce_currency_symbol() ); ?>
						</br>
						<span class="description"><?php echo __( 'Ex. If you set 5, then the user with 100 points gets $5.')?></span>
					</div>
				</div>

				<div class="woopoints-admin-line">
					<?php submit_button( __('Save', 'woocommerce-points' ) ); ?>
				</div>

			</div>

			<?php settings_fields( 'woopoints-settings' ); ?>

		</form>
	<?php 
	}

	/**
	 * Pages section.
	 */
	protected static function section_pages () {
		$alert = '';
		if ( isset( $_POST['submit'] ) ) {
			delete_option( 'woopoints-display-product' );
			add_option( 'woopoints-display-product', !empty( $_POST['woopoints-display-product'] )? '1' : '0' );
			delete_option( 'woopoints-display-cart' );
			add_option( 'woopoints-display-cart', !empty( $_POST['woopoints-display-cart'] )? '1' : '0' );

			$alert= __( 'Pages section updated.', 'woocommerce-points' );
		}

		$display_product = get_option( 'woopoints-display-product', false );
		$display_product_checked = '';
		if ( $display_product || ( $display_product !== '0' ) ) {
			$display_product_checked = 'checked';
		}
		$display_cart = get_option( 'woopoints-display-cart', false );
		$display_cart_checked = '';
		if ( $display_cart || ( $display_cart !== '0' ) ) {
			$display_cart_checked = 'checked';
		}

		if ( $alert != '' ) {
			echo '<div class="notice notice-success" >' . $alert . '</div>';
		}
		?>
		<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
			<h3><?php echo __( 'Pages', 'woocommerce-points' ); ?></h3>

			<p><?php echo __( 'You are free to create your own My Points page using our shortcodes available.', 'woocommerce-points' ); ?></p>
			<p><?php echo __( 'An example could be:', 'woocommerce-points' ); ?></p>
			
			<code>
			&lt;h3&gt;<?php echo __( 'Total points:', 'woocommerce-points' ); ?> [woopoints_user_points status="accepted"]&lt;/h3&gt; </br>
				&lt;p&gt;<?php echo __( 'Recent activity', 'woocommerce-points' ); ?>&lt;/p&gt; </br>
				&lt;p&gt;[woopoints_user_points_details]&lt;/p&gt;
			</code>
			
			<h4><?php echo __( 'Shortcodes available.', 'woocommerce-points' ); ?></h4>
			<p><?php echo __( 'You can use these shortcodes in your pages for create My Points customer page.', 'woocommerce-points' ); ?></p>
			<p><i><b>[woopoints_user_points]</b></i> <?php echo __( 'Display the total user points.', 'woocommerce-points' ); ?></p>
			<p><i><b>[woopoints_user_points_details]</b></i> <?php echo __( 'Display a table with the user points detail.', 'woocommerce-points' ); ?></p>
		</div>
		<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
			<form method="post" action="">
				<input type="checkbox" name="woopoints-display-product" value="1" <?php echo $display_product_checked; ?> > <?php echo __( 'Display the points on the product page.', 'woocommerce-points' ); ?><br>
				<input type="checkbox" name="woopoints-display-cart" value="1" <?php echo $display_cart_checked; ?> > <?php echo __( 'Display the points on the cart page.', 'woocommerce-points' ); ?><br>

				<div class="woopoints-admin-line">
					<?php submit_button( __( 'Save', 'woocommerce-points' ) ); ?>
				</div>

				<?php settings_fields( 'woopoints-settings' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Dashboard Notifications section.
	 */
	public static function woopoints_admin_notifications_section () {

		$alert = '';
		if ( isset( $_POST['submit'] ) ) {
			update_option( 'woopoints-notif_subject', sanitize_text_field( $_POST['points_notif_subject'] ) );
			update_option( 'woopoints-notif_content', sanitize_textarea_field( $_POST['points_notif_content'] ) );

			delete_option( 'woopoints-notif_enable' );
			add_option( 'woopoints-notif_enable', !empty( $_POST['points_notif_enable'] )? '1' : '0' );

			$alert= __( 'Notifications updated.', 'woocommerce-points' );
		}

		if ( $alert != '' ) {
			echo '<div class="notice notice-success" >' . $alert . '</div>';
		}

		?>
			<h2><?php echo __( 'Notifications', 'woocommerce-points' ); ?></h2>
			<hr>
				<form method="post" action="">

					<div class="wrap" style="border: 1px solid #ccc; padding:10px;">
					<h3><?php echo __( 'Notifications', 'woocommerce-points' ); ?></h3>
					<div class="woopoints-admin-line">
						<?php 
						$notif_enable = '';
						if ( get_option('woopoints-notif_enable', '1') == '1' ) {
							$notif_enable = 'checked';
						}
						?>
						<input type="checkbox" name="points_notif_enable" value="1" <?php echo $notif_enable; ?> /> <?php echo __( 'Enable user notifications:', 'woocommerce-points' ); ?>
					</div>

					<div class="woopoints-admin-line">
						<div class="woopoints-admin-label">
							<?php echo __( 'Email subject', 'woocommerce-points' ); ?>
						</div>
						<div class="woopoints-admin-value">
							<?php 
							$label = get_option('woopoints-notif_subject', 'Your Points Notifications' );
							?>
							<input type="text" name="points_notif_subject" value="<?php echo $label; ?>" class="regular-text" />
						</div>
					</div>

					<div class="woopoints-admin-line">
						<div class="woopoints-admin-label">
							<?php echo __( 'Email content', 'woocommerce-points' ); ?>
						</div>
						<div class="woopoints-admin-value">
							<?php 
							$label = esc_textarea( get_option('woopoints-notif_content', 'You have received [num_points] [points_label].' ) );
							?>
							<textarea name="points_notif_content" class="widefat" rows="8" cols="20" ><?php echo $label; ?></textarea>
							<br>
							<p class="description"><?php echo __( 'You can use these tokens:', 'woocommerce-points' );?> [num_points], [points_label], [ref_id], [description], [status], [username]</p>
						</div>
					</div>

					<div class="woopoints-admin-line">
						<?php submit_button( __( 'Save', 'woocommerce-points' ) ); ?>
					</div>

					<?php settings_fields( 'woopoints-settings' ); ?>

			</form>
		<?php
	}

	public static function woopoints_admin_points_edit( $point_id = null ) {
		global $wpdb;

		$output = '';
		if ( !current_user_can( 'administrator' ) ) {
			wp_die( __( 'Access denied.', 'woocommerce-points' ) );
		}

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$cancel_url  = remove_query_arg( 'point_id', remove_query_arg( 'action', $current_url ) );
		$current_url = remove_query_arg( 'point_id', $current_url );
		$current_url = remove_query_arg( 'action', $current_url );

		$saved = false;  // temporal

		$user_id = 0;
		if ( $point_id !== null ) {
			$points = WooPoints::get_point( $point_id );

			if ( $points !== null ) {
				$user_id = $points->user_id;
				/*
				$user = get_user_by( 'ID', $points->user_id );
				if ( $user ) {
					$username = $user->user_login;
				} else {
					$username = '';
				}
				*/
				$num_points = $points->points;
				$description = $points->description;
				$datetime = $points->datetime;
				$ref_id = $points->ref_id;
				$status = $points->status;
			} 
		} else {
			$user_id = '';
			$num_points = 0;
			$description = '';
			$datetime = '';
			$ref_id = '';
			$status = WOO_POINTS_STATUS_ACCEPTED;
		}

		$output .= '<div class="woopoints">';
		$output .= '<h2>';
		if ( empty( $point_id ) ) {
			$output .= __( 'New Points', 'woocommerce-points' );
		} else {
			$output .= __( 'Edit Points', 'woocommerce-points' );
		}
		$output .= '</h2>';

		$output .= '<form id="points" action="' . $current_url . '" method="post">';
		$output .= '<div>';

		if ( $point_id ) {
			$output .= sprintf( '<input type="hidden" name="point_id" value="%d" />', intval( $point_id ) );
		}

		$output .= '<input type="hidden" name="action" value="edit" />';

		$output .= '<div class="woopoints_row_field">';
		$output .= '<div class="title">' . __( 'Username', 'woocommerce-points' ) . '</div>';
		$output .= wp_dropdown_users(array('echo' => 0, 'name' => 'user_id', 'selected' => $user_id, 'show_option_none' => 'Select a user'));
		$output .= '</div>';

		

		$output .= '<div class="woopoints_row_field">';
		$output .= '<div class="title">' . __( 'Date & Time', 'woocommerce-points' ) . '</div>';
		$output .= sprintf( '<input type="datetime-local" name="datetime" value="%s" id="datetimepicker__" />', esc_attr( $datetime ) );
		$output .= '</div>';

		$output .= '<div class="woopoints_row_field">';
		$output .= '<div class="title">' . __( 'Description', 'woocommerce-points' ) . '</div>';
		$output .= '<textarea name="description">';
		$output .= stripslashes( $description );
		$output .= '</textarea>';
		$output .= '</div>';

		$output .= '<div class="woopoints_row_field">';
		$output .= '<div class="title">' . __( 'Ref. ID', 'woocommerce-points' ) . '</div>';
		$output .= sprintf( '<input type="text" name="ref_id" value="%s" />', esc_attr( $ref_id ) );
		$output .= '</div>';

		$output .= '<div class="woopoints_row_field">';
		$output .= '<div class="title">' . __( 'Points', 'woocommerce-points' ) . '</div>';
		//$output .= sprintf( '<input type="text" name="points" value="%s" />', esc_attr( $num_points ) );
		$output .= sprintf( '<input type="number" min="0.0" name="points" value="%s" step="any" />', esc_attr( $num_points ) );
		$output .= '</div>';

		$status_descriptions = array(
				WOO_POINTS_STATUS_ACCEPTED => __( 'Accepted', 'woocommerce-points' ),
				WOO_POINTS_STATUS_PENDING  => __( 'Pending', 'woocommerce-points' ),
				WOO_POINTS_STATUS_REJECTED => __( 'Rejected', 'woocommerce-points' ),
				WOO_POINTS_STATUS_PAID     => __( 'Paid', 'woocommerce-points' ),
		);
		$output .= '<div class="woopoints_row_field">';
		$output .= '<div class="title">' . __( 'Status', 'woocommerce-points' ) . '</div>';
		$output .= ' ';
		$output .= '<select name="status">';
		foreach ( $status_descriptions as $key => $label ) {
			$selected = $key == $status ? ' selected="selected" ' : '';
			$output .= '<option ' . $selected . ' value="' . esc_attr( $key ) . '">' . $label . '</option>';
		}
		$output .= '</select>';
		$output .= '</div>';

		$output .= '<div class="woopoints_row_field">';
		$output .= wp_nonce_field( 'save', 'woopoints-nonce', true, false );

		$output .= sprintf( '<input class="button" type="submit" name="save" value="%s"/>', __( 'Save', 'woocommerce-points' ) );
		$output .= ' ';
		$output .= sprintf( '<a class="button" href="%s">%s</a>', $cancel_url, $saved ? __( 'Back', 'woocommerce-points' ) : __( 'Cancel', 'woocommerce-points' ) );
		$output .= '</div>';

		$output .= '</div>';
		$output .= '</form>';

		$output .= '</div>';

		echo $output;
	}

}