<?php

declare( strict_types=1 );

namespace FioTransactions\Api;

use FioApi\Downloader;

trait TAccount {

	private $downloader;

	public function __construct( Downloader $downloader ) {
		$this->downloader = $downloader;
	}

	protected function getTransactionsListFromTo( \DateTimeInterface $from, \DateTimeInterface $to ) {
		$transactionList = $this->downloader->downloadFromTo( $from, $to );

		return $transactionList;
	}

	protected function getTransactions( \DateTimeInterface $from, \DateTimeInterface $to ): array {
		return $this->getTransactionsListFromTo( $from, $to )->getTransactions();
	}

}
