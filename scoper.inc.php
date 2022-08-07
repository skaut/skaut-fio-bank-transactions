<?php
/**
 * PHP-Scoper configuration
 *
 * @package skaut-fio-bank-transactions
 */

use Isolated\Symfony\Component\Finder\Finder;

/**
 * Constructs a finder for composer dependencies.
 *
 * @return Finder The initialized Finder.
 */
function dependency_finder() {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	exec( 'composer show --no-dev --name-only', $dependencies );
	$finder = Finder::create()->files()->name( array( '*.php', '/LICENSE(.txt)?/' ) )->in( 'vendor' );

	foreach ( $dependencies as $dependency ) {
		$finder->path( '#^' . $dependency . '/#' );
	}

	return $finder;
}

return array(
	'prefix'                     => 'FioTransactions\\Vendor',
	'finders'                    => array(
		dependency_finder(),
		Finder::create()->files()
			->name( array( '*.php', '/LICENSE(.txt)?/' ) )
			->depth( 0 )
			->in( 'vendor/composer' ),
		Finder::create()->files()
			->name( 'autoload.php' )
			->depth( 0 )
			->in( 'vendor' ),
	),
	'patchers'                   => array(
		static function ( $file_path, $prefix, $contents ) {
			$regex_prefix = mb_ereg_replace( '\\\\', '\\\\\\\\', $prefix );
			$replace_prefix = mb_ereg_replace( '\\\\', '\\\\', $prefix );
			if ( __DIR__ . '/vendor/composer/autoload_real.php' === $file_path ) {
				$contents = mb_ereg_replace( "if \\('Composer\\\\\\\\Autoload\\\\\\\\ClassLoader' === \\\$class\\)", "if ('{$replace_prefix}\\\\Composer\\\\Autoload\\\\ClassLoader' === \$class)", $contents );
				$contents = mb_ereg_replace( "\\\\spl_autoload_unregister\\(array\\('ComposerAutoloaderInit", "\\spl_autoload_unregister(array('{$replace_prefix}\\\\ComposerAutoloaderInit", $contents );
			}
			if ( __DIR__ . '/vendor/guzzlehttp/guzzle/src/functions.php' === $file_path ) {
				$contents = mb_ereg_replace( "\\\\{$replace_prefix}\\\\uri_template\(", "\\uri_template(", $contents );
			}
			// PSR-0 support
			if ( __DIR__ . '/vendor/composer/ClassLoader.php' === $file_path ) {
				$contents = mb_ereg_replace( "// PSR-0 lookup\n", "// PSR-0 lookup\n        \$scoperPrefix = '{$replace_prefix}\\\\';\n        if (substr(\$class, 0, strlen(\$scoperPrefix)) == \$scoperPrefix) {\n            \$class = substr(\$class, strlen(\$scoperPrefix));\n            \$first = \$class[0];\n            \$logicalPathPsr4 = substr(\$logicalPathPsr4, strlen(\$scoperPrefix));\n        }\n", $contents );
			}

			return $contents;
		},
	),
);
