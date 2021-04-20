<?php

declare( strict_types=1 );

namespace FioTransactions\General;

use FioTransactions\Accounts\AccountsInit;

final class General {

	private $accountsInit;

	public function __construct( AccountsInit $accountsInit ) {
		$this->accountsInit = $accountsInit;
	}

}
