<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;
use TiMacDonald\JsonApi\JsonApiResource;

class JsonApiTest extends TestCase
{
    public function testItCanReturnASingleResource(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJsonApi('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
            ],
        ]);
    }

    public function testItCanReturnACollection(): void
    {
        $users = [
            BasicModel::make([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ]),
            BasicModel::make([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ]),
        ];
        Route::get('test-route', fn () => UserResource::collection($users));

        $response = $this->getJsonApi('test-route');

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
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2',
                    ],
                    'relationships' => [],
                ],
            ],
        ]);
    }

    public function testItCastsEmptyAttributesAndRelationshipsToAnObject(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make(BasicModel::make()));

        $response = $this->getJsonApi('test-route');

        self::assertStringContainsString('"attributes":{},"relationships":{}', $response->content());
    }

    public function testItAddsMetaToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class(BasicModel::make(['id' => 'expected-id'])) extends JsonApiResource {
            protected function toMeta(Request $request): array
            {
                return [
                    'meta-key' => 'meta-value',
                ];
            }
        });

        $response = $this->getJsonApi('test-route');

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
            ],
        ]);
    }

    public function testItAddsLinksToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class(BasicModel::make(['id' => 'expected-id'])) extends JsonApiResource {
            protected function toLinks(Request $request): array
            {
                return [
                    'links-key' => 'links-value',
                ];
            }
        });

        $response = $this->getJsonApi('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'links' => [
                    'links-key' => 'links-value',
                ],
            ],
        ]);
    }

    public function testItSetsTheContentTypeHeaderForASingleResource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make(BasicModel::make(['id' => 'xxxx'])));

        $response = $this->getJsonApi('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
    }

    public function testItSetsTheContentTypeHeaderForACollectionOfResources(): void
    {
        //
    }

    public function testItCanOptOutAddingTheContentTypeHeaderForASingleResource(): void
    {
    }

    public function testItCanOptOutOfAddingTheContentTypeHeaderForACollectionOfResources(): void
    {
    }

    public function testItChecksToAcceptHeaderForASingleResource(): void
    {
    }

    public function testItChecksToAcceptHeaderForACollectionOfResources(): void
    {
    }

    public function testItCanOptOutOfAcceptHeaderChecksForSingleResource(): void
    {
        //
    }

    public function testItCanOptOutOfAcceptHeaderChecksForACollectionOfResources(): void
    {
        //
    }
}
