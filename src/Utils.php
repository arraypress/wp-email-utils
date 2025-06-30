<?php
/**
 * WordPress Utilities Class
 *
 * Provides utility functions for WordPress-specific operations,
 * including salt generation and common WordPress integrations.
 *
 * @package ArrayPress\EmailUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\EmailUtils;

/**
 * Utils Class
 *
 * WordPress-specific utility operations.
 */
class Utils {

	/**
	 * Get WordPress-based salt for hashing.
	 *
	 * @return string WordPress-derived salt.
	 */
	public static function get_wordpress_salt(): string {
		// Try WordPress-specific salt first
		if ( function_exists( 'wp_salt' ) ) {
			return wp_salt() . wp_salt( 'secure_auth' );
		}

		// Fallback to WordPress constants
		$salts = [
			defined( 'AUTH_KEY' ) ? AUTH_KEY : '',
			defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '',
			defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : '',
			defined( 'NONCE_KEY' ) ? NONCE_KEY : '',
		];

		$combined = implode( '', $salts );

		// Final fallback (though this should rarely happen in WordPress)
		return ! empty( $combined ) ? $combined : 'fallback_salt_' . ABSPATH;
	}

	/**
	 * Generate a secure hash using WordPress salts.
	 *
	 * @param string $value     The value to hash.
	 * @param string $algorithm The hashing algorithm (default: 'sha256').
	 * @param string $salt      Optional custom salt. If empty, uses WordPress salt.
	 *
	 * @return string The hashed value.
	 */
	public static function hash( string $value, string $algorithm = 'sha256', string $salt = '' ): string {
		if ( empty( $salt ) ) {
			$salt = self::get_wordpress_salt();
		}

		return hash( $algorithm, $value . $salt );
	}

}