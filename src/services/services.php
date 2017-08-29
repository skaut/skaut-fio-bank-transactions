<?php

declare( strict_types=1 );

namespace FioTransactions\Services;

use Pimple\Container;
use FioTransactions\Apis\FioGateway;
use FioTransactions\Frontend\Frontend;
use FioTransactions\Admin\Settings;
use FioTransactions\Admin\Admin;

class Services {

	protected static $services = null;

	private static function init() {
		self::$services = new Container();
		self::registerServices();
	}

	private static function registerServices() {
		self::$services['fioGateway'] = function ( Container $container ) {
			return new FioGateway();
		};

		self::$services['frontend'] = function ( Container $container ) {
			return new Frontend( $container['fioGateway'] );
		};

		self::$services['adminSettings'] = function ( Container $container ) {
			return new Settings();
		};

		self::$services['admin'] = function ( Container $container ) {
			return new Admin( $container['adminSettings'] );
		};
	}

	public static function getServicesContainer(): Container {
		if ( self::$services === null ) {
			self::init();
		}

		return self::$services;
	}
}