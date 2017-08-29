<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

use FioTransactions\Api\AccountFactory;

final class Frontend {

	private $accountFactory;

	public function __construct( AccountFactory $accountFactory ) {
		$this->accountFactory = $accountFactory;
		$this->frontendDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		( new Shortcode( $this->accountFactory ) );
	}

}
