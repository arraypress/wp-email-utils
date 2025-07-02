# WordPress Email Utilities

A lightweight PHP library for email address operations, validation, anonymization, and privacy-compliant handling. Designed with clean APIs and efficient bulk operations.

## Features

* 🎯 **Clean API**: Separate classes for single (`Email`) and multiple (`Emails`) operations
* 🔒 **Privacy First**: Built-in anonymization and masking for GDPR compliance
* 📧 **Full Validation**: Comprehensive email validation and verification
* 🏷️ **Subaddressing**: Complete support for email subaddressing (plus addressing)
* 🔍 **Smart Detection**: Provider categorization with 5000+ disposable domains
* 📊 **Bulk Operations**: Efficient processing of multiple email addresses
* 🛡️ **Security Ready**: Domain pattern matching and filtering utilities
* ⚡ **Lean Design**: Lazy-loaded data files, optimized for performance
* 🗂️ **Comprehensive Lists**: Includes curated allowlist and blocklist

## Requirements

* PHP 7.4 or later

## Installation

```bash
composer require arraypress/email-utils
```

## Basic Usage

### Single Email Operations (`Email` class)

```php
use ArrayPress\EmailUtils\Email;

// Validation
if ( Email::is_valid( 'user@example.com' ) ) {
	// Valid email address
}

// Get parts
$domain = Email::get_domain( 'user@example.com' );     // "example.com"
$local  = Email::get_local( 'user@example.com' );      // "user"

// Privacy & anonymization
$anonymous = Email::anonymize( 'john.doe@example.com' );
// Returns: "jo***@ex***.com"

$masked = Email::mask( 'john.doe@example.com', 2, 1 );
// Returns: "jo****e@example.com"

// Normalization
$normalized = Email::normalize( ' USER@EXAMPLE.COM ' );
// Returns: "user@example.com"
```

### Multiple Email Operations (`Emails` class)

```php
use ArrayPress\EmailUtils\Emails;

$emails = [
	'valid@example.com',
	'invalid-email',
	'user+tag@gmail.com',
	'test@disposable.com'
];

// Bulk validation
$valid_emails       = Emails::filter( $emails );        // Get only valid emails
$invalid_emails     = Emails::invalid( $emails );       // Get only invalid emails  
$validation_results = Emails::validate( $emails );      // Get all results

// Bulk anonymization and hashing
$anonymous_emails = Emails::anonymize( $emails );
$hashed_emails    = Emails::hash( $emails );

// Extract from text
$text      = "Contact us at support@company.com or sales@company.com";
$extracted = Emails::extract( $text );
// Returns: ["support@company.com", "sales@company.com"]
```

## Advanced Features

### Provider Detection

```php
// Check provider types
$is_common    = Email::is_common_provider( 'user@gmail.com' );           // true
$is_authority = Email::is_authority_provider( 'user@icloud.com' );    // true

// Disposable email detection (with 5000+ domains)
$is_disposable = Email::is_disposable( 'user@10minutemail.com' );     // true (basic)
$is_disposable = Email::is_disposable( 'user@obscure-temp.site', true ); // true (extended)

// Check against allowlist (trusted domains)
$is_trusted = Email::is_allowlisted( 'gmail.com' );                   // true

// Get disposable domain statistics
$stats = Email::get_disposable_stats();
// Returns: ['basic_count' => 5, 'extended_count' => 5247, 'allowlist_count' => 185, ...]

// Bulk provider analysis
$provider_stats = Emails::count_by_provider_type( $emails, true ); // use extended list
// Returns: ['common' => 5, 'authority' => 3, 'disposable' => 12, 'other' => 1]
```

### Email Hashing & Privacy

```php
use ArrayPress\EmailUtils\Email;
use ArrayPress\EmailUtils\Utils;

// Simple hashing (uses WordPress salts automatically)
$hashed = Email::hash( 'user@gmail.com' );
// Returns: "wp_salted_hash@gmail.com"

// Full privacy (hash domain too)
$private = Email::hash( 'user@gmail.com', true );
// Returns: "hash1@hash2"

// Short hash for IDs
$short_id = Email::hash( 'user@gmail.com', false, 8 );
// Returns: "a1b2c3d4" (truncated)

// Custom salt
$custom = Email::hash( 'user@gmail.com', false, 0, 'my_salt' );

// Bulk hashing
$hashed_emails  = Emails::hash( $emails );           // Basic hashing
$private_emails = Emails::hash( $emails, true );    // Hash domains too
$short_ids      = Emails::hash( $emails, false, 8 );     // 8-char truncated

// Direct WordPress utilities
$wp_salt     = Utils::get_wordpress_salt();
$secure_hash = Utils::hash( 'any_value', 'sha256', 'custom_salt' );
```

### Disposable Email Management

```php
// Remove all disposable emails from a list
$clean_emails = Emails::remove_disposable( $emails );

// Get only disposable emails
$disposable_emails = Emails::filter_disposable( $emails );

// Custom disposable domain lists
Email::load_custom_disposable_domains( '/path/to/custom-disposable.txt' );
Email::load_custom_allowlist( '/path/to/trusted-domains.txt' );
```

> **Disposable Domain Lists:** This library includes comprehensive disposable email domain lists from the [disposable-email-domains](https://github.com/disposable-email-domains/disposable-email-domains) project. This actively maintained repository is trusted by major platforms including PyPI and contains over 5,000 known disposable email domains.

## Data Sources & Credits

The disposable email detection in this library is powered by:

- **[disposable-email-domains](https://github.com/disposable-email-domains/disposable-email-domains)** - The most comprehensive and actively maintained list of disposable email domains
- **Used by major platforms** including PyPI (Python Package Index)
- **Community maintained** with regular updates and contributions
- **License:** CC0 (Public Domain)

To update the disposable domain lists in your project:

1. Download the latest lists from the [disposable-email-domains repository](https://github.com/disposable-email-domains/disposable-email-domains)
2. Replace the files in your `data/` directory:
   - `disposable_email_blocklist.conf`
   - `allowlist.conf`
3. The library will automatically use the updated lists on next load

### Subaddressing Support

```php
// Check for subaddressing
$has_tag = Email::is_subaddressed( 'user+shopping@gmail.com' );  // true

// Extract parts
$base = Email::get_base_address( 'user+shopping@gmail.com' );    // "user@gmail.com"
$tag  = Email::get_subaddress( 'user+shopping@gmail.com' );       // "shopping"

// Add subaddressing
$tagged = Email::add_subaddress( 'user@gmail.com', 'newsletters' );
// Returns: "user+newsletters@gmail.com"

// Create purpose-specific emails
$shopping_email = Email::create_tagged( 'user@gmail.com', 'shopping', '2024' );
// Returns: "user+shopping-2024@gmail.com"

// Bulk operations
$base_addresses    = Emails::to_base_addresses( $emails );
$subaddressed_only = Emails::filter_subaddressed( $emails );
```

### Domain Operations

```php
// Group by domain
$grouped = Emails::group_by_domain( $emails );
// Returns: ['gmail.com' => [...], 'yahoo.com' => [...]]

// Count by domain
$domain_counts = Emails::count_by_domain( $emails );
$gmail_count   = Emails::count_by_domain( $emails, 'gmail.com' );

// Pattern matching
$patterns = [ '*.company.com', 'gmail.com', '.edu' ];
$matches  = Emails::filter_by_domain_patterns( $emails, $patterns );

// Provider filtering
$gmail_emails      = Emails::filter_by_provider_type( $emails, 'common' );
$disposable_emails = Emails::filter_by_provider_type( $emails, 'disposable' );
```

### Spam Detection & Scoring

```php
// Get spam score (0-100, higher = more suspicious)
$score = Email::get_spam_score( 'user123456@suspicious-domain.tk' );

// Bulk scoring
$scores = Emails::get_spam_scores( $emails );

// Filter by spam threshold
$clean_emails = Emails::filter_by_spam_score( $emails, 30 ); // Keep emails with score <= 30
```

### Email Analysis & Statistics

```php
// Get comprehensive statistics
$stats = Emails::get_statistics($emails);
/*
Returns:
[
    'total' => 100,
    'valid' => 95,
    'invalid' => 5,
    'valid_percent' => 95.0,
    'subaddressed' => 12,
    'provider_types' => ['common' => 60, 'authority' => 20, 'disposable' => 5, 'other' => 10],
    'unique_domains' => 25,
    'top_domains' => ['gmail.com' => 30, 'yahoo.com' => 15, ...]
]
*/
```

### Duplicate Management

```php
$emails_with_duplicates = [
	'user@example.com',
	'USER@EXAMPLE.COM',
	'user+tag@example.com',
	'user@example.com'
];

// Remove exact duplicates
$unique = Emails::remove_duplicates( $emails_with_duplicates );

// Remove duplicates ignoring subaddressing
$unique_base = Emails::remove_duplicates( $emails_with_duplicates, true );

// Compare ignoring subaddressing
$same_base = Email::compare_ignoring_subaddress(
	'user+tag1@gmail.com',
	'user+tag2@gmail.com'
); // true
```

## Privacy & GDPR Compliance

The library is designed with privacy in mind:

### Anonymization Methods

```php
// Full anonymization - safe for storage
$anonymous = Email::anonymize( 'john.doe@company.com' );
// Returns: "jo***@co***.com"

// Display masking - safe for UI display
$masked = Email::mask( 'john.doe@company.com', 3, 2 );
// Returns: "joh***oe@company.com"

// Check if already anonymized
$is_anon = Email::is_anonymized( 'jo***@co***.com' ); // true
```

### Bulk Privacy Operations

```php
// Anonymize multiple emails for analytics
$anonymous_emails = Emails::anonymize_all( $user_emails );

// Normalize for consistent storage
$normalized_emails = Emails::normalize_all( $user_emails );
```

## Validation & Verification

```php
// Basic validation
$is_valid = Email::is_valid( 'user@example.com' );

// MX record verification
$has_mx = Email::has_valid_mx( 'user@example.com' );

// International domain support
$ascii_email = Email::to_ascii( 'user@tëst.com' );

// Private/internal email detection
$is_private = Email::is_private( 'user@localhost' );
```

## Use Cases

### Email List Cleaning

```php
$cleaned_emails = Emails::clean( $emails );
```

### Newsletter Signup Validation

```php
function validate_newsletter_signup( string $email ): array {
	$result = [ 'valid' => false, 'warnings' => [] ];

	if ( ! Email::is_valid( $email ) ) {
		return $result;
	}

	$result['valid'] = true;

	// Check against 5000+ disposable domains
	if ( Email::is_disposable( $email, true ) ) {
		$result['warnings'][] = 'Disposable email detected';
	}

	// Check if it's allowlisted (trusted)
	if ( Email::is_allowlisted( $email ) ) {
		$result['trusted'] = true;
	}

	if ( Email::get_spam_score( $email ) > 50 ) {
		$result['warnings'][] = 'High spam score';
	}

	if ( ! Email::has_valid_mx( $email ) ) {
		$result['warnings'][] = 'No MX records found';
	}

	return $result;
}
```

### Privacy-Compliant Analytics

```php
function log_user_analytics( string $email ): void {
	// Store only anonymized email for analytics
	$anonymous_email = Email::anonymize( $email );
	$domain          = Email::get_domain( $email );
	$provider_type   = Email::is_common_provider( $email ) ? 'common' : 'other';

	// Safe to store these anonymized/aggregated values
	analytics_log( [
		'email_hash'    => $anonymous_email,
		'domain'        => $domain,
		'provider_type' => $provider_type
	] );
}
```

## API Reference

### Email Class (Single Operations)

**Validation:**
- `is_valid( string $email ): bool`
- `has_valid_mx( string $email ): bool`
- `get_spam_score( string $email, bool $check_mx = false ): int`

**Parts & Information:**
- `get_domain( string $email ): ?string`
- `get_local( string $email ): ?string`
- `normalize( string $email ): ?string`
- `to_ascii( string $email ): ?string`
- `to_ascii( string $email ): ?string`

**Privacy:**
- `anonymize( string $email ): ?string`
- `mask( string $email, int $show_first = 1, int $show_last = 1 ): ?string`
- `is_anonymized( string $email ): bool`

**Provider Detection:**
- `is_common_provider( string $email ): bool`
- `is_authority_provider( string $email ): bool`
- `is_disposable( string $email ): bool`
- `is_private( string $email ): bool`

**Subaddressing:**
- `is_subaddressed( string $email ): bool`
- `get_base_address( string $email ): ?string`
- `get_subaddress( string $email ): ?string`
- `add_subaddress( string $email, string $tag ): ?string`
- `create_tagged( string $email, string $purpose, ?string $suffix = null ): ?string`

### Emails Class (Bulk Operations)

**Validation & Filtering:**
- `validate( array $emails ): array`        # Get validation results for all
- `filter( array $emails ): array`          # Return only valid emails
- `invalid( array $emails ): array`         # Return only invalid emails
- `filter_by_spam_score( array $emails, int $threshold = 50, bool $check_mx = false ): array`

**Text Processing:**
- `extract( string $text ): array`                # Extract emails from text
- `remove_duplicates( array $emails, bool $ignore_subaddress = false ): array`
- `normalize( array $emails ): array`

**Privacy:**
- `anonymize_all( array $emails ): array`

**Analysis:**
- `group_by_domain( array $emails ): array`
- `count_by_domain( array $emails, ?string $domain = null)`
- `count_by_provider_type( array $emails ): array`
- `get_statistics( array $emails ): array`

**Hashing:**
- `hash( array $emails, bool $hash_domain = false, int $length = 0, string $salt = '' ): array`

## Utils Class (WordPress Integration)

**WordPress Utilities:**
- `get_wordpress_salt(): string`                    # Get WordPress-derived salt
- `hash(string $value, string $algorithm = 'sha256', string $salt = '' ): string` # Hash with WP salts

## Error Handling

All methods return `null`, `false`, or empty arrays for invalid inputs rather than throwing exceptions, making them safe for direct use in conditionals.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

**Disposable Domain Updates:** The disposable email domain lists are maintained by the [disposable-email-domains](https://github.com/disposable-email-domains/disposable-email-domains) project. To add or remove domains from the blocklist, please contribute directly to their repository.

## License

This project is licensed under the GPL-2.0-or-later License.

**Disposable Domain Lists:** Licensed under CC0 (Public Domain) by the disposable-email-domains project.

## Support

- [Documentation](https://github.com/arraypress/wp-email-utils)
- [Issue Tracker](https://github.com/arraypress/wp-email-utils/issues)
- [Disposable Email Domains Project](https://github.com/disposable-email-domains/disposable-email-domains)