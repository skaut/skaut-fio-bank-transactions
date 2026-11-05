<?php

declare( strict_types=1 );

namespace FioTransactions\Api;

interface IAccount {
	public function getTransactionsFromTo( string $from, string $until ): array;

	public function getTransactionsSince( string $since ): array;
}
