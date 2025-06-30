<?php
/**
 * Emails Utility Class
 *
 * Provides utility functions for working with multiple email addresses,
 * including bulk validation, filtering, extraction, and analysis operations.
 *
 * @package ArrayPress\EmailUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\EmailUtils;

/**
 * Emails Class
 *
 * Bulk operations for working with multiple email addresses.
 */
class Emails {

	/**
	 * Validate multiple email addresses.
	 *
	 * @param array $emails Array of email addresses to validate.
	 *
	 * @return array Array with emails as keys and validation results as values.
	 */
	public static function validate( array $emails ): array {
		$results = [];
		foreach ( $emails as $email ) {
			$email             = trim( $email );
			$results[ $email ] = Email::is_valid( $email );
		}

		return $results;
	}

	/**
	 * Filter and return only valid email addresses.
	 *
	 * @param array $emails Array of email addresses to filter.
	 *
	 * @return array Array of valid email addresses.
	 */
	public static function filter( array $emails ): array {
		$valid = [];
		foreach ( $emails as $email ) {
			$email = trim( $email );
			if ( Email::is_valid( $email ) ) {
				$valid[] = $email;
			}
		}

		return $valid;
	}

	/**
	 * Filter and return only invalid email addresses.
	 *
	 * @param array $emails Array of email addresses to filter.
	 *
	 * @return array Array of invalid email addresses.
	 */
	public static function invalid( array $emails ): array {
		$invalid = [];
		foreach ( $emails as $email ) {
			$email = trim( $email );
			if ( ! Email::is_valid( $email ) ) {
				$invalid[] = $email;
			}
		}

		return $invalid;
	}

	/**
	 * Anonymize multiple email addresses.
	 *
	 * @param array $emails Array of email addresses to anonymize.
	 *
	 * @return array Array of anonymized email addresses.
	 */
	public static function anonymize( array $emails ): array {
		$anonymized = [];
		foreach ( $emails as $email ) {
			$result = Email::anonymize( $email );
			if ( $result !== null ) {
				$anonymized[] = $result;
			}
		}

		return $anonymized;
	}

	/**
	 * Hash multiple email addresses.
	 *
	 * @param array  $emails      Array of email addresses to hash.
	 * @param bool   $hash_domain Whether to hash the domain as well.
	 * @param int    $length      Length to truncate hash (0 = full hash).
	 * @param string $salt        Optional custom salt.
	 *
	 * @return array Array of hashed email addresses.
	 */
	public static function hash( array $emails, bool $hash_domain = false, int $length = 0, string $salt = '' ): array {
		$hashed = [];

		foreach ( $emails as $email ) {
			$result = Email::hash( $email, $hash_domain, $length, $salt );
			if ( $result !== null ) {
				$hashed[] = $result;
			}
		}

		return $hashed;
	}

	/**
	 * Extract all email addresses from text.
	 *
	 * @param string $text Text to extract email addresses from.
	 *
	 * @return array Array of extracted email addresses.
	 */
	public static function extract( string $text ): array {
		$words  = str_word_count( $text, 1, '.@+-_' );
		$emails = array_filter( $words, [ Email::class, 'is_valid' ] );

		// Normalize and remove duplicates
		$emails = array_map( [ Email::class, 'normalize' ], $emails );
		$emails = array_filter( $emails ); // Remove nulls

		return array_values( array_unique( $emails ) );
	}

	/**
	 * Remove duplicate email addresses from an array.
	 *
	 * @param array $emails            Array of email addresses.
	 * @param bool  $ignore_subaddress Whether to ignore subaddressing when detecting duplicates.
	 *
	 * @return array Array of unique email addresses.
	 */
	public static function remove_duplicates( array $emails, bool $ignore_subaddress = false ): array {
		$unique = [];
		$seen   = [];

		foreach ( $emails as $email ) {
			$email = trim( $email );
			if ( ! Email::is_valid( $email ) ) {
				continue;
			}

			$key = $ignore_subaddress ? Email::get_base_address( $email ) : strtolower( $email );
			if ( ! in_array( $key, $seen, true ) ) {
				$unique[] = $email;
				$seen[]   = $key;
			}
		}

		return $unique;
	}

	/**
	 * Group email addresses by their domain.
	 *
	 * @param array $emails Array of email addresses to group.
	 *
	 * @return array Array with domains as keys and email arrays as values.
	 */
	public static function group_by_domain( array $emails ): array {
		$groups = [];

		foreach ( $emails as $email ) {
			$domain = Email::get_domain( $email );
			if ( $domain ) {
				$groups[ $domain ][] = $email;
			}
		}

		return $groups;
	}

	/**
	 * Count email addresses by domain.
	 *
	 * @param array       $emails Array of email addresses.
	 * @param string|null $domain Optional specific domain to count.
	 *
	 * @return array|int Domain counts array or specific domain count.
	 */
	public static function count_by_domain( array $emails, ?string $domain = null ) {
		$counts = [];

		foreach ( $emails as $email ) {
			$email_domain = Email::get_domain( $email );
			if ( $email_domain ) {
				$counts[ $email_domain ] = ( $counts[ $email_domain ] ?? 0 ) + 1;
			}
		}

		if ( $domain !== null ) {
			return $counts[ $domain ] ?? 0;
		}

		return $counts;
	}

	/**
	 * Count email addresses by provider type.
	 *
	 * @param array $emails Array of email addresses.
	 *
	 * @return array Counts by provider type (common, authority, disposable, other).
	 */
	public static function count_by_provider_type( array $emails ): array {
		$counts = [
			'common'     => 0,
			'authority'  => 0,
			'disposable' => 0,
			'other'      => 0
		];

		foreach ( $emails as $email ) {
			if ( ! Email::is_valid( $email ) ) {
				continue;
			}

			if ( Email::is_disposable( $email ) ) {
				$counts['disposable'] ++;
			} elseif ( Email::is_authority_provider( $email ) ) {
				$counts['authority'] ++;
			} elseif ( Email::is_common_provider( $email ) ) {
				$counts['common'] ++;
			} else {
				$counts['other'] ++;
			}
		}

		return $counts;
	}

	/**
	 * Filter emails by domain patterns.
	 *
	 * @param array $emails   Array of email addresses to filter.
	 * @param array $patterns Array of domain patterns to match against.
	 * @param bool  $include  Whether to include (true) or exclude (false) matches.
	 *
	 * @return array Filtered email addresses.
	 */
	public static function filter_by_domain_patterns( array $emails, array $patterns, bool $include = true ): array {
		$filtered = [];

		foreach ( $emails as $email ) {
			$matches = self::matches_domain_patterns( $email, $patterns );
			if ( ( $include && $matches ) || ( ! $include && ! $matches ) ) {
				$filtered[] = $email;
			}
		}

		return $filtered;
	}

