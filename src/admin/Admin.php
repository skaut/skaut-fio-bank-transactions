<?php

declare( strict_types=1 );

namespace FioTransactions\Admin;

final class Admin {

	private $settings;
	private $adminDirUrl = '';

	public function __construct( Settings $settings ) {
		$this->settings    = $settings;
		$this->adminDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueStylesAndScripts' ] );
	}

	public function enqueueStylesAndScripts() {
		wp_enqueue_style(
			FIOTRANSACTIONS_NAME,
			$this->adminDirUrl . 'css/fio-admin.css',
			[],
			FIOTRANSACTIONS_VERSION,
			'all'
		);
	}

}
