<?php


function bp_member_map_js( $args = '' ) {
	echo bp_get_member_map_js( $args );
}
	function bp_get_member_map_js( $args = '' ) {

		// Defaults
		$defaults = array(
			'latitude'		=> apply_filters( 'bp_member_map_center_latitude', bp_member_map_get_setting( 'default_latitude' ) ) ,
			'longitude'		=> apply_filters( 'bp_member_map_center_longitude', bp_member_map_get_setting( 'default_longitude' ) ),
			'zoom'			=> bp_member_map_get_setting( 'default_zoom' ),
			'type'			=> apply_filters( 'bp_member_map_type', bp_member_map_get_setting( 'default_type' ) ),
			'content'		=> apply_filters( 'bp_member_map_content', bp_member_map_get_setting( 'default_data' ) ),
			'object_id'		=> apply_filters( 'bp_member_map_object_id', bp_member_map_get_setting( 'object_id' ) ),
			'directions_to'	=> false
		);

		// Parse defaults
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Apply filters to $zoom now that args are parsed
		$zoom = apply_filters( 'bp_member_map_zoom', $zoom, $latitude, $longitude );

		// Don't trust the content
		$content = str_replace( '&lt;', '<', $content );
		$content = str_replace( '&gt;', '>', $content );
		$content = mysql_escape_string( $content );

		// If directions are allowed
		if ( $directions_to )
			$directions_form = "
			<form method=\"get\" action=\"http://maps.google.com/maps\">
				<input type=\"hidden\" name=\"daddr\" value=\"" . $directionsto . "\" />
				<input type=\"text\" class=\"text\" name=\"saddr\" />
				<input type=\"submit\" class=\"submit\" value=\"Directions\" />
			</form>";

		// Compile the return value
		$result = "
			<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false'></script>
			<script type='text/javascript'>
				jQuery(document).ready(function(){
					function makeMap() {
						var latlng = new google.maps.LatLng(" . $latitude . ", " . $longitude . ")

						var myOptions = {
							zoom: " . $zoom . ",
							center: latlng,
							mapTypeControl: true,
							mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
							navigationControl: true,
							navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL},
							mapTypeId: google.maps.MapTypeId." . $type . "
						};

						
						var map = new google.maps.Map(document.getElementById('" . $object_id . "'), myOptions);
						var contentString = '<div class=\"infoWindow\">" . $content . $directions_to . "</div>';
						var infowindow = new google.maps.InfoWindow({ content: contentString });

						if ( map ) {
							var marker = new google.maps.Marker({
								position: latlng,
								map: map,
								title: ''
							});

							google.maps.event.addListener(marker, 'click', function() {
							  infowindow.open(map,marker);
							});
						}
					}

					if ( jQuery('#" . $object_id . "').length ) {
						window.onload = makeMap;
					}
				});
			</script>\n";

		// Return result
		return $result;

	}

function bp_member_map_show_img ( $args = '' ) {

	$user_location = BP_Member_Map_User::location( bp_displayed_user_id() );

	// Defaults
	$defaults = array(
		'latitude'      => $user_location['latitude'],
		'longitude'     => $user_location['longitude'],
		'zoom'          => bp_member_map_get_setting( 'default_zoom' ),
		'height'        => '200',
		'width'         => '200',
		'type'          => apply_filters( 'bp_member_map_type', bp_member_map_get_setting( 'default_type' ) ),
	);

	// Parse defaults
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// Apply filters to $zoom now that args are parsed
	$zoom = apply_filters( 'bp_member_map_zoom', $zoom, $latitude, $longitude );
?>
		<img id="member-map" src="http://maps.google.com/maps/api/staticmap?sensor=false&center=<?php echo $latitude . ',' . $longitude; ?>&zoom=<?php echo $zoom; ?>&maptype=<?php echo strtolower( $type ); ?>&size=<?php echo $height . 'x' . $width; ?>&markers=color:green|size:small|<?php echo $latitude . ',' . $longitude; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" />
<?php
}

/**
 * bp_member_map_show()
 * 
 * Show the map element
 * 
 * @param array $args Type: active ( default ) | random | newest | popular | online | alphabetical
 */
function bp_member_map_show( $args = '' ) {

	// Defaults
	$defaults = array(
		'width' => '200',
		'height' => '200',
		'class' => 'bp-member-map',
		'object_id' => bp_member_map_get_setting( 'object_id' )
	);

	// Parse defaults
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
?>
	<div id="<?php echo $object_id; ?>" class="<?php echo $class; ?>" style="width: <?php echo $width; ?>px; height: <?php echo $height; ?>px">
		<span></span>
	</div>
<?php
}

/**
 * bp_member_map_get_location(
 *
 * Returns the location based on a place
 *
 * @param string $place The location to search for
 * @return array Return latitude and longitude
 */
function bp_member_map_get_location( $place ) {
	return BP_Member_Map::location( $place );
}

/**
 * bp_member_map_has_access()
 *
 * Make sure user can perform special tasks
 * 
 * @return bool $can_do
 */
function bp_member_map_has_access() {

	if ( is_super_admin() )
		$has_access = true;
	else
		$has_access = false;

	return apply_filters( 'bp_member_map_has_access', $has_access );
}

/**
 * bp_member_map_profile_zoom()
 *
 * If viewing a member profile, zoom in a little bit
 *
 * @param string $zoom Height of satellite
 * @param string $latitude Horizontal position
 * @param string $longitude Vertical position
 * @return string
 */
function bp_member_map_profile_zoom( $zoom, $latitude, $longitude ) {
	if ( $latitude != bp_member_map_get_setting( 'default_latitude' ) && $longitude != bp_member_map_get_setting( 'default_longitude' ) ) {
		if ( 'public' == bp_current_action() ) {
			$zoom = '2';
		}
	}

	return $zoom;
}
add_filter( 'bp_member_map_zoom', 'bp_member_map_profile_zoom', 10, 3 );

/**
 * bp_member_map_get_setting()
 *
 * Gets global map setting
 *
 * @global array $bp
 * @param string $setting Setting to get
 * @return string
 */
function bp_member_map_get_setting( $setting ) {
	global $bp;

	return $bp->member_map->settings[$setting];
}

/**
 * bp_member_map_set_setting()
 *
 * Sets the global map setting
 *
 * @global array $bp
 * @param string $setting Setting to set
 * @param string $value  Value to assign
 */
function bp_member_map_set_setting( $setting, $value ) {
	global $bp;

	$bp->member_map->settings[$setting] = $value;
}

/**
 * bp_member_map_get_marker(0
 *
 * Gets a specific marker in the map marker array
 *
 * @global array $bp
 * @param integer $marker Which marker to return
 * @return array
 */
function bp_member_map_get_marker( $marker = '0' ) {
	global $bp;

	return $bp->member_map->markers[$marker];
}

?>
