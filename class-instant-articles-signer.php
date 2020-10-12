<?php
/**
 * Facebook Instant Articles for WP.
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package default
 */

/**
 * Class responsible for handling the signature of graph rescrape calls
 *
 * @since 4.1
 */
class Instant_Articles_Signer {

	const PUBLIC_KEY_OPTION  = 'instant-articles-rescrape-public-key';
	const PRIVATE_KEY_OPTION = 'instant-articles-rescrape-private-key';
	const PUBLIC_KEY_PATH    = '.well-known/graph-api/apikey.pub';

	public static function init() {
		add_action( 'wp', array( 'Instant_Articles_Signer', 'output_public_key' ) );
	}

	public static function output_public_key() {
		global $wp;
		if ( self::PUBLIC_KEY_PATH === $wp->request ) {
			status_header( 200 );
			echo esc_html( self::get_public_key() );
			die();
		}
	}

	public static function get_public_key() {
		$public_key = get_option( self::PUBLIC_KEY_OPTION );
		if ( ! $public_key ) {
			self::gen_keys();
			$public_key = get_option( self::PUBLIC_KEY_OPTION );
		}
		return $public_key;
	}

	public static function get_private_key() {
		$private_key = get_option( self::PRIVATE_KEY_OPTION );
		if ( ! $private_key ) {
			self::gen_keys();
			$private_key = get_option( self::PRIVATE_KEY_OPTION );
		}
		return $private_key;
	}

	private static function gen_keys( $force = false ) {
		$public_key  = get_option( self::PUBLIC_KEY_OPTION );
		$private_key = get_option( self::PRIVATE_KEY_OPTION );

		if ( ! $force && $private_key && $public_key ) {
			return;
		}

		// Create the private and public key
		$result = openssl_pkey_new();

		// Extract the private key from $result to $private_key
		openssl_pkey_export( $result, $private_key );

		// Extract the public key from $result to $public_key
		$public_key = openssl_pkey_get_details( $result );
		$public_key = $public_key['key'];

		update_option( self::PRIVATE_KEY_OPTION, $private_key );
		update_option( self::PUBLIC_KEY_OPTION, $public_key );
	}

	public static function get_signature( $data ) {
		openssl_sign( $data, $signature, self::get_private_key(), OPENSSL_ALGO_SHA1 );
		return rawurlencode( base64_encode( $signature ) );
	}

	public static function sign_request_path( $path ) {
		$now       = new DateTime();
		$ts        = $now->getTimestamp();
		$path      = add_query_arg( 'ts', $ts, $path );
		$signature = self::get_signature( urldecode( $path ) );
		$path      = add_query_arg( 'hmac', $signature, $path );
		return $path;
	}

}
