<?php

declare( strict_types=1 );

namespace FioTransactions\Api;

use FioTransactions\Vendor\FioApi\Downloader;

trait TAccount {

	private $downloader;

	public function __construct( Downloader $downloader ) {
		$this->downloader = $downloader;
	}

	protected function getTransactionsListFromTo( \DateTimeInterface $from, \DateTimeInterface $until ) {
		$transactionList = $this->downloader->downloadFromTo( $from, $until );

		return $transactionList;
	}

	protected function getTransactions( \DateTimeInterface $from, \DateTimeInterface $until ): array {
		return $this->getTransactionsListFromTo( $from, $until )->getTransactions();
	}
}
