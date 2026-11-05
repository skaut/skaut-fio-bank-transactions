<?php

declare( strict_types=1 );

namespace FioTransactions\Blocks;

use FioTransactions\Accounts\AccountsInit;
use FioTransactions\Frontend\Renderer;
use FioTransactions\Utils\Helpers;

/**
 * "Transakce z Fio banky" block (server rendered).
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
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			FIOTRANSACTIONS_VERSION,
			true
		);

		register_block_type(
			self::NAME,
			array(
				'api_version'     => 3,
				'title'           => 'Transakce z Fio banky',
				'category'        => 'widgets',
				'icon'            => 'money-alt',
				'editor_script'   => FIOTRANSACTIONS_NAME . '_block',
				'render_callback' => array( $this, 'render' ),
				'supports'        => array( 'html' => false ),
				'attributes'      => array(
					'account'  => array(
						'type'    => 'number',
						'default' => 0,
					),
					'period'   => array(
						'type'    => 'string',
						'enum'    => array_keys( Renderer::PERIODS ),
						'default' => Renderer::DEFAULT_PERIOD,
					),
					'days'     => array(
						'type'    => 'number',
						'default' => Renderer::DEFAULT_DAYS,
					),
					'columns'  => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'string' ),
						'default' => explode( ',', Renderer::DEFAULT_COLUMNS ),
					),
					'ss'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'vsPrefix' => array(
						'type'    => 'string',
						'default' => '',
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
				'account'   => $attributes['account'] ?? 0,
				'period'    => $attributes['period'] ?? Renderer::DEFAULT_PERIOD,
				'days'      => $attributes['days'] ?? Renderer::DEFAULT_DAYS,
				'columns'   => implode( ',', array_map( 'strval', $columns ) ),
				'ss'        => $attributes['ss'] ?? '',
				'vs_prefix' => $attributes['vsPrefix'] ?? '',
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
		foreach ( Renderer::PERIODS as $value => $label ) {
			$periods[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		$columns = array();
		foreach ( Renderer::COLUMNS as $value => $label ) {
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
