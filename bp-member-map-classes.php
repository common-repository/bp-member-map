<?php

class BP_Member_Map {

	function init() {
		// Add root component
		add_action( 'bp_setup_root_components', array( 'BP_Member_Map', 'add_root_component' ) );

		// Setup globals
		add_action( 'bp_setup_globals', array( 'BP_Member_Map', 'setup_globals' ) );

		// wp_head
		//add_action( 'wp_head', array( 'BP_Member_Map', 'wp_head' ) );
	}

	/**
	 * activation()
	 *
	 * Placeholder for plugin activation sequence
	 */
	function activation() {	}

	/**
	 * deactivation()
	 *
	 * Placeholder for plugin deactivation sequence
	 */
	function deactivation() { }

	/**
	 * add_root_component()
	 *
	 * Placeholder for BuddyPress plugin root component
	 */
	function add_root_component() {
		bp_core_add_root_component( BP_MEMBER_MAP_SLUG );
	}

	/**
	 * setup_globals()
	 *
	 * Setup all plugin globals
	 *
	 * @global array $bp
	 * @global object $wpdb
	 */
	function setup_globals() {
		global $bp, $wpdb;

		// For internal identification
		$bp->member_map->id = 'member-map';
		$bp->member_map->slug = BP_MEMBER_MAP_SLUG;
		$bp->member_map->settings = BP_Member_Map::settings();

		// Set logged in user location
		if ( $bp->loggedin_user->id )
			$bp->loggedin_user->location = BP_Member_Map_User::location( $bp->loggedin_user->id );

		// Set displayed user location
		if ( $bp->displayed_user->id )
			$bp->displayed_user->location = BP_Member_Map_User::location( $bp->displayed_user->id );

		// If no user displayed, set as logged in user
		else
			$bp->displayed_user->location = $bp->loggedin_user->location;

		// Set first point on map to displayed_user location
		if ( $bp->displayed_user->id )
			$bp->member_map->markers[0] = $bp->displayed_user->location;

		// Register this in the active components array
		$bp->active_components[$bp->member_map->slug] = $bp->member_map->id;

		do_action( 'bp_member_map_setup_globals' );
	}

	/**
	 * settings()
	 *
	 * Loads up any saved settings and filters each default value
	 *
	 * @return array
	 */
	function settings() {
		$settings = get_site_option( 'bp_member_map_settings', false );

		// Set default values and allow them to be filtered
		$defaults = array(
			'location'			=> false,
			'units'				=> apply_filters( 'bp_member_map_default_units', BP_MEMBER_MAP_UNITS ),
			'default_latitude'	=> apply_filters( 'bp_member_map_default_latitude', BP_MEMBER_MAP_DEFAULT_LATITUDE ),
			'default_longitude'	=> apply_filters( 'bp_member_map_default_longitude', BP_MEMBER_MAP_DEFAULT_LONGITUDE ),
			'default_zoom'		=> apply_filters( 'bp_member_map_default_zoom', BP_MEMBER_MAP_DEFAULT_ZOOM ),
			'default_data'		=> apply_filters( 'bp_member_map_default_data', BP_MEMBER_MAP_DEFAULT_CONTENT ),
			'default_type'		=> apply_filters( 'bp_member_map_default_type', BP_MEMBER_MAP_DEFAULT_TYPE ),
			'object_id'			=> apply_filters( 'bp_member_map_default_object_id', BP_MEMBER_MAP_DEFAULT_OBJECT_ID ),
		);

		// Allow settings array to be filtered and return
		return apply_filters( 'bp_member_map_settings', wp_parse_args( $settings, $defaults ) );
	}

	/**
	 * location()
	 *
	 * Returns latitude and longitude based on $place provided
	 *
	 * @param string $place Any string submitted by the user
	 * @return array $location Latitude and longitude in array
	 */
	function location( $place ) {
		$whereurl = apply_filters( 'bp_member_map_get_location', stripslashes( urlencode ( $place ) ) );

		// Prepare JSON
		$provider = 'http://maps.google.com/maps/api/geocode/json';
		$provider = add_query_arg( 'address', $whereurl, $provider );
		$provider = add_query_arg( 'sensor', 'false', $provider );

		// Get JSON results
		if ( $result = wp_remote_retrieve_body( wp_remote_get( $provider ) ) ) {
			// Trim and decode results
			$result = trim( $result );
			$data = json_decode( $result );

			// Assign values
			$location['latitude'] = is_numeric( $data->results[0]->geometry->location->lat ) ? $data->results[0]->geometry->location->lat : bp_member_map_get_setting( 'default_latitude' );
			$location['longitude'] = is_numeric( $data->results[0]->geometry->location->lng ) ? $data->results[0]->geometry->location->lng : bp_member_map_get_setting( 'default_longitude' );
		} else {
			// Assign values
			$location['latitude'] = bp_member_map_get_setting( 'default_latitude' );
			$location['longitude'] = bp_member_map_get_setting( 'default_longitude' );
		}

		// Return results
		return apply_filters( 'bp_member_map_location', $location );
	}

