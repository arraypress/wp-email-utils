<?php
/**
 * Email Utility Class
 *
 * Provides utility functions for working with single email addresses,
 * including validation, anonymization, domain operations, and privacy compliance.
 *
 * @package ArrayPress\EmailUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\EmailUtils;

/**
 * Email Class
 *
 * Core operations for working with single email addresses.
 */
class Email {

	/**
	 * Common email provider domains
	 *
	 * @var string[]
	 */
	protected static array $common_domains = [
		'gmail.com',
		'yahoo.com',
		'hotmail.com',
		'outlook.com',
		'aol.com',
		'icloud.com',
		'protonmail.com',
		'mail.com',
		'zoho.com',
		'live.com'
	];

	/**
	 * Email providers that support subaddressing (plus addressing)
	 *
	 * @var string[]
	 */
	protected static array $subaddress_domains = [
		'gmail.com',
		'googlemail.com',
		'yahoo.com',
		'fastmail.com',
		'outlook.com',
		'hotmail.com',
		'protonmail.com'
	];

	/**
	 * Authoritative email providers (high trust)
	 *
	 * @var string[]
	 */
	protected static array $authority_domains = [
		'gmail.com',
		'icloud.com',
		'outlook.com',
		'hotmail.com',
		'yahoo.com'
	];

	/**
	 * Common TLDs for validation scoring
	 *
	 * @var string[]
	 */
	protected static array $common_tlds = [
		'com',
		'org',
		'net',
		'edu',
		'gov',
		'io',
		'co',
		'us',
		'uk',
		'ca'
	];

	/**
	 * The standard anonymized email placeholder.
	 */
	const ANONYMIZED_PLACEHOLDER = 'deleted@site.invalid';

	/**
	 * Validate an email address.
	 *
	 * @param string $email The email address to validate.
	 *
	 * @return bool True if the email address is valid.
	 */
	public static function is_valid( string $email ): bool {
		$email = trim( $email );

		return is_email( $email ) !== false;
	}

	/**
	 * Get the domain part of an email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return string|null The domain or null if invalid.
	 */
	public static function get_domain( string $email ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		return substr( strrchr( $email, '@' ), 1 );
	}

	/**
	 * Get the local part (username) of an email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return string|null The local part or null if invalid.
	 */
	public static function get_local( string $email ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		return substr( $email, 0, strpos( $email, '@' ) );
	}

	/**
	 * Anonymize an email address for privacy compliance.
	 *
	 * @param string $email The email address to anonymize.
	 *
	 * @return string|null The anonymized email or null if invalid.
	 */
	public static function anonymize( string $email ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		list( $local, $domain ) = explode( '@', $email );
		$domain_parts = explode( '.', $domain );

		// Anonymize the local part (username)
		$anonymized_local = substr( $local, 0, 2 ) . str_repeat( '*', max( strlen( $local ) - 2, 3 ) );

		// Only anonymize the first part of the domain, preserve the rest
		if ( count( $domain_parts ) > 1 ) {
			$first_domain_part     = $domain_parts[0];
			$anonymized_first_part = substr( $first_domain_part, 0, 2 ) .
			                         str_repeat( '*', max( strlen( $first_domain_part ) - 2, 3 ) );

			// Rebuild domain: anonymized_first_part + all remaining parts
			$remaining_parts   = array_slice( $domain_parts, 1 );
			$anonymized_domain = $anonymized_first_part . '.' . implode( '.', $remaining_parts );
		} else {
			// Single part domain (shouldn't happen with valid emails, but just in case)
			$anonymized_domain = substr( $domain, 0, 2 ) . str_repeat( '*', max( strlen( $domain ) - 2, 3 ) );
		}

		return $anonymized_local . '@' . $anonymized_domain;
	}

	/**
	 * Mask an email address for display purposes.
	 *
	 * @param string $email      The email address to mask.
	 * @param int    $show_first Number of characters to show at the start.
	 * @param int    $show_last  Number of characters to show at the end.
	 *
	 * @return string|null The masked email or null if invalid.
	 */
	public static function mask( string $email, int $show_first = 1, int $show_last = 1 ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		list( $local, $domain ) = explode( '@', $email );
		$local_length = strlen( $local );

		if ( $show_first + $show_last >= $local_length ) {
			return $email;
		}

		$masked_length = $local_length - $show_first - $show_last;
		$masked_local  = substr( $local, 0, $show_first ) .
		                 str_repeat( '*', $masked_length ) .
		                 substr( $local, - $show_last );

		return $masked_local . '@' . $domain;
	}

	/**
	 * Normalize an email address.
	 *
	 * @param string $email The email address to normalize.
	 *
	 * @return string|null The normalized email or null if invalid.
	 */
	public static function normalize( string $email ): ?string {
		$email = trim( $email );
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		return strtolower( $email );
	}

