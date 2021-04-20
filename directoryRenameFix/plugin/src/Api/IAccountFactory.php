<?php

declare( strict_types=1 );

namespace FioTransactions\api;

interface IAccountFactory {
	public function createByToken( string $token ): IAccount;
}