<?php

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
    public function test_it_includes_all_attributes_by_default(): void
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
            ]
        ]);
    }

    public function test_it_excludes_attributes_when_using_sparse_fieldsets(): void
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
            ]
        ]);
    }

    public function test_it_excludes_all_attributes_when_none_explicitly_requested(): void
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
            ]
        ]);
    }

    public function test_it_resolves_closure_wrapped_attributes(): void
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
            ]
        ]);
    }

    public function test_it_doesnt_resolve_closure_wrapped_attributes_when_not_requested(): void
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
            ]
        ]);
    }

    public function test_closure_wrapped_attributes_get_the_request_at_an_argument(): void
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
            ]
        ]);
    }

    public function test_it_throws_when_fields_parameter_is_not_an_array(): void
    {
        $model = BasicModel::make(['id' => 'expected-id']);
        Route::get('test-route', fn () => new BasicJsonApiResource($model));

        $response = $this->getJson('test-route?fields=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter must be an array of resource types.',
        ]);
    }

    public function test_it_throws_when_fields_parameter_is_not_a_string_value(): void
    {
        $model = BasicModel::make(['id' => 'expected-id']);
        Route::get('test-route', fn () => new BasicJsonApiResource($model));

        $response = $this->getJson('test-route?fields[basicModels][foo]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter value must be a comma seperated list of attributes.',
        ]);
    }

    public function test_it_can_specify_minimal_attributes(): void
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

    public function test_it_can_add_available_attributes_to_the_meta_object_of_a_resource(): void
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
            ]
        ]);

        JsonApiResource::excludeAvailableAttributesViaMeta();
        JsonApiResource::maximalAttributes();
    }

    public function test_it_can_use_sparse_fieldsets_with_nested_collections(): void
    {
        $resource = BasicModel::make(['id' => 'parent-id-1']);
        $nesteds = new Collection([
            NestedResource::make(['id' => 'nested-id-1', 'name' => 'Tim']),
            NestedResource::make(['id' => 'nested-id-2', 'name' => 'Jaz']),
        ]);
        $resource->setRelation('nesteds', $nesteds);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            public function toRelationships(Request $request): array
            {
                return [
                    'nesteds' => fn () => new class($this->nesteds, JsonResourceWithAttributes::class) extends JsonApiResourceCollection {
                    },
                ];
            }
        });

        $response = $this->get('test-route?include=nesteds&fields[nestedResources]=name');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'parent-id-1',
                'type' => 'basicResources',
                'attributes' => [],
                'relationships' => [
                    'nesteds' => [
                        [
                            'data' => [
                                'id' => 'nested-id-1',
                                'type' => 'nestedResources',
                            ]
                        ],
                        [
                            'data' => [
                                'id' => 'nested-id-2',
                                'type' => 'nestedResources',
                            ]
                        ]
                    ]
                ],
            ],
            'included' => [
                [
                    'id' => 'nested-id-1',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Tim',
                        // 'location' => 'Melbourne',
                    ],
                    'relationships' => [],
                ],
                [
                    'id' => 'nested-id-2',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Jaz',
                        // 'location' => 'Melbourne',
                    ],
                    'relationships' => [],
                ]
            ]
        ]);
    }
}
