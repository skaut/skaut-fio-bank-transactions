<?php

declare( strict_types=1 );

namespace FioTransactions\Blocks;

use FioTransactions\Accounts\AccountsInit;
use FioTransactions\Frontend\Renderer;
use FioTransactions\Utils\Helpers;

/**
 * "Fio bank transactions" block (server rendered).
 */
final class Block {

	const NAME = 'skaut-fio-bank-transactions/transactions';

	private $accountsInit;
	private $renderer;

	public function __construct( AccountsInit $accountsInit, Renderer $renderer ) {
		$this->accountsInit = $accountsInit;
		$this->renderer     = $renderer;
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'addEditorData' ) );
	}

	public function register() {
		wp_register_script(
			FIOTRANSACTIONS_NAME . '_block',
			plugin_dir_url( __FILE__ ) . 'public/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
			FIOTRANSACTIONS_VERSION,
			true
		);
		// Translations come from the translate.wordpress.org language packs.
		wp_set_script_translations( FIOTRANSACTIONS_NAME . '_block', 'skaut-fio-bank-transactions' );

		register_block_type(
			self::NAME,
			array(
				'api_version'     => 3,
				'title'           => __( 'Fio bank transactions', 'skaut-fio-bank-transactions' ),
				'description'     => __( 'Table of transactions from a Fio banka account.', 'skaut-fio-bank-transactions' ),
				'category'        => 'widgets',
				'icon'            => 'money-alt',
				'editor_script'   => FIOTRANSACTIONS_NAME . '_block',
				'render_callback' => array( $this, 'render' ),
				'supports'        => array( 'html' => false ),
				'attributes'      => array(
					'account'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'period'     => array(
						'type'    => 'string',
						'enum'    => array( 'days', 'last_7_days', 'this_month', 'last_month' ),
						'default' => Renderer::DEFAULT_PERIOD,
					),
					'days'       => array(
						'type'    => 'number',
						'default' => Renderer::DEFAULT_DAYS,
					),
					'columns'    => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'string' ),
						'default' => explode( ',', Renderer::DEFAULT_COLUMNS ),
					),
					'ss'         => array(
						'type'    => 'string',
						'default' => '',
					),
					'vsPrefix'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'showSearch' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'paging'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'pageLength' => array(
						'type'    => 'number',
						'enum'    => Renderer::PAGE_LENGTHS,
						'default' => Renderer::DEFAULT_PAGE_LENGTH,
					),
				),
			)
		);
	}

	/**
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render( array $attributes ): string {
		$columns = isset( $attributes['columns'] ) && is_array( $attributes['columns'] ) ? $attributes['columns'] : array();

		return $this->renderer->render(
			array(
				'account'     => $attributes['account'] ?? 0,
				'period'      => $attributes['period'] ?? Renderer::DEFAULT_PERIOD,
				'days'        => $attributes['days'] ?? Renderer::DEFAULT_DAYS,
				'columns'     => implode( ',', array_map( 'strval', $columns ) ),
				'ss'          => $attributes['ss'] ?? '',
				'vs_prefix'   => $attributes['vsPrefix'] ?? '',
				'search'      => $attributes['showSearch'] ?? true,
				'paging'      => $attributes['paging'] ?? true,
				'page_length' => $attributes['pageLength'] ?? Renderer::DEFAULT_PAGE_LENGTH,
			)
		);
	}

	/**
	 * Passes the list of accounts and columns to the editor script.
	 */
	public function addEditorData() {
		$accounts = array();
		if ( Helpers::userCanEmbedAccounts( get_current_user_id() ) ) {
			foreach ( $this->accountsInit->getAllAccounts() as $account ) {
				$accounts[] = array(
					'value' => $account->ID,
					'label' => $account->post_title,
				);
			}
		}

		$periods = array();
		foreach ( Renderer::getPeriods() as $value => $label ) {
			$periods[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		$columns = array();
		foreach ( Renderer::getColumns() as $value => $label ) {
			$columns[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		wp_add_inline_script(
			FIOTRANSACTIONS_NAME . '_block',
			'window.fioTransactionsBlock = ' . wp_json_encode(
				array(
					'accounts'   => $accounts,
					'periods'    => $periods,
					'columns'    => $columns,
					'canEmbed'   => Helpers::userCanEmbedAccounts( get_current_user_id() ),
					'postType'   => AccountsInit::ACCOUNTS_TYPE_SLUG,
					'maxDays'    => Renderer::MAX_DAYS,
					'defaultDay' => Renderer::DEFAULT_DAYS,
				)
			) . ';',
			'before'
		);
	}
}
