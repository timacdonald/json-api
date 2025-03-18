<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Testing\TestResponse;
use Opis\JsonSchema\Validator;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RuntimeException;

use function is_string;

class TestCase extends BaseTestCase
{
    public const JSON_API_SCHEMA_URL = 'https://raw.githubusercontent.com/json-api/json-api/refs/heads/gh-pages/_schemas/1.0/schema.json';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    protected function assertValidJsonApi(TestResponse|string|array $data): void
    {
        if ($data instanceof TestResponse) {
            $data = json_decode(json: $data->content(), flags: JSON_THROW_ON_ERROR);
        }

        if (is_string($data)) {
            $data = json_decode(json: $data, flags: JSON_THROW_ON_ERROR);
        }

        $data = json_decode(json_encode($data));

        $result = tap(new Validator, function ($validator) {
            $validator->resolver()->registerFile(
                self::JSON_API_SCHEMA_URL,
                $this->localSchemaPath(self::JSON_API_SCHEMA_URL)
            );
        })->validate($data, self::JSON_API_SCHEMA_URL);

        if ($result->isValid()) {
            $this->assertTrue($result->isValid());

            return;
        }

        $this->assertTrue(
            $result->isValid(),
            print_r((new \Opis\JsonSchema\Errors\ErrorFormatter)->format($result->error()), true)
        );
    }

    private function localSchemaPath(string $url): string
    {
        $schema = __DIR__.'/../schema.json';

        if (file_exists($schema)) {
            return $schema;
        }

        $contents = file_get_contents($url);

        if ($contents === false) {
            throw new RuntimeException('Unable to download schema from '.$url);
        }

        if (file_put_contents($schema, $contents) === false) {
            throw new RuntimeException('Unable to save schema to '.$schema);
        }

        return $schema;
    }
}
