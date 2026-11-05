<?php

declare( strict_types=1 );

namespace FioTransactions\Accounts;

class Columns {

	public function __construct() {
		$this->initHooks();
	}

	protected function initHooks() {
		add_filter(
			'manage_edit-' . AccountsInit::ACCOUNTS_TYPE_SLUG . '_columns',
			array(
				$this,
				'lastModifiedAdminColumn',
			)
		);
		add_filter(
			'manage_edit-' . AccountsInit::ACCOUNTS_TYPE_SLUG . '_sortable_columns',
			array(
				$this,
				'sortableLastModifiedColumn',
			)
		);
		add_action(
			'manage_' . AccountsInit::ACCOUNTS_TYPE_SLUG . '_posts_custom_column',
			array(
				$this,
				'lastModifiedAdminColumnContent',
			),
			10,
			2
		);
	}

	public function lastModifiedAdminColumn( array $columns = array() ): array {
		$columns['modified_last'] = __( 'Last modified', 'skaut-fio-bank-transactions' );

		return $columns;
	}

	public function sortableLastModifiedColumn( array $columns = array() ): array {
		$columns['modified_last'] = 'modified';

		return $columns;
	}

	public function lastModifiedAdminColumnContent( string $columnName, int $postId ) {
		if ( 'modified_last' !== $columnName ) {
			return;
		}

		$post = get_post( $postId );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$modifiedDate = sprintf(
			/* translators: %s: human-readable time difference */
			_x( '%s ago', '%s = human-readable time difference', 'skaut-fio-bank-transactions' ),
			human_time_diff( intval( strtotime( $post->post_modified_gmt . ' UTC' ) ), time() )
		);
		$modifiedAuthor = get_the_modified_author();

		echo esc_html( $modifiedDate );
		echo '<br>';
		echo '<strong>' . esc_html( $modifiedAuthor ) . '</strong>';
	}
}
