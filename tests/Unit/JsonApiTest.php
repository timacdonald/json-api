<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;

class JsonApiTest extends TestCase
{
    public function testItCanReturnASingleResource(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
        ]);
    }

    public function testItCanReturnACollection(): void
    {
        $users = [
            (new BasicModel([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ])),
            (new BasicModel([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ])),
        ];
        Route::get('test-route', fn () => UserResource::collection($users));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'user-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-1',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'included' => [],
        ]);
    }

    public function testItCastsEmptyAttributesAndRelationshipsToAnObject(): void
    {
        Route::get('test-route', fn () => UserResource::make((new BasicModel(['id' => 'user-id']))));

        $response = $this->getJson('test-route?fields[basicModels]=');

        self::assertStringContainsString('"attributes":{},"relationships":{},"meta":{},"links":{}', $response->content());
    }

    public function testItAddsMetaToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            protected function toMeta(Request $request): array
            {
                return [
                    'meta-key' => 'meta-value',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [
                    'meta-key' => 'meta-value',
                ],
                'links' => [],
            ],
            'included' => [],
        ]);
    }

    public function testItAddsLinksToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            protected function toLinks(Request $request): array
            {
                return [
                    'links-key' => 'links-value',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [],
                'links' => [
                    'links-key' => 'links-value',
                ],
            ],
            'included' => [],
        ]);
    }

    public function testItSetsTheContentTypeHeaderForASingleResource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'xxxx']))));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
    }

    public function testItSetsTheContentTypeHeaderForACollectionOfResources(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::collection([(new BasicModel(['id' => 'xxxx']))]));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
    }

    public function testItCanCustomiseTheTypeResolution(): void
    {
        JsonApiResource::resolveTypeUsing(fn (BasicModel $model): string => $model::class);
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'expected-id']))));

        $response = $this->get("test-route");

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'Tests\\Models\\BasicModel',
                'relationships' => [],
                'attributes' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
        ]);

        JsonApiResource::resolveTypeNormally();
    }

    public function testItCanCustomiseTheIdResolution(): void
    {
        JsonApiResource::resolveIdUsing(fn (BasicModel $model): string => 'expected-id');
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'missing-id']))));

        $response = $this->get("test-route");

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'relationships' => [],
                'attributes' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
        ]);

        JsonApiResource::resolveIdNormally();
    }

    public function testItClearsTheHelperCachesAfterPreparingResponseForASingleResource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'missing-id']))));

        $response = $this->get("test-route?include=test&fields[basicModels]=a");

        $response->assertExactJson([
            'data' => [
                'id' => 'missing-id',
                'type' => 'basicModels',
                'relationships' => [],
                'attributes' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
        ]);
        $this->assertCount(0, Fields::getInstance()->cache());
        $this->assertCount(0, Includes::getInstance()->cache());
    }

    public function testItClearsTheHelperCachesAfterPreparingResponseForACollectionOfResources(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::collection([ (new BasicModel(['id' => 'missing-id'])) ]));

        $response = $this->get("test-route?include=test&fields[basicModels]=a");

        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'missing-id',
                    'type' => 'basicModels',
                    'relationships' => [],
                    'attributes' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'included' => [],
        ]);
        $this->assertCount(0, Fields::getInstance()->cache());
        $this->assertCount(0, Includes::getInstance()->cache());
    }
}
