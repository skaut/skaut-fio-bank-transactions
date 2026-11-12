<?php

declare( strict_types=1 );

namespace FioTransactions\Accounts;

use FioTransactions\Utils\Helpers;
use FioTransactions\Utils\TokenStorage;

final class Admin {

	const CREATE_KEY_ACTION = 'fio_bank_transactions_create_key';

	public function __construct() {
		$this->initHooks();
	}

	private function initHooks() {
		( new Columns() );

		add_action( 'add_meta_boxes', array( $this, 'addMetaboxForTokenField' ) );
		add_action( 'save_post', array( $this, 'saveTokenCustomField' ) );
		add_action( 'admin_notices', array( $this, 'encryptionNotice' ) );
		add_action( 'load-edit.php', array( $this, 'migrateTokens' ) );
		add_action( 'admin_post_' . self::CREATE_KEY_ACTION, array( $this, 'createKey' ) );
	}

	private static function isAccountsScreen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}

		return AccountsInit::ACCOUNTS_TYPE_SLUG === $screen->post_type;
	}

	private static function accountsUrl(): string {
		return admin_url( 'edit.php?post_type=' . AccountsInit::ACCOUNTS_TYPE_SLUG );
	}

	/**
	 * Creates the key file on request of an administrator (button in the notice).
	 *
	 * @SuppressWarnings("PHPMD.ExitExpression") Required after a redirect in an admin-post handler.
	 */
	public function createKey() {
		if ( ! current_user_can( Helpers::getFioManagerCapability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skaut-fio-bank-transactions' ) );
		}
		check_admin_referer( self::CREATE_KEY_ACTION );

		$result = TokenStorage::createKeyFile() ? 'created' : 'failed';
		wp_safe_redirect( add_query_arg( FIOTRANSACTIONS_NAME . '_key', $result, self::accountsUrl() ) );
		exit;
	}

	/**
	 * Encrypts the plain text tokens of older versions when the list of accounts is opened.
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
	 * On the account screens: the result of creating the key, or a request to create it.
	 */
	public function encryptionNotice() {
		if ( ! self::isAccountsScreen() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only selects a message after the redirect.
		$result = isset( $_GET[ FIOTRANSACTIONS_NAME . '_key' ] ) ? sanitize_key( wp_unslash( $_GET[ FIOTRANSACTIONS_NAME . '_key' ] ) ) : '';

		if ( TokenStorage::isEncryptionEnabled() ) {
			if ( 'created' === $result ) {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html(
					sprintf(
						/* translators: %s: path to the key file */
						__( 'The encryption key was created in %s. Do not delete this file and copy it when moving the site, otherwise the tokens will have to be entered again.', 'skaut-fio-bank-transactions' ),
						TokenStorage::keyFilePath()
					)
				);
				echo '</p></div>';
			}

			return;
		}

		if ( 'failed' === $result ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The key file could not be created, the wp-content folder is not writable. Add the key to wp-config.php manually (see below).', 'skaut-fio-bank-transactions' ) . '</p></div>';
		}

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Create the encryption key first.', 'skaut-fio-bank-transactions' ) . '</strong> ';
		echo esc_html__( 'The Fio tokens are stored encrypted with a key that is kept outside the database. Accounts can be added only after the key is created.', 'skaut-fio-bank-transactions' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><p>';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::CREATE_KEY_ACTION ) . '">';
		wp_nonce_field( self::CREATE_KEY_ACTION );
		submit_button( __( 'Create the encryption key', 'skaut-fio-bank-transactions' ), 'primary', 'submit', false );
		echo '</p></form>';
		echo '<p>' . esc_html__( 'Alternatively, add this line to the wp-config.php file:', 'skaut-fio-bank-transactions' ) . ' <code>define( \'' . esc_html( TokenStorage::KEY_CONSTANT ) . '\', \'' . esc_html( TokenStorage::generateKey() ) . '\' );</code></p></div>';
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

	private static function canSaveToken( int $postId ): bool {
		$nonce = isset( $_POST[ FIOTRANSACTIONS_NAME . '_metabox_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ FIOTRANSACTIONS_NAME . '_metabox_nonce' ] ) ) : '';
		if ( false === wp_verify_nonce( $nonce, FIOTRANSACTIONS_NAME . '_metabox' ) ) {
			return false;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && true === DOING_AUTOSAVE ) || false !== wp_is_post_revision( $postId ) ) {
			return false;
		}

		return get_post_type( $postId ) === AccountsInit::ACCOUNTS_TYPE_SLUG && current_user_can( 'edit_post', $postId );
	}

	public function saveTokenCustomField( int $postId ) {
		if ( ! self::canSaveToken( $postId ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in canSaveToken().
		$raw = isset( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ) && is_string( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ) ? sanitize_text_field( wp_unslash( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ) ) : '';

		// Fio API tokens are alphanumeric only; strip anything else. The saved token is never sent back
		// to the browser, so an empty field means "keep the current token".
		$token = strval( preg_replace( '/[^a-zA-Z0-9]/', '', $raw ) );
		if ( '' !== $token ) {
			TokenStorage::save( $postId, $token );
		}
	}

	public function TokenFieldContent( \WP_Post $post ) {
		if ( ! TokenStorage::isEncryptionEnabled() ) {
			echo '<p><strong>' . esc_html__( 'Create the encryption key first (see the notice at the top of the page), then you can enter the token.', 'skaut-fio-bank-transactions' ) . '</strong></p>';

			return;
		}

		wp_nonce_field( FIOTRANSACTIONS_NAME . '_metabox', FIOTRANSACTIONS_NAME . '_metabox_nonce' );
		$hasToken = TokenStorage::hasToken( $post->ID );

		if ( $hasToken && TokenStorage::isUnreadable( $post->ID ) ) {
			echo '<p><strong>' . esc_html__( 'The saved token cannot be decrypted (the encryption key is missing or was changed). Enter the token again.', 'skaut-fio-bank-transactions' ) . '</strong></p>';
			$hasToken = false;
		}

		if ( $hasToken ) {
			?>
			<h2><?php esc_html_e( 'Saved token', 'skaut-fio-bank-transactions' ); ?>:</h2>
			<input type="text" value="<?php echo esc_attr( TokenStorage::masked( $post->ID ) ); ?>" class="regular-text code" style="width: 100%;" readonly="readonly" disabled="disabled"/>
			<p class="description"><?php esc_html_e( 'The token is stored encrypted and cannot be displayed. You can only replace it with a new token created in the Fio internet banking.', 'skaut-fio-bank-transactions' ); ?></p>
			<h2><?php esc_html_e( 'New token', 'skaut-fio-bank-transactions' ); ?>:</h2>
			<?php
		} else {
			?>
			<h2><?php esc_html_e( 'Enter the 64-character token from the Fio internet banking', 'skaut-fio-bank-transactions' ); ?>:</h2>
			<?php
		}
		?>
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
