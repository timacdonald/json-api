<?php

namespace Tests;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use TiMacDonald\JsonApi\Contracts\ResourceIdResolver;
use TiMacDonald\JsonApi\Contracts\ResourceTypeable;
use TiMacDonald\JsonApi\Contracts\ResourceTypeResolver;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiServiceProvider;

class JsonApiTest extends TestCase
{
    /**
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            JsonApiServiceProvider::class,
        ];
    }

    public function test_it_gets_the_id_and_type_from_the_resource_automatically(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_can_rebind_resource_id_resolution_logic(): void
    {
        $resource = new BasicResource('missing id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $this->app->bind(ResourceIdResolver::class, fn () => fn (BasicResource $resource) => 'expected id');
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_can_rebind_resource_type_resolution_logic(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $this->app->bind(ResourceTypeResolver::class, fn () => fn (BasicResource $resource) => 'expectedType');
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'expectedType',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_includes_attributes_by_default(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, string>
             */
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_includes_and_excludes_attributes_when_using_sparse_fieldsets(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, string>
             */
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                    'location' => 'Melbourne',
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicResource]=name,location');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
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
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, string>
             */
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                    'location' => 'Melbourne',
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicResource]=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [
                    //
                ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_resolves_closure_wrapped_attributes(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, string|\Closure>
             */
            protected function toAttributes(Request $request): array
            {
                return [
                    'location' => fn (Request $request) => 'Melbourne',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [
                    'location' => 'Melbourne',
                ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_doesnt_resolve_closure_based_attributes_that_have_not_been_requested(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, string|\Closure>
             */
            protected function toAttributes(Request $request): array
            {
                return [
                    'location' => fn (Request $request) => throw new Exception('foo'),
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicResource]=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [
                    //
               ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_resolves_relationships(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, \Closure(\Illuminate\Http\Request $request): array<string, string>>
             */
            protected function toRelationships(Request $request): array
            {
                return [
                    'user' => fn (Request $request) => [
                        'id' => '1',
                        'type' => 'user',
                    ],
                ];
            }
        });

        $response = $this->getJson('test-route?include=user');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [],
                'relationships' => [
                    'user' => [
                        'id' => '1',
                        'type' => 'user',
                    ],
                ],
            ]
        ]);
    }

    public function test_it_excludes_relationships_not_requested(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            /**
             * @return array<string, \Closure(\Illuminate\Http\Request $request): array<string, string>>
             */
            protected function toRelationships(Request $request): array
            {
                return [
                    'user' => fn (Request $request) => [
                        'id' => '1',
                        'type' => 'user',
                    ],
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected id',
                'type' => 'basicResource',
                'attributes' => [],
                'relationships' => [
                    //
                ],
            ]
        ]);
    }

    public function test_it_throws_when_requested_fields_is_not_a_string(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->getJson('test-route?fields[basicResource][foo]=name');

        $response->assertStatus(400);
        // TODO: format error messages as per the spec.
        $response->assertExactJson([
            'message' => 'The type fields parameter must be a comma seperated list of attributes.',
        ]);
    }

    public function test_it_throws_when_requested_fields_is_not_a_stringasodm(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->getJson('test-route?fields=name');

        $response->assertStatus(400);
        // TODO: format error messages as per the spec.
        $response->assertExactJson([
            'message' => 'The fields parameter must be an array of resource types.',
        ]);
    }

    public function test_it_throws_when_requested_includes_is_an_array(): void
    {
        $resource = new BasicResource('expected id');
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->getJson('test-route?include[]=name');

        $response->assertStatus(400);
        // TODO: format error messages as per the spec.
        $response->assertExactJson([
            'message' => 'The include parameter must be a comma seperated list of relationship paths.',
        ]);
    }
}

class BasicResource extends Model
{
    public function __construct(private string $id)
    {
        //
    }

    public function getKey(): string
    {
        return $this->id;
    }
}

class BadResourceType
{
    //
}
