<?php
/**
 * Checks that the built plugin can load its prefixed dependencies. A broken autoloader (e.g. after a
 * change of the Composer output format) otherwise shows up only as "Could not connect to Fio banka".
 *
 * Usage: php scripts/verify-build.php dist
 *
 * @package skaut-fio-bank-transactions
 */

namespace FioTransactions\Build;

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput -- CLI build script.

/**
 * Returns the classes that cannot be loaded from the build in the given directory.
 *
 * @return array<int, string>
 */
function missing_classes( string $dist ): array {
	require $dist . '/vendor/scoper-autoload.php';

	return array_values(
		array_filter(
			array(
				'FioTransactions\\Vendor\\FioApi\\Downloader',
				'FioTransactions\\Vendor\\GuzzleHttp\\Client',
				'FioTransactions\\Vendor\\Composer\\CaBundle\\CaBundle',
			),
			static function ( string $className ): bool {
				return ! class_exists( $className );
			}
		)
	);
}

/**
 * Prints the result and returns the exit code.
 */
function run( string $dist ): int {
	$missing = missing_classes( $dist );
	if ( array() !== $missing ) {
		fwrite( STDERR, 'Build is broken, these classes cannot be loaded: ' . implode( ', ', $missing ) . "\n" );

		return 1;
	}

	echo "Build OK: the dependencies of the plugin can be loaded.\n";

	return 0;
}

exit( run( $argv[1] ?? 'dist' ) );
