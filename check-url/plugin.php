<?php
/*
Plugin Name: Check URL
Plugin URI: http://code.google.com/p/yourls-check-url/
Description: This plugin checks the reachability of an entered URL before creating the short link for it. An error is then returned if the entered URL is unreachable.
Version: 1.3
Author: Aylwin
Author URI: http://adigitalife.net/
*/

// Hook our custom function into the 'shunt_add_new_link' filter
yourls_add_filter( 'shunt_add_new_link', 'churl_reachability' );

// Add a new link in the DB, either with custom keyword, or find one
function churl_reachability( $churl_reachable, $url, $keyword = '' ) {
	global $ydb;

	if (function_exists('yourls_get_protocol')){
		$skip_protocol = check_protocol( $url );
	} else {
		preg_match( '!^[a-zA-Z0-9\+\.-]+:(//)?!', $url, $matches );
		$protocol = ( isset( $matches[0] ) ? $matches[0] : '' );
		$different_protocols = array (
			'mailto://',
			'ftp://',
			'file://',
			'telnet://',
			'ssh://',
			'sip://'
        );

		$skip_protocol = in_array( $protocol, $different_protocols );
	}
	
	// Return to normal routine if non-http(s) protocol is valid
	if ($skip_protocol == true){
		return false;
	}

	// Check if the long URL is reachable	
	require_once( YOURLS_INC.'/functions-http.php' );
	$churl_reachable = yourls_get_remote_content( $url );
	
	// Return error if the entered URL is unreachable
	if ( $churl_reachable == false ){
		$return['status']   = 'fail';
		$return['code']     = 'error:url';
		$return['message']  = 'The entered URL is unreachable.  Check the URL or try again later.';
		$return['statusCode'] = 200; // regardless of result, this is still a valid request
		return yourls_apply_filter( 'add_new_link_fail_unreachable', $return, $url, $keyword, $title );
	}
	
	return false;
}

function check_protocol( $url, $protocols = array() ) {
	if( ! $protocols ) {
		global $yourls_allowedprotocols;
		$protocols = $yourls_allowedprotocols;
	}
	
	if ( ( yourls_get_protocol( $url ) == 'http://' ) || ( yourls_get_protocol( $url ) == 'https://' ) ) {
		return false;
	} else {
		$protocol = yourls_get_protocol( $url );
		return yourls_apply_filter( 'is_allowed_protocol', in_array( $protocol, $protocols ), $url, $protocols );
	}
}