<?php
/**
 * Removes all data of the plugin when it is deleted in the WordPress administration.
 *
 * WordPress runs this file without loading the plugin, so it does not depend on the plugin code
 * (or on its compatibility checks). It removes:
 * - all accounts, in any status (published, draft, trash, auto-draft) and their revisions,
 * - the Fio API tokens and any other post meta of the plugin, even of orphaned posts,
 * - options and cached transactions,
 * - the encryption key file (the tokens it protected are gone).
 *
 * @package skaut-fio-bank-transactions
 */

namespace FioTransactions;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

const UNINSTALL_PREFIX = 'fio_bank_transactions_';

/**
 * Runs the cleanup (a function, so the variables do not leak into the global scope).
 */
function uninstall() {
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

	if ( file_exists( strval( constant( 'WP_CONTENT_DIR' ) ) . '/fio-bank-transactions-key.php' ) ) {
		wp_delete_file( strval( constant( 'WP_CONTENT_DIR' ) ) . '/fio-bank-transactions-key.php' );
	}
}

uninstall();
