<?php
/**
 * class-woo-points-woocommerce-views.php
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
 * @since points 1.4.0
 */

/**
 * Woo Points Woocommerce class
 */
class WooPoints_WoocommerceViews {
	
	public static function init() {

		// Product page
		if ( get_option( 'woopoints-display-product', 0 ) == 1 ) {
			add_action( 'woocommerce_single_product_summary', array ( __CLASS__, 'woocommerce_single_product_summary' ), 20 );
		}
		if ( get_option( 'woopoints-display-cart', 0 ) == 1 ) {
			// Cart page
			add_action( 'woocommerce_after_cart_table', array ( __CLASS__, 'woocommerce_after_cart_table' ), 20 );
		}
	}

	/**
	 * Display the points on the products page.
	 */
	public static function woocommerce_single_product_summary() {
		global $product;

		$price = $product->get_price();

		$round = get_option( 'woopoints-points_round', 'floor');
		$rate = get_option( 'woopoints-points_per_dollar', 0 );

		$points = bcmul( $price, $rate, 2 );

		switch ( $round ) {
			case 'ceil' :
				$points = ceil( $points );
				break;
			case 'floor' :
			default :
				$points = floor( $points );
				break;
		}

		echo '<p class="woopoints-alert">' . sprintf( __( 'Get %d %s.', 'woocommerce-points' ), $points, WooPoints_Shortcodes::get_label( $points ) ) . '</a>';
	}

	public static function woocommerce_after_cart_table() {
		global $woocommerce;

		$round = get_option( 'woopoints-points_round', 'floor');
		$rate = get_option( 'woopoints-points_per_dollar', 0 );

		$total = $woocommerce->cart->subtotal;

		$points = bcmul( $total, $rate, 2 );

		switch ( $round ) {
			case 'ceil' :
				$points = ceil( $points );
				break;
			case 'floor' :
			default :
				$points = floor( $points );
				break;
		}
		echo '<p class="woopoints-alert">' . sprintf( __( 'Get %d %s.', 'woocommerce-points' ), $points, WooPoints_Shortcodes::get_label( $points ) ) . '</a>';
	}

}

WooPoints_WoocommerceViews::init();
