<?php

declare( strict_types=1 );

namespace FioTransactions\General;

use FioTransactions\Accounts\AccountsInit;
use FioTransactions\Blocks\Block;
use FioTransactions\Frontend\Renderer;

/**
 * Functionality loaded both in the administration and on the frontend.
 */
final class General {

	public function __construct( AccountsInit $accountsInit, Renderer $renderer ) {
		( new Block( $accountsInit, $renderer ) );
	}
}
