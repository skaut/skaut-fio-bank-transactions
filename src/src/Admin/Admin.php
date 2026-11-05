<?php

declare( strict_types=1 );

namespace FioTransactions\Admin;

final class Admin {

	private $adminDirUrl = '';

	public function __construct() {
		$this->adminDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		( new Settings() );

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
