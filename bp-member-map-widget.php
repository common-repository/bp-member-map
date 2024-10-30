<?php

function bp_map( $args = '' ) {
	return bp_get_member_map_js( array( 'latitude' => $args['lat'], 'longitude' => $args['lng'], 'zoom' => $args['zoom'], 'type' => $args['type'], 'content' => $args['content'], 'directions_to' => $args['directions_to'] ) );
}
add_shortcode( 'MAP', 'bp_map' );

class BP_Map_Widget extends WP_Widget {

	// constructor
	function bp_map_widget() {
		$widget_ops = array (
			'classname' => 'bp_map_widget',
			'description' => __( 'Add a map to your blog or site' )
		);
		$this->WP_Widget( 'module', __( 'Map', 'bp-member-map' ), $widget_ops );
	}

	// output the content of the widget
	function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;

		if ( $instance['title'] )
			echo $before_title . $instance['title'] . $after_title;

		bp_member_map_js( array( 'latitude' => $instance['lat'], 'longitude' => $instance['lng'], 'zoom' => $instance['zoom'], 'type' => $instance['type'], 'content' => $instance['content'], 'directions_to' => $instance['directions_to'] ) );

		echo $after_widget;
	}

	// process widget options to be saved
	function update( $new_instance, $old_instance ) {
		print_r( $old_instance );
		print_r( $new_instance );
		return $new_instance;
	}

	// output the options form on admin
	function form( $instance ) {
		global $wpdb;

		$title			= esc_attr( $instance['title'] );
		$lat			= esc_attr( $instance['lat'] );
		$lng			= esc_attr( $instance['lng'] );
		$zoom			= esc_attr( $instance['zoom'] );
		$type			= esc_attr( $instance['type'] );
		$content		= esc_attr( $instance['content'] );
		$directions_to	= esc_attr( $instance['directions_to'] );
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bp-member-map' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'lat' ); ?>"><?php _e( 'Latitude:', 'bp-member-map' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'lat' ); ?>" name="<?php echo $this->get_field_name( 'lat' ); ?>" type="text" value="<?php echo $lat; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'lng' ); ?>"><?php _e( 'Longitude:', 'bp-member-map' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'lng' ); ?>" name="<?php echo $this->get_field_name( 'lng' ); ?>" type="text" value="<?php echo $lng; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'zoom' ); ?>"><?php _e( 'Zoom Level: <small>(1-19)</small>', 'bp-member-map' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'zoom' ); ?>" name="<?php echo $this->get_field_name( 'zoom' ); ?>" type="text" value="<?php echo $zoom; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Map Type:<br /><small>(ROADMAP, SATELLITE, HYBRID, TERRAIN)</small>', 'bp-member-map' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name('type'); ?>" type="text" value="<?php echo $type; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'directions_to' ); ?>"><?php _e( 'Address for directions:', 'bp-member-map' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'directions_to' ); ?>" name="<?php echo $this->get_field_name( 'directions_to' ); ?>" type="text" value="<?php echo $directions_to; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'content' ); ?>"><?php _e( 'Info Bubble Content:', 'bp-member-map' ); ?></label>
			<textarea rows="7" class="widefat" id="<?php echo $this->get_field_id( 'content' ); ?>" name="<?php echo $this->get_field_name( 'content' ); ?>"><?php echo $content; ?></textarea>
		</p>
<?php
	}
}
// BP Map Widget
add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Map_Widget");' ) );

?>