<?php

declare( strict_types=1 );

namespace FioTransactions\Api;

use FioApi\Downloader;

final class AccountFactory implements IAccountFactory {

	public function createByToken( string $token ): IAccount {
		return new Account( new Downloader( $token ) );
	}

}
