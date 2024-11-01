<?php
/**
* class-points-shortcodes.php
*
* Copyright (c) 2010-2012 "eggemplo" Antonio Blanco Oliva www.eggemplo.com
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
* @author Antonio Blanco Oliva
* @package points
* @since points 1.0
*/
class WooPoints_Shortcodes {

	/**
	 * Add shortcodes.
	 */
	public static function init() {

		add_shortcode( 'woopoints_users_list', array( __CLASS__, 'woopoints_users_list' ) );
		add_shortcode( 'woopoints_user_points', array( __CLASS__, 'woopoints_user_points' ) );
		add_shortcode( 'woopoints_user_points_details', array( __CLASS__, 'woopoints_user_points_details' ) );
		add_shortcode( 'woopoints_request_coupon', array( __CLASS__, 'woopoints_request_coupon' ) );

	}

	public static function woopoints_users_list ( $atts, $content = null ) {
		$options = shortcode_atts(
				array(
						'limit'  => 10,
						'order_by' => 'points',
						'order' => 'DESC'
				),
				$atts
		);
		extract( $options );
		$output = '';

		$pointsusers = WooPoints::get_users();

		if ( sizeof( $pointsusers )>0 ) {
			foreach ( $pointsusers as $pointsuser ) {
				$total = WooPoints::get_user_total_points( $pointsuser );
				$output .='<div class="woopoints-user">';
				$output .= '<span class="woopoints-user-username">';
				$output .= get_user_meta ( $pointsuser, 'nickname', true );
				$output .= ':</span>';
				$output .= '<span class="woopoints-user-points">';
				$output .= ' ' . $total . ' ' . get_option('woopoints-points_label', WOO_POINTS_DEFAULT_POINTS_LABEL);
				$output .= '</span>';
				$output .= '</div>';
			}
		} else {
			$output .= '<p>' . __( 'No users', 'woocommerce-points' ) . '</p>';
		}

		return $output;
	}

	/**
	 * Shortcode. Display the total user points.
	 * @param array $atts
	 * 		id: User id. If not set, then the current user is used.
	 * 		status: The points status. If not set, then Accepted and Paid are counted.
	 * @param string $content
	 */
	public static function woopoints_user_points ( $atts, $content = null ) {
		$output = '';

		$options = shortcode_atts(
				array(
						'user_id'     => '',
						'status'      => null,
						'label'       => true
				),
				$atts
		);
		extract( $options );

		if ( $user_id === '' ) {
			$user_id = get_current_user_id();
		}

		if ( ( strtoupper( $label ) === 'FALSE' ) || ( $label == 0 ) ) {
			$label = false;
		}

		if ( $user_id !== 0 ) {
			// If not status is indicated, then add accepted and paid
			if ( $status == null ) {
				$points = WooPoints::get_user_total_points( $user_id, WOO_POINTS_STATUS_ACCEPTED );
				$points += WooPoints::get_user_total_points( $user_id, WOO_POINTS_STATUS_PAID );
			} else {
				$points = WooPoints::get_user_total_points( $user_id, $status );
			}
			$output .= $points;
			if ( $label ) {
				$output .= ' ' . self::get_label( $points );
			}
		}

		return $output;
	}

	/**
	 * Shortcode. Display the user points details.
	 * @param array $atts
	 * 		id: User id. If not set, then the current user is used.
	 * 		status: The points status.
	 * @param string $content
	 */
	public static function woopoints_user_points_details ( $atts, $content = null ) {
		$options = shortcode_atts(
				array(
						'user_id'         => '',
						'items_per_page'  => 10,
						'order_by'        => 'point_id',
						'order'           => 'DESC',
						'description'     => true
				),
				$atts
				);
		extract( $options );

		if ( is_string( $description ) && ( ( $description == '0' ) || ( strtolower( $description ) == 'false' ) ) ) {
			$description = false;
		}

		$desc_th = '';
		if ( $description ) {
			$desc_th = 	'<th>' . __( 'Description', 'woocommerce-points' ) . '</th>';
		}

		$user_id = get_current_user_id();
		$points = WooPoints::get_points_by_user( $user_id, null, $order_by, $order, OBJECT );

		// Pagination
		$total           = sizeof( $points );
		$page            = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
		$offset          = ( $page * $items_per_page ) - $items_per_page;
		$totalPage       = ceil($total / $items_per_page);

		$points = WooPoints::get_points_by_user( $user_id, $items_per_page, $order_by, $order, OBJECT, $offset );

		$output = '<table class="woopoints_user_points_table">' .
				'<tr>' .
				'<th>' . __( 'Datetime', 'woocommerce-points' ) . '</th>' .
				'<th>' . ucfirst( self::get_label( 100 ) ) . '</th>' .
				'<th>' . __( 'Ref.', 'woocommerce-points' ) . '</th>' .
				'<th>' . __( 'Status', 'woocommerce-points' ) . '</th>' .
				$desc_th .
				'</tr>';

				if ( $user_id !== 0 ) {
					if ( sizeof( $points ) > 0 ) {
						foreach ( $points as $point ) {
							$desc_td = '';
							if ( $description ) {
								$desc_td = 	'<td>' . $point->description . '</td>';
							}
							$output .= '<tr>' .
									'<td>' . $point->datetime . '</td>' .
									'<td>' . $point->points . '</td>' .
									'<td>' . $point->ref_id . '</td>' .
									'<td>' . $point->status . '</td>' .
									$desc_td .
									'</tr>';
						}
					}
				}
		
		$output .= '</table>';

		// Pagination
		if($totalPage > 1){
			$customPagHTML = '<div><span>' . __( 'Page', 'woocommerce-points' ) . ' '. $page .' '. __( 'of', 'woocommerce-points' ) . ' ' . $totalPage . '</span><br>' . paginate_links( array(
					'base' => add_query_arg( 'cpage', '%#%' ),
					'format' => '',
					'prev_text' => __('&laquo;'),
					'next_text' => __('&raquo;'),
					'total' => $totalPage,
					'current' => $page
			)).'</div>';
			$output .= $customPagHTML;
		}

		return $output;
	}

