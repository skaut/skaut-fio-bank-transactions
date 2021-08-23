<?php

declare( strict_types=1 );

namespace FioTransactions\Services;

use FioTransactions\Vendor\Pimple\Container;
use FioTransactions\Api\AccountFactory;
use FioTransactions\Accounts\AccountsInit;
use FioTransactions\General\General;
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
		self::$services['fioAccountFactory'] = function ( Container $container ) {
			return new AccountFactory();
		};

		self::$services['accountsInit'] = function ( Container $container ) {
			return new AccountsInit();
		};

		self::$services['general'] = function ( Container $container ) {
			return new General( $container['accountsInit'] );
		};

		self::$services['frontend'] = function ( Container $container ) {
			return new Frontend( $container['fioAccountFactory'] );
		};

		self::$services['admin'] = function ( Container $container ) {
			return new Admin( $container['accountsInit'] );
		};
	}

	public static function getServicesContainer(): Container {
		if ( self::$services === null ) {
			self::init();
		}

		return self::$services;
	}
}
