<?php

declare( strict_types=1 );

namespace FioTransactions\Frontend;

/**
 * The [fio] shortcode.
 *
 * Attributes:
 * - account   (required) ID of the account.
 * - period    days (default), last_7_days, this_month or last_month.
 * - days      Number of days to show for period "days", default and maximum 90.
 * - columns   Comma separated list of columns: date, amount, account, name, vs, ss, ks, message, comment, type.
 * - ss        Show only transactions with this specific symbol.
 * - vs_prefix Show only transactions whose variable symbol starts with this prefix.
 */
final class Shortcode {

	private $renderer;

	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
		$this->initHooks();
	}

	private function initHooks() {
		add_shortcode( 'fio', array( $this, 'processShortcode' ) );
	}

	/**
	 * @param array<string, mixed>|string $atts Shortcode attributes (an empty string when there are none).
	 */
	public function processShortcode( $atts = array() ): string {
		return $this->renderer->render( is_array( $atts ) ? $atts : array() );
	}
}
