<?php

declare( strict_types=1 );

namespace FioTransactions\Admin;

use FioTransactions\Utils\Helpers;

final class Settings {

	const HELP_PAGE_URL = 'https://napoveda.skaut.cz/dobryweb/skaut-fio-bank-transactions';

	private $adminDirUrl = '';

	public function __construct() {
		$this->adminDirUrl = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_filter(
			'plugin_action_links_' . FIOTRANSACTIONS_PLUGIN_BASENAME,
			array(
				$this,
				'addSettingsLinkToPluginsTable',
			)
		);
		add_filter(
			'plugin_action_links_' . FIOTRANSACTIONS_PLUGIN_BASENAME,
			array(
				$this,
				'addHelpLinkToPluginsTable',
			)
		);

		add_action( 'admin_menu', array( $this, 'setupSettingPage' ), 5 );
		add_action( 'admin_init', array( $this, 'setupSettingFields' ) );
	}

	public function addSettingsLinkToPluginsTable( array $links = array() ): array {
		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=' . FIOTRANSACTIONS_NAME ) . '">' . __( 'Settings' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	public function addHelpLinkToPluginsTable( array $links = array() ): array {
		$mylinks = array(
			'<a href="' . self::HELP_PAGE_URL . '" target="_blank">' . __( 'Help' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	public function setupSettingPage() {
		add_menu_page(
			__( 'Obecné', 'fio-transactions' ),
			__( 'Fio Bank', 'fio-transactions' ),
			Helpers::getFioManagerCapability(),
			FIOTRANSACTIONS_NAME,
			array( $this, 'printSettingPage' ),
			$this->adminDirUrl . 'img/fio.png'
		);

		/*
		add_submenu_page(
			FIOTRANSACTIONS_NAME,
			__( 'Obecné', 'fio-transactions' ),
			__( 'Obecné', 'fio-transactions' ),
			Helpers::getFioManagerCapability(),
			FIOTRANSACTIONS_NAME,
			[ $this, 'printSettingPage' ]
		);*/
	}

	public function printSettingPage() {
		?>
		<script>
			document.location.href = '<?php echo esc_js( admin_url( 'edit.php?post_type=fio_accounts' ) ); ?>';
		</script>
		<?php
		if ( ! current_user_can( Helpers::getFioManagerCapability() ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php _e( 'Test', 'fio-transactions' ); ?></h1>
			<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php
				settings_fields( FIOTRANSACTIONS_NAME );
				do_settings_sections( FIOTRANSACTIONS_NAME );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function setupSettingFields() {
		add_settings_section(
			FIOTRANSACTIONS_NAME . '_settings',
			__( 'Test', 'fio-transactions' ),
			function () {
				echo sprintf( __( 'Návod pro nastavení pluginu najdete v <a href="%s" target="_blank">nápovědě</a>.', 'fio-bank-transactions' ), self::HELP_PAGE_URL );
			},
			FIOTRANSACTIONS_NAME
		);

		add_settings_field(
			FIOTRANSACTIONS_NAME . '_test',
			__( 'Test', 'fio-transactions' ),
			array( $this, 'fieldTest' ),
			FIOTRANSACTIONS_NAME,
			FIOTRANSACTIONS_NAME . '_settings'
		);

		register_setting(
			FIOTRANSACTIONS_NAME,
			FIOTRANSACTIONS_NAME . '_test',
			array(
				'type'              => 'text',
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	public function fieldTest() {
		echo '<input name="' . FIOTRANSACTIONS_NAME . '_test" id="' . FIOTRANSACTIONS_NAME . '_test" type="text" value="' . esc_attr( get_option( FIOTRANSACTIONS_NAME . '_test' ) ) . '" class="regular-text" />';
	}

}
