<?php
/**
 * class-points.php
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
 * Points class
 */
class WooPoints {

	public static function get_points_by_user ( $user_id, $limit = null, $order_by = null, $order = null, $output = OBJECT, $offset = 0 ) {
		global $wpdb;

		$limit_str = '';
		if ( isset( $limit ) && ( $limit !== null ) ) {
			$limit_str = ' LIMIT ' . $offset . ' ,' . $limit;
		}
		$order_by_str = '';
		if ( isset( $order_by ) && ( $order_by !== null ) ) {
			$order_by_str = ' ORDER BY ' . $order_by;
		}
		$order_str = '';
		if ( isset( $order ) && ( $order !== null ) ) {
			$order_str = ' ' . $order;
		}

		$result = $wpdb->get_results('SELECT * FROM ' . WooPoints_Database::get_table( 'users' ) . " WHERE user_id = '$user_id'" . $order_by_str . $order_str . $limit_str, $output );

		return $result;

	}

	public static function get_points_by_ref_id ( $ref_id, $output = OBJECT ) {
		global $wpdb;

		$result = $wpdb->get_results('SELECT * FROM ' . WooPoints_Database::get_table( 'users' ) . " WHERE ref_id = '$ref_id'", $output );

		return $result;

	}

	public static function get_user_total_points ( $user_id, $status = null ) {
		global $wpdb;

		$result = 0;

		$where_status = '';
		if ( $status !== null ) {
			$where_status = " AND status = '" . $status . "'";
		}
		$points = $wpdb->get_row( 'SELECT SUM(points) as total FROM ' . WooPoints_Database::get_table( 'users' ) . " WHERE user_id = '$user_id' " . $where_status);

		if ( $points ) {
			$result = $points->total;
		}
		return $result;
	}

	/**
	 * Get the users who have points with their total points.
	 * @param int $limit
	 * @param string $order_by
	 * @param string $order
	 * @param string $status If not set, then WOO_POINTS_STATUS_ACCEPTED AND WOO_POINTS_STATUS_PAID are used.
	 */
	public static function get_users_total_points (  $limit = null, $order_by = null, $order = null, $status = null ) {
		global $wpdb;

		$where_status = '';
		if ( $status !== null ) {
			$where_status = " WHERE status = '" . $status . "'";
		} else {
			$where_status = " WHERE status = '" . WOO_POINTS_STATUS_ACCEPTED . "' OR status = '" . WOO_POINTS_STATUS_PAID . "'";
		}
		$points = $wpdb->get_results( 'SELECT SUM(points) as total, user_id FROM ' . WooPoints_Database::get_table( 'users' ) . $where_status . ' GROUP BY user_id' );

		return $points;
	}

	/**
	 * Get users id who have some points
	 * @param  $user_id
	 * @return array
	 */
	public static function get_users() {
		global $wpdb;

		$users_id = $wpdb->get_results( 'SELECT user_id FROM ' . WooPoints_Database::get_table( 'users' ) . ' GROUP BY user_id' );

		$result = array();
		if ( sizeof( $users_id ) > 0 ) {
			foreach ( $users_id as $user_id ) {
				$result[] = $user_id->user_id;
			}
		}
		return $result;
	}

	public static function set_points ( $points, $user_id, $info = array() ) {
		global $wpdb;

		$values = array( 'points' => $points );

		if ( isset( $info['datetime'] ) && ( $info['datetime'] !== '' ) ) {
			$values['datetime'] = $info['datetime'];
		} else {
			$values['datetime'] = date( 'Y-m-d H:i:s', time() );
		}
		if ( isset( $info['description'] ) ) {
			$values['description'] = $info['description'];
		}
		if ( isset( $info['status'] ) ) {
			$values['status'] = $info['status'];
		}
		if ( isset( $info['type'] ) ) {
			$values['type'] = $info['type'];
		}
		if ( isset( $info['data'] ) ) {
			$values['data'] = $info['data']; // yet serialized
		}
		if ( isset( $info['ip'] ) ) {
			$values['ip'] = $info['ip'];
		}
		if ( isset( $info['ipv6'] ) ) {
			$values['ipv6'] = $info['ipv6'];
		}
		if ( isset( $info['ref_id'] ) ) {
			$values['ref_id'] = $info['ref_id'];
		}
		$values['user_id'] = $user_id;

		$rows_affected = $wpdb->insert( WooPoints_Database::get_table( 'users' ), $values );

		if ( $rows_affected > 0 ) {
			$last_point_id = $wpdb->insert_id;
			apply_filters( 'woopoints_added_points', $last_point_id );
		}

		return $rows_affected;
	}

