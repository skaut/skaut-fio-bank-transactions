<?php

declare( strict_types=1 );

namespace FioTransactions\Accounts;

use FioTransactions\Utils\Helpers;

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
			'name'                  => _x( 'Správa účtů', 'Post Type General Name', 'skaut-fio-bank-transactions' ),
			'singular_name'         => _x( 'Účet', 'Post Type Singular Name', 'skaut-fio-bank-transactions' ),
			'menu_name'             => __( 'Správa účtů', 'skaut-fio-bank-transactions' ),
			'name_admin_bar'        => __( 'Správa účtů', 'skaut-fio-bank-transactions' ),
			'archives'              => __( 'Archiv účtů', 'skaut-fio-bank-transactions' ),
			'attributes'            => __( 'Atributy', 'skaut-fio-bank-transactions' ),
			'parent_item_colon'     => __( 'Nadřazený účet', 'skaut-fio-bank-transactions' ),
			'all_items'             => __( 'Správa účtů', 'skaut-fio-bank-transactions' ),
			'add_new_item'          => __( 'Přidat nový účet', 'skaut-fio-bank-transactions' ),
			'add_new'               => __( 'Přidat účet', 'skaut-fio-bank-transactions' ),
			'new_item'              => __( 'Nový účet', 'skaut-fio-bank-transactions' ),
			'edit_item'             => __( 'Upravit účet', 'skaut-fio-bank-transactions' ),
			'update_item'           => __( 'Aktualizovat účet', 'skaut-fio-bank-transactions' ),
			'view_item'             => __( 'Zobrazit účet', 'skaut-fio-bank-transactions' ),
			'view_items'            => __( 'Zobrazit účet', 'skaut-fio-bank-transactions' ),
			'search_items'          => __( 'Hledat v účtech', 'skaut-fio-bank-transactions' ),
			'not_found'             => __( 'Žádné účty', 'skaut-fio-bank-transactions' ),
			'not_found_in_trash'    => __( 'Koš je prázdný', 'skaut-fio-bank-transactions' ),
			'featured_image'        => __( 'Náhledový obrázek', 'skaut-fio-bank-transactions' ),
			'set_featured_image'    => __( 'Zadat náhledový obrázek', 'skaut-fio-bank-transactions' ),
			'remove_featured_image' => __( 'Odstranit náhledový obrázek', 'skaut-fio-bank-transactions' ),
			'use_featured_image'    => __( 'Použít jako náhledový obrázek', 'skaut-fio-bank-transactions' ),
			'insert_into_item'      => __( 'Vložit do účtu', 'skaut-fio-bank-transactions' ),
			'uploaded_to_this_item' => __( 'Přiřazeno k tomuto účtu', 'skaut-fio-bank-transactions' ),
			'items_list'            => __( 'Seznam účtů', 'skaut-fio-bank-transactions' ),
			'items_list_navigation' => __( 'Navigace v seznamu účtů', 'skaut-fio-bank-transactions' ),
			'filter_items_list'     => __( 'Filtrovat účty', 'skaut-fio-bank-transactions' ),
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
			'create_posts'           => Helpers::getFioManagerCapability(),
		);
		$args         = array(
			'label'               => __( 'Účty', 'skaut-fio-bank-transactions' ),
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
			$title = __( 'Zadejte název účtu', 'skaut-fio-bank-transactions' );
		}

		return $title;
	}

	public function updatedMessages( array $messages = array() ): array {
		$post                                 = get_post();
		$messages[ self::ACCOUNTS_TYPE_SLUG ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Hotovo', 'skaut-fio-bank-transactions' ), // My Post Type updated.
			2  => __( 'Hotovo', 'skaut-fio-bank-transactions' ), // Custom field updated.
			3  => __( 'Hotovo', 'skaut-fio-bank-transactions' ), // Custom field deleted.
			4  => __( 'Hotovo', 'skaut-fio-bank-transactions' ), // My Post Type updated.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, mirrors WordPress core.
			5  => isset( $_GET['revision'] ) ? sprintf( /* translators: %s: revision title */ __( 'Účet byl obnoven na starší verzi z %s', 'skaut-fio-bank-transactions' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false,
			6  => __( 'Hotovo', 'skaut-fio-bank-transactions' ), // My Post Type published.
			7  => __( 'Účet byl uložen', 'skaut-fio-bank-transactions' ), // My Post Type saved.
			8  => __( 'Hotovo', 'skaut-fio-bank-transactions' ), // My Post Type submitted.
			9  => sprintf(
				/* translators: %1$s: date */
				__( 'Účet byl naplánován na: <strong>%1$s</strong>.', 'skaut-fio-bank-transactions' ),
				// translators: Publish box date format, see https://www.php.net/manual/datetime.format.php
				date_i18n(
					__( 'M j, Y @ G:i' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Core date format string.
					$post instanceof \WP_Post ? intval( strtotime( $post->post_date ) ) : time()
				)
			),
			10 => __( 'Koncept účtu aktualizován', 'skaut-fio-bank-transactions' ), // My Post Type draft updated.
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
