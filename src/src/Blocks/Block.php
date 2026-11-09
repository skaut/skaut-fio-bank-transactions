<?php

declare( strict_types=1 );

namespace FioTransactions\Blocks;

use FioTransactions\Accounts\AccountsInit;
use FioTransactions\Frontend\Renderer;
use FioTransactions\Frontend\TableSettings;
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
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			FIOTRANSACTIONS_VERSION,
			true
		);

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
						'default' => TableSettings::DEFAULT_PERIOD,
					),
					'days'       => array(
						'type'    => 'number',
						'default' => TableSettings::DEFAULT_DAYS,
					),
					'columns'    => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'string' ),
						'default' => explode( ',', TableSettings::DEFAULT_COLUMNS ),
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
						'enum'    => TableSettings::PAGE_LENGTHS,
						'default' => TableSettings::DEFAULT_PAGE_LENGTH,
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
				'period'      => $attributes['period'] ?? TableSettings::DEFAULT_PERIOD,
				'days'        => $attributes['days'] ?? TableSettings::DEFAULT_DAYS,
				'columns'     => implode( ',', array_map( 'strval', $columns ) ),
				'ss'          => $attributes['ss'] ?? '',
				'vs_prefix'   => $attributes['vsPrefix'] ?? '',
				'search'      => $attributes['showSearch'] ?? true,
				'paging'      => $attributes['paging'] ?? true,
				'page_length' => $attributes['pageLength'] ?? TableSettings::DEFAULT_PAGE_LENGTH,
			)
		);
	}

	/**
	 * Texts of the block editor controls. They are translated here (PHP translations from the
	 * language pack) and passed to the editor script, so the script needs no JSON translation file.
	 *
	 * @return array<string, string>
	 */
	private static function getLabels(): array {
		return array(
			'title'                => __( 'Fio bank transactions', 'skaut-fio-bank-transactions' ),
			'account'              => __( 'Account', 'skaut-fio-bank-transactions' ),
			'selectAccount'        => __( '— Select an account —', 'skaut-fio-bank-transactions' ),
			'settings'             => __( 'Settings', 'skaut-fio-bank-transactions' ),
			'period'               => __( 'Period', 'skaut-fio-bank-transactions' ),
			'days'                 => sprintf(
				/* translators: %d: maximum number of days */
				__( 'Number of days (max. %d)', 'skaut-fio-bank-transactions' ),
				TableSettings::MAX_DAYS
			),
			'daysHelp'             => __( 'Without an additional authorization, Fio banka provides only the last 90 days.', 'skaut-fio-bank-transactions' ),
			'specificSymbol'       => __( 'Only this specific symbol (SS)', 'skaut-fio-bank-transactions' ),
			'variableSymbolPrefix' => __( 'Only variable symbols starting with', 'skaut-fio-bank-transactions' ),
			'tableDisplay'         => __( 'Table display', 'skaut-fio-bank-transactions' ),
			'showSearch'           => __( 'Show search', 'skaut-fio-bank-transactions' ),
			'pagination'           => __( 'Pagination', 'skaut-fio-bank-transactions' ),
			'paginationOn'         => __( 'The table is split into pages.', 'skaut-fio-bank-transactions' ),
			'paginationOff'        => __( 'All transactions are shown at once.', 'skaut-fio-bank-transactions' ),
			'rowsPerPage'          => __( 'Rows per page', 'skaut-fio-bank-transactions' ),
			'all'                  => __( 'All', 'skaut-fio-bank-transactions' ),
			'columns'              => __( 'Columns', 'skaut-fio-bank-transactions' ),
			'notAllowed'           => __( 'You are not allowed to insert transactions of bank accounts.', 'skaut-fio-bank-transactions' ),
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
		foreach ( TableSettings::getPeriods() as $value => $label ) {
			$periods[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		$columns = array();
		foreach ( TableSettings::getColumns() as $value => $label ) {
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
					'maxDays'    => TableSettings::MAX_DAYS,
					'defaultDay' => TableSettings::DEFAULT_DAYS,
					'labels'     => self::getLabels(),
				)
			) . ';',
			'before'
		);
	}
}
