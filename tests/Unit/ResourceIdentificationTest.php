<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use stdClass;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\Exceptions\ResourceIdentificationException;

class ResourceIdentificationTest extends TestCase
{
    public function testItResolvesTheIdAndTypeOfAModel(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
        ]));
        Route::get('test-route', static fn () => (new BasicJsonApiResource($user)));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'links' => [],
                'meta' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCastsAModelsIntegerIdToAString(): void
    {
        $user = (new BasicModel([
            'id' => 55,
        ]))->setKeyType('int');
        Route::get('test-route', static fn () => (new BasicJsonApiResource($user)));

        self::assertSame(55, $user->getKey());

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '55',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'links' => [],
                'meta' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTheIdOfANonObject(): void
    {
        $array = [];
        Route::get('test-route', static fn () => (new BasicJsonApiResource($array)));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for array.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTheIdOfAnObject(): void
    {
        $array = new stdClass();
        Route::get('test-route', static fn () => (new BasicJsonApiResource($array)));

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object id for stdClass.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }

    public function testItThrowsWhenUnableToAutomaticallyResolveTheTypeOfANonObject(): void
    {
        $array = [];
        Route::get('test-route', static fn () => new class ($array) extends BasicJsonApiResource {
            public function toId($request): string
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
        Route::get('test-route', static fn () => new class ($object) extends BasicJsonApiResource {
            public function toId($request): string
            {
                return 'id';
            }
        });

        $this->expectException(ResourceIdentificationException::class);
        $this->expectExceptionMessage('Unable to resolve resource object type for stdClass.');

        $this->withoutExceptionHandling()->getJson('test-route');
    }
}
