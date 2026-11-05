<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

use FioTransactions\Accounts\AccountsInit;
use FioTransactions\Api\IAccountFactory;
use FioTransactions\Utils\Helpers;

/**
 * Renders the transactions table of the block.
 */
final class Renderer {

	const DEFAULT_DAYS = 90;
	/**
	 * Without an extra authorization in the internet banking the Fio API returns only the last 90 days.
	 */
	const MAX_DAYS        = 90;
	const DEFAULT_PERIOD  = 'days';
	const DEFAULT_COLUMNS = 'date,amount,account,message,comment,type';

	/**
	 * Available periods: key => label.
	 */
	const PERIODS = array(
		'days'        => 'Posledních N dní',
		'last_7_days' => 'Posledních 7 dní',
		'this_month'  => 'Tento měsíc',
		'last_month'  => 'Minulý měsíc',
	);

	/**
	 * Available columns: key => header.
	 */
	const COLUMNS = array(
		'date'    => 'Datum',
		'amount'  => 'Částka',
		'account' => 'Protiúčet',
		'name'    => 'Název protiúčtu',
		'vs'      => 'VS',
		'ss'      => 'SS',
		'ks'      => 'KS',
		'message' => 'Zpráva pro příjemce',
		'comment' => 'Poznámka',
		'type'    => 'Typ',
	);

	private $accountFactory;

	public function __construct( IAccountFactory $accountFactory ) {
		$this->accountFactory = $accountFactory;
	}

	/**
	 * Normalizes user supplied block attributes.
	 *
	 * @param array<string, mixed> $atts Raw attributes.
	 * @return array{account: int, period: string, days: int, columns: array<int, string>, ss: string, vs_prefix: string}
	 */
	public static function normalizeAttributes( array $atts ): array {
		$atts = array_merge(
			array(
				'account'   => 0,
				'period'    => self::DEFAULT_PERIOD,
				'days'      => self::DEFAULT_DAYS,
				'columns'   => self::DEFAULT_COLUMNS,
				'ss'        => '',
				'vs_prefix' => '',
			),
			$atts
		);

		$days = absint( $atts['days'] );
		if ( $days < 1 ) {
			$days = self::DEFAULT_DAYS;
		}

		$period = strtolower( trim( strval( $atts['period'] ) ) );
		if ( ! array_key_exists( $period, self::PERIODS ) ) {
			$period = self::DEFAULT_PERIOD;
		}

		$columns = array_values(
			array_unique(
				array_intersect(
					array_map( 'trim', explode( ',', strtolower( strval( $atts['columns'] ) ) ) ),
					array_keys( self::COLUMNS )
				)
			)
		);
		if ( array() === $columns ) {
			$columns = explode( ',', self::DEFAULT_COLUMNS );
		}

		return array(
			'account'   => absint( $atts['account'] ),
			'period'    => $period,
			'days'      => min( $days, self::MAX_DAYS ),
			'columns'   => $columns,
			'ss'        => preg_replace( '/\D/', '', strval( $atts['ss'] ) ),
			'vs_prefix' => preg_replace( '/\D/', '', strval( $atts['vs_prefix'] ) ),
		);
	}

	/**
	 * @param array<string, mixed> $atts Raw attributes.
	 */
	public function render( array $atts ): string {
		$atts = self::normalizeAttributes( $atts );

		if ( $atts['account'] < 1 || ! $this->isAllowed() ) {
			return '';
		}

		$accountsWpQuery = new \WP_Query(
			array(
				'post_type'      => AccountsInit::ACCOUNTS_TYPE_SLUG,
				'p'              => $atts['account'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! $accountsWpQuery->have_posts() ) {
			return '';
		}

		$token = get_post_meta( intval( ( $accountsWpQuery->posts ?? array( 0 ) )[0] ), FIOTRANSACTIONS_NAME . '_token', true );
		if ( ! is_string( $token ) || '' === $token ) {
			return '';
		}

		list( $from, $until ) = self::getDateRange( $atts['period'], $atts['days'], new \DateTimeImmutable( 'now', wp_timezone() ) );
		$transactions         = $this->getTransactions( $token, $from, $until );
		if ( null === $transactions ) {
			return '<div>' . esc_html__( 'Chyba při spojení s Fio bankou.', 'skaut-fio-bank-transactions' ) . ' <a href="javascript:location.reload(true);">' . esc_html__( 'Zkuste to znovu.', 'skaut-fio-bank-transactions' ) . '</a></div>';
		}

		$transactions = self::filterTransactions( $transactions, $atts['ss'], $atts['vs_prefix'] );

		wp_enqueue_style( FIOTRANSACTIONS_NAME );
		wp_enqueue_script( FIOTRANSACTIONS_NAME );

		return $this->prepareTable( $transactions, $atts['columns'] );
	}

	/**
	 * The block may only be used by trusted users - otherwise e.g. a contributor could
	 * display transactions of any account in a post preview just by guessing its ID.
	 */
	private function isAllowed(): bool {
		$post = get_post();
		if ( $post instanceof \WP_Post && ! Helpers::userCanEmbedAccounts( intval( $post->post_author ) ) ) {
			return false;
		}

		// Editor previews (REST block renderer, admin AJAX) may run without a post context.
		$isRestRequest = function_exists( 'wp_is_serving_rest_request' ) ? wp_is_serving_rest_request() : ( defined( 'REST_REQUEST' ) && true === REST_REQUEST );
		if ( $isRestRequest || wp_doing_ajax() || is_admin() ) {
			return Helpers::userCanEmbedAccounts( get_current_user_id() );
		}

		return true;
	}

	/**
	 * Returns transactions (newest first) as plain arrays, cached in a transient.
	 *
	 * The Fio API allows only one request per token every 30 seconds, so without the cache busy pages
	 * regularly failed (issue #453). When the API fails, the last successfully downloaded data is used.
	 *
	 * @return array<int, array<string, string|float>>|null Null when no data is available.
	 */
	/**
	 * Returns the first and the last day (Y-m-d) of the period.
	 *
	 * @return array{0: string, 1: string}
	 */
	public static function getDateRange( string $period, int $days, \DateTimeImmutable $today ): array {
		$today = $today->setTime( 0, 0 );

		switch ( $period ) {
			case 'last_7_days':
				$from = $today->modify( '-6 days' );
				break;
			case 'this_month':
				$from = $today->modify( 'first day of this month' );
				break;
			case 'last_month':
				$from  = $today->modify( 'first day of last month' );
				$today = $today->modify( 'last day of last month' );
				break;
			default:
				$from = $today->modify( '-' . max( 0, min( $days, self::MAX_DAYS ) - 1 ) . ' days' );
		}

		return array( $from->format( 'Y-m-d' ), $today->format( 'Y-m-d' ) );
	}

	private function getTransactions( string $token, string $from, string $until ): ?array {
		$cacheKey   = FIOTRANSACTIONS_NAME . '_tx_' . md5( $token . '|' . $from . '|' . $until );
		$staleKey   = $cacheKey . '_stale';
		$cached     = get_transient( $cacheKey );
		$expiration = intval( apply_filters( FIOTRANSACTIONS_NAME . '_cache_expiration', 15 * 60 ) );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		try {
			$account      = $this->accountFactory->createByToken( $token );
			$transactions = array_reverse( array_map( array( self::class, 'transactionToArray' ), $account->getTransactionsFromTo( $from, $until ) ) );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
				// The exception message contains the request URL including the secret API token - never log it unredacted.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Fio bank transactions: ' . get_class( $e ) . ': ' . str_replace( $token, '***', $e->getMessage() ) );
			}

			$stale = get_transient( $staleKey );

			return is_array( $stale ) ? $stale : null;
		}

		set_transient( $cacheKey, $transactions, max( 30, $expiration ) );
		set_transient( $staleKey, $transactions, 7 * 24 * 60 * 60 );

		return $transactions;
	}

	/**
	 * @param object $transaction FioApi transaction.
	 * @return array<string, string|float>
	 */
	public static function transactionToArray( $transaction ): array {
		return array(
			'date'     => $transaction->getDate()->format( 'Y-m-d' ),
			'amount'   => $transaction->getAmount(),
			'currency' => $transaction->getCurrency(),
			'account'  => strval( $transaction->getSenderAccountNumber() ),
			'bankCode' => strval( $transaction->getSenderBankCode() ),
			'name'     => strval( $transaction->getSenderName() ),
			'vs'       => strval( $transaction->getVariableSymbol() ),
			'ss'       => strval( $transaction->getSpecificSymbol() ),
			'ks'       => strval( $transaction->getConstantSymbol() ),
			'message'  => strval( $transaction->getUserMessage() ),
			'comment'  => strval( $transaction->getComment() ),
			'type'     => $transaction->getTransactionType(),
		);
	}

	/**
	 * @param array<int, array<string, string|float>> $transactions
	 * @return array<int, array<string, string|float>>
	 */
	public static function filterTransactions( array $transactions, string $specificSymbol, string $variableSymbolPrefix ): array {
		return array_values(
			array_filter(
				$transactions,
				static function ( array $transaction ) use ( $specificSymbol, $variableSymbolPrefix ): bool {
					if ( '' !== $specificSymbol && ltrim( strval( $transaction['ss'] ), '0' ) !== ltrim( $specificSymbol, '0' ) ) {
						return false;
					}

					return '' === $variableSymbolPrefix || 0 === strpos( strval( $transaction['vs'] ), $variableSymbolPrefix );
				}
			)
		);
	}

	/**
	 * @param array<int, array<string, string|float>> $transactions
	 * @param array<int, string>                      $columns
	 */
	private function prepareTable( array $transactions, array $columns ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase -- Public hook name kept for backwards compatibility.
		do_action( FIOTRANSACTIONS_NAME . '_transactionsForTable', $transactions );

		$html = '<table class="fioTransactionsTable"><thead><tr>';
		foreach ( $columns as $column ) {
			$html .= '<th>' . esc_html( self::COLUMNS[ $column ] ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';

		foreach ( $transactions as $transaction ) {
			$html .= '<tr>';
			foreach ( $columns as $column ) {
				$html .= $this->renderCell( $column, $transaction );
			}
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase -- Public hook name kept for backwards compatibility.
		return strval( apply_filters( FIOTRANSACTIONS_NAME . '_tableHtml', $html ) );
	}

	/**
	 * @param array<string, string|float> $transaction
	 */
	private function renderCell( string $column, array $transaction ): string {
		switch ( $column ) {
			case 'date':
				$date = \DateTimeImmutable::createFromFormat( 'Y-m-d', strval( $transaction['date'] ) );

				return '<td class="' . FIOTRANSACTIONS_NAME . '_date" data-order="' . esc_attr( strval( $transaction['date'] ) ) . '">' . esc_html( false !== $date ? $date->format( 'd.m.Y' ) : '' ) . '</td>';
			case 'amount':
				$amount = floatval( $transaction['amount'] );
				$class  = FIOTRANSACTIONS_NAME . '_amount ' . FIOTRANSACTIONS_NAME . ( $amount > 0 ? '_income' : '_outcome' );

				return '<td class="' . esc_attr( $class ) . '" data-order="' . esc_attr( strval( $amount ) ) . '">' . esc_html( number_format( $amount, 2, ',', ' ' ) . ' ' . strval( $transaction['currency'] ) ) . '</td>';
			case 'account':
				$counterAccount = strval( $transaction['account'] );
				if ( '' !== $transaction['bankCode'] ) {
					$counterAccount .= '/' . strval( $transaction['bankCode'] );
				}

				return '<td>' . esc_html( $counterAccount ) . '</td>';
			default:
				return '<td>' . esc_html( strval( $transaction[ $column ] ) ) . '</td>';
		}
	}
}
