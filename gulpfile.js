const gulp = require( 'gulp' );

const merge = require( 'merge-stream' );
const replace = require( 'gulp-replace' );
const shell = require( 'gulp-shell' );

gulp.task(
	'build:deps:composer:scoper',
	shell.task('vendor/bin/php-scoper add-prefix --force')
);

gulp.task( 'build:deps:composer:certs', function () {
	return gulp
		.src( ['vendor/**/*.pem'] )
		.pipe( gulp.dest( 'dist/vendor/' ) );
} );

gulp.task(
	'build:deps:composer:autoloader',
	gulp.series(
		shell.task(
			'composer dump-autoload --no-dev' +
				( process.env.NODE_ENV === 'production' ? ' -o' : '' )
		),
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
		'build:deps:composer:certs',
		'build:deps:composer:autoloader'
	)
);

gulp.task( 'build:deps:npm:datatables.net:files', function () {
	return gulp
		.src( ['node_modules/datatables.net-plugins/i18n/cs.json'] )
		.pipe( gulp.dest( 'dist/bundled/datatables-files' ) );
} );

gulp.task( 'build:deps:npm:datatables.net', gulp.parallel( 'build:deps:npm:datatables.net:files', function () {
	return gulp
		.src( ['node_modules/datatables.net-dt/css/jquery.dataTables.min.css', 'node_modules/datatables.net/js/jquery.dataTables.min.js', 'node_modules/datatables.net-plugins/sorting/datetime-moment.js'] )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} ) );

gulp.task( 'build:deps:npm:moment', function () {
	return gulp
		.src( 'node_modules/moment/min/moment.min.js' )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} );

gulp.task(
	'build:deps:npm',
	gulp.series(
		'build:deps:npm:datatables.net',
		'build:deps:npm:moment',
	)
);

gulp.task(
	'build:deps',
	gulp.parallel( 'build:deps:composer', 'build:deps:npm' )
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
