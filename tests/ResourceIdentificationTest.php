<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;
use stdClass;

class ResourceIdentificationTest extends TestCase
{
    public function test_it_resolves_the_id_and_type_of_a_model(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
        ]);
        Route::get('test-route', fn () => BasicJsonApiResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_casts_a_models_integer_id_to_a_string(): void
    {
        $user = BasicModel::make([
            'id' => 55,
        ])->setKeyType('int');
        Route::get('test-route', fn () => BasicJsonApiResource::make($user));

        self::assertSame(55, $user->getKey());

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '55',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_throws_when_unable_to_automatically_resolve_the_id_of_a_non_object(): void
    {
        $array = [];
        Route::get('test-route', fn () => BasicJsonApiResource::make($array));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for array.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_the_id_of_an_object(): void
    {
        $array = new stdClass;
        Route::get('test-route', fn () => BasicJsonApiResource::make($array));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for stdClass.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_the_type_of_a_non_object(): void
    {
        $array = [];
        Route::get('test-route', fn () => new class($array) extends BasicJsonApiResource {
            protected function toId(Request $request): string
            {
                return 'id';
            }
        });

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for array.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_type_of_an_object(): void
    {
        $object = new stdClass;
        Route::get('test-route', fn () => new class($object) extends BasicJsonApiResource {
            protected function toId(Request $request): string
            {
                return 'id';
            }
        });

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for stdClass.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }
}
