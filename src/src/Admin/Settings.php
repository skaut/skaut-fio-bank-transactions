<?php

declare( strict_types=1 );

namespace FioTransactions\Admin;

use FioTransactions\Utils\Helpers;

final class Settings {

	const HELP_PAGE_URL = 'https://napoveda.skaut.cz/dobryweb/skaut-fio-bank-transactions';

	public function __construct() {
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
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . FIOTRANSACTIONS_NAME ) ) . '">' . esc_html__( 'Settings', 'skaut-fio-bank-transactions' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	public function addHelpLinkToPluginsTable( array $links = array() ): array {
		$mylinks = array(
			'<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Help', 'skaut-fio-bank-transactions' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	public function setupSettingPage() {
		add_menu_page(
			__( 'General', 'skaut-fio-bank-transactions' ),
			__( 'Fio Bank', 'skaut-fio-bank-transactions' ),
			Helpers::getFioManagerCapability(),
			FIOTRANSACTIONS_NAME,
			array( $this, 'printSettingPage' ),
			'dashicons-money-alt'
		);
	}

	public function printSettingPage() {
		?>
		<script>
			document.location.href = '<?php echo esc_js( admin_url( 'edit.php?post_type=fio_accounts' ) ); ?>';
		</script>
		<?php
		if ( ! current_user_can( Helpers::getFioManagerCapability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'skaut-fio-bank-transactions' ) );
		}

		settings_errors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Test', 'skaut-fio-bank-transactions' ); ?></h1>
			<form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
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
			__( 'Test', 'skaut-fio-bank-transactions' ),
			function () {
				printf(
					/* translators: 1: opening link tag, 2: closing link tag */
					esc_html__( 'You can find the plugin setup guide in the %1$shelp%2$s.', 'skaut-fio-bank-transactions' ),
					'<a href="' . esc_url( self::HELP_PAGE_URL ) . '" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
			},
			FIOTRANSACTIONS_NAME
		);

		add_settings_field(
			FIOTRANSACTIONS_NAME . '_test',
			__( 'Test', 'skaut-fio-bank-transactions' ),
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
		echo '<input name="' . esc_attr( FIOTRANSACTIONS_NAME ) . '_test" id="' . esc_attr( FIOTRANSACTIONS_NAME ) . '_test" type="text" value="' . esc_attr( get_option( FIOTRANSACTIONS_NAME . '_test' ) ) . '" class="regular-text" />';
	}
}