	/**
	 * Check if an email address is from a private/reserved domain.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool True if the email is from a private domain.
	 */
	public static function is_private( string $email ): bool {
		$domain = self::get_domain( $email );
		if ( ! $domain ) {
			return false;
		}

		// Check for localhost and internal domains
		$private_patterns = [
			'localhost',
			'127.0.0.1',
			'192.168.',
			'10.',
			'172.16.',
			'.local',
			'.internal',
			'.test'
		];

		foreach ( $private_patterns as $pattern ) {
			if ( str_contains( $domain, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an email address is from a common provider.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool True if from a common provider.
	 */
	public static function is_common_provider( string $email ): bool {
		$domain = self::get_domain( $email );

		return $domain && in_array( $domain, self::$common_domains, true );
	}

	/**
	 * Check if an email address is from an authority provider.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool True if from an authority provider.
	 */
	public static function is_authority_provider( string $email ): bool {
		$domain = self::get_domain( $email );

		return $domain && in_array( $domain, self::$authority_domains, true );
	}

	/**
	 * Check if an email address is from a disposable provider.
	 *
	 * @param string $email           The email address to check.
	 * @param bool   $check_allowlist Whether to check against allowlist first.
	 *
	 * @return bool True if from a disposable provider.
	 */
	public static function is_disposable( string $email, bool $check_allowlist = true ): bool {
		return Blocklist::is_disposable( $email, $check_allowlist );
	}

	/**
	 * Check if a domain is in the allowlist.
	 *
	 * @param string $input Email address or domain to check.
	 *
	 * @return bool True if domain is allowlisted.
	 */
	public static function is_allowlisted( string $input ): bool {
		return Blocklist::is_allowlisted( $input );
	}

	/**
	 * Check if an email address has subaddressing (contains plus sign).
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool True if the email has subaddressing.
	 */
	public static function is_subaddressed( string $email ): bool {
		$local = self::get_local( $email );

		return $local && str_contains( $local, '+' );
	}

	/**
	 * Get the base email address without subaddressing.
	 *
	 * @param string $email The email address.
	 *
	 * @return string|null The base email or null if invalid.
	 */
	public static function get_base_address( string $email ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		list( $local, $domain ) = explode( '@', $email );
		$base_local = explode( '+', $local )[0];

		return $base_local . '@' . $domain;
	}

	/**
	 * Get the subaddress (tag) from an email.
	 *
	 * @param string $email The email address.
	 *
	 * @return string|null The subaddress or null if none exists.
	 */
	public static function get_subaddress( string $email ): ?string {
		if ( ! self::is_subaddressed( $email ) ) {
			return null;
		}

		$local = self::get_local( $email );
		$parts = explode( '+', $local );

		return $parts[1] ?? null;
	}

	/**
	 * Add subaddressing to an email address.
	 *
	 * @param string $email The base email address.
	 * @param string $tag   The tag to add.
	 *
	 * @return string|null The email with subaddress or null if invalid/unsupported.
	 */
	public static function add_subaddress( string $email, string $tag ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		$domain = self::get_domain( $email );
		if ( ! in_array( $domain, self::$subaddress_domains, true ) ) {
			return null;
		}

		// Clean the tag
		$tag = trim( str_replace( [ '+', ' ' ], [ '', '-' ], $tag ) );
		if ( empty( $tag ) ) {
			return null;
		}

		list( $local, $domain ) = explode( '@', $email );
		$base_local = explode( '+', $local )[0]; // Remove existing subaddress

		return $base_local . '+' . $tag . '@' . $domain;
	}

	/**
	 * Check if an email has valid MX records.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool True if MX records exist.
	 */
	public static function has_valid_mx( string $email ): bool {
		$domain = self::get_domain( $email );
		if ( ! $domain ) {
			return false;
		}

		return checkdnsrr( $domain );
	}

	/**
	 * Calculate a spam score for an email address.
	 *
	 * @param string $email    The email address to score.
	 * @param bool   $check_mx Whether to check MX records.
	 *
	 * @return int Score from 0-100 (higher = more suspicious).
	 */
	public static function get_spam_score( string $email, bool $check_mx = false ): int {
		if ( ! self::is_valid( $email ) ) {
			return 100;
		}

		$score  = 0;
		$local  = self::get_local( $email );
		$domain = self::get_domain( $email );

		// Check for excessive numbers in local part (but ignore common year patterns)
		$number_count = preg_match_all( '/\d/', $local );
		if ( $number_count > 3 ) {
			// Check if it's likely a birth year (19xx or 20xx)
			if ( ! preg_match( '/19\d{2}|20\d{2}/', $local ) ) {
				$score += 10;
			} elseif ( $number_count > 6 ) {
				// Too many numbers even with year
				$score += 5;
			}
		}

		// Check dots in local part
		$dot_count = substr_count( $local, '.' );
		if ( $dot_count > 2 ) {
			$score += 5 + ( $dot_count * 5 );
		}

		// Check local part length
		if ( strlen( $local ) > 20 ) {
			$score += 10;
		}

		// Check domain TLD
		$tld = substr( strrchr( $domain, '.' ), 1 );
		if ( ! in_array( $tld, self::$common_tlds, true ) ) {
			$score += 15;
		}

		// Check common providers (reduce score)
		if ( self::is_common_provider( $email ) ) {
			$score -= 10;
		}

		// Check disposable providers
		if ( self::is_disposable( $email ) ) {
			$score += 30;
		}

		// Check multiple hyphens or underscores
		if ( substr_count( $local, '-' ) > 1 || substr_count( $local, '_' ) > 1 ) {
			$score += 10;
		}

		// Check domain length
		$domain_parts = explode( '.', $domain );
		if ( strlen( $domain_parts[0] ) > 15 ) {
			$score += 10;
		}

		// Check MX records if requested
		if ( $check_mx && ! self::has_valid_mx( $email ) ) {
			$score += 25;
		}

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Check if an email is already anonymized.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool True if the email appears to be anonymized.
	 */
	public static function is_anonymized( string $email ): bool {
		return str_contains( $email, '*' ) || $email === self::ANONYMIZED_PLACEHOLDER;
	}

	/**
	 * Replace an email with the standard anonymized placeholder.
	 * Useful for GDPR compliance and maintaining database integrity.
	 *
	 * @param string $email The email address to replace.
	 *
	 * @return string|null The anonymized placeholder email or null if invalid.
	 */
	public static function placeholder( string $email ): ?string {
		return self::is_valid( $email ) ? self::ANONYMIZED_PLACEHOLDER : null;
	}

	/**
	 * Get the anonymized placeholder email.
	 *
	 * @return string The standard anonymized email placeholder.
	 */
	public static function get_placeholder(): string {
		return self::ANONYMIZED_PLACEHOLDER;
	}

	/**
	 * Compare two emails ignoring subaddressing.
	 *
	 * @param string $email1 First email address.
	 * @param string $email2 Second email address.
	 *
	 * @return bool True if base addresses match.
	 */
	public static function compare_ignoring_subaddress( string $email1, string $email2 ): bool {
		$base1 = self::get_base_address( $email1 );
		$base2 = self::get_base_address( $email2 );

		return $base1 && $base2 && $base1 === $base2;
	}

	/**
	 * Convert an email to ASCII (Punycode for international domains).
	 *
	 * @param string $email The email address to convert.
	 *
	 * @return string|null The ASCII email or null if invalid.
	 */
	public static function to_ascii( string $email ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		list( $local, $domain ) = explode( '@', $email );

		if ( function_exists( 'idn_to_ascii' ) ) {
			$ascii_domain = idn_to_ascii(
				$domain,
				IDNA_NONTRANSITIONAL_TO_ASCII
			);
			if ( $ascii_domain !== false ) {
				return $local . '@' . $ascii_domain;
			}
		}

		return $email;
	}

	/**
	 * Create a tagged email for specific purposes.
	 *
	 * @param string      $email   Base email address.
	 * @param string      $purpose Purpose tag (e.g., 'shopping', 'newsletters').
	 * @param string|null $suffix  Optional suffix (defaults to current year).
	 *
	 * @return string|null Tagged email or null if invalid/unsupported.
	 */
	public static function create_tagged( string $email, string $purpose, ?string $suffix = null ): ?string {
		$tag = strtolower( $purpose );

		if ( $suffix !== null ) {
			$tag .= '-' . $suffix;
		} else {
			$tag .= '-' . date( 'Y' );
		}

		return self::add_subaddress( $email, $tag );
	}

	/**
	 * Hash an email address for secure storage.
	 *
	 * @param string $email       The email address to hash.
	 * @param bool   $hash_domain Whether to hash the domain as well (default: false).
	 * @param int    $length      Length to truncate hash (0 = full hash).
	 * @param string $salt        Optional custom salt. If empty, uses WordPress salt.
	 *
	 * @return string|null The hashed email or null if invalid.
	 */
	public static function hash( string $email, bool $hash_domain = false, int $length = 0, string $salt = '' ): ?string {
		if ( ! self::is_valid( $email ) ) {
			return null;
		}

		list( $local, $domain ) = explode( '@', $email );

		// Hash the local part
		$hashed_local = Utils::hash( $local, 'sha256', $salt );

		if ( $hash_domain ) {
			// Hash the domain as well
			$hashed_domain = Utils::hash( $domain, 'sha256', $salt );
			$result        = $hashed_local . '@' . $hashed_domain;
		} else {
			// Keep domain for analytics purposes
			$result = $hashed_local . '@' . $domain;
		}

		// Truncate if length specified
		if ( $length > 0 && $length < strlen( $result ) ) {
			return substr( $result, 0, $length );
		}

		return $result;
	}

}