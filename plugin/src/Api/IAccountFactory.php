<?php

declare( strict_types=1 );

namespace FioTransactions\Api;

interface IAccountFactory {
	public function createByToken( string $token ): IAccount;
}