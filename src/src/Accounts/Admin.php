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
		if ( $postType == AccountsInit::ACCOUNTS_TYPE_SLUG ) {
			add_meta_box(
				FIOTRANSACTIONS_NAME . '_token_metabox',
				__( 'Token', 'fio-bank-transactions' ),
				array( $this, 'TokenFieldContent' ),
				AccountsInit::ACCOUNTS_TYPE_SLUG,
				'advanced',
				'high'
			);
		}
	}

	public function saveTokenCustomField( int $postId ) {
		if ( array_key_exists( FIOTRANSACTIONS_NAME . '_token', $_POST ) ) {
			update_post_meta(
				$postId,
				FIOTRANSACTIONS_NAME . '_token',
				sanitize_meta( FIOTRANSACTIONS_NAME . '_token', wp_unslash( $_POST[ FIOTRANSACTIONS_NAME . '_token' ] ), 'post' )
			);
		}
	}

	public function TokenFieldContent( \WP_Post $post ) {
		?>
		<h2><?php esc_html_e( 'Zadejte 64 znakový token z internetového bankovnictví', 'fio-bank-transactions' ); ?>:</h2>
		<input type="text" name="<?php echo esc_attr( FIOTRANSACTIONS_NAME ) . '_token'; ?>"
			   value="<?php echo esc_attr( get_post_meta( $post->ID, FIOTRANSACTIONS_NAME . '_token', true ) ); ?>"
			   placeholder="<?php esc_attr_e( 'token', 'fio-bank-transactions' ); ?>"
			   class="regular-text"
			   style="width: 100%;"
			   pattern="[a-zA-Z0-9]{64}"
			   required="required"/>
		<p><a href="http://napoveda.fapi.cz/article/144-jak-vygenerovat-token-ve-fio-bance"
			  target="_blank"><?php esc_html_e( 'Jak získám token?', 'fio-bank-transactions' ); ?></a></p>
		<?php
	}

	public function addRulesUi( \WP_Post $post ) {
		if ( $post->post_type != AccountsInit::ACCOUNTS_TYPE_SLUG ) {
			return;
		}
		?>
		<div class="meta-box-sortables">
			<div class="postbox" style="margin-top: 2.5em;">
				<button type="button" class="handlediv" aria-expanded="true"><span
						class="screen-reader-text"><?php esc_html_e( 'Zobrazit / skrýt panel: Token', 'fio-bank-transactions' ); ?></span><span
						class="toggle-indicator" aria-hidden="true"></span></button>
				<h2 class="hndle ui-sortable-handle">
					<span><?php esc_html_e( 'Zadejte token', 'fio-bank-transactions' ); ?></span>
				</h2>
				<div class="inside" style="padding: 0.75em 1.5em 1.25em 1.5em;">
					<label class="screen-reader-text"
						   for="post_author_override"><?php esc_html_e( 'Zadejte token', 'fio-bank-transactions' ); ?></label>

				</div>
			</div>
		</div>
		<?php
	}

}
