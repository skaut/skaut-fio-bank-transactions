<?php
/**
 * Removes all data of the plugin when it is deleted in the WordPress administration.
 *
 * WordPress runs this file without loading the plugin, so it does not depend on the plugin code
 * (or on its compatibility checks). It removes:
 * - all accounts, in any status (published, draft, trash, auto-draft) and their revisions,
 * - the Fio API tokens and any other post meta of the plugin, even of orphaned posts,
 * - options and cached transactions,
 * - the plugin from the list of active plugins of the site (otherwise a reinstalled plugin would be
 *   active again on a site of a network right away) and the uninstall hook of older versions,
 * - the encryption key file (the tokens it protected are gone).
 *
 * On a multisite network this is done for every site of the network: the plugin is deleted from the
 * network admin, where WordPress runs this file only in the context of the main site.
 *
 * @package skaut-fio-bank-transactions
 */

namespace FioTransactions;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

const UNINSTALL_PREFIX = 'fio_bank_transactions_';

/**
 * Removes the data of the plugin from the current site.
 */
function uninstall_site() {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- One-off cleanup, nothing to cache.

	// Accounts in every status - get_posts() with post_status "any" skips trash and auto-drafts.
	$accountIds = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'fio_accounts' ) );
	foreach ( $accountIds as $accountId ) {
		// Also deletes the revisions and the post meta (tokens).
		wp_delete_post( intval( $accountId ), true );
	}

	// Any meta of the plugin that is left (e.g. from posts deleted outside WordPress).
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s", $wpdb->esc_like( UNINSTALL_PREFIX ) . '%' ) );

	// Options and cached transactions (transients).
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( UNINSTALL_PREFIX ) . '%',
			$wpdb->esc_like( '_transient_' . UNINSTALL_PREFIX ) . '%',
			$wpdb->esc_like( '_transient_timeout_' . UNINSTALL_PREFIX ) . '%'
		)
	);

	// phpcs:enable

	// The plugin is deleted, so it is no longer active here. WordPress itself removes it only from the
	// main site; a site of a network would keep it in its list and a reinstalled plugin would be
	// active again right away.
	$pluginFile    = strval( constant( 'WP_UNINSTALL_PLUGIN' ) );
	$activePlugins = get_option( 'active_plugins' );
	if ( is_array( $activePlugins ) && in_array( $pluginFile, $activePlugins, true ) ) {
		update_option( 'active_plugins', array_values( array_diff( $activePlugins, array( $pluginFile ) ) ) );
	}

	// Uninstall hook registered by older versions in the options of this site.
	$uninstallPlugins = get_option( 'uninstall_plugins' );
	if ( is_array( $uninstallPlugins ) && isset( $uninstallPlugins[ $pluginFile ] ) ) {
		unset( $uninstallPlugins[ $pluginFile ] );
		update_option( 'uninstall_plugins', $uninstallPlugins );
	}
}

/**
 * Runs the cleanup on every site (a function, so the variables do not leak into the global scope).
 */
function uninstall() {
	if ( is_multisite() ) {
		$siteIds = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);
		foreach ( $siteIds as $siteId ) {
			switch_to_blog( intval( $siteId ) );
			uninstall_site();
			restore_current_blog();
		}
	} else {
		uninstall_site();
	}

	if ( file_exists( strval( constant( 'WP_CONTENT_DIR' ) ) . '/fio-bank-transactions-key.php' ) ) {
		wp_delete_file( strval( constant( 'WP_CONTENT_DIR' ) ) . '/fio-bank-transactions-key.php' );
	}
}

uninstall();
