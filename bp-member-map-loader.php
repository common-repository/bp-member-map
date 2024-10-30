<?php
/*
Plugin Name: BP Member Map
Plugin URI: http://buddypress.org
Description: Adds a member map to BuddyPress.
Author: John James Jacoby
Version: 1.1.6
Author URI: http://johnjamesjacoby.com
*/

/**
 * BP_Member_Map_Loader
 *
 * Loads plugin
 */
class BP_Member_Map_Loader {
	/**
	 * bp_member_map_constants()
	 *
	 * Default component constants that can be overridden or filtered
	 */
	function constants() {

		// Default slug for component
		if ( !defined( 'BP_MEMBER_MAP_SLUG' ) )
			define( 'BP_MEMBER_MAP_SLUG', apply_filters( 'bp_member_map_slug', 'map' ) );

		// Default latitude when no data is available
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_LATITUDE' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_LATITUDE', apply_filters( 'bp_member_map_default_latitude', '38' ) );

		// Default latitude when no data is available
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_LONGITUDE' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_LONGITUDE', apply_filters( 'bp_member_map_default_longitude', '-97' ) );

		// Default zoom height when no data is available (1-19)
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_ZOOM' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_ZOOM', apply_filters( 'bp_member_map_default_zoom', '2' ) );

		// Default map unit of measurement is kilometers
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_UNITS' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_UNITS', apply_filters( 'bp_member_map_default_units', 'kms' ) );

		// Default map type (ROADMAP, SATELLITE, HYBRID, TERRAIN)
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_TYPE' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_TYPE', apply_filters( 'bp_member_map_default_type', 'SATELLITE' ) );

		// Default DOM id of the map
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_OBJECT_ID' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_OBJECT_ID', apply_filters( 'bp_member_map_default_object_id', 'member-map' ) );

		// Default zoom height when no data is available
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_CONTENT' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_CONTENT', apply_filters( 'bp_member_map_default_content', __( 'No Location', 'bp-member-map' ) ) );

		// Default distance to search for
		if ( !defined( 'BP_MEMBER_MAP_DEFAULT_SEARCH' ) )
			define( 'BP_MEMBER_MAP_DEFAULT_SEARCH', apply_filters( 'bp_member_map_default_search_distance', 1000 ) );
	}

	/**
	 * bp_member_map_includes()
	 *
	 * Load required files
	 *
	 * @uses is_admin If in WordPress admin, load additional file
	 */
	function includes() {
		// Load the files
		require_once( WP_PLUGIN_DIR . '/bp-member-map/bp-member-map-classes.php' );
		require_once( WP_PLUGIN_DIR . '/bp-member-map/bp-member-map-templatetags.php' );

		// Quick admin check
		if ( is_admin() )
			require_once( WP_PLUGIN_DIR . '/bp-member-map/bp-member-map-admin.php' );
	}

	/**
	 * bp_member_map_init()
	 *
	 * Initialize plugin
	 *
	 * @uses BP_Member_Map_Loader::bp_member_map_constants()
	 * @uses BP_Member_Map_Loader::bp_member_map_includes()
	 * @uses BP_Member_Map::init()
	 * @uses BP_Member_Map_User::init()
	 * @uses BP_Member_Map_Admin::init()
	 * @uses is_admin()
	 * @uses do_action Calls custom action to allow external enhancement
	 */
	function init() {

		// Define all the constants
		BP_Member_Map_Loader::constants();

		// Include required files
		BP_Member_Map_Loader::includes();

		// Initialize site action hooks
		BP_Member_Map::init();

		// Initialize user action hooks
		BP_Member_Map_User::init();

		// Admin initialize
		if ( is_admin() )
			BP_Member_Map_Admin::init();

		/**
		 * For developers:
		 * ---------------------
		 * If you want to make sure your code is loaded after this plugin
		 * have your code load on this action
		 */
		do_action ( 'bp_member_map_init' );
	}
}

// Do the ditty
if ( defined( 'BP_VERSION' ) || did_action( 'bp_include' ) )
	BP_Member_Map_Loader::init();
else
	add_action( 'bp_include', array( 'BP_Member_Map_Loader', 'init' ) );

?>