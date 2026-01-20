<?php

declare(strict_types=1);

namespace Pawapay\Contracts;

use Illuminate\Http\Client\Response;
use Pawapay\Enums\PawaPayEndpoint;

interface PawapayClientInterface
{
    public function post(PawaPayEndpoint $endpoint, array $data = []);

    public function get(PawaPayEndpoint $endpoint, array $query = []);

    public function put(PawaPayEndpoint $endpoint, array $data = []);

    public function patch(PawaPayEndpoint $endpoint, array $data = []);

    public function delete(PawaPayEndpoint $endpoint);
}
