<?php
/**
 * class-woo-points-database.php
 *
 * Copyright (c) 2011,2012 Antonio Blanco http://www.eggemplo.com
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
 *
 */

class WooPoints_Database {

	public static $prefix = 'woo_points_';

	public static function get_table( $table ) {
		global $wpdb;
		$result = '';
		switch ( $table ) {
			case 'users':
				$result = $wpdb->prefix . self::$prefix . 'users';
				break;
		}
		return $result;
	}

}