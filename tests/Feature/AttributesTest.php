<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;

class AttributesTest extends TestCase
{
    public function testItIncludesAllAttributesByDefault(): void
    {
        $model = (new BasicModel([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
        ]));
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItExcludesAttributesWhenUsingSparseFieldsets(): void
    {
        $model = (new BasicModel([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
            'location' => 'Melbourne',
        ]));
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItExcludesAllAttributesWhenNoneExplicitlyRequested(): void
    {
        $model = (new BasicModel([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
            'location' => 'Melbourne',
        ]));
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItResolvesClosureWrappedAttributes(): void
    {
        $model = (new BasicModel([
            'id' => 'expected-id',
            'location' => 'Melbourne',
        ]));
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItDoesntResolveClosureWrappedAttributesWhenNotRequested(): void
    {
        $model = (new BasicModel([
            'id' => 'expected-id',
        ]));
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItThrowsWhenFieldsParameterIsNotAnArray(): void
    {
        $user = (new BasicModel(['id' => 'expected-id']));
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->withExceptionHandling()->getJson('test-route?fields=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter must be an array of resource types.',
        ]);
    }

    public function testItThrowsWhenFieldsParameterIsNotAStringValue(): void
    {
        $user = (new BasicModel(['id' => 'expected-id']));
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->withExceptionHandling()->getJson('test-route?fields[basicModels][foo]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter value must be a comma seperated list of attributes.',
        ]);
    }

    public function testItCanSpecifyMinimalAttributes(): void
    {
        JsonApiResource::useMinimalAttributes();
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'type' => 'basicModels',
                'id' => 'user-id',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanRequestAttributesWhenUsingMinimalAttributes()
    {
        JsonApiResource::useMinimalAttributes();
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
            'location' => 'Melbourne',
        ]));
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route?fields[basicModels]=name');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'type' => 'basicModels',
                'id' => 'user-id',
                'attributes' => [
                    'name' => 'user-name',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanUseSparseFieldsetsWithIncludedCollections(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]))->setRelation('posts', [
            (new BasicModel([
                'id' => 'post-id-1',
                'title' => 'post-title-1',
                'content' => 'post-content-1',
            ])),
            (new BasicModel([
                'id' => 'post-id-2',
                'title' => 'post-title-2',
                'content' => 'post-content-2',
            ])),
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route?include=posts&fields[basicModels]=title');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'relationships' => [
                    'posts' => [
                        'data' => [
                            [
                                'id' => 'post-id-1',
                                'type' => 'basicModels',
                            ],
                            [
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
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-2',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItRemovesPotentiallyMissingAttributes(): void
    {
        $model = new BasicModel([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
            'address' => '123 fake street',
        ]);
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
            {
                return [
                    'name' => $this->when(false, fn () => $this->name),
                    'address' => fn () => $this->when(false, fn () => $this->address),
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
                    'email' => 'tim@example.com',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludePotentiallyMissingValues(): void
    {
        $model = new BasicModel([
            'id' => 'expected-id',
            'name' => 'Tim',
            'email' => 'tim@example.com',
            'address' => '123 fake street',
        ]);
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
            {
                return [
                    'name' => $this->when(true, fn () => $this->name),
                    'address' => fn () => $this->when(true, $this->address),
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
                    'address' => '123 fake street',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testPotentiallyMissingValuesAreRespectedOverSparseFieldsets()
    {
        $model = new BasicModel([
            'id' => 'expected-id',
            'name' => 'Tim',
            'phone' => '123456',
            'email' => 'tim@example.com',
            'address' => '123 fake street',
        ]);
        Route::get('test-route', fn () => new class ($model) extends JsonApiResource {
            public function toAttributes($request): array
            {
                return [
                    'name' => $this->when(false, fn () => $this->name),
                    'phone' => fn () => $this->when(false, $this->phone),
                    'address' => $this->when(true, fn () => $this->address),
                    'email' => fn () => $this->when(true, $this->email),
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicModels]=name,phone,address,email');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [
                    'email' => 'tim@example.com',
                    'address' => '123 fake street',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }
}
