<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Testing;

use Illuminate\Testing\TestResponse;

trait MakesJsonApiRequests
{
    public function jsonApi(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json($method, $uri, $data, array_merge([
            'Accept' => 'application/vnd.api+json',
        ], $headers));
    }

    public function getJsonApi(string $uri, array $headers = []): TestResponse
    {
        return $this->jsonApi('GET', $uri, [], $headers);
    }

    public function postJsonApi(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonApi('POST', $uri, $headers);
    }

    public function putJsonApi(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonApi('PUT', $uri, $data, $headers);
    }

    public function patchJsonApi(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonApi('PATCH', $uri, $data, $headers);
    }

    public function deleteJsonApi(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonApi('DELETE', $uri, $data, $headers);
    }

    public function optionsJsonApi(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->jsonApi('OPTIONS', $uri, $data, $headers);
    }
}
