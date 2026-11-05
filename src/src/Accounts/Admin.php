<?php

declare( strict_types=1 );

namespace FioTransactions\Accounts;

final class Admin {

	public function __construct() {
		$this->initHooks();
	}

	private function initHooks() {
		( new Columns() );

		add_action( 'add_meta_boxes', array( $this, 'addMetaboxForTokenField' ) );
		add_action( 'save_post', array( $this, 'saveTokenCustomField' ) );
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
			$token = preg_replace( '/[^a-zA-Z0-9]/', '', sanitize_text_field( wp_unslash( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ) ) );
			update_post_meta(
				$postId,
				FIOTRANSACTIONS_NAME . '_token',
				sanitize_meta( FIOTRANSACTIONS_NAME . '_token', $token, 'post' )
			);
		}
	}

	public function TokenFieldContent( \WP_Post $post ) {
		wp_nonce_field( FIOTRANSACTIONS_NAME . '_metabox', FIOTRANSACTIONS_NAME . '_metabox_nonce' );
		?>
		<h2><?php esc_html_e( 'Zadejte 64 znakový token z internetového bankovnictví', 'skaut-fio-bank-transactions' ); ?>:</h2>
		<input type="text" name="<?php echo esc_attr( FIOTRANSACTIONS_NAME ) . '_token'; ?>"
				value="<?php echo esc_attr( get_post_meta( $post->ID, FIOTRANSACTIONS_NAME . '_token', true ) ); ?>"
				placeholder="<?php esc_attr_e( 'token', 'skaut-fio-bank-transactions' ); ?>"
				class="regular-text"
				style="width: 100%;"
				pattern="[a-zA-Z0-9]{64}"
				required="required"/>
		<p><a href="https://napoveda.fapi.cz/article/144-jak-vygenerovat-token-ve-fio-bance"
				target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Jak získám token?', 'skaut-fio-bank-transactions' ); ?></a></p>
		<?php
	}
}
