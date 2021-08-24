<?php

declare( strict_types=1 );

namespace FioTransactions\Admin;

use FioTransactions\Accounts\AccountsInit;

final class Shortcode {

	private $accountsInit;
	private $adminDirUrl = '';

	public function __construct( AccountsInit $accountsInit ) {
		$this->accountsInit = $accountsInit;
		$this->adminDirUrl  = plugin_dir_url( __FILE__ ) . 'public/';
		$this->initHooks();
	}

	private function initHooks() {
		add_action( 'admin_footer', array( $this, 'initAvailableAccounts' ) );

		add_action(
			'admin_init',
			function () {
				if ( get_user_option( 'rich_editing' ) ) {
					add_filter( 'mce_external_plugins', array( $this, 'registerTinymcePlugin' ) );
					add_filter( 'mce_buttons', array( $this, 'addTinymceButton' ) );
				}
			}
		);
	}

	public function registerTinymcePlugin( array $plugins = array() ): array {
		$plugins['fio_bank_transactions_accounts'] = $this->adminDirUrl . 'js/fio-tinymceAccountsButton.js';

		return $plugins;
	}

	public function addTinymceButton( array $buttons = array() ): array {
		$buttons[] = 'fio_bank_transactions_accounts';

		return $buttons;
	}

	public function initAvailableAccounts() {
		?>
		<script>
			window.accountOptions = [];

			<?php
			foreach ( (array) $this->accountsInit->getAllAccounts() as $account ) {
				echo 'window.accountOptions.push({text: "' . esc_js( $account->post_title ) . '", value: "' . esc_js( $account->ID ) . '"});';
			}
			?>
		</script>
		<?php
	}

}
