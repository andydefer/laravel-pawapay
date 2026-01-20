<?php

declare(strict_types=1);

namespace Pawapay\Contracts;

use Pawapay\Enums\PawaPayEndpoint;

interface PawapayClientInterface
{
    public function post(PawaPayEndpoint $endpoint, array $data = []);

    public function get(PawaPayEndpoint $endpoint, array $query = [], array $parameters = []);

    public function put(PawaPayEndpoint $endpoint, array $data = []);

    public function patch(PawaPayEndpoint $endpoint, array $data = []);

    public function delete(PawaPayEndpoint $endpoint);
}
