<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

/**
 * Settings of the transactions table: defaults, allowed values, labels and normalization of the block attributes.
 */
final class TableSettings {

	const DEFAULT_DAYS = 90;
	/**
	 * Without an extra authorization in the internet banking the Fio API returns only the last 90 days.
	 */
	const MAX_DAYS        = 90;
	const DEFAULT_PERIOD  = 'days';
	const DEFAULT_COLUMNS = 'date,amount,account,message,comment,type';
	/**
	 * Allowed numbers of rows per page; -1 = all.
	 */
	const PAGE_LENGTHS        = array( 10, 25, 50, 100, -1 );
	const DEFAULT_PAGE_LENGTH = 10;

	/**
	 * Available periods: key => translated label.
	 *
	 * @return array<string, string>
	 */
	public static function getPeriods(): array {
		return array(
			'days'        => __( 'Last N days', 'skaut-fio-bank-transactions' ),
			'last_7_days' => __( 'Last 7 days', 'skaut-fio-bank-transactions' ),
			'this_month'  => __( 'This month', 'skaut-fio-bank-transactions' ),
			'last_month'  => __( 'Last month', 'skaut-fio-bank-transactions' ),
		);
	}

	/**
	 * Available columns: key => translated header.
	 *
	 * @return array<string, string>
	 */
	public static function getColumns(): array {
		return array(
			'date'    => __( 'Date', 'skaut-fio-bank-transactions' ),
			'amount'  => __( 'Amount', 'skaut-fio-bank-transactions' ),
			'account' => __( 'Counterparty account', 'skaut-fio-bank-transactions' ),
			'name'    => __( 'Counterparty name', 'skaut-fio-bank-transactions' ),
			/* translators: Abbreviation of "variable symbol", a payment identifier used by Czech and Slovak banks. */
			'vs'      => __( 'VS', 'skaut-fio-bank-transactions' ),
			/* translators: Abbreviation of "specific symbol", a payment identifier used by Czech and Slovak banks. */
			'ss'      => __( 'SS', 'skaut-fio-bank-transactions' ),
			/* translators: Abbreviation of "constant symbol", a payment identifier used by Czech and Slovak banks. */
			'ks'      => __( 'KS', 'skaut-fio-bank-transactions' ),
			'message' => __( 'Message for recipient', 'skaut-fio-bank-transactions' ),
			'comment' => __( 'Note', 'skaut-fio-bank-transactions' ),
			'type'    => __( 'Type', 'skaut-fio-bank-transactions' ),
		);
	}

	/**
	 * Normalizes user supplied block attributes.
	 *
	 * @param array<string, mixed> $atts Raw attributes.
	 * @return array{account: int, period: string, days: int, columns: array<int, string>, ss: string, vs_prefix: string, search: bool, paging: bool, page_length: int}
	 */
	public static function normalizeAttributes( array $atts ): array {
		$atts = array_merge(
			array(
				'account'     => 0,
				'period'      => self::DEFAULT_PERIOD,
				'days'        => self::DEFAULT_DAYS,
				'columns'     => self::DEFAULT_COLUMNS,
				'ss'          => '',
				'vs_prefix'   => '',
				'search'      => true,
				'paging'      => true,
				'page_length' => self::DEFAULT_PAGE_LENGTH,
			),
			$atts
		);

		$pageLength = intval( $atts['page_length'] );
		if ( ! in_array( $pageLength, self::PAGE_LENGTHS, true ) ) {
			$pageLength = self::DEFAULT_PAGE_LENGTH;
		}

		$days = absint( $atts['days'] );
		if ( $days < 1 ) {
			$days = self::DEFAULT_DAYS;
		}

		$period = strtolower( trim( strval( $atts['period'] ) ) );
		if ( ! array_key_exists( $period, self::getPeriods() ) ) {
			$period = self::DEFAULT_PERIOD;
		}

		$columns = array_values(
			array_unique(
				array_intersect(
					array_map( 'trim', explode( ',', strtolower( strval( $atts['columns'] ) ) ) ),
					array_keys( self::getColumns() )
				)
			)
		);
		if ( array() === $columns ) {
			$columns = explode( ',', self::DEFAULT_COLUMNS );
		}

		return array(
			'account'     => absint( $atts['account'] ),
			'period'      => $period,
			'days'        => min( $days, self::MAX_DAYS ),
			'columns'     => $columns,
			'ss'          => preg_replace( '/\D/', '', strval( $atts['ss'] ) ),
			'vs_prefix'   => preg_replace( '/\D/', '', strval( $atts['vs_prefix'] ) ),
			'search'      => self::toBool( $atts['search'] ),
			'paging'      => self::toBool( $atts['paging'] ),
			'page_length' => $pageLength,
		);
	}

	/**
	 * @param mixed $value Boolean attribute value.
	 */
	private static function toBool( $value ): bool {
		return ! in_array( $value, array( false, 0, '0', 'false', 'no', 'off', '' ), true );
	}

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
}
