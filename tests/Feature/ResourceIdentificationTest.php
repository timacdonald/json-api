<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use stdClass;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

class ResourceIdentificationTest extends TestCase
{
    public function test_it()
    {
        $this->assertValidJsonApi(<<<'JSON'
{
  "data": [
    {
      "id": "25240",
      "type": "posts",
      "attributes": {
        "title": "So what is JSON:API all about anyway?",
        "excerpt": "..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "users",
            "id": "74812"
          }
        }
      }
    },
    {
      "id": "39974",
      "type": "posts",
      "attributes": {
        "title": "Building an API with Laravel, using the JSON:API specification.",
        "excerpt": "..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "users",
            "id": "74812"
          }
        }
      }
    }
  ],
  "included": [
    {
      "type": "users",
      "id": "74812",
      "attributes": {
        "name": "Tim"
      }
    }
  ]
}
JSON);
    }

    public function test_it_resolves_the_id_and_type_of_a_model(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
        ]));
        Route::get('test-route', fn () => (new BasicJsonApiResource($user)));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_casts_a_models_integer_id_to_a_string(): void
    {
        $user = (new BasicModel([
            'id' => 55,
        ]))->setKeyType('int');
        Route::get('test-route', fn () => (new BasicJsonApiResource($user)));

        self::assertSame(55, $user->getKey());

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '55',
                'type' => 'basicModels',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_throws_when_unable_to_automatically_resolve_the_id_of_a_non_object(): void
    {
        $array = [];
        Route::get('test-route', fn () => (new BasicJsonApiResource($array)));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for [array].');

        $this->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_the_id_of_an_object(): void
    {
        $array = new stdClass;
        Route::get('test-route', fn () => (new BasicJsonApiResource($array)));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for [stdClass].');

        $this->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_the_type_of_a_non_object(): void
    {
        $array = [];
        Route::get('test-route', fn () => new class($array) extends BasicJsonApiResource
        {
            public function toId($request): string
            {
                return 'id';
            }
        });

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for [array].');

        $this->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_type_of_an_object(): void
    {
        $object = new stdClass;
        Route::get('test-route', fn () => new class($object) extends BasicJsonApiResource
        {
            public function toId($request): string
            {
                return 'id';
            }
        });

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for [stdClass].');

        $this->withoutExceptionHandling()->getJson('test-route');
    }
}
