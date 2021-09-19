<?php

declare(strict_types=1);

namespace Tests;

use RuntimeException;
use Exception;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use SebastianBergmann\Environment\Runtime;
use TiMacDonald\JsonApi\Contracts\ResourceIdResolver;
use TiMacDonald\JsonApi\Contracts\ResourceTypeable;
use TiMacDonald\JsonApi\Contracts\ResourceTypeResolver;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\JsonApiServiceProvider;
use stdClass;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function test_it_resolves_the_id_and_type_of_a_model(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new BasicJsonApiResource($resource));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_casts_a_models_integer_id_to_a_string(): void
    {
        $resource = new BasicResource(['id' => 55]);
        $resource->setKeyType('int');
        Route::get('test-route', fn () => new BasicJsonApiResource($resource));

        self::assertSame(55, $resource->getKey());

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '55',
                'type' => 'basicResources',
                'attributes' => [],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_throws_when_unable_to_automatically_resolve_id_of_a_non_object(): void
    {
        $this->app->bind(ResourceTypeResolver::class, fn () => fn () => 'type');
        $resource = [];
        Route::get('test-route', fn () => new BasicJsonApiResource($resource));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve Resource Object id for type array.');

        $this->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_id_of_an_object(): void
    {
        $this->app->bind(ResourceTypeResolver::class, fn () => fn () => 'type');
        $resource = new stdClass;
        Route::get('test-route', fn () => new BasicJsonApiResource($resource));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve Resource Object id for class stdClass.');

        $this->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_type_of_a_non_object(): void
    {
        $this->app->bind(ResourceIdResolver::class, fn () =>fn () =>  'id');
        $resource = [];
        Route::get('test-route', fn () => new BasicJsonApiResource($resource));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve Resource Object type for type array.');

        $this->getJson('test-route');
    }

    public function test_it_throws_when_unable_to_automatically_resolve_type_of_an_object(): void
    {
        $this->app->bind(ResourceIdResolver::class, fn () =>fn () =>  'id');
        $resource = new stdClass;
        Route::get('test-route', fn () => new BasicJsonApiResource($resource));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve Resource Object type for class stdClass.');

        $this->getJson('test-route');
    }

    public function test_it_loads_services_on_demand(): void
    {
        $provider = new JsonApiServiceProvider($this->app);

        self::assertInstanceOf(DeferrableProvider::class, $provider);
        self::assertSame([
            ResourceIdResolver::class,
            ResourceTypeResolver::class,
        ], $provider->provides());
    }

    public function test_it_includes_attributes(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
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
                'id' => 'expected-id',
                'type' => 'basicResources',
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
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                    'location' => 'Melbourne',
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicResources]=name,location');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
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
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                    'email' => 'tim@example.com',
                    'location' => 'Melbourne',
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicResources]=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [
                    //
                ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_resolves_closure_wrapped_attributes(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
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
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [
                    'location' => 'Melbourne',
                ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_doesnt_resolve_closure_wrapped_attributes_when_not_requested(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'location' => fn (Request $request) => throw new Exception('foo'),
                ];
            }
        });

        $response = $this->getJson('test-route?fields[basicResources]=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [
                    //
               ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_throws_when_requested_fields_is_not_an_array(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->withExceptionHandling()->getJson('test-route?fields=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The fields parameter must be an array of resource types.',
        ]);
    }

    public function test_it_throws_when_requested_fields_value_is_not_a_string(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->withExceptionHandling()->getJson('test-route?fields[basicResources][foo]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The type fields parameter must be a comma seperated list of attributes.',
        ]);
    }

    public function test_it_resolves_relationships(): void
    {
        $resource = new BasicResource(['id' => 'parent-id']);
        $resource->nested = new NestedResource(['id' => 'nested-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toRelationships(Request $request): array
            {
                return [
                    'author' => fn (Request $request) => new class($this->resource->nested) extends JsonApiResource {
                        protected function toAttributes(Request $request): array
                        {
                            return [
                                'name' => 'Tim',
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=author');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'parent-id',
                'type' => 'basicResources',
                'attributes' => [],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'id' => 'nested-id',
                            'type' => 'nestedResources',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'nested-id',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Tim',
                    ],
                    'relationships' => [
                        //
                    ]
                ],
            ],
        ]);
    }

    public function test_it_doesnt_resolve_relationships_unless_requested(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toRelationships(Request $request): array
            {
                return [
                    'user' => fn (Request $request) => throw new Exception('foo'),
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [],
                'relationships' => [
                    //
                ],
            ]
        ]);
    }

    public function test_it_can_include_nested_relationships(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                ];
            }

            protected function toRelationships(Request $request): array
            {
                return [
                    'nested-level-1' => fn () => new class(new NestedResource(['id' => 'nested-level-1'])) extends JsonApiResource {
                        public function toAttributes(Request $request): array
                        {
                            return [
                                'name' => 'Taz',
                            ];
                        }

                        public function toRelationships(Request $request): array
                        {
                            return [
                                'nested-level-2' => fn () => new class(new NestedResource(['id' => 'nested-level-2'])) extends JsonApiResource {
                                    public function toAttributes(Request $request): array
                                    {
                                        return [
                                            'name' => 'Jaz',
                                        ];
                                    }

                                    public function toRelationships(Request $request): array
                                    {
                                        return [
                                            'nested-level-3' => fn () => new class(new NestedResource(['id' => 'nested-level-3'])) extends JsonApiResource {
                                                public function toAttributes(Request $request): array
                                                {
                                                    return [
                                                        'name' => 'James',
                                                    ];
                                                }
                                            },
                                        ];
                                    }
                                },
                                'nested-level-2-alt' => fn () => new class(new NestedResource(['id' => 'nested-level-2-alt'])) extends JsonApiResource {
                                    public function toAttributes(Request $request): array
                                    {
                                        return [
                                            'name' => 'Jess',
                                        ];
                                    }
                                }
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=nested-level-1.nested-level-2.nested-level-3,nested-level-1.nested-level-2-alt');

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [
                    'name' => 'Tim',
                ],
                'relationships' => [
                    'nested-level-1' => [
                        'data' => [
                            'id' => 'nested-level-1',
                            'type' => 'nestedResources',
                        ]
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'nested-level-1',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Taz',
                    ],
                    'relationships' => [
                        'nested-level-2' => [
                            'data' => [
                                'id' => 'nested-level-2',
                                'type' => 'nestedResources',
                            ],
                        ],
                        'nested-level-2-alt' => [
                            'data' => [
                                'id' => 'nested-level-2-alt',
                                'type' => 'nestedResources',
                            ],
                        ]
                    ]
                ],
                [
                    'id' => 'nested-level-2',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Jaz',
                    ],
                    'relationships' => [
                        'nested-level-3' => [
                            'data' => [
                                'type' => 'nestedResources',
                                'id' => 'nested-level-3'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'nested-level-2-alt',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Jess',
                    ],
                    'relationships' => []
                ],
                [
                    'id' => 'nested-level-3',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'James',
                    ],
                    'relationships' => []
                ]
            ],
        ]);
    }

    // public function test_it_excludes_attributes_in_nested_resources(): void
    // {
    //     $this->markTestSkipped();
    // }

    // public function test_cached_includes_are_cleaned_up_after_rendering_response(): void
    // {
    //     $resource = new BasicResource(['id' => 'expected-id']);
    //     $resource->nested = new NestedResource(['id' => 'nested-level-1']);
    //     $resource->nested->nested = new NestedResource(['id' => 'nested-level-2']);
    //     $jsonResource = new class($resource) extends JsonApiResource {
    //         public function getCache()
    //         {
    //             return $this->parsedIncludesCache;
    //         }

    //         protected function toAttributes(Request $request): array
    //         {
    //             return [
    //                 'name' => 'Tim',
    //             ];
    //         }

    //         protected function toRelationships(Request $request): array
    //         {
    //             return [
    //                 'nested-relation' => fn () => new class($this->resource->nested) extends JsonApiResource {
    //                     public function getCache()
    //                     {
    //                         return $this->parsedIncludesCache;
    //                     }

    //                     public function toAttributes(Request $request): array
    //                     {
    //                         return [
    //                             'name' => 'Taz',
    //                         ];
    //                     }

    //                     public function toRelationships(Request $request): array
    //                     {
    //                         return [
    //                             'nested-relation' => fn () => new class($this->resource->nested) extends JsonApiResource {
    //                                 public function toAttributes(Request $request): array
    //                                 {
    //                                     return [
    //                                         'name' => 'Rusty',
    //                                     ];
    //                                 }
    //                             },
    //                         ];
    //                     }
    //                 },
    //             ];
    //         }
    //     };
    //     $request = Request::create('http://example.com?include=nested-relation.nested-relation');

    //     $jsonResource->toArray($request);
    //     $jsonResource->with($request);
    //     dd($jsonResource->getCache());
    // }

    public function test_it_includes_nested_resources_when_their_key_is_the_same(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            protected function toAttributes(Request $request): array
            {
                return [
                    'name' => 'Tim',
                ];
            }

            protected function toRelationships(Request $request): array
            {
                return [
                    'nested-relation' => fn () => new class(new NestedResource(['id' => 'nested-level-1'])) extends JsonApiResource {
                        public function toAttributes(Request $request): array
                        {
                            return [
                                'name' => 'Taz',
                            ];
                        }

                        public function toRelationships(Request $request): array
                        {
                            return [
                                'nested-relation' => fn () => new class(new NestedResource(['id' => 'nested-level-2'])) extends JsonApiResource {
                                    public function toAttributes(Request $request): array
                                    {
                                        return [
                                            'name' => 'Rusty',
                                        ];
                                    }
                                },
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=nested-relation.nested-relation');

        $response->assertOk();

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicResources',
                'attributes' => [
                    'name' => 'Tim',
                ],
                'relationships' => [
                    'nested-relation' => [
                        'data' => [
                            'id' => 'nested-level-1',
                            'type' => 'nestedResources',
                        ]
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'nested-level-1',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Taz',
                    ],
                    'relationships' => [
                        'nested-relation' => [
                            'data' => [
                                'id' => 'nested-level-2',
                                'type' => 'nestedResources',
                            ],
                        ],
                    ]
                ],
                [
                    'id' => 'nested-level-2',
                    'type' => 'nestedResources',
                    'attributes' => [
                        'name' => 'Rusty',
                    ],
                    'relationships' => []
                ],
            ],
        ]);
    }

    public function test_it_throws_when_requested_includes_is_an_array(): void
    {
        $resource = new BasicResource(['id' => 'expected-id']);
        Route::get('test-route', fn () => new class($resource) extends JsonApiResource {
            //
        });

        $response = $this->withExceptionHandling()->getJson('test-route?include[]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The include parameter must be a comma seperated list of relationship paths.'
        ]);
    }

    public function test_it_can_return_a_collection(): void
    {
        $collection = new Collection([
            new BasicResource(['id' => 'expected-id-1']),
            new BasicResource(['id' => 'expected-id-2']),
        ]);
        Route::get('test-route', fn () => BasicJsonApiResource::collection($collection));

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'expected-id-1',
                    'type' => 'basicResources',
                    'attributes' => [],
                    'relationships' => [],
                ],
                [
                    'id' => 'expected-id-2',
                    'type' => 'basicResources',
                    'attributes' => [],
                    'relationships' => [],
                ]
            ]
        ]);
    }

    public function test_it_can_include_relationships_for_a_collection(): void
    {
        $collection = new Collection([
            BasicResource::make(['id' => 'parent-id-1'])
                ->setRelation('nested', NestedResource::make(['id' => 'nested-id-1'])),
            BasicResource::make(['id' => 'parent-id-2'])
                ->setRelation('nested', NestedResource::make(['id' => 'nested-id-2'])),
        ]);
        Route::get('test-route', fn () => JsonResourceWithInclude::collection($collection));

        $response = $this->get('test-route?include=nested');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'parent-id-1',
                    'type' => 'basicResources',
                    'attributes' => [],
                    'relationships' => [
                        'nested' => [
                            'data' => [
                                'id' => 'nested-id-1',
                                'type' => 'nestedResources',
                            ]
                        ]
                    ],
                ],
                [
                    'id' => 'parent-id-2',
                    'type' => 'basicResources',
                    'attributes' => [],
                    'relationships' => [
                        'nested' => [
                            'data' => [
                                'id' => 'nested-id-2',
                                'type' => 'nestedResources',
                            ]
                        ]
                    ],
                ]
            ],
            'included' => [
                [
                    'id' => 'nested-id-1',
                    'type' => 'nestedResources',
                    'attributes' => [],
                    'relationships' => [],
                ],
                [
                    'id' => 'nested-id-2',
                    'type' => 'nestedResources',
                    'attributes' => [],
                    'relationships' => [],
                ]
            ]
        ]);
    }

    public function test_it_can_include_a_collection_for_a_resource(): void
    {
        $resource = BasicResource::make(['id' => 'parent-id-1']);
        $nesteds = new Collection([
            NestedResource::make(['id' => 'nested-id-1']),
            NestedResource::make(['id' => 'nested-id-2']),
        ]);
        $resource->setRelation('nesteds', $nesteds);
        Route::get('test-route', fn () => JsonResourceWithInclude::make($resource));

        $response = $this->get('test-route?include=nesteds');

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
                    'attributes' => [],
                    'relationships' => [],
                ],
                [
                    'id' => 'nested-id-2',
                    'type' => 'nestedResources',
                    'attributes' => [],
                    'relationships' => [],
                ]
            ]
        ]);
    }

    public function test_it_can_include_a_collection_for_a_collection_of_a_resource(): void
    {
        $resource = BasicResource::make(['id' => 'parent-id-1']);
        $nesteds = new Collection([
            NestedResource::make(['id' => 'nested-id-1']),
            NestedResource::make(['id' => 'nested-id-2']),
        ]);
        $resource->setRelation('nesteds', $nesteds);
        $nesteds[0]->setRelation('nesteds', new Collection([
            NestedResource::make(['id' => 'nested-id-3']),
            NestedResource::make(['id' => 'nested-id-4']),
        ]));
        $nesteds[1]->setRelation('nesteds', new Collection);
        Route::get('test-route', fn () => JsonResourceWithInclude::make($resource));

        $response = $this->get('test-route?include=nesteds.nesteds');

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
                    'attributes' => [],
                    'relationships' => [
                        'nesteds' => [
                            [
                                'data' => [
                                    'id' => 'nested-id-3',
                                    'type' => 'nestedResources',
                                ]
                        ],
                        [
                            'data' => [
                                'id' => 'nested-id-4',
                                'type' => 'nestedResources',
                            ]
                        ]
                        ]
                    ],
                ],
                [
                    'id' => 'nested-id-2',
                    'type' => 'nestedResources',
                    'attributes' => [],
                    'relationships' => [
                        'nesteds' => [],
                    ],
                ],
                [
                    'id' => 'nested-id-3',
                    'type' => 'nestedResources',
                    'attributes' => [],
                    'relationships' => [],
                ],
                [
                    'id' => 'nested-id-4',
                    'type' => 'nestedResources',
                    'attributes' => [],
                    'relationships' => [],
                ]
            ]
        ]);
    }

    public function test_it_can_use_sparse_fieldsets_with_nested_collections(): void
    {
        $resource = BasicResource::make(['id' => 'parent-id-1']);
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

    public function test_it_can_specify_minimal_attributes(): void
    {
        JsonApiResource::minimalAttributes();
        $resource = new BasicResource(['id' => 'expected-id', 'name' => 'missing name']);
        Route::get('test-route', fn () => JsonResourceWithAttributes::make($resource));

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'type' => 'basicResources',
                'id' => 'expected-id',
                'attributes' => [],
                'relationships' => [],
            ],
        ]);
        JsonApiResource::maximalAttributes();
    }
}

/**
 * @property NestedResource $nested
 */
class BasicResource extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}

/**
 * @property NestedResource $nested
 */
class NestedResource extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}

class BasicJsonApiResource extends JsonApiResource
{
    //
}

class JsonResourceWithInclude extends JsonApiResource
{
    public function toRelationships(Request $request): array
    {
        return [
            'nested' => fn () => JsonResourceWithInclude::make($this->nested),
            'nesteds' => fn () => JsonResourceWithInclude::collection($this->nesteds),
        ];
    }
}

class JsonResourceWithAttributes extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'location' => 'Melbourne',
        ];
    }
}
