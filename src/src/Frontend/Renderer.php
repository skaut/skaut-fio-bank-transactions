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

	private $accountFactory;

	public function __construct( IAccountFactory $accountFactory ) {
		$this->accountFactory = $accountFactory;
	}

	/**
	 * @param array<string, mixed> $atts Raw attributes.
	 */
	public function render( array $atts ): string {
		$atts = TableSettings::normalizeAttributes( $atts );

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

		$transactions = $this->getTransactions( $token );
		if ( null === $transactions ) {
			return '<div>' . esc_html__( 'Could not connect to Fio banka.', 'skaut-fio-bank-transactions' ) . ' <a href="javascript:location.reload(true);">' . esc_html__( 'Try again.', 'skaut-fio-bank-transactions' ) . '</a></div>';
		}

		list( $from, $until ) = TableSettings::getDateRange( $atts['period'], $atts['days'], new \DateTimeImmutable( 'now', wp_timezone() ) );
		$transactions         = self::filterByDate( $transactions, $from, $until );
		$transactions         = self::filterTransactions( $transactions, $atts['ss'], $atts['vs_prefix'] );

		wp_enqueue_style( FIOTRANSACTIONS_NAME );
		wp_enqueue_script( FIOTRANSACTIONS_NAME );

		return $this->prepareTable( $transactions, $atts['columns'], $atts );
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
	private function getTransactions( string $token ): ?array {
		// One download (the whole 90 day window) per token, shared by all blocks and periods - every
		// period needs only a subset, and a separate request per period would hit the API limit.
		$cacheKey   = FIOTRANSACTIONS_NAME . '_tx_' . md5( $token );
		$staleKey   = $cacheKey . '_stale';
		$waitKey    = $cacheKey . '_wait';
		$cached     = get_transient( $cacheKey );
		$expiration = intval( apply_filters( FIOTRANSACTIONS_NAME . '_cache_expiration', 15 * 60 ) );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		// After a failed request do not call the API again for 30 seconds, otherwise every page load
		// (or every change in the block editor preview) would hit the rate limit again.
		if ( false !== get_transient( $waitKey ) ) {
			$stale = get_transient( $staleKey );

			return is_array( $stale ) ? $stale : null;
		}

		list( $from, $until ) = TableSettings::getDateRange( TableSettings::DEFAULT_PERIOD, TableSettings::MAX_DAYS, new \DateTimeImmutable( 'now', wp_timezone() ) );

		try {
			$account      = $this->accountFactory->createByToken( $token );
			$transactions = array_reverse( array_map( array( self::class, 'transactionToArray' ), $account->getTransactionsFromTo( $from, $until ) ) );
		} catch ( \Throwable $e ) {
			set_transient( $waitKey, 1, 30 );

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
	 * @param array<int, array<string, string|float>> $transactions
	 * @return array<int, array<string, string|float>>
	 */
	public static function filterByDate( array $transactions, string $from, string $until ): array {
		return array_values(
			array_filter(
				$transactions,
				static function ( array $transaction ) use ( $from, $until ): bool {
					$date = strval( $transaction['date'] );

					return $date >= $from && $date <= $until;
				}
			)
		);
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
	/**
	 * @param array{search: bool, paging: bool, page_length: int} $options Table options.
	 */
	private function prepareTable( array $transactions, array $columns, array $options ): string {
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase -- Public hook name kept for backwards compatibility.
		do_action( FIOTRANSACTIONS_NAME . '_transactionsForTable', $transactions );

		$html  = '<div class="fioTransactions">';
		$html .= '<div class="fioTransactionsFilter" role="group" aria-label="' . esc_attr__( 'Show transactions', 'skaut-fio-bank-transactions' ) . '" hidden>';
		foreach ( array(
			'all' => __( 'All transactions', 'skaut-fio-bank-transactions' ),
			'in'  => __( 'Incoming', 'skaut-fio-bank-transactions' ),
			'out' => __( 'Outgoing', 'skaut-fio-bank-transactions' ),
		) as $direction => $label ) {
			$html .= '<button type="button" class="fioTransactionsFilter__button" data-direction="' . esc_attr( $direction ) . '" aria-pressed="' . ( 'all' === $direction ? 'true' : 'false' ) . '">' . esc_html( $label ) . '</button>';
		}
		$html   .= '</div>';
		$html   .= '<table class="fioTransactionsTable" data-fio-search="' . ( $options['search'] ? 'true' : 'false' ) . '" data-fio-paging="' . ( $options['paging'] ? 'true' : 'false' ) . '" data-fio-page-length="' . esc_attr( strval( $options['page_length'] ) ) . '"><thead><tr>';
		$headers = TableSettings::getColumns();
		foreach ( $columns as $column ) {
			$html .= '<th>' . esc_html( $headers[ $column ] ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';

		foreach ( $transactions as $transaction ) {
			$html .= '<tr data-direction="' . ( floatval( $transaction['amount'] ) < 0 ? 'out' : 'in' ) . '">';
			foreach ( $columns as $column ) {
				$html .= $this->renderCell( $column, $transaction );
			}
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

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
