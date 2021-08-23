<?php
/**
 * Plugin Name:       Fio bank - transactions
 * Plugin URI:        https://github.com/skaut/skaut-fio-bank-transactions
 * Description:       Zobrazování transakcí z Fio banky.
 * Version:           1.1.2
 * Author:            Junák - český skaut
 * Author URI:        https://github.com/skaut
 * Text Domain:       skaut-fio-bank-transactions
 */

namespace FioTransactions;

use FioTransactions\Services\Services;
use FioTransactions\Utils\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FIOTRANSACTIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FIOTRANSACTIONS_PATH', plugin_dir_path( __FILE__ ) );
define( 'FIOTRANSACTIONS_URL', plugin_dir_url( __FILE__ ) );
define( 'FIOTRANSACTIONS_NAME', 'fio_bank_transactions' );
define( 'FIOTRANSACTIONS_VERSION', '1.1.2' );

class FioTransactions {

	public function __construct() {
		$this->initHooks();

		// if incompatible version of WP / PHP or deactivating plugin right now => don´t init
		if ( ! $this->isCompatibleVersionOfWp() ||
		     ! $this->isCompatibleVersionOfPhp() ||
		     ( isset( $_GET['action'], $_GET['plugin'] ) &&
		       'deactivate' == $_GET['action'] &&
		       FIOTRANSACTIONS_PLUGIN_BASENAME == $_GET['plugin'] )
		) {
			return;
		}

		require FIOTRANSACTIONS_PATH . 'vendor/scoper-autoload.php';

		$this->init();
	}

	protected function initHooks() {
		add_action( 'admin_init', [ $this, 'checkVersionAndPossiblyDeactivatePlugin' ] );

		register_activation_hook( __FILE__, [ $this, 'activation' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );
		register_uninstall_hook( __FILE__, [ __CLASS__, 'uninstall' ] );
	}

	protected function init() {
		( Services::getServicesContainer()['general'] );
		if ( is_admin() ) {
			( Services::getServicesContainer()['admin'] );
		} else {
			( Services::getServicesContainer()['frontend'] );
		}
	}

	protected function isCompatibleVersionOfWp() {
		if ( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.9.6', '>=' ) ) {
			return true;
		}

		return false;
	}

	protected function isCompatibleVersionOfPhp() {
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			return true;
		}

		return false;
	}

	public function activation() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );
			wp_die( __( 'Plugin Fio Bank transactions vyžaduje verzi WordPress 4.9.6 nebo vyšší!', 'fio-bank-transactions' ) );
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );
			wp_die( __( 'Plugin Fio Bank transactions vyžaduje verzi PHP 7.4 nebo vyšší!', 'fio-bank-transactions' ) );
		}
	}

	public function deactivation() {

	}

	public static function uninstall() {
		global $wpdb;
		$options = $wpdb->get_results( $wpdb->prepare( "
SELECT `option_name`
FROM $wpdb->options
WHERE `option_name` LIKE %s
", [ FIOTRANSACTIONS_NAME . '_%' ] ) );
		foreach ( $options as $option ) {
			delete_option( $option->option_name );
		}

		return true;
	}

	public function checkVersionAndPossiblyDeactivatePlugin() {
		if ( ! $this->isCompatibleVersionOfWp() ) {
			if ( is_plugin_active( FIOTRANSACTIONS_PLUGIN_BASENAME ) ) {

				deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );

				Helpers::showAdminNotice( esc_html__( 'Plugin Fio Bank transactions vyžaduje verzi WordPress 4.9.6 nebo vyšší!', 'fio-bank-transactions' ), 'warning' );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}

		if ( ! $this->isCompatibleVersionOfPhp() ) {
			if ( is_plugin_active( FIOTRANSACTIONS_PLUGIN_BASENAME ) ) {

				deactivate_plugins( FIOTRANSACTIONS_PLUGIN_BASENAME );

				Helpers::showAdminNotice( esc_html__( 'Plugin Fio Bank transactions vyžaduje verzi PHP 7.4 nebo vyšší!', 'fio-bank-transactions' ), 'warning' );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}
}

global $fioTransactions;
$fioTransactions = new FioTransactions();
