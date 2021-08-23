<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

use FioTransactions\api\AccountFactory;
use FioTransactions\Accounts\AccountsInit;

final class Shortcode {

	private $accountFactory;
	private $frontendDirUrl = '';

	public function __construct( AccountFactory $accountFactory ) {
		$this->accountFactory = $accountFactory;
		$this->frontendDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueStylesAndScripts' ] );

		add_shortcode( 'fio', [ $this, 'processShortcode' ] );
	}

	private function prepareTable( array $transactions ): string {
		do_action( FIOTRANSACTIONS_NAME . '_transactionsForTable', $transactions );
		$html = '';

		$html .= '<table class="fioTransactionsTable"><thead style="font-weight: bold;"><tr>';
		$html .= '<th>Datum</th><th>Částka</th><th>Protiúčet</th><th>Zpráva pro příjemce</th><th>Poznámka</th><th>Typ</th>';
		$html .= '</tr></thead ><tbody>';

		foreach ( $transactions as $transaction ) {
			$incomeOutcome = FIOTRANSACTIONS_NAME . '_outcome';
			if ( $transaction->getAmount() > 0 ) {
				$incomeOutcome = FIOTRANSACTIONS_NAME . '_income';
			}
			$html .= '<tr>';
			$html .= '<td class="' . FIOTRANSACTIONS_NAME . '_date">' . esc_html( $transaction->getDate()->format( 'd.m.Y' ) ) . '</td>';
			$html .= '<td class="' . FIOTRANSACTIONS_NAME . '_amount ' . $incomeOutcome . '">' . esc_html( number_format( $transaction->getAmount(), 2, ',', ' ' ) ) . ' ' . esc_html( $transaction->getCurrency() ) . '</td>';
			$html .= '<td>' . esc_html( $transaction->getSenderAccountNumber() ) . '\\' . $transaction->getSenderBankCode() . '</td>';
			$html .= '<td>' . esc_html( $transaction->getUserMessage() ) . '</td>';
			$html .= '<td>' . esc_html( $transaction->getComment() ) . '</td>';
			$html .= '<td>' . esc_html( $transaction->getTransactionType() ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		$html = strval( apply_filters( FIOTRANSACTIONS_NAME . '_tableHtml', $html ) );

		return $html;
	}

	public function enqueueStylesAndScripts() {
		wp_enqueue_style(
			'datatables',
			'https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css',
			[],
			'1.10.24',
			'all'
		);

		wp_enqueue_script(
			'datatables',
			'https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js',
			[ 'jquery' ],
			'1.10.24',
			true
		);

		wp_enqueue_script(
			'fio-momentjs',
			'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js',
			[ 'datatables' ],
			'2.8.4',
			true
		);

		wp_enqueue_script(
			'fio-datetime-momentjs',
			'https://cdn.datatables.net/plug-ins/1.10.24/sorting/datetime-moment.js',
			[ 'fio-momentjs' ],
			'1.10.24',
			true
		);

		wp_enqueue_style(
			FIOTRANSACTIONS_NAME,
			$this->frontendDirUrl . 'css/fio-frontend.css',
			[],
			FIOTRANSACTIONS_VERSION,
			'all'
		);

		wp_enqueue_script(
			FIOTRANSACTIONS_NAME,
			$this->frontendDirUrl . 'js/fio-frontend.js',
			[ 'jquery' ],
			FIOTRANSACTIONS_VERSION,
			true
		);
	}

	public function processShortcode( array $atts = [] ): string {
		if ( isset( $atts['account'] ) && $atts['account'] > 0 ) {

			$accountsWpQuery = new \WP_Query( [
				'post_type'      => AccountsInit::ACCOUNTS_TYPE_SLUG,
				'p'              => absint( $atts['account'] ),
				'posts_per_page' => 1,
				'fields'         => 'ids'
			] );

			if ( ! $accountsWpQuery->have_posts() ) {
				return '';
			}

			$token = get_post_meta( $accountsWpQuery->posts[0], FIOTRANSACTIONS_NAME . '_token', true );

			$account = $this->accountFactory->createByToken( $token );

			try {
				$transactions = $account->getTransactionsSince( '-1 year' );
				$transactions = array_reverse( $transactions );
			} catch ( \Exception $e ) {
				// Only for debug purposes, will be deleted in version 1.0
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( $e->getMessage() );
				}

				return '<div>' . __( 'Chyba při spojení s Fio bankou.', 'fio-bank-transactions' ) . ' <a href="javascript:location.reload(true);">' . __( 'Zkuste to znovu.', 'fio-bank-transactions' ) . '</a></div>';
			}

			return $this->prepareTable( $transactions );

		}

		return '';
	}

}
