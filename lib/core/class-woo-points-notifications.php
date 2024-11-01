<?php
/**
 * class-woo-points-notifications.php
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
 * @since points 1.3.0
 */

/**
 * Points class
 */
class WooPoints_Notifications {

	public static function init() {
		if ( get_option( 'woopoints-notif_enable', '0' ) == '1' ) {
			add_filter( 'woopoints_added_points', array( __CLASS__, 'woopoints_added_points' ), 10, 1 );
		}
	}

	public static function woopoints_added_points ( $points_id ) {
		$points = WooPoints::get_point( $points_id );
		$user_id = $points->user_id;
		$user = get_user_by( 'ID', $user_id );
		if ( $user ) {
			$subject = get_option( 'woopoints-notif_subject', __( 'Your Points Notifications', 'woocommerce-points' ) );
			$content = get_option( 'woopoints-notif_content', __( 'You have received [num_points] [points_label].', 'woocommerce-points' ) );

			// tags content
			$tags = array();
			$tags['num_points']   = $points->points;
			$tags['points_label'] = get_option( 'woopoints-points_label', 'points');
			$tags['ref_id']       = $points->ref_id;
			$tags['description']  = $points->description;
			$tags['status']       = $points->status;
			$tags['username']     = $user->user_login;

			$tags = apply_filters( 'woopoints_notif_content_tokens', $tags );

			foreach ( $tags as $key => $value ) {
				$subject = str_replace( '[' . $key . ']', $value, $subject );
				$content = str_replace( '[' . $key . ']', $value, $content );
			}
			@wp_mail( $user->user_email, $subject, $content );
		}
	}

}
WooPoints_Notifications::init();