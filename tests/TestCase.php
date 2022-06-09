<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Testing\TestResponse;
use Opis\JsonSchema\Validator;
use Orchestra\Testbench\TestCase as BaseTestCase;
use RuntimeException;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;

class TestCase extends BaseTestCase
{
    const JSON_API_SCHEMA_URL = 'http://jsonapi.org/schema';

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Includes::getInstance()->flush();

        Fields::getInstance()->flush();
    }

    protected function assertValidJsonApiString(string $string)
    {
        $data = json_decode(json: $string, flags: JSON_THROW_ON_ERROR);

        $result = $this->jsonApiValidator()->validate($data, self::JSON_API_SCHEMA_URL);

        $this->assertTrue($result->isValid());
    }

    protected function assertValidJsonApi(TestResponse $response)
    {
        $this->assertValidJsonApiString($response->content());
    }

    private function jsonApiValidator(): Validator
    {
        $validator = new Validator();

        $validator->resolver()->registerFile(
            self::JSON_API_SCHEMA_URL,
            $this->localSchemaPath(self::JSON_API_SCHEMA_URL)
        );

        return $validator;
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
