<?php

declare( strict_types=1 );

namespace FioTransactions\Utils;

/**
 * Stores the Fio API tokens of the accounts.
 *
 * The tokens are encrypted (libsodium secretbox, provided by PHP or by the polyfill bundled with
 * WordPress) with a key that is not stored in the database, so a leaked database or backup does not
 * reveal them. The key is taken from the FIO_BANK_TRANSACTIONS_KEY constant (wp-config.php) or, when it
 * is not defined, from the key file wp-content/fio-bank-transactions-key.php, which the plugin creates
 * on request of an administrator. Tokens saved as plain text by older versions keep working and are
 * encrypted automatically once a key exists; new tokens can be saved only with a key.
 */
final class TokenStorage {

	const KEY_CONSTANT = 'FIO_BANK_TRANSACTIONS_KEY';
	const PREFIX       = 'enc:v1:';
	const KEY_FILE     = 'fio-bank-transactions-key.php';

	/**
	 * Cached content of the key file (null = not read yet).
	 *
	 * @var string|null
	 */
	private static $fileSecret = null;

	public static function metaKey(): string {
		return FIOTRANSACTIONS_NAME . '_token';
	}

	public static function keyFilePath(): string {
		return strval( constant( 'WP_CONTENT_DIR' ) ) . '/' . self::KEY_FILE;
	}

	/**
	 * The secret the encryption key is derived from: the constant, else the key file, else ''.
	 */
	private static function secret(): string {
		if ( defined( self::KEY_CONSTANT ) && is_string( constant( self::KEY_CONSTANT ) ) && '' !== constant( self::KEY_CONSTANT ) ) {
			return constant( self::KEY_CONSTANT );
		}

		if ( null === self::$fileSecret ) {
			self::$fileSecret = '';
			if ( is_readable( self::keyFilePath() ) ) {
				$value            = include self::keyFilePath();
				self::$fileSecret = is_string( $value ) ? $value : '';
			}
		}

		return self::$fileSecret;
	}

	public static function isEncryptionEnabled(): bool {
		return '' !== self::secret();
	}

	/**
	 * Creates the key file with a random key. Returns false when it cannot be written.
	 */
	public static function createKeyFile(): bool {
		global $wp_filesystem;

		if ( self::isEncryptionEnabled() ) {
			return true;
		}

		require_once strval( constant( 'ABSPATH' ) ) . 'wp-admin/includes/file.php';
		if ( 'direct' !== get_filesystem_method( array(), strval( constant( 'WP_CONTENT_DIR' ) ) ) || true !== WP_Filesystem() || ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return false;
		}

		$content = "<?php\n"
			. "// Encryption key of the Fio bank - transactions plugin. Do not delete or change this file,\n"
			. "// otherwise the Fio tokens have to be entered again. Copy it when moving the site.\n"
			. "defined( 'ABSPATH' ) || exit;\n"
			. "return '" . self::generateKey() . "';\n";

		if ( ! $wp_filesystem->put_contents( self::keyFilePath(), $content, 0640 ) ) {
			return false;
		}

		// Read it back the same way it is used.
		self::$fileSecret = null;
		if ( 64 !== strlen( self::secret() ) ) {
			$wp_filesystem->delete( self::keyFilePath() );
			self::$fileSecret = null;

			return false;
		}

		return true;
	}

	/**
	 * Removes the key file (on uninstall, together with the tokens).
	 */
	public static function deleteKeyFile() {
		if ( file_exists( self::keyFilePath() ) ) {
			wp_delete_file( self::keyFilePath() );
		}
	}

	/**
	 * Saves the token encrypted. Returns false when no key exists (tokens are never saved as plain text).
	 */
	public static function save( int $postId, string $token ): bool {
		if ( ! self::isEncryptionEnabled() ) {
			return false;
		}
		update_post_meta( $postId, self::metaKey(), self::encrypt( $token ) );

		return true;
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
	 * The token with all but the last four characters replaced by asterisks, or ''.
	 */
	public static function masked( int $postId ): string {
		$token = self::get( $postId );
		if ( strlen( $token ) <= 4 ) {
			return '';
		}

		return str_repeat( '*', strlen( $token ) - 4 ) . substr( $token, -4 );
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
		return sodium_crypto_generichash( self::secret(), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
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
