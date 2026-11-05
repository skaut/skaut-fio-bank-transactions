<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

final class Frontend {

	private $renderer;

	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
		$this->initHooks();
	}

	private function initHooks() {
		( new Shortcode( $this->renderer ) );

		add_action( 'wp_enqueue_scripts', array( self::class, 'registerStylesAndScripts' ) );
	}

	/**
	 * Registers the assets; they are enqueued by Renderer only on pages which contain a table.
	 */
	public static function registerStylesAndScripts() {
		$frontendDirUrl = plugin_dir_url( __FILE__ ) . 'public/';

		wp_register_style(
			FIOTRANSACTIONS_NAME . '_datatables',
			FIOTRANSACTIONS_URL . 'bundled/dataTables.dataTables.min.css',
			array(),
			FIOTRANSACTIONS_VERSION,
			'all'
		);

		wp_register_script(
			FIOTRANSACTIONS_NAME . '_datatables',
			FIOTRANSACTIONS_URL . 'bundled/dataTables.min.js',
			array(),
			FIOTRANSACTIONS_VERSION,
			true
		);

		wp_register_style(
			FIOTRANSACTIONS_NAME,
			$frontendDirUrl . 'css/fio-frontend.css',
			array( FIOTRANSACTIONS_NAME . '_datatables' ),
			FIOTRANSACTIONS_VERSION,
			'all'
		);

		wp_register_script(
			FIOTRANSACTIONS_NAME,
			$frontendDirUrl . 'js/fio-frontend.js',
			array( FIOTRANSACTIONS_NAME . '_datatables' ),
			FIOTRANSACTIONS_VERSION,
			true
		);

		wp_localize_script(
			FIOTRANSACTIONS_NAME,
			'fioTransactionsFrontendLocalize',
			array(
				'datatablesFilesUrl' => FIOTRANSACTIONS_URL . 'bundled/datatables-files',
			)
		);
	}
}
