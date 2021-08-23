<?php
/**
 * PHP-Scoper configuration
 *
 * @package skaut-fio-bank-transactions
 */

use Isolated\Symfony\Component\Finder\Finder;

return array(
	'prefix'                     => 'FioTransactions\\Vendor',
	'finders'                    => array(
		Finder::create()->files()
			->name( array( '*.php', '/LICENSE(.txt)?/' ) )

			->path( '#^guzzlehttp/guzzle/#' )
			->path( '#^guzzlehttp/promises/#' )
			->path( '#^guzzlehttp/psr7/#' )
			->path( '#^mhujer/fio-api-php/#' )
			->path( '#^pimple/pimple/#' )
			->path( '#^psr/container/#' )
			->path( '#^psr/http-client/#' )
			->path( '#^psr/http-factory/#' )
			->path( '#^psr/http-message/#' )
			->path( '#^ralouphie/getallheaders/#' )
			->path( '#^composer/#' )
			->in( 'vendor' ),
		Finder::create()->files()
			->name( 'autoload.php' )
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
			// PSR-0 support
			if ( __DIR__ . '/vendor/composer/ClassLoader.php' === $file_path ) {
				$contents = mb_ereg_replace( "// PSR-0 lookup\n", "// PSR-0 lookup\n        \$scoperPrefix = '{$replace_prefix}\\\\';\n        if (substr(\$class, 0, strlen(\$scoperPrefix)) == \$scoperPrefix) {\n            \$class = substr(\$class, strlen(\$scoperPrefix));\n            \$first = \$class[0];\n            \$logicalPathPsr4 = substr(\$logicalPathPsr4, strlen(\$scoperPrefix));\n        }\n", $contents );
			}

			return $contents;
		},
	),
	'whitelist-global-classes'   => false,
	'whitelist-global-constants' => false,
	'whitelist-global-functions' => false,
);
