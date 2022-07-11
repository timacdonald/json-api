<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\PostResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\JsonApiServerImplementation;
use TiMacDonald\JsonApi\Link;
use TiMacDonald\JsonApi\RelationshipCollectionLink;
use TiMacDonald\JsonApi\RelationshipLink;
use TiMacDonald\JsonApi\ResourceIdentifier;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;
use function get_class;

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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCastsEmptyAttributesAndRelationshipsToAnObject(): void
    {
        Route::get('test-route', fn () => UserResource::make((new BasicModel(['id' => 'user-id']))));

        $response = $this->getJson('test-route?fields[basicModels]=');

        self::assertStringContainsString('"attributes":{},"relationships":{},"meta":{},"links":{}', $response->content());
        $this->assertValidJsonApi($response);
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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItAddsArbitraryLinksToIndividualResources(): void
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
                    'links-key' => [
                        'href' => 'links-value',
                        'meta' => [],
                    ],
                ],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItHandlesSelfAndRelatedLinks(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            protected function toLinks(Request $request): array
            {
                return [
                    Link::self('https://example.test/self', [
                        'some' => 'meta',
                    ]),
                    Link::related('https://example.test/related'),
                    'home' => 'https://example.test',
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
                    'self' => [
                        'href' => 'https://example.test/self',
                        'meta' => [
                            'some' => 'meta',
                        ],
                    ],
                    'related' => [
                        'href' => 'https://example.test/related',
                        'meta' => [],
                    ],
                    'home' => [
                        'href' => 'https://example.test',
                        'meta' => [],
                    ],
                ],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItSetsTheContentTypeHeaderForASingleResource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'xxxx']))));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
        $this->assertValidJsonApi($response);
    }

    public function testItSetsTheContentTypeHeaderForACollectionOfResources(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::collection([(new BasicModel(['id' => 'xxxx']))]));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
        $this->assertValidJsonApi($response);
    }

    public function testItCanCustomiseTheTypeResolution(): void
    {
        JsonApiResource::resolveTypeUsing(fn (BasicModel $model): string => get_class($model));
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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);

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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);

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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
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
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
        $this->assertCount(0, Fields::getInstance()->cache());
        $this->assertCount(0, Includes::getInstance()->cache());
    }

    public function testItCastsEmptyResourceIdentifierMetaToObject(): void
    {
        $relationship = new ResourceIdentifier('5', 'users');

        $json = json_encode($relationship);

        self::assertSame('{"id":"5","type":"users","meta":{}}', $json);
    }

    public function testItCastsEmptyLinksMetaToObject(): void
    {
        $link = Link::self('https://timacdonald.me', []);

        $json = json_encode($link);

        self::assertSame('{"href":"https:\/\/timacdonald.me","meta":{}}', $json);
    }

    public function testItCastsEmptyImplementationMetaToObject(): void
    {
        $implementation = new JsonApiServerImplementation('1.5', []);

        $json = json_encode($implementation);

        self::assertSame('{"version":"1.5","meta":{}}', $json);
    }

    public function testItCanSpecifyAnImplementation(): void
    {
        BasicJsonApiResource::resolveServerImplementationUsing(fn () => new JsonApiServerImplementation('1.4.3', [
            'secure' => true,
        ]));
        $user = new BasicModel([
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
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.4.3',
                'meta' => [
                    'secure' => true,
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);

        BasicJsonApiResource::resolveServerImplementationNormally();
    }

    public function testItCastsEmptyRelationshipLinkMetaToJsonObject()
    {
        $resourceLink = new RelationshipLink(
            new ResourceIdentifier('expected-id', 'expected-type')
        );

        $json = json_encode($resourceLink);

        self::assertSame('{"data":{"id":"expected-id","type":"expected-type","meta":{}},"meta":{},"links":{}}', $json);
    }

    public function testItCanPopulateAllTheMetasAndAllTheLinks()
    {
        // 1. Null resource ✅
        // 2. Single resource ✅
        // 3. Empty collection of resources.
        // 4. Collection of resources.
        JsonApiResource::resolveServerImplementationUsing(fn () => (new JsonApiServerImplementation('1.0'))->withMeta([
            'implementation' => 'meta',
        ]));
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]))->setRelation('avatar', new BasicModel([
            'id' => 'avatar-id',
        ]))->setRelation('posts', [
            (new BasicModel(['id' => 'post-id-1'])),
            (new BasicModel(['id' => 'post-id-2'])),
        ]);
        Route::get('test-route', fn () => (new class ($user) extends JsonApiResource {
            protected function toMeta(Request $request): array
            {
                return [
                    'user-internal' => 'meta',
                ];
            }

            protected function toLinks(Request $request): array
            {
                return [
                    Link::self('user-internal.com')->withMeta(['user-internal.com' => 'meta']),
                ];
            }

            protected function toRelationships(Request $request): array
            {
                return [
                    'profile' => fn () => (new class (null) extends JsonApiResource {
                        protected function toLinks(Request $request): array
                        {
                            // This should not be present in the response.
                            return [
                                Link::self('profile-internal.com')->withMeta([
                                    'profile-internal.com' => 'meta',
                                ]),
                            ];
                        }

                        protected function toMeta(Request $request): array
                        {
                            // This should not be present in the response.
                            return [
                                'profile-internal' => 'meta',
                            ];
                        }

                        public function toResourceIdentifier(Request $request): ResourceIdentifier
                        {
                            // This should not be present in the response...
                            return parent::toResourceIdentifier($request)->withMeta([
                                'profile-internal-resource-identifier' => 'meta',
                            ]);
                        }

                        public function toResourceLink(Request $request): RelationshipLink
                        {
                            return parent::toResourceLink($request)->withMeta([
                                'profile-internal-resource-link' => 'meta',
                            ])->withLinks([
                                Link::self('profile-internal-resource-link.com')->withMeta([
                                    'profile-internal-resource-link.com' => 'meta',
                                ]),
                            ]);
                        }
                    })->withMeta([
                        // This should not be in the response.
                        'profile-external' => 'meta',
                    ])->withLinks([
                        // This should not be in the response.
                        Link::related('profile-external.com')->withMeta([
                            'profile-external.com' => 'meta',
                        ]),
                    ])->withResourceIdentifier(
                        // This should not be in the response.
                        fn (ResourceIdentifier $identifier) => $identifier->withMeta([
                            'profile-external-resource-identifier' => 'meta',
                        ])
                    )->withRelationshipLink(
                        fn (RelationshipLink $link) => $link->withMeta([
                            'profile-external-resource-link' => 'meta',
                        ])->withLinks([
                            Link::related('profile-external-resource-link.com')->withMeta([
                                'profile-external-resource-link.com' => 'meta',
                            ]),
                        ])
                    ),
                    'avatar' => fn () => (new class ($this->resource->avatar) extends JsonApiResource {
                        protected function toLinks(Request $request): array
                        {
                            return [
                                Link::self('avatar-internal.com')->withMeta([
                                    'avatar-internal.com' => 'meta',
                                ]),
                            ];
                        }

                        protected function toMeta(Request $request): array
                        {
                            return [
                                'avatar-internal' => 'meta',
                            ];
                        }

                        public function toResourceIdentifier(Request $request): ResourceIdentifier
                        {
                            return parent::toResourceIdentifier($request)->withMeta([
                                'avatar-internal-resource-identifier' => 'meta',
                            ]);
                        }

                        public function toResourceLink(Request $request): RelationshipLink
                        {
                            return parent::toResourceLink($request)->withMeta([
                                'avatar-internal-resource-link' => 'meta',
                            ])->withLinks([
                                Link::self('avatar-internal-resource-link.com')->withMeta([
                                    'avatar-internal-resource-link.com' => 'meta',
                                ]),
                            ]);
                        }
                    })->withMeta([
                        'avatar-external' => 'meta',
                    ])->withLinks([
                        Link::related('avatar-external.com')->withMeta([
                            'avatar-external.com' => 'meta',
                        ]),
                    ])->withResourceIdentifier(
                        fn (ResourceIdentifier $identifier) => $identifier->withMeta([
                            'avatar-external-resource-identifier' => 'meta',
                        ])
                    )->withRelationshipLink(
                        fn (RelationshipLink $link) => $link->withMeta([
                            'avatar-external-resource-link' => 'meta',
                        ])->withLinks([
                            Link::related('avatar-external-resource-link.com')->withMeta([
                                'avatar-external-resource-link.com' => 'meta',
                            ]),
                        ])
                    ),
                    'posts' => fn () => (new class ($this->posts) extends JsonApiResource {
                        protected function toMeta(Request $request): array
                        {
                            return [
                                'posts-internal' => 'meta',
                            ];
                        }

                        protected function toLinks(Request $request): array
                        {
                            return [
                                Link::self('posts-internal.com')->withMeta([
                                    'posts-internal.com' => 'meta',
                                ]),
                            ];
                        }

                        public function toResourceIdentifier(Request $request): ResourceIdentifier
                        {
                            return parent::toResourceIdentifier($request)->withMeta([
                                'posts-internal-resource-identifier' => 'meta',
                            ]);
                        }

                        public function toResourceLink(Request $request): RelationshipLink
                        {
                            // should not be present in the response.
                            return parent::toResourceLink($request)->withMeta([
                                'posts-internal-resource-link' => 'meta',
                            ])->withLinks([
                                Link::self('posts-internal-resource-link.com')->withMeta([
                                    'posts-internal-resource-link.com' => 'meta',
                                ]),
                            ]);
                        }

                        public static function collection($resource): JsonApiResourceCollection
                        {
                            return parent::collection($resource)
                                ->withRelationshipLink(fn ($link) => $link->withLinks([
                                    Link::self('posts-collection-internal-resource-link.com', [
                                        'posts-collection-internal-resource-link' => 'meta',
                                    ])
                                ])->withMeta([
                                    'posts-internal-collection-resource-link' => 'meta',
                                ]))
                                ->map(fn (JsonApiResource $resource) => $resource->withMeta([
                                        'posts-internal-collection' => 'meta',
                                    ])->withLinks([
                                        Link::related('posts-internal-collection.com')->withMeta([
                                            'posts-internal-collection.com' => 'meta',
                                        ])
                                    ])->withResourceIdentifier(fn ($identifier) => $identifier->withMeta([
                                        'posts-internal-collection-resource-identifier' => 'meta', 
                                    ]))
                                );
                        }
                    })::collection($this->posts)
                        ->withRelationshipLink(fn ($link) => $link->withLinks([
                            Link::related('posts-external-resource-link.com', [
                                'posts-external-resource-link' => 'meta',
                            ]),
                        ])->withMeta([
                            'posts-external-resource-link' => 'meta',
                        ]))
                        ->map(fn ($post) => $post->withResourceIdentifier(fn ($identifier) => $identifier->withMeta([
                            'posts-external-resource-identifier' => 'meta', 
                        ])))
                ];
            }
        })->withMeta([
            'user-external' => 'meta',
        ])->withLinks([
            Link::related('user-external.com')->withMeta([
                'user-external.com' => 'meta',
            ]),
        ]));

        $response = $this->getJson('test-route?include=avatar,posts,profile');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [
                    'profile' => [
                        'data' => null,
                        'links' => [
                            'self' => [
                                'href' => 'profile-internal-resource-link.com',
                                'meta' => [
                                    'profile-internal-resource-link.com' => 'meta',
                                ],
                            ],
                            'related' => [
                                'href' => 'profile-external-resource-link.com',
                                'meta' => [
                                    'profile-external-resource-link.com' => 'meta',
                                ],
                            ],
                        ],
                        'meta' => [
                            'profile-internal-resource-link' => 'meta',
                            'profile-external-resource-link' => 'meta',
                        ],
                    ],
                    'avatar' => [
                        'data' => [
                            'id' => 'avatar-id',
                            'type' => 'basicModels',
                            'meta' => [
                                'avatar-internal-resource-identifier' => 'meta',
                                'avatar-external-resource-identifier' => 'meta',
                            ],
                        ],
                        'links' => [
                            'self' => [
                                'href' => 'avatar-internal-resource-link.com',
                                'meta' => [
                                    'avatar-internal-resource-link.com' => 'meta',
                                ],
                            ],
                            'related' => [
                                'href' => 'avatar-external-resource-link.com',
                                'meta' => [
                                    'avatar-external-resource-link.com' => 'meta',
                                ],
                            ],
                        ],
                        'meta' => [
                            'avatar-internal-resource-link' => 'meta',
                            'avatar-external-resource-link' => 'meta',
                        ],
                    ],
                    'posts' => [
                        'data' => [
                            [
                                'id' => 'post-id-1',
                                'type' => 'basicModels',
                                'meta' => [
                                    'posts-internal-resource-identifier' => 'meta',
                                    'posts-internal-collection-resource-identifier' => 'meta',
                                    'posts-external-resource-identifier' => 'meta',
                                ],
                            ],
                            [
                                'id' => 'post-id-2',
                                'type' => 'basicModels',
                                'meta' => [
                                    'posts-internal-resource-identifier' => 'meta',
                                    'posts-internal-collection-resource-identifier' => 'meta',
                                    'posts-external-resource-identifier' => 'meta',
                                ],
                            ],
                        ],
                        'links' => [
                            'self' => [
                                'href' => 'posts-collection-internal-resource-link.com',
                                'meta' => [
                                    'posts-collection-internal-resource-link' => 'meta',
                                ],
                            ],
                            'related' => [
                                'href' => 'posts-external-resource-link.com',
                                'meta' => [
                                    'posts-external-resource-link' => 'meta',
                                ],
                            ],
                        ],
                        'meta' => [
                            'posts-internal-collection-resource-link' => 'meta',
                            'posts-external-resource-link' => 'meta',
                        ],
                    ],
                ],
                'meta' => [
                    'user-internal' => 'meta',
                    'user-external' => 'meta',
                ],
                'links' => [
                    'self' => [
                        'href' => 'user-internal.com',
                        'meta' => [
                            'user-internal.com' => 'meta',
                        ],
                    ],
                    'related' => [
                        'href' => 'user-external.com',
                        'meta' => [
                            'user-external.com' => 'meta',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'avatar-id',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'meta' => [
                        'avatar-internal' => 'meta',
                        'avatar-external' => 'meta',
                    ],
                    'links' => [
                        'self' => [
                            'href' => 'avatar-internal.com',
                            'meta' => [
                                'avatar-internal.com' => 'meta',
                            ],
                        ],
                        'related' => [
                            'href' => 'avatar-external.com',
                            'meta' => [
                                'avatar-external.com' => 'meta',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'post-id-1',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'meta' => [
                        'posts-internal' => 'meta',
                        'posts-internal-collection' => 'meta',
                        // TODO external
                    ],
                    'links' => [
                        'self' => [
                            'href' => 'posts-internal.com',
                            'meta' => [
                                'posts-internal.com' => 'meta',
                            ]
                        ],
                        'related' => [
                            'href' => 'posts-internal-collection.com',
                            'meta' => [
                                'posts-internal-collection.com' => 'meta',
                            ]
                        ],
                        // TODO external
                    ],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'meta' => [
                        'posts-internal' => 'meta',
                        'posts-internal-collection' => 'meta',
                        // TODO external
                    ],
                    'links' => [
                        'self' => [
                            'href' => 'posts-internal.com',
                            'meta' => [
                                'posts-internal.com' => 'meta',
                            ]
                        ],
                        'related' => [
                            'href' => 'posts-internal-collection.com',
                            'meta' => [
                                'posts-internal-collection.com' => 'meta',
                            ]
                        ],
                        // TODO external
                    ],
                ],
            ],
            'jsonapi' => [
                'meta' => [
                    'implementation' => 'meta',
                ],
                'version' => '1.0',
            ],
        ]);
        $this->assertValidJsonApi($response);

        JsonApiResource::resolveServerImplementationNormally();
    }
}
