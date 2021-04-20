<?php

declare( strict_types=1 );

namespace FioTransactions\Api;

use FioApi\Downloader;

final class Account implements IAccount {

	use TAccount {
		TAccount::__construct as private __parentConstruct;
	}

	public function __construct( Downloader $downloader ) {
		$this->__parentConstruct( $downloader );
	}

	public function getTransactionsFromTo( string $from, string $to ): array {
		return $this->getTransactions( new \DateTimeImmutable( $from ), new \DateTimeImmutable( $to ) );
	}

	public function getTransactionsSince( string $since ): array {
		return $this->getTransactions( new \DateTimeImmutable( $since ), new \DateTimeImmutable() );
	}

}