	/**
	 * Get a points list.
	 * @param int $limit
	 * @param string $order_by
	 * @param string $order
	 * @return Ambigous <mixed, NULL, multitype:, multitype:multitype: , multitype:Ambigous <multitype:, NULL> >
	 */
	public static function get_points ( $limit = null, $order_by = null, $order = null, $output = OBJECT ) {
		global $wpdb;
		
		$where_str = " WHERE status != '" . WOO_POINTS_STATUS_REMOVED . "'";
		
		$limit_str = '';
		if ( isset( $limit ) && ( $limit !== null ) ) {
			$limit_str = ' LIMIT 0 ,' . $limit;
		}
		$order_by_str = '';
		if ( isset( $order_by ) && ( $order_by !== null ) ) {
			$order_by_str = ' ORDER BY ' . $order_by;
		}
		$order_str = '';
		if ( isset( $order ) && ( $order !== null ) ) {
			$order_str = ' ' . $order;
		}

		$result = $wpdb->get_results( 'SELECT * FROM ' . WooPoints_Database::get_table( 'users' ) . $where_str . $order_by_str . $order_str . $limit_str, $output );

		return $result;
	}

	public static function get_point ( $point_id = null ) {
		global $wpdb;

		$result = null;

		if ( isset( $point_id ) && ( $point_id !== null ) ) {

			$points_id_str = ' WHERE point_id = ' . (int)$point_id;
			$result = $wpdb->get_row( 'SELECT * FROM ' . WooPoints_Database::get_table( 'users' ) . $points_id_str );
		}

		return $result;
	}

	public static function remove_points( $point_id ) {
		global $wpdb;

		$values = array();
		$values['status'] = WOO_POINTS_STATUS_REMOVED;

		$rows_affected = $wpdb->update( WooPoints_Database::get_table( 'users' ), $values , array( 'point_id' => $point_id ) );

		if ( !$rows_affected ) {
			$rows_affected = null;
		}
		return $rows_affected;
	}

	public static function update_points( $point_id, $info = array() ) {
		global $wpdb;

		$values = array();

		if ( isset( $info['user_id'] ) ) {
			$values['user_id'] = $info['user_id'];
		}
		if ( isset( $info['datetime'] ) ) {
			$values['datetime'] = $info['datetime'];
		}
		if ( isset( $info['description'] ) ) {
			$values['description'] = $info['description'];
		}
		if ( isset( $info['status'] ) ) {
			$values['status'] = $info['status'];
		}
		if ( isset( $info['points'] ) ) {
			$values['points'] = $info['points'];
		}
		if ( isset( $info['type'] ) ) {
			$values['type'] = $info['type'];
		}
		if ( isset( $info['data'] ) ) {
			$values['data'] = $info['data']; // yet serialized
		}
		if ( isset( $info['ip'] ) ) {
			$values['ip'] = $info['ip'];
		}
		if ( isset( $info['ipv6'] ) ) {
			$values['ipv6'] = $info['ipv6'];
		}
		if ( isset( $info['ref_id'] ) ) {
			$values['ref_id'] = $info['ref_id'];
		}

		$rows_affected = $wpdb->update( WooPoints_Database::get_table( 'users' ), $values , array( 'point_id' => $point_id ) );

		if ( !$rows_affected ) { // insert
			$rows_affected = null;
		}
		return $rows_affected;
	}

	/**
	 * Update all the points of a user.
	 * @param int $user_id
	 * @param array $info with the info to update.
	 */
	public static function update_user_points( $user_id, $info = array() ) {
		global $wpdb;
	
		$values = array();
	
		if ( isset( $info['datetime'] ) ) {
			$values['datetime'] = $info['datetime'];
		}
		if ( isset( $info['description'] ) ) {
			$values['description'] = $info['description'];
		}
		if ( isset( $info['status'] ) ) {
			$values['status'] = $info['status'];
		}
		if ( isset( $info['type'] ) ) {
			$values['type'] = $info['type'];
		}
		if ( isset( $info['data'] ) ) {
			$values['data'] = $info['data']; // yet serialized
		}
		if ( isset( $info['ip'] ) ) {
			$values['ip'] = $info['ip'];
		}
		if ( isset( $info['ipv6'] ) ) {
			$values['ipv6'] = $info['ipv6'];
		}
		if ( isset( $info['ref_id'] ) ) {
			$values['ref_id'] = $info['ref_id'];
		}
	
		$rows_affected = $wpdb->update( WooPoints_Database::get_table( 'users' ), $values , array( 'user_id' => $user_id ) );
	
		if ( !$rows_affected ) { // insert
			$rows_affected = null;
		}
		return $rows_affected;
	}

}
