<?php
/**
 * Plugin Name:       Fio bank - transactions
 * Plugin URI:        https://github.com/skaut/skaut-fio-bank-transactions
 * Description:       Zobrazování transakcí z Fio banky.
 * Version:           1.3.3
 * Author:            Junák - český skaut
 * Author URI:        https://github.com/skaut
 * Text Domain:       skaut-fio-bank-transactions
 * Requires at least: 7.0
 * Requires PHP:      8.3
 */

namespace FioTransactions;

use FioTransactions\Accounts\AccountsInit;
use FioTransactions\Admin\Admin;
use FioTransactions\Api\AccountFactory;
use FioTransactions\Frontend\Frontend;
use FioTransactions\Frontend\Renderer;
use FioTransactions\General\General;
use FioTransactions\Utils\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FIOTRANSACTIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FIOTRANSACTIONS_PATH', plugin_dir_path( __FILE__ ) );
define( 'FIOTRANSACTIONS_URL', plugin_dir_url( __FILE__ ) );
define( 'FIOTRANSACTIONS_NAME', 'fio_bank_transactions' );
define( 'FIOTRANSACTIONS_VERSION', '1.3.3' );

class FioTransactions {

	public function __construct() {
		$this->initHooks();

		// If incompatible version of WP / PHP or deactivating plugin right now => don't init.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only check of the core deactivation request.
		if ( ! $this->isCompatibleVersionOfWp() ||
			! $this->isCompatibleVersionOfPhp() ||
			( isset( $_GET['action'], $_GET['plugin'] ) &&
				'deactivate' === $_GET['action'] &&
				FIOTRANSACTIONS_PLUGIN_BASENAME === $_GET['plugin'] )
		) {
			return;
		}
		// phpcs:enable

		require __DIR__ . '/vendor/scoper-autoload.php';

		require __DIR__ . '/src/Accounts/AccountsInit.php';
		require __DIR__ . '/src/Accounts/Admin.php';
		require __DIR__ . '/src/Accounts/Columns.php';

		require __DIR__ . '/src/Admin/Admin.php';
		require __DIR__ . '/src/Admin/Settings.php';

		require __DIR__ . '/src/Api/IAccount.php';
		require __DIR__ . '/src/Api/IAccountFactory.php';
		require __DIR__ . '/src/Api/TAccount.php';
		require __DIR__ . '/src/Api/Account.php';
		require __DIR__ . '/src/Api/AccountFactory.php';

		require __DIR__ . '/src/Blocks/Block.php';

		require __DIR__ . '/src/Frontend/Frontend.php';
		require __DIR__ . '/src/Frontend/Renderer.php';

		require __DIR__ . '/src/General/General.php';

		require __DIR__ . '/src/Utils/Helpers.php';

		$this->init();
	}

	protected function initHooks() {
		add_action( 'admin_init', array( $this, 'checkVersionAndPossiblyDeactivatePlugin' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	protected function init() {
		$accountsInit = new AccountsInit();
		$renderer     = new Renderer( new AccountFactory() );

		( new General( $accountsInit, $renderer ) );
		if ( is_admin() ) {
			( new Admin() );
		} else {
			( new Frontend() );
		}
	}

	protected function isCompatibleVersionOfWp(): bool {
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '7.0', '>=' ) ) {
			return true;
		}

		return false;
	}

	protected function isCompatibleVersionOfPhp(): bool {
		if ( version_compare( PHP_VERSION, '8.3', '>=' ) ) {
			return true;
		}

		return false;
	}

	public function activation() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );
			wp_die( esc_html__( 'Plugin Fio Bank transactions vyžaduje verzi WordPress 7.0 nebo vyšší!', 'skaut-fio-bank-transactions' ) );
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );
			wp_die( esc_html__( 'Plugin Fio Bank transactions vyžaduje verzi PHP 8.3 nebo vyšší!', 'skaut-fio-bank-transactions' ) );
		}
	}

	public function deactivation() {
	}

	public static function uninstall() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Uninstall cleanup, nothing to cache.
		$options = $wpdb->get_results(
			$wpdb->prepare(
				"
SELECT `option_name`
FROM $wpdb->options
WHERE `option_name` LIKE %s
",
				$wpdb->esc_like( FIOTRANSACTIONS_NAME . '_' ) . '%'
			)
		);
		foreach ( $options as $option ) {
			delete_option( $option->option_name );
		}

		// Cached transactions.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->options WHERE `option_name` LIKE %s OR `option_name` LIKE %s",
				$wpdb->esc_like( '_transient_' . FIOTRANSACTIONS_NAME . '_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . FIOTRANSACTIONS_NAME . '_' ) . '%'
			)
		);
		// phpcs:enable

		// Accounts including their API tokens (post meta) and revisions.
		$accountIds = get_posts(
			array(
				'post_type'        => 'fio_accounts',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		foreach ( $accountIds as $accountId ) {
			wp_delete_post( intval( $accountId ), true );
		}

		return true;
	}

	public function checkVersionAndPossiblyDeactivatePlugin() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			if ( is_plugin_active( FIOTRANSACTIONS_PLUGIN_BASENAME ) ) {
				deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );

				Helpers::showAdminNotice( esc_html__( 'Plugin Fio Bank transactions vyžaduje verzi WordPress 7.0 nebo vyšší!', 'skaut-fio-bank-transactions' ), 'warning' );

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only hides the core "Plugin activated" notice.
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			if ( is_plugin_active( FIOTRANSACTIONS_PLUGIN_BASENAME ) ) {
				deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );

				Helpers::showAdminNotice( esc_html__( 'Plugin Fio Bank transactions vyžaduje verzi PHP 8.3 nebo vyšší!', 'skaut-fio-bank-transactions' ), 'warning' );

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only hides the core "Plugin activated" notice.
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}
}

global $fioTransactions;
$fioTransactions = new FioTransactions();
