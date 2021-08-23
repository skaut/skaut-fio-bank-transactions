const gulp = require( 'gulp' );

const merge = require( 'merge-stream' );
const replace = require( 'gulp-replace' );
const shell = require( 'gulp-shell' );

gulp.task(
	'build:deps:composer:scoper',
	shell.task(
		'vendor/bin/php-scoper add-prefix --force --output-dir=dist/vendor'
	)
);

gulp.task(
	'build:deps:composer:autoloader',
	gulp.series(
		shell.task( 'composer dump-autoload --no-dev' ),
		function () {
			return merge(
				gulp.src( [
					'vendor/composer/autoload_classmap.php',
					//'vendor/composer/autoload_files.php',
					'vendor/composer/autoload_namespaces.php',
					'vendor/composer/autoload_psr4.php',
				] ),
				gulp
					.src( [ 'vendor/composer/autoload_static.php' ] )
					.pipe(
						replace(
							'namespace Composer\\Autoload;',
							'namespace FioTransactions\\Vendor\\Composer\\Autoload;'
						)
					)
					.pipe(
						replace(
							/'(.*)\\\\' => \n/g,
							"'FioTransactions\\\\Vendor\\\\$1\\\\' => \n"
						)
					)
			).pipe( gulp.dest( 'dist/vendor/composer/' ) );
		},
		shell.task( 'composer dump-autoload' )
	)
);

gulp.task(
	'build:deps:composer',
	gulp.series(
		'build:deps:composer:scoper',
		'build:deps:composer:autoloader'
	)
);

gulp.task(
	'build:deps',
	gulp.parallel( 'build:deps:composer' )
);

gulp.task( 'build:php:base', function () {
	return gulp
		.src( [ 'src/*.php' ] )
		.pipe( gulp.dest( 'dist/' ) );
} );

gulp.task( 'build:php:other', function () {
	// TODO: Split these
	return gulp
		.src( [ 'src/**/*.css', 'src/**/*.js', 'src/**/*.php', 'src/**/*.png', 'src/**/*.txt' ] )
		.pipe( gulp.dest( 'dist/' ) );
} );

gulp.task(
	'build:php',
	gulp.parallel(
		'build:php:base',
		'build:php:other'
	)
);

gulp.task(
	'build',
	gulp.parallel(
		'build:deps',
		'build:php'
	)
);