	/**
	 * wp_head()
	 *
	 * Hooks into wp_head()
	 *
	 * @return Only return if no data to display
	 */
	function wp_head() {

		// Make sure we have map data to show
		if ( !$location = bp_member_map_get_marker() )
			return;

		// Load up the JS
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'bp_member_map_google', 'http://maps.google.com/maps/api/js?sensor=false' );

		bp_member_map_js( array(
			'latitude'		=> $location['latitude'],
			'longitude'		=> $location['longitude'],
			'zoom'			=> bp_member_map_get_setting( 'default_zoom' ),
			'type'			=> bp_member_map_get_setting( 'default_type' ),
			'content'		=> bp_member_map_get_setting( 'default_data' ),
			'object_id'		=> bp_member_map_get_setting( 'object_id' ),
			'directions_to'	=> false
		) );
	}
}
register_activation_hook( __FILE__, array( 'BP_Member_Map', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'BP_Member_Map', 'deactivation' ) );

// Plot points on a map based on xprofile location and usermeta
class BP_Member_Map_User {

	/**
	 * init()
	 *
	 * Hooks into BuddyPress XProfile actions
	 */
	function init() {
		// Add action to just before xprofile data is saved
		add_action( 'xprofile_data_before_save', array( 'BP_Member_Map_User', 'save_field' ), 10, 1 );

		// Add action to after xprofile data is saved
		add_action( 'xprofile_updated_profile', array( 'BP_Member_Map_User', 'update_profile' ) );
	}

	/**
	 * location()
	 *
	 * Gets location data from usermeta and returns in array
	 *
	 * @param integer $user_id
	 * @return array $location
	 */
	function location( $user_id = '' ) {

		// Attempt to gather latitude and longitude data from user meta
		$user_lat = get_usermeta( $user_id, 'bp_member_map_lat' );
		$user_lon = get_usermeta( $user_id, 'bp_member_map_lon' );

		// If meta, set it; if not, provide default
		$location['latitude']	= apply_filters( 'bp_member_map_latitude', $user_lat ? $user_lat : bp_member_map_get_setting( 'default_latitude' ) );
		$location['longitude']	= apply_filters( 'bp_member_map_longitude', $user_lon ? $user_lon : bp_member_map_get_setting( 'default_longitude' ) );

		return $location;
	}

	/**
	 * save_field()
	 *
	 * Updates usermeta when field is saved
	 *
	 * @param object $field
	 * @return object $field
	 */
	function save_field( $field ) {

		// If passed value isn't the location field then stop
		if ( $field->field_id != bp_member_map_get_setting( 'location' ) )
			return $field;

		// Field is good, load entire field
		$field_location = xprofile_get_field( bp_member_map_get_setting( 'location' ) );

		// Got the field, get the info.
		if ( $field_location )
			$place = apply_filters( 'bp_member_save_location', stripslashes( urlencode ( $field->value ) ) );

		// Get lat and lon from sanitized xprofile value
		$location = BP_Member_Map::location( $place );

		// If value is good, save it to user meta
		update_usermeta( $field->user_id, 'bp_member_map_lat', $location['latitude'] );
		update_usermeta( $field->user_id, 'bp_member_map_lon', $location['longitude'] );

		// We're all done, return the field for processing
		return $field;
	}

	/**
	 * update_profile()
	 *
	 * Makes sure live global is updated when profile is saved
	 *
	 * @global array $bp
	 */
	function update_profile() {
		global $bp;

		// Only update if editing your own profile
		if ( bp_is_my_profile() )
			$bp->loggedin_user->location = BP_Member_Map_User::location( $bp->loggedin_user->id );
	}
}

?>