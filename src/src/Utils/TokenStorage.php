<?php

declare( strict_types=1 );

namespace FioTransactions\Utils;

/**
 * Stores the Fio API tokens of the accounts.
 *
 * When the constant FIO_BANK_TRANSACTIONS_KEY is defined (in wp-config.php), the tokens are encrypted
 * (libsodium secretbox, provided by PHP or by the polyfill bundled with WordPress) with a key derived
 * from it. The key is not stored in the database, so a leaked database or backup does not reveal the
 * tokens. Without the constant the tokens are stored as plain text, as in older versions; they are
 * encrypted automatically once the constant is defined.
 */
final class TokenStorage {

	const KEY_CONSTANT = 'FIO_BANK_TRANSACTIONS_KEY';
	const PREFIX       = 'enc:v1:';

	public static function metaKey(): string {
		return FIOTRANSACTIONS_NAME . '_token';
	}

	public static function isEncryptionEnabled(): bool {
		return defined( self::KEY_CONSTANT ) && is_string( constant( self::KEY_CONSTANT ) ) && '' !== constant( self::KEY_CONSTANT );
	}

	public static function save( int $postId, string $token ) {
		$value = self::isEncryptionEnabled() ? self::encrypt( $token ) : $token;
		update_post_meta( $postId, self::metaKey(), $value );
	}

	/**
	 * Returns the token in plain text, or '' when there is none or it cannot be decrypted.
	 */
	public static function get( int $postId ): string {
		$stored = get_post_meta( $postId, self::metaKey(), true );
		if ( ! is_string( $stored ) || '' === $stored ) {
			return '';
		}

		if ( ! self::isEncrypted( $stored ) ) {
			// Token from an older version or saved before the key was defined - encrypt it now.
			if ( self::isEncryptionEnabled() ) {
				self::save( $postId, $stored );
			}

			return $stored;
		}

		return self::decrypt( $stored ) ?? '';
	}

	public static function hasToken( int $postId ): bool {
		$stored = get_post_meta( $postId, self::metaKey(), true );

		return is_string( $stored ) && '' !== $stored;
	}

	/**
	 * True when a token is stored but cannot be decrypted (missing or changed key).
	 */
	public static function isUnreadable( int $postId ): bool {
		return self::hasToken( $postId ) && '' === self::get( $postId );
	}

	public static function isEncryptedForPost( int $postId ): bool {
		$stored = get_post_meta( $postId, self::metaKey(), true );

		return is_string( $stored ) && self::isEncrypted( $stored );
	}

	/**
	 * The last four characters of the token (to recognize it without revealing it), or ''.
	 */
	public static function hint( int $postId ): string {
		$token = self::get( $postId );

		return strlen( $token ) > 4 ? substr( $token, -4 ) : '';
	}

	/**
	 * Encrypts the plain text tokens of all accounts (after the key has been defined).
	 *
	 * @param array<int, int> $postIds Account IDs.
	 */
	public static function migrate( array $postIds ) {
		if ( ! self::isEncryptionEnabled() ) {
			return;
		}
		foreach ( $postIds as $postId ) {
			self::get( intval( $postId ) );
		}
	}

	/**
	 * A random value for the FIO_BANK_TRANSACTIONS_KEY constant.
	 */
	public static function generateKey(): string {
		return bin2hex( random_bytes( 32 ) );
	}

	private static function isEncrypted( string $value ): bool {
		return 0 === strpos( $value, self::PREFIX );
	}

	private static function key(): string {
		return sodium_crypto_generichash( strval( constant( self::KEY_CONSTANT ) ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	private static function encrypt( string $token ): string {
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Binary ciphertext stored as text.
		return self::PREFIX . base64_encode( $nonce . sodium_crypto_secretbox( $token, $nonce, self::key() ) );
	}

	private static function decrypt( string $value ): ?string {
		if ( ! self::isEncryptionEnabled() ) {
			return null;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Binary ciphertext stored as text.
		$raw = base64_decode( substr( $value, strlen( self::PREFIX ) ), true );
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return null;
		}

		try {
			$token = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), self::key() );
		} catch ( \Throwable ) {
			// Corrupted value.
			return null;
		}

		return false === $token ? null : $token;
	}
}
