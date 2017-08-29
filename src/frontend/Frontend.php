<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

use FioTransactions\Apis\FioGateway;

final class Frontend {

	private $frontendDirUrl = '';

	public function __construct( FioGateway $fioGateway ) {
		$this->fioGateway     = $fioGateway;
		$this->frontendDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueStylesAndScripts' ] );
	}

	public function enqueueStyles() {
		wp_enqueue_style(
			FIOTRANSACTIONS_NAME . '_frontend',
			$this->frontendDirUrl . 'css/fio-frontend.css',
			[],
			FIOTRANSACTIONS_VERSION,
			'all'
		);
	}

}
