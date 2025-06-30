<?php
/**
 * Email Domain Blocklist/Allowlist Management
 *
 * Provides utility functions for managing disposable email domains,
 * allowlists, and domain-based filtering operations.
 *
 * @package ArrayPress\EmailUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\EmailUtils;

/**
 * Blocklist Class
 *
 * Domain list management and filtering operations.
 */
class Blocklist {

	/**
	 * Disposable domains loaded from external file
	 *
	 * @var string[]|null
	 */
	protected static ?array $disposable_domains = null;

	/**
	 * Allowlist domains (trusted providers that should never be blocked)
	 *
	 * @var string[]|null
	 */
	protected static ?array $allowlist_domains = null;

	/**
	 * Check if an email address is from a disposable provider.
	 *
	 * @param string $email           The email address to check.
	 * @param bool   $check_allowlist Whether to check against allowlist first.
	 *
	 * @return bool True if from a disposable provider.
	 */
	public static function is_disposable( string $email, bool $check_allowlist = true ): bool {
		$domain = Email::get_domain( $email );
		if ( ! $domain ) {
			return false;
		}

		// Check allowlist first if enabled (trusted domains are never disposable)
		if ( $check_allowlist && self::is_allowlisted( $domain ) ) {
			return false;
		}

		// Check disposable domains list
		$disposable_domains = self::get_disposable_domains();

		return $disposable_domains && in_array( $domain, $disposable_domains, true );
	}

	/**
	 * Check if a domain is in the allowlist.
	 *
	 * @param string $input Email address or domain to check.
	 *
	 * @return bool True if domain is allowlisted.
	 */
	public static function is_allowlisted( string $input ): bool {
		// Auto-detect if it's an email or domain
		$domain = str_contains( $input, '@' ) ? Email::get_domain( $input ) : $input;

		if ( ! $domain ) {
			return false;
		}

		$allowlist = self::get_allowlist_domains();

		return $allowlist && in_array( $domain, $allowlist, true );
	}

	/**
	 * Get disposable domains list.
	 *
	 * @return array|null Array of disposable domains or null if not loaded.
	 */
	public static function get_disposable_domains(): ?array {
		if ( self::$disposable_domains === null ) {
			self::load_disposable_domains();
		}

		return self::$disposable_domains;
	}

	/**
	 * Get allowlist domains.
	 *
	 * @return array|null Array of allowlisted domains or null if not loaded.
	 */
	public static function get_allowlist_domains(): ?array {
		if ( self::$allowlist_domains === null ) {
			self::load_allowlist_domains();
		}

		return self::$allowlist_domains;
	}

	/**
	 * Load disposable domains from data file.
	 *
	 * @return bool True if loaded successfully.
	 */
	public static function load_disposable_domains(): bool {
		$file_path = self::get_data_file_path( 'disposable_email_blocklist.conf' );

		if ( ! file_exists( $file_path ) ) {
			self::$disposable_domains = [];

			return false;
		}

		$domains                  = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		self::$disposable_domains = $domains ? array_map( 'trim', $domains ) : [];

		return ! empty( self::$disposable_domains );
	}

	/**
	 * Load allowlist domains from data file.
	 *
	 * @return bool True if loaded successfully.
	 */
	public static function load_allowlist_domains(): bool {
		$file_path = self::get_data_file_path( 'allowlist.conf' );

		if ( ! file_exists( $file_path ) ) {
			self::$allowlist_domains = [];

			return false;
		}

		$domains                 = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		self::$allowlist_domains = $domains ? array_map( 'trim', $domains ) : [];

		return ! empty( self::$allowlist_domains );
	}

	/**
	 * Get the path to a data file.
	 *
	 * @param string $filename The filename to get path for.
	 *
	 * @return string The full path to the data file.
	 */
	protected static function get_data_file_path( string $filename ): string {
		// Get the directory where this class file is located
		$class_dir = dirname( __FILE__ );

		// Go up one level and into data directory
		return dirname( $class_dir ) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Manually load custom disposable domains list.
	 *
	 * @param string $file_path Path to disposable domains file.
	 *
	 * @return bool True if loaded successfully.
	 */
	public static function load_custom_disposable_domains( string $file_path ): bool {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$domains = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $domains ) {
			self::$disposable_domains = array_map( 'trim', $domains );

			return true;
		}

		return false;
	}

	/**
	 * Manually load custom allowlist domains.
	 *
	 * @param string $file_path Path to allowlist file.
	 *
	 * @return bool True if loaded successfully.
	 */
	public static function load_custom_allowlist( string $file_path ): bool {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$domains = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $domains ) {
			self::$allowlist_domains = array_map( 'trim', $domains );

			return true;
		}

		return false;
	}

	/**
	 * Add domains to disposable list.
	 *
	 * @param array|string $domains Domain(s) to add.
	 *
	 * @return bool True if added successfully.
	 */
	public static function add_disposable_domains( $domains ): bool {
		$domains = (array) $domains;

		// Ensure disposable domains are loaded
		if ( self::$disposable_domains === null ) {
			self::load_disposable_domains();
		}

		self::$disposable_domains = array_unique( array_merge( self::$disposable_domains, $domains ) );

		return true;
	}

	/**
	 * Add domains to allowlist.
	 *
	 * @param array|string $domains Domain(s) to add.
	 *
	 * @return bool True if added successfully.
	 */
	public static function add_allowlist_domains( $domains ): bool {
		$domains = (array) $domains;

		// Ensure allowlist domains are loaded
		if ( self::$allowlist_domains === null ) {
			self::load_allowlist_domains();
		}

		self::$allowlist_domains = array_unique( array_merge( self::$allowlist_domains, $domains ) );

		return true;
	}

	/**
	 * Remove domains from disposable list.
	 *
	 * @param array|string $domains Domain(s) to remove.
	 *
	 * @return bool True if removed successfully.
	 */
	public static function remove_disposable_domains( $domains ): bool {
		$domains = (array) $domains;

		// Ensure disposable domains are loaded
		if ( self::$disposable_domains === null ) {
			self::load_disposable_domains();
		}

		self::$disposable_domains = array_diff( self::$disposable_domains, $domains );

		return true;
	}

	/**
	 * Remove domains from allowlist.
	 *
	 * @param array|string $domains Domain(s) to remove.
	 *
	 * @return bool True if removed successfully.
	 */
	public static function remove_allowlist_domains( $domains ): bool {
		$domains = (array) $domains;

		// Ensure allowlist domains are loaded
		if ( self::$allowlist_domains === null ) {
			self::load_allowlist_domains();
		}

		self::$allowlist_domains = array_diff( self::$allowlist_domains, $domains );

		return true;
	}

	/**
	 * Clear all loaded domain lists.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$disposable_domains = null;
		self::$allowlist_domains  = null;
	}

	/**
	 * Get statistics about loaded domain lists.
	 *
	 * @return array Statistics about disposable and allowlist domains.
	 */
	public static function get_statistics(): array {
		$disposable = self::get_disposable_domains();
		$allowlist  = self::get_allowlist_domains();

		return [
			'disposable_count'  => $disposable ? count( $disposable ) : 0,
			'allowlist_count'   => $allowlist ? count( $allowlist ) : 0,
			'disposable_loaded' => ! empty( $disposable ),
			'allowlist_loaded'  => ! empty( $allowlist )
		];
	}

}