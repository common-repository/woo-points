<?php
/**
 * class-points-widget.php
 * 
 * Copyright (c) 2010, 2011 "eggemplo" Antonio Blanco www.eggemplo.com
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
 * @since points 1.0 
 */

/**
 * Points widget.
 */
class WooPoints_Widget extends WP_Widget {

	/**
	 * Creates a points widget.
	 */
	function __construct() {
		parent::__construct( false, $name = 'WooPoints - User Points' );
		add_action( 'wp_print_styles', array( __CLASS__, '_print_styles' ) );
	}

	/**
	 * Enqueues required stylesheets.
	 */
	public static function _print_styles() {
		wp_enqueue_style( 'woopoints', WOO_POINTS_PLUGIN_URL . 'css/points.css', array() );
	}

	/**
	 * Widget output
	 * 
	 * @see WP_Widget::widget()
	 */
	function widget( $args, $instance ) {

		extract( $args );
		$title = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$widget_id = $args['widget_id'];
		echo $before_widget;
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$limit = $instance['limit'];
		$order = $instance['order'];
		$order_by = 'points';

		$pointsusers = WooPoints::get_users_total_points( $limit, $order_by, $order, WOO_POINTS_STATUS_ACCEPTED );

		if ( sizeof( $pointsusers )>0 ) {
			foreach ( $pointsusers as $pointsuser ) {
				echo '<div class="woopoints-user">';
				echo '<span class="woopoints-user-username">';
				echo get_user_meta ( $pointsuser->user_id, 'nickname', true );
				echo ':</span>';
				echo ' ';
				echo '<span class="woopoints-user-points">';
				echo $pointsuser->total . ' ' . get_option('woopoints-points_label', WOO_POINTS_DEFAULT_POINTS_LABEL);
				echo '</span>';
				echo '</div>';
			}
		} else {
			echo '<p>' . __('No users', 'woocommerce-points' ) . '</p>';
		}

		echo $after_widget;
	}

	/**
	 * Save widget options
	 * 
	 * @see WP_Widget::update()
	 */
	function update( $new_instance, $old_instance ) {
		$settings = $old_instance;

		// title
		if ( !empty( $new_instance['title'] ) ) {
			$settings['title'] = strip_tags( $new_instance['title'] );
		} else {
			unset( $settings['title'] );
		}

		// limit
		if ( !empty( $new_instance['limit'] ) ) {
			$settings['limit'] = strip_tags( $new_instance['limit'] );
		} else {
			unset( $settings['limit'] );
		}

		// order
		if ( !empty( $new_instance['order'] ) ) {
			$settings['order'] = strip_tags( $new_instance['order'] );
		} else {
			unset( $settings['order'] );
		}

		return $settings;
	}

	/**
	 * Output admin widget options form
	 * 
	 * @see WP_Widget::form()
	 */
	function form( $instance ) {

		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'woocommerce-points' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
		$limit = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : '5';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of users to display:', 'woocommerce-points' ); ?></label> 
			<input class="" size="3" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $limit; ?>" />
		</p>
		<?php
		$order = isset( $instance['order'] ) ? esc_attr( $instance['order'] ) : 'DESC';
		$selectdesc = ($order == 'DESC')?"selected":"";
		$selectasc = ($order == 'ASC')?"selected":"";
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Order:', 'woocommerce-points' ); ?></label> 
			<select class="widefat" id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>" >
				<option value="DESC" <?php echo $selectdesc;?> ><?php _e( 'Desc', 'woocommerce-points' );?></option>
				<option value="ASC" <?php echo $selectasc;?> ><?php _e( 'Asc', 'woocommerce-points' );?></option>
			</select>
		</p>
		<?php

	}
}
?>