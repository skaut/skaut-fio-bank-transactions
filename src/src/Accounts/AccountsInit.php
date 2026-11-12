<?php

declare( strict_types=1 );

namespace FioTransactions\Accounts;

use FioTransactions\Utils\Helpers;
use FioTransactions\Utils\TokenStorage;

final class AccountsInit {

	const ACCOUNTS_TYPE_SINGULAR = 'fio_account';
	const ACCOUNTS_TYPE_SLUG     = 'fio_accounts';

	public function __construct() {
		$this->initHooks();
	}

	private function initHooks() {
		if ( is_admin() ) {
			( new Admin() );
		}

		add_action( 'init', array( $this, 'registerPostType' ) );

		if ( is_admin() ) {
			add_filter( 'default_content', array( $this, 'defaultContent' ) );
			add_filter( 'enter_title_here', array( $this, 'titlePlaceholder' ) );
			add_filter( 'post_updated_messages', array( $this, 'updatedMessages' ) );
		}
	}

	public function registerPostType() {
		$labels       = array(
			'name'                  => _x( 'Accounts', 'Post Type General Name', 'skaut-fio-bank-transactions' ),
			'singular_name'         => _x( 'Account', 'Post Type Singular Name', 'skaut-fio-bank-transactions' ),
			'menu_name'             => __( 'Accounts', 'skaut-fio-bank-transactions' ),
			'name_admin_bar'        => __( 'Accounts', 'skaut-fio-bank-transactions' ),
			'archives'              => __( 'Account archives', 'skaut-fio-bank-transactions' ),
			'attributes'            => __( 'Account attributes', 'skaut-fio-bank-transactions' ),
			'parent_item_colon'     => __( 'Parent account:', 'skaut-fio-bank-transactions' ),
			'all_items'             => __( 'Accounts', 'skaut-fio-bank-transactions' ),
			'add_new_item'          => __( 'Add new account', 'skaut-fio-bank-transactions' ),
			'add_new'               => __( 'Add account', 'skaut-fio-bank-transactions' ),
			'new_item'              => __( 'New account', 'skaut-fio-bank-transactions' ),
			'edit_item'             => __( 'Edit account', 'skaut-fio-bank-transactions' ),
			'update_item'           => __( 'Update account', 'skaut-fio-bank-transactions' ),
			'view_item'             => __( 'View account', 'skaut-fio-bank-transactions' ),
			'view_items'            => __( 'View accounts', 'skaut-fio-bank-transactions' ),
			'search_items'          => __( 'Search accounts', 'skaut-fio-bank-transactions' ),
			'not_found'             => __( 'No accounts found', 'skaut-fio-bank-transactions' ),
			'not_found_in_trash'    => __( 'No accounts found in Trash', 'skaut-fio-bank-transactions' ),
			'featured_image'        => __( 'Featured image', 'skaut-fio-bank-transactions' ),
			'set_featured_image'    => __( 'Set featured image', 'skaut-fio-bank-transactions' ),
			'remove_featured_image' => __( 'Remove featured image', 'skaut-fio-bank-transactions' ),
			'use_featured_image'    => __( 'Use as featured image', 'skaut-fio-bank-transactions' ),
			'insert_into_item'      => __( 'Insert into account', 'skaut-fio-bank-transactions' ),
			'uploaded_to_this_item' => __( 'Uploaded to this account', 'skaut-fio-bank-transactions' ),
			'items_list'            => __( 'Accounts list', 'skaut-fio-bank-transactions' ),
			'items_list_navigation' => __( 'Accounts list navigation', 'skaut-fio-bank-transactions' ),
			'filter_items_list'     => __( 'Filter accounts list', 'skaut-fio-bank-transactions' ),
		);
		$capabilities = array(
			'edit_post'              => Helpers::getFioManagerCapability(),
			'read_post'              => Helpers::getFioManagerCapability(),
			'delete_post'            => Helpers::getFioManagerCapability(),
			'edit_posts'             => Helpers::getFioManagerCapability(),
			'edit_others_posts'      => Helpers::getFioManagerCapability(),
			'publish_posts'          => Helpers::getFioManagerCapability(),
			'read_private_posts'     => Helpers::getFioManagerCapability(),
			'delete_posts'           => Helpers::getFioManagerCapability(),
			'delete_private_posts'   => Helpers::getFioManagerCapability(),
			'delete_published_posts' => Helpers::getFioManagerCapability(),
			'delete_others_posts'    => Helpers::getFioManagerCapability(),
			'edit_private_posts'     => Helpers::getFioManagerCapability(),
			'edit_published_posts'   => Helpers::getFioManagerCapability(),
			// No new accounts until the encryption key exists (tokens are never stored as plain text).
			'create_posts'           => TokenStorage::isEncryptionEnabled() ? Helpers::getFioManagerCapability() : 'do_not_allow',
		);
		$args         = array(
			'label'               => __( 'Accounts', 'skaut-fio-bank-transactions' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'revisions' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => FIOTRANSACTIONS_NAME,
			'menu_position'       => 3,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capabilities'        => $capabilities,
			'show_in_rest'        => false,
		);
		register_post_type( self::ACCOUNTS_TYPE_SLUG, $args );
	}

	public function defaultContent( string $content ): string {
		global $post_type;
		if ( self::ACCOUNTS_TYPE_SLUG === $post_type ) {
			$content = '';
		}

		return $content;
	}

	public function titlePlaceholder( string $title ): string {
		global $post_type;
		if ( self::ACCOUNTS_TYPE_SLUG === $post_type ) {
			$title = __( 'Enter the account name', 'skaut-fio-bank-transactions' );
		}

		return $title;
	}

	public function updatedMessages( array $messages = array() ): array {
		$post                                 = get_post();
		$messages[ self::ACCOUNTS_TYPE_SLUG ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Account updated.', 'skaut-fio-bank-transactions' ), // My Post Type updated.
			2  => __( 'Account updated.', 'skaut-fio-bank-transactions' ), // Custom field updated.
			3  => __( 'Account updated.', 'skaut-fio-bank-transactions' ), // Custom field deleted.
			4  => __( 'Account updated.', 'skaut-fio-bank-transactions' ), // My Post Type updated.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, mirrors WordPress core.
			5  => isset( $_GET['revision'] ) ? sprintf( /* translators: %s: revision title */ __( 'Account restored to revision from %s.', 'skaut-fio-bank-transactions' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false,
			6  => __( 'Account updated.', 'skaut-fio-bank-transactions' ), // My Post Type published.
			7  => __( 'Account saved.', 'skaut-fio-bank-transactions' ), // My Post Type saved.
			8  => __( 'Account updated.', 'skaut-fio-bank-transactions' ), // My Post Type submitted.
			9  => sprintf(
				/* translators: %1$s: date */
				__( 'Account scheduled for: <strong>%1$s</strong>.', 'skaut-fio-bank-transactions' ),
				// translators: Publish box date format, see https://www.php.net/manual/datetime.format.php
				date_i18n(
					__( 'M j, Y @ G:i' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Core date format string.
					$post instanceof \WP_Post ? intval( strtotime( $post->post_date ) ) : time()
				)
			),
			10 => __( 'Account draft updated.', 'skaut-fio-bank-transactions' ), // My Post Type draft updated.
		);

		return $messages;
	}

	public function getAllAccounts(): array {
		$accountsWpQuery = new \WP_Query(
			array(
				'post_type'     => self::ACCOUNTS_TYPE_SLUG,
				'nopaging'      => true,
				'no_found_rows' => true,
			)
		);

		return is_array( $accountsWpQuery->posts ) ? $accountsWpQuery->posts : array();
	}
}
