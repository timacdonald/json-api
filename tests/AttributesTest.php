<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;
use TiMacDonald\JsonApi\JsonApiResource;

class AttributesTest extends TestCase
{
    public function testItIncludesAllAttributesByDefault(): void
    {
        $model = BasicModel::make([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
        ]);
        Route::get('test-route', fn () => new class($model) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => $this->name,
                    'email' => $this->email,
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                ],
                'relationships' => [],
            ],
        ]);
    }

    public function testItExcludesAttributesWhenUsingSparseFieldsets(): void
    {
        $model = BasicModel::make([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
            'location' => 'Melbourne',
        ]);
        Route::get('test-route', fn () => new class($model) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => $this->name,
                    'email' => $this->email,
                    'location' => $this->location,
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicModels]=name,location');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'Tim',
                    'location' => 'Melbourne',
                ],
                'relationships' => [],
            ],
        ]);
    }

    public function testItExcludesAllAttributesWhenNoneExplicitlyRequested(): void
    {
        $model = BasicModel::make([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
            'location' => 'Melbourne',
        ]);
        Route::get('test-route', fn () => new class($model) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => $this->name,
                    'email' => $this->email,
                    'location' => $this->location,
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicModels]=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
            ],
        ]);
    }

    public function testItResolvesClosureWrappedAttributes(): void
    {
        $model = BasicModel::make([
            'id' => 'expected-id',
            'location' => 'Melbourne',
        ]);
        Route::get('test-route', fn () => new class($model) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'location' => fn () => $this->location,
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [
                    'location' => 'Melbourne',
                ],
                'relationships' => [],
            ],
        ]);
    }

    public function testItDoesntResolveClosureWrappedAttributesWhenNotRequested(): void
    {
        $model = BasicModel::make([
            'id' => 'expected-id',
        ]);
        Route::get('test-route', fn () => new class($model) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'location' => fn () => throw new Exception('xxxx'),
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicModels]=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
            ],
        ]);
    }

    public function testClosureWrappedAttributesGetTheRequestAtAnArgument(): void
    {
        $model = BasicModel::make([
            'id' => 'expected-id',
        ]);
        Route::get('test-route', fn () => new class($model) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'request_is_the_same' => fn ($attributeArgument) => $request === $attributeArgument,
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [
                    'request_is_the_same' => true,
                ],
                'relationships' => [],
            ],
        ]);
    }

    public function testItThrowsWhenFieldsParameterIsNotAnArray(): void
    {
        $model = BasicModel::make(['id' => 'expected-id']);
        Route::get('test-route', fn () => new BasicJsonApiResource($model));

        $response = $this->getJson('test-route?fields=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter must be an array of resource types.',
        ]);
    }

    public function testItThrowsWhenFieldsParameterIsNotAStringValue(): void
    {
        $model = BasicModel::make(['id' => 'expected-id']);
        Route::get('test-route', fn () => new BasicJsonApiResource($model));

        $response = $this->getJson('test-route?fields[basicModels][foo]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter value must be a comma seperated list of attributes.',
        ]);
    }

    public function testItCanSpecifyMinimalAttributes(): void
    {
        JsonApiResource::minimalAttributes();
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'type' => 'basicModels',
                'id' => 'user-id',
                'attributes' => [],
                'relationships' => [],
            ],
        ]);
        JsonApiResource::maximalAttributes();
    }

    public function testItCanAddAvailableAttributesToTheMetaObjectOfAResource(): void
    {
        JsonApiResource::minimalAttributes();
        JsonApiResource::includeAvailableAttributesViaMeta();
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [
                    'availableAttributes' => [
                        'name',
                    ],
                ],
            ],
        ]);

        JsonApiResource::excludeAvailableAttributesViaMeta();
        JsonApiResource::maximalAttributes();
    }

    public function testItCanUseSparseFieldsetsWithIncludedCollections(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ])->setRelation('posts', [
            BasicModel::make([
                'id' => 'post-id-1',
                'title' => 'post-title-1',
                'content' => 'post-content-1',
            ]),
            BasicModel::make([
                'id' => 'post-id-2',
                'title' => 'post-title-2',
                'content' => 'post-content-2',
            ]),
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->get('test-route?include=posts&fields[basicModels]=title');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [
                    'posts' => [
                        [
                            'data' => [
                                'id' => 'post-id-1',
                                'type' => 'basicModels',
                            ],
                        ],
                        [
                            'data' => [
                                'id' => 'post-id-2',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'post-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-1',
                    ],
                    'relationships' => [],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-2',
                    ],
                    'relationships' => [],
                ],
            ],
        ]);
    }
}
