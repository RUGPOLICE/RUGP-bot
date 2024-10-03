<?php

namespace App\Services\Network;

abstract class NetworkService
{
    abstract public function get(string $endpoint, array $query = []): ?array;

    abstract public function getJetton(string $address, ?string $chain = null): ?array;

    abstract public function getJettonHolders(string $address, float $supply, int $limit = 20, ?string $chain = null): ?array;

    abstract public function getLock(string $address, float $supply, array $holders, ?string $chain = null): ?array;

    abstract public function getTaxes(string $address, ?string $chain = null): ?array;
}
