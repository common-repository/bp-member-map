<?php

/**
 * BP_Member_Map_Admin
 *
 * Admin class for BP Member Map
 */
class BP_Member_Map_Admin {

	function init() {
		add_action( 'admin_menu', array( 'BP_Member_Map_Admin', 'add_buddypress_page' ) );
		add_action( 'admin_head', array( 'BP_Member_Map_Admin', 'admin_head' ) );
	}

	function admin_head() {
		global $profileuser;

		$user_location = BP_Member_Map_User::location( $profileuser->ID );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'bp_member_map_google', 'http://maps.google.com/maps/api/js?sensor=false', array( 'jquery' ) );

		bp_member_map_js( array( 'latitude' => $user_location['latitude'], 'longitude' => $user_location['longitude'] ) );
	}

	function add_buddypress_page() {
		if ( !bp_member_map_has_access() )
			return false;

		add_submenu_page( 'bp-general-settings', __( 'Map Settings', 'bp-member-map' ), __( 'Map Settings', 'bp-member-map' ), 'admin-options', 'bp-member-map-admin', array( 'BP_Member_Map_Admin', 'page' ) );
	}

	function page() {
		global $bp, $field;

		$fields = array();

		if ( bp_has_profile() ) {
			while ( bp_profile_groups() ) {
				bp_the_profile_group();
				while ( bp_profile_fields() ) {
					bp_the_profile_field();

					$one_field = array();
					$one_field['id'] = $field->id;
					$one_field['name'] = $field->name;

					$fields[] = $one_field;
				}
			}
		}

		if ( isset( $_POST[ 'submit' ] ) ) {
			check_admin_referer( 'BP_Member_Map_Admin' );

			bp_member_map_set_setting( 'type', strip_tags( $_POST['type'] ) );
			bp_member_map_set_setting( 'location', strip_tags( $_POST['location'] ) );
			bp_member_map_set_setting( 'units', strip_tags( $_POST['units'] ) );

			update_site_option( 'bp_member_map_settings', $bp->member_map->settings );
?>

			<div class="updated"><p><strong><?php _e( 'Settings saved.', 'bp-member-map' ); ?></strong></p></div>
<?php	} ?>

		<div class="wrap">
		    <h2><?php _e( 'Map Settings', 'bp-member-map' ) ?></h2>
			<form name="options" method="post" action="">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="location"><?php _e( "Field that represents user's location:", 'bp-member-map' ); ?></label></th>
							<td>
								<select id="location" name="location">
<?php foreach( $fields as $field ) { ?>
									<option value="<?php echo $field['id']; ?>"<?php if ( $field['id'] == bp_member_map_get_setting( 'location' ) ) echo " selected"; ?>><?php echo $field['name']; ?></option>
<?php } ?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="units"><?php _e( 'Unit of measurement:', 'bp-member-map' ); ?></label></th>
							<td>
								<select id="units" name="units">
									<option value="miles"<?php if ( 'miles' == bp_member_map_get_setting( 'units' ) ) echo " selected"; ?>><?php _e( "Miles", 'bp-member-map' ); ?></option>
									<option value="kms"<?php if ( 'kms' == bp_member_map_get_setting( 'units' ) ) echo " selected"; ?>><?php _e( "Kilometers", 'bp-member-map' ); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<?php wp_nonce_field( 'BP_Member_Map_Admin' ) ?>
					<input type="submit" name="submit" value="<?php esc_attr_e( 'Update Settings', 'bp-member-map' ) ?>" />
				</p>
			</form>
		</div>
<?php
	}

	function user_profile_map( $profileuser ) {
		$user_location = BP_Member_Map_User::location( $profileuser->ID );
?>
		<h3><?php _e( 'Location', 'bp-member-map' ); ?></h3>
		<table class="form-table">
<?php if ( bp_member_map_has_access() ) : ?>
			<tr valign="top">
				<th scope="row"><?php _e( 'Latitude', 'bp-member-map' ); ?></th>
				<td>
					<input type="text" class="regular-text" value="<?php echo $user_location['latitude']; ?>" id="bp-member-map-latitude" name="bp-member-map-latitude">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Longitude', 'bp-member-map' ); ?></th>
				<td>
					<input type="text" class="regular-text" value="<?php echo $user_location['longitude']; ?>" id="bp-member-map-longitude" name="bp-member-map-longitude">
				</td>
			</tr>
<?php endif; ?>
			<tr valign="top">
				<th scope="row"><?php _e( 'Map', 'bp-member-map' ); ?></th>
				<td>
					<?php bp_member_map_show(); ?>
				</td>
			</tr>
		</table>
<?php
	}
}
add_action( 'init', array( 'BP_Member_Map_Admin', 'init' ) );
add_action( 'edit_user_profile', array( 'BP_Member_Map_Admin', 'user_profile_map' ) );
add_action( 'show_user_profile', array( 'BP_Member_Map_Admin', 'user_profile_map' ) );
?>