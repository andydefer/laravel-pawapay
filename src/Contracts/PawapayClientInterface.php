<?php

declare(strict_types=1);

namespace Pawapay\Contracts;

use Pawapay\Enums\PawaPayEndpoint;

/**
 * Interface for making HTTP requests to the PawaPay API.
 *
 * This interface defines the contract for a client that communicates with
 * the PawaPay API using various HTTP methods, ensuring consistent interaction
 * with the payment service endpoints.
 */
interface PawapayClientInterface
{
    /**
     * Send a POST request to the specified PawaPay endpoint.
     *
     * @param PawaPayEndpoint $endpoint The API endpoint to send the request to
     * @param array $data The data to send in the request body
     * @return mixed The response from the API
     */
    public function post(PawaPayEndpoint $endpoint, array $data = []);

    /**
     * Send a GET request to the specified PawaPay endpoint.
     *
     * @param PawaPayEndpoint $endpoint The API endpoint to send the request to
     * @param array $query Query parameters to include in the request URL
     * @param array $parameters Path parameters to replace in the endpoint URL
     * @return mixed The response from the API
     */
    public function get(PawaPayEndpoint $endpoint, array $query = [], array $parameters = []);

    /**
     * Send a PUT request to the specified PawaPay endpoint.
     *
     * @param PawaPayEndpoint $endpoint The API endpoint to send the request to
     * @param array $data The data to send in the request body
     * @return mixed The response from the API
     */
    public function put(PawaPayEndpoint $endpoint, array $data = []);

    /**
     * Send a PATCH request to the specified PawaPay endpoint.
     *
     * @param PawaPayEndpoint $endpoint The API endpoint to send the request to
     * @param array $data The data to send in the request body
     * @return mixed The response from the API
     */
    public function patch(PawaPayEndpoint $endpoint, array $data = []);

    /**
     * Send a DELETE request to the specified PawaPay endpoint.
     *
     * @param PawaPayEndpoint $endpoint The API endpoint to send the request to
     * @return mixed The response from the API
     */
    public function delete(PawaPayEndpoint $endpoint);
}