	/**
	 * Check if an email matches any domain pattern.
	 *
	 * @param string $email    Email address to check.
	 * @param array  $patterns Array of patterns (supports *.domain.com format).
	 *
	 * @return bool True if email matches any pattern.
	 */
	public static function matches_domain_patterns( string $email, array $patterns ): bool {
		$domain = Email::get_domain( $email );
		if ( ! $domain ) {
			return false;
		}

		foreach ( $patterns as $pattern ) {
			$pattern = strtolower( trim( $pattern ) );

			// Exact match
			if ( $pattern === $domain ) {
				return true;
			}

			// Wildcard subdomain match (*.example.com)
			if ( str_starts_with( $pattern, '*.' ) ) {
				$base_domain = substr( $pattern, 2 );
				if ( $domain === $base_domain || str_ends_with( $domain, '.' . $base_domain ) ) {
					return true;
				}
			}

			// Domain ending match (.example.com)
			if ( str_starts_with( $pattern, '.' ) && str_ends_with( $domain, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filter emails by provider type.
	 *
	 * @param array  $emails Array of email addresses.
	 * @param string $type   Provider type ('common', 'authority', 'disposable', 'private').
	 *
	 * @return array Filtered email addresses.
	 */
	public static function filter_by_provider_type( array $emails, string $type ): array {
		$filtered = [];

		foreach ( $emails as $email ) {
			$include = false;

			switch ( $type ) {
				case 'common':
					$include = Email::is_common_provider( $email );
					break;
				case 'authority':
					$include = Email::is_authority_provider( $email );
					break;
				case 'disposable':
					$include = Email::is_disposable( $email );
					break;
				case 'private':
					$include = Email::is_private( $email );
					break;
			}

			if ( $include ) {
				$filtered[] = $email;
			}
		}

		return $filtered;
	}

	/**
	 * Get emails with subaddressing.
	 *
	 * @param array $emails Array of email addresses.
	 *
	 * @return array Array of emails that have subaddressing.
	 */
	public static function filter_subaddressed( array $emails ): array {
		$filtered = [];

		foreach ( $emails as $email ) {
			if ( Email::is_subaddressed( $email ) ) {
				$filtered[] = $email;
			}
		}

		return $filtered;
	}

	/**
	 * Convert all emails to their base addresses (remove subaddressing).
	 *
	 * @param array $emails Array of email addresses.
	 *
	 * @return array Array of base email addresses.
	 */
	public static function to_base_addresses( array $emails ): array {
		$base_addresses = [];

		foreach ( $emails as $email ) {
			$base = Email::get_base_address( $email );
			if ( $base ) {
				$base_addresses[] = $base;
			}
		}

		return array_values( array_unique( $base_addresses ) );
	}

	/**
	 * Normalize multiple email addresses.
	 *
	 * @param array $emails Array of email addresses to normalize.
	 *
	 * @return array Array of normalized email addresses.
	 */
	public static function normalize( array $emails ): array {
		$normalized = [];

		foreach ( $emails as $email ) {
			$result = Email::normalize( $email );
			if ( $result !== null ) {
				$normalized[] = $result;
			}
		}

		return $normalized;
	}

	/**
	 * Calculate spam scores for multiple emails.
	 *
	 * @param array $emails   Array of email addresses.
	 * @param bool  $check_mx Whether to check MX records.
	 *
	 * @return array Array with emails as keys and scores as values.
	 */
	public static function get_spam_scores( array $emails, bool $check_mx = false ): array {
		$scores = [];

		foreach ( $emails as $email ) {
			$scores[ $email ] = Email::get_spam_score( $email, $check_mx );
		}

		return $scores;
	}

	/**
	 * Filter emails by spam score threshold.
	 *
	 * @param array $emails    Array of email addresses.
	 * @param int   $threshold Maximum allowed spam score.
	 * @param bool  $check_mx  Whether to check MX records.
	 *
	 * @return array Array of emails below the spam threshold.
	 */
	public static function filter_by_spam_score( array $emails, int $threshold = 50, bool $check_mx = false ): array {
		$filtered = [];

		foreach ( $emails as $email ) {
			if ( Email::get_spam_score( $email, $check_mx ) <= $threshold ) {
				$filtered[] = $email;
			}
		}

		return $filtered;
	}

	/**
	 * Remove disposable emails from an array.
	 *
	 * @param array $emails Array of email addresses.
	 *
	 * @return array Array with disposable emails removed.
	 */
	public static function remove_disposable( array $emails ): array {
		$filtered = [];

		foreach ( $emails as $email ) {
			if ( ! Email::is_disposable( $email ) ) {
				$filtered[] = $email;
			}
		}

		return $filtered;
	}

	/**
	 * Get only disposable emails from an array.
	 *
	 * @param array $emails Array of email addresses.
	 *
	 * @return array Array of disposable emails.
	 */
	public static function filter_disposable( array $emails ): array {
		return self::filter_by_provider_type( $emails, 'disposable' );
	}

	/**
	 * Get statistics for an array of email addresses.
	 *
	 * @param array $emails Array of email addresses.
	 *
	 * @return array Statistics including counts, percentages, and breakdowns.
	 */
	public static function get_statistics( array $emails ): array {
		$total       = count( $emails );
		$valid       = self::filter( $emails );
		$valid_count = count( $valid );

		if ( $valid_count === 0 ) {
			return [
				'total'         => $total,
				'valid'         => 0,
				'invalid'       => $total,
				'valid_percent' => 0
			];
		}

		$provider_counts = self::count_by_provider_type( $valid );
		$domain_counts   = self::count_by_domain( $valid );
		$subaddressed    = count( self::filter_subaddressed( $valid ) );

		// Get top domains
		arsort( $domain_counts );
		$top_domains = array_slice( $domain_counts, 0, 5, true );

		return [
			'total'           => $total,
			'valid'           => $valid_count,
			'invalid'         => $total - $valid_count,
			'valid_percent'   => round( ( $valid_count / $total ) * 100, 2 ),
			'subaddressed'    => $subaddressed,
			'provider_types'  => $provider_counts,
			'unique_domains'  => count( $domain_counts ),
			'top_domains'     => $top_domains,
			'blocklist_stats' => Blocklist::get_statistics()
		];
	}

}