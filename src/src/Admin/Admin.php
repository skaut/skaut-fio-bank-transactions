<?php

declare( strict_types=1 );

namespace FioTransactions\Admin;

use FioTransactions\Accounts\AccountsInit;

final class Admin {

	private $accountsInit;
	private $adminDirUrl = '';

	public function __construct( AccountsInit $accountsInit ) {
		$this->accountsInit = $accountsInit;
		$this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		( new Settings() );
		( new Shortcode( $this->accountsInit ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueStylesAndScripts' ) );
	}

	public function enqueueStylesAndScripts() {
		wp_enqueue_style(
			FIOTRANSACTIONS_NAME,
			$this->adminDirUrl . 'css/fio-admin.css',
			array(),
			FIOTRANSACTIONS_VERSION,
			'all'
		);
	}

}