	/**
	 * Display a form for request a coupon.
	 * @param array $atts
	 * @param string $content
	 */
	public static function woopoints_request_coupon ( $atts, $content = null ) {
		global $wpdb;

		$output = '';
		// submit?
		$coupon_title = '';
		if ( isset( $_POST['request_coupon'] ) ) {
			if ( !isset( $_POST['woopoints-request_coupon'] ) || ! wp_verify_nonce( $_POST['woopoints-request_coupon'], 'woopoints-nonce' ) ) {
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
					if ( $user_id ) {
						$coupon_title = WooPoints_Woocommerce::create_coupon( $user_id );
						if ( $coupon_title !== '' ) {
							// pay
							WooPoints::update_user_points($user_id, array( 'status' => WOO_POINTS_STATUS_PAID ) );
							$output .= '<p>';
							$output .= sprintf( __( 'Your <strong>%s</strong> coupon is now available.', 'woocommerce-points' ), $coupon_title );
							$output .= '</p>';
						} else {
							$output .= '<p>';
							$output .= __( 'Something went wrong.', 'woocommerce-points' );
							$output .= '</p>';
						}
					}
				}
			}
		} else {
			// shortcode
			$options = shortcode_atts(
				array(
					'min'  => 0
				),
				$atts
			);
			extract( $options );

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();

				$_coupons = $wpdb->get_results(
						"SELECT DISTINCT ID, post_title FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id WHERE p.post_type = 'shop_coupon' AND p.post_status = 'publish' AND pm.meta_key = 'woopoints_user_id' AND pm.meta_value = '$user_id' "
						);
				if ( is_array( $_coupons ) && sizeof( $_coupons ) > 0 ) {
					$output .= '<p>';
					$output .= 'You have available these coupons: ';
					foreach ( $_coupons as $_coupon ) {
						$output .= $_coupon->post_title;
						$output .= ',';
					}
					$output = substr( $output, 0, -1 ); // remove last ,
					$output .= '</p>';
				}

				$user_points = WooPoints::get_user_total_points( $user_id, WOO_POINTS_STATUS_ACCEPTED );
				if ( $user_points >= $min ) {
					$output .= '<form action="' . get_the_permalink() .'" method="post">';
					$output .= '<input type="submit" class="button" name="request_coupon" value="' . __( 'Request my coupon', 'woocommerce-points' ) . '" />';
					$output .= wp_nonce_field( 'woopoints-request_coupon', 'woopoints-nonce', true, false );
					$output .= '</form>';
				} else {
					$output .= '<p>';
					$output .= sprintf(
								__( 'When you reach the minimum of %d %s you can request your discount coupon.', 'woocommerce-points' ),
								$min,
								get_option('woopoints-points_label', WOO_POINTS_DEFAULT_POINTS_LABEL)
							);
					$output .= '</p>';
				}
			}
		}

		return $output;
	}

	/**
	 * Get the correct label according the number of points (singular or plural)
	 * @param int $points
	 */
	public static function get_label ( $points ) {
		$output = '';
		if ( intval( $points ) > 1 ) {
			$output .= get_option( 'woopoints-points_label', 'points' );
		} else {
			$output .= get_option( 'woopoints-points_singular_label', 'point' );
		}
		return $output;
	}

}
WooPoints_Shortcodes::init();
