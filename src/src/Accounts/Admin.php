<?php

declare( strict_types=1 );

namespace FioTransactions\Accounts;

use FioTransactions\Utils\TokenStorage;

final class Admin {

	public function __construct() {
		$this->initHooks();
	}

	private function initHooks() {
		( new Columns() );

		add_action( 'add_meta_boxes', array( $this, 'addMetaboxForTokenField' ) );
		add_action( 'save_post', array( $this, 'saveTokenCustomField' ) );
		add_action( 'admin_notices', array( $this, 'encryptionNotice' ) );
		add_action( 'load-edit.php', array( $this, 'migrateTokens' ) );
	}

	private static function isAccountsScreen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}

		return AccountsInit::ACCOUNTS_TYPE_SLUG === $screen->post_type;
	}

	/**
	 * Encrypts the plain text tokens when the list of accounts is opened (after the key has been defined).
	 */
	public function migrateTokens() {
		if ( ! self::isAccountsScreen() || ! TokenStorage::isEncryptionEnabled() ) {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'        => AccountsInit::ACCOUNTS_TYPE_SLUG,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		TokenStorage::migrate( array_map( 'intval', $ids ) );
	}

	/**
	 * Recommends defining the encryption key on the account screens.
	 */
	public function encryptionNotice() {
		if ( TokenStorage::isEncryptionEnabled() || ! self::isAccountsScreen() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'The Fio tokens are stored in the database as plain text.', 'skaut-fio-bank-transactions' ) . '</strong> ';
		echo esc_html__( 'To store them encrypted, add the following line to the wp-config.php file (above the line "That\'s all, stop editing!"). The tokens are then encrypted automatically. Keep the key secret and do not change it, otherwise the tokens will have to be entered again.', 'skaut-fio-bank-transactions' ) . '</p>';
		echo '<p><code>define( \'' . esc_html( TokenStorage::KEY_CONSTANT ) . '\', \'' . esc_html( TokenStorage::generateKey() ) . '\' );</code></p></div>';
	}

	public function addMetaboxForTokenField( string $postType ) {
		if ( AccountsInit::ACCOUNTS_TYPE_SLUG === $postType ) {
			add_meta_box(
				FIOTRANSACTIONS_NAME . '_token_metabox',
				__( 'Token', 'skaut-fio-bank-transactions' ),
				array( $this, 'TokenFieldContent' ),
				AccountsInit::ACCOUNTS_TYPE_SLUG,
				'advanced',
				'high'
			);
		}
	}

	public function saveTokenCustomField( int $postId ) {
		if ( ! isset( $_POST[ FIOTRANSACTIONS_NAME . '_metabox_nonce' ] ) || false === wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ FIOTRANSACTIONS_NAME . '_metabox_nonce' ] ) ), FIOTRANSACTIONS_NAME . '_metabox' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && true === DOING_AUTOSAVE ) || false !== wp_is_post_revision( $postId ) ) {
			return;
		}

		if ( get_post_type( $postId ) !== AccountsInit::ACCOUNTS_TYPE_SLUG || ! current_user_can( 'edit_post', $postId ) ) {
			return;
		}

		if ( array_key_exists( FIOTRANSACTIONS_NAME . '_token', $_POST ) && is_string( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ) ) {
			// Fio API tokens are alphanumeric only; strip anything else.
			$token = strval( preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ) ) ) );

			// The saved token is never sent back to the browser, so an empty field means "keep the current token".
			if ( '' !== $token ) {
				TokenStorage::save( $postId, $token );
			}
		}
	}

	public function TokenFieldContent( \WP_Post $post ) {
		wp_nonce_field( FIOTRANSACTIONS_NAME . '_metabox', FIOTRANSACTIONS_NAME . '_metabox_nonce' );
		$hasToken = TokenStorage::hasToken( $post->ID );
		?>
		<h2><?php esc_html_e( 'Enter the 64-character token from the Fio internet banking', 'skaut-fio-bank-transactions' ); ?>:</h2>
		<?php if ( $hasToken ) : ?>
			<p>
				<?php
				if ( TokenStorage::isUnreadable( $post->ID ) ) {
					echo '<strong>' . esc_html__( 'The saved token cannot be decrypted (the FIO_BANK_TRANSACTIONS_KEY constant is missing or was changed). Enter the token again.', 'skaut-fio-bank-transactions' ) . '</strong>';
				} else {
					echo esc_html(
						sprintf(
							/* translators: %s: last four characters of the token */
							__( 'A token ending with "%s" is saved. It is not shown for security reasons; to change it, enter a new token.', 'skaut-fio-bank-transactions' ),
							TokenStorage::hint( $post->ID )
						)
					);
					echo ' ';
					echo esc_html( TokenStorage::isEncryptedForPost( $post->ID ) ? __( 'The token is stored encrypted.', 'skaut-fio-bank-transactions' ) : __( 'The token is stored as plain text.', 'skaut-fio-bank-transactions' ) );
				}
				?>
			</p>
		<?php endif; ?>
		<input type="password" name="<?php echo esc_attr( FIOTRANSACTIONS_NAME ) . '_token'; ?>"
				value=""
				placeholder="<?php echo esc_attr( $hasToken ? __( 'Leave empty to keep the saved token', 'skaut-fio-bank-transactions' ) : __( 'token', 'skaut-fio-bank-transactions' ) ); ?>"
				class="regular-text"
				style="width: 100%;"
				autocomplete="off"
				spellcheck="false"
				pattern="[a-zA-Z0-9]{64}"
				<?php echo $hasToken ? '' : 'required="required"'; ?>/>
		<p><a href="https://napoveda.fapi.cz/article/31-jak-vytvorit-token-ve-fio-bance"
				target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How do I get a token?', 'skaut-fio-bank-transactions' ); ?></a></p>
		<?php
	}
}
