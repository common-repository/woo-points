<?php
/**
 * class-woo-points-woocommerce.php
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
 * @package woopoints
 * @since points 1.0.0
 */

/**
 * Woo Points Woocommerce class
 */
class WooPoints_Woocommerce {
	
	public static function init() {
		add_action ( 'woocommerce_checkout_order_processed', array ( __CLASS__, 'woocommerce_checkout_order_processed' ) );

	}

	public static function woocommerce_checkout_order_processed( $order_id ) {

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$order_subtotal = null;
			$currency       = get_option( 'woocommerce_currency' );
	
			if ( function_exists( 'wc_get_order' ) ) {
				if ( $order = wc_get_order( $order_id ) ) {
					if ( method_exists( $order, 'get_subtotal' ) ) {
						$order_subtotal = $order->get_subtotal();
					}
					if ( method_exists( $order, 'get_total_discount' ) ) {
						$order_subtotal -= $order->get_total_discount(); // excluding tax
						if ( $order_subtotal < 0 ) {
							$order_subtotal = 0;
						}
					}
					if ( method_exists( $order, 'get_currency' ) ) {
						$currency = $order->get_currency();
					} else if ( method_exists( $order, 'get_order_currency' ) ) {
						$currency = $order->get_order_currency();
					}
				}
			}

			if ( $order_subtotal === null ) {
				$order_total        = get_post_meta( $order_id, '_order_total', true );
				$order_tax          = get_post_meta( $order_id, '_order_tax', true );
				$order_shipping     = get_post_meta( $order_id, '_order_shipping', true );
				$order_shipping_tax = get_post_meta( $order_id, '_order_shipping_tax', true );
				$order_subtotal     = $order_total - $order_tax - $order_shipping - $order_shipping_tax;
			}

			$round = get_option( 'woopoints-points_round', 'floor');
			$rate = get_option( 'woopoints-points_per_dollar', 0 );
			$status = get_option( 'woopoints-points_status', WOO_POINTS_STATUS_ACCEPTED );

			$points = bcmul( $order_subtotal, $rate, 2 );

			switch ( $round ) {
				case 'ceil' :
					$points = ceil( $points );
					break;
				case 'floor' :
				default :
					$points = floor( $points );
					break;
			}

			$data = array();
			$data['user_id'] = $user_id;
			$data['description'] = sprintf( __( 'Order %d', 'woocommerce-points' ), $order_id );
			$data['status'] = $status;
			$data['points'] = $points;
			$data['ref_id'] = $order_id;

			WooPoints::set_points( $points, $user_id, $data );
		}
	}

	public static function order_status_cancelled ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
	
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_REJECTED
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	public static function order_status_completed ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
		
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_ACCEPTED
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	public static function order_status_failed ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
	
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_PENDING
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	public static function order_status_on_hold ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
	
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_PENDING
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	public static function order_status_pending ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
	
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_PENDING
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	public static function order_status_processing ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
	
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_PENDING
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	public static function order_status_refunded ( $order_id ) {
		$points = WooPoints::get_points_by_ref_id ( $order_id );
	
		if ( sizeof( $points ) > 0 ) {
			foreach ( $points as $point ) {
				$data = array(
						'status' => WOO_POINTS_STATUS_REJECTED
				);
				WooPoints::update_points( $point->point_id, $data );
			}
		}
	}

	/**
	 * Create a couponfor the user_id
	 * @param int $user_id
	 * @return string|null $coupon_title
	 */
	public static function create_coupon ( $user_id ) {
		$result = null;
		$label = ucfirst( get_option('woopoints-points_label', 'points') );
		$coupon_title = $label . '_' . $user_id;

		$cnt_pre = '_';
		$cnt = 0;
		while ( get_page_by_title( $coupon_title, OBJECT, 'shop_coupon' ) ) {
			$coupon_title = $label . '_' . $user_id . $cnt_pre . $cnt;
			$cnt++;
		}
		if ( !get_page_by_title( $coupon_title, OBJECT, 'shop_coupon' ) ) {
			// calculate the amount
			$user_points = WooPoints::get_user_total_points( $user_id, WOO_POINTS_STATUS_ACCEPTED );
			$coupons_dollars = get_option('woopoints-coupons_dollars', 0);
			$coupon_amount = wc_format_decimal( ( $user_points * $coupons_dollars ) / 100 );

			$postarr = array(
					'post_author'  => 1,
					'post_content' => sprintf( __( '%s Coupons', 'woocommerce-points' ), $label ),
					'post_status'  => 'publish',
					'post_title'   => $coupon_title, // do no translation here, this is a coupon code
					'post_content' => sprintf( __( 'Generated from %d %s', 'woocommerce-points' ), $user_points, $label ),
					'post_type'    => 'shop_coupon'
			);

			if ( $coupon_id = wp_insert_post( $postarr ) ) {
				update_post_meta( $coupon_id, 'woopoints_user_id', $user_id );
				update_post_meta( $coupon_id, 'coupon_amount', $coupon_amount );
				update_post_meta( $coupon_id, 'discount_type', 'fixed_cart' );
				update_post_meta( $coupon_id, 'individual_use', 'no' );
				update_post_meta( $coupon_id, 'usage_limit', 1 );

				$result = $coupon_title;
			}
		}

		return $result;
	}
}

WooPoints_Woocommerce::init();
