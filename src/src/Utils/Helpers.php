<?php

declare( strict_types=1 );

namespace FioTransactions\Utils;

class Helpers {

	public static function showAdminNotice( string $message, string $type = 'warning', string $hideNoticeOnPage = '' ) {
		add_action(
			'admin_notices',
			function () use ( $message, $type, $hideNoticeOnPage ) {
				$screen   = get_current_screen();
				$screenId = $screen instanceof \WP_Screen ? $screen->id : '';
				if ( '' === $hideNoticeOnPage || $hideNoticeOnPage !== $screenId ) {
					$class = 'notice notice-' . $type . ' is-dismissible';
					printf(
						'<div class="%1$s"><p>%2$s</p><button type="button" class="notice-dismiss">
		<span class="screen-reader-text">' . esc_html__( 'Zavřít', 'skaut-fio-bank-transactions' ) . '</span>
	</button></div>',
						esc_attr( $class ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$message
					);
				}
			}
		);
	}

	public static function getFioManagerCapability(): string {
		static $capability = '';

		if ( '' === $capability ) {
			$capability = apply_filters( FIOTRANSACTIONS_NAME . '_manager_capability', 'manage_options' );
		}

		return $capability;
	}

	/**
	 * Capability a user needs to embed account transactions via the block.
	 *
	 * Contributors and authors must not be able to display (e.g. in a post preview) transactions
	 * of an arbitrary account just by guessing its ID.
	 */
	public static function getFioEmbedCapability(): string {
		static $capability = '';

		if ( '' === $capability ) {
			$capability = strval( apply_filters( FIOTRANSACTIONS_NAME . '_embed_capability', 'edit_others_posts' ) );
		}

		return $capability;
	}

	public static function userCanEmbedAccounts( int $userId ): bool {
		return user_can( $userId, self::getFioEmbedCapability() ) || user_can( $userId, self::getFioManagerCapability() );
	}
}
