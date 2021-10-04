<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use stdClass;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

class ResourceIdentificationTest extends TestCase
{
    public function testItResolvesTheIdAndTypeOfAModel(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
        ]);
        Route::get('test-route', static fn () => BasicJsonApiResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
            ],
        ]);
    }

    public function testItCastsAModelsIntegerIdToAString(): void
    {
        $user = BasicModel::make([
            'id' => 55,
        ])->setKeyType('int');
        Route::get('test-route', static fn () => BasicJsonApiResource::make($user));

        self::assertSame(55, $user->getKey());

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '55',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
            ],
        ]);
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTheIdOfANonObject(): void
    {
        $array = [];
        Route::get('test-route', static fn () => BasicJsonApiResource::make($array));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for array.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTheIdOfAnObject(): void
    {
        $array = new stdClass();
        Route::get('test-route', static fn () => BasicJsonApiResource::make($array));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for stdClass.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTheTypeOfANonObject(): void
    {
        $array = [];
        Route::get('test-route', static fn () => new class($array) extends BasicJsonApiResource {
            protected function toId(Request $request): string
            {
                return 'id';
            }
        });

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for array.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTypeOfAnObject(): void
    {
        $object = new stdClass();
        Route::get('test-route', static fn () => new class($object) extends BasicJsonApiResource {
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
