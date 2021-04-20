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

		add_action( 'init', [ $this, 'registerPostType' ] );

		if ( is_admin() ) {
			add_filter( 'default_content', [ $this, 'defaultContent' ] );
			add_filter( 'enter_title_here', [ $this, 'titlePlaceholder' ] );
			add_filter( 'post_updated_messages', [ $this, 'updatedMessages' ] );
		}
	}

	public function registerPostType() {
		$labels       = [
			'name'                  => _x( 'Správa účtů', 'Post Type General Name', 'fio-bank-transactions' ),
			'singular_name'         => _x( 'Účet', 'Post Type Singular Name', 'fio-bank-transactions' ),
			'menu_name'             => __( 'Správa účtů', 'fio-bank-transactions' ),
			'name_admin_bar'        => __( 'Správa účtů', 'fio-bank-transactions' ),
			'archives'              => __( 'Archiv účtů', 'fio-bank-transactions' ),
			'attributes'            => __( 'Atributy', 'fio-bank-transactions' ),
			'parent_item_colon'     => __( 'Nadřazený účet', 'fio-bank-transactions' ),
			'all_items'             => __( 'Správa účtů', 'fio-bank-transactions' ),
			'add_new_item'          => __( 'Přidat nový účet', 'fio-bank-transactions' ),
			'add_new'               => __( 'Přidat účet', 'fio-bank-transactions' ),
			'new_item'              => __( 'Nový účet', 'fio-bank-transactions' ),
			'edit_item'             => __( 'Upravit účet', 'fio-bank-transactions' ),
			'update_item'           => __( 'Aktualizovat účet', 'fio-bank-transactions' ),
			'view_item'             => __( 'Zobrazit účet', 'fio-bank-transactions' ),
			'view_items'            => __( 'Zobrazit účet', 'fio-bank-transactions' ),
			'search_items'          => __( 'Hledat v účtech', 'fio-bank-transactions' ),
			'not_found'             => __( 'Žádné účty', 'fio-bank-transactions' ),
			'not_found_in_trash'    => __( 'Koš je prázdný', 'fio-bank-transactions' ),
			'featured_image'        => __( 'Náhledový obrázek', 'fio-bank-transactions' ),
			'set_featured_image'    => __( 'Zadat náhledový obrázek', 'fio-bank-transactions' ),
			'remove_featured_image' => __( 'Odstranit náhledový obrázek', 'fio-bank-transactions' ),
			'use_featured_image'    => __( 'Použít jako náhledový obrázek', 'fio-bank-transactions' ),
			'insert_into_item'      => __( 'Vložit do účtu', 'fio-bank-transactions' ),
			'uploaded_to_this_item' => __( 'Přiřazeno k tomuto účtu', 'fio-bank-transactions' ),
			'items_list'            => __( 'Seznam účtů', 'fio-bank-transactions' ),
			'items_list_navigation' => __( 'Navigace v seznamu účtů', 'fio-bank-transactions' ),
			'filter_items_list'     => __( 'Filtrovat účty', 'fio-bank-transactions' )
		];
		$capabilities = [
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
			'create_posts'           => Helpers::getFioManagerCapability()
		];
		$args         = [
			'label'               => __( 'Účty', 'fio-bank-transactions' ),
			'labels'              => $labels,
			'supports'            => [ 'title', 'author', 'revisions' ],
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
			'show_in_rest'        => false
		];
		register_post_type( self::ACCOUNTS_TYPE_SLUG, $args );
	}

	public function defaultContent( string $content ): string {
		global $post_type;
		if ( $post_type == self::ACCOUNTS_TYPE_SLUG ) {
			$content = '';
		}

		return $content;
	}

	public function titlePlaceholder( string $title ): string {
		global $post_type;
		if ( $post_type == self::ACCOUNTS_TYPE_SLUG ) {
			$title = __( 'Zadejte název účtu', 'fio-bank-transactions' );
		}

		return $title;
	}

	public function updatedMessages( array $messages = [] ): array {
		$post                                 = get_post();
		$messages[ self::ACCOUNTS_TYPE_SLUG ] = [
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Hotovo', 'fio-bank-transactions' ), // My Post Type updated.
			2  => __( 'Hotovo', 'fio-bank-transactions' ), // Custom field updated.
			3  => __( 'Hotovo', 'fio-bank-transactions' ), // Custom field deleted.
			4  => __( 'Hotovo', 'fio-bank-transactions' ), // My Post Type updated.
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Účet byl obnoven na starší verzi z %s'), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false,
			6  => __( 'Hotovo', 'fio-bank-transactions' ), // My Post Type published.
			7  => __( 'Účet byl uložen', 'fio-bank-transactions' ), // My Post Type saved.
			8  => __( 'Hotovo', 'fio-bank-transactions' ), // My Post Type submitted.
			9  => sprintf(
				__( 'Účet byl naplánován na: <strong>%1$s</strong>.' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Koncept účtu aktualizován', 'fio-bank-transactions' ) // My Post Type draft updated.
		];

		return $messages;
	}

	public function getAllAccounts(): array {
		$accountsWpQuery = new \WP_Query( [
			'post_type'     => self::ACCOUNTS_TYPE_SLUG,
			'nopaging'      => true,
			'no_found_rows' => true
		] );

		if ( $accountsWpQuery->have_posts() ) {
			return $accountsWpQuery->posts;
		}

		return [];
	}

}
