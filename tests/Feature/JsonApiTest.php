<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use TiMacDonald\JsonApi\Link;
use TiMacDonald\JsonApi\RelationshipObject;
use TiMacDonald\JsonApi\ResourceIdentifier;
use TiMacDonald\JsonApi\ServerImplementation;

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
            ],
            'jsonapi' => [
                'version' => '1.0',
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
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2',
                    ],
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItExcludesEmptyAttributesAndRelationships(): void
    {
        Route::get('test-route', fn () => UserResource::make((new BasicModel(['id' => 'user-id']))));

        $response = $this->getJson('test-route?fields[basicModels]=');

        self::assertStringNotContainsString('"attributes"', $response->content());
        self::assertStringNotContainsString('"relationships"', $response->content());
        self::assertStringNotContainsString('"meta"', $response->content());
        self::assertStringNotContainsString('"links"', $response->content());
        $this->assertValidJsonApi($response);
    }

    public function testItAddsMetaToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            public function toMeta($request): array
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
                'meta' => [
                    'meta-key' => 'meta-value',
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItHandlesSelfAndRelatedLinks(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            public function toLinks($request): array
            {
                return [
                    Link::self('https://example.test/self', [
                        'some' => 'meta',
                    ]),
                    Link::related('https://example.test/related'),
                    new Link('home', 'https://example.test'),
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'links' => [
                    'self' => [
                        'href' => 'https://example.test/self',
                        'meta' => [
                            'some' => 'meta',
                        ],
                    ],
                    'related' => [
                        'href' => 'https://example.test/related',
                    ],
                    'home' => [
                        'href' => 'https://example.test',
                    ],
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
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
        JsonApiResource::resolveTypeUsing(fn (BasicModel $model): string => $model::class);
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'expected-id']))));

        $response = $this->get('test-route');

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'Tests\\Models\\BasicModel',
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanCustomiseTheIdResolution(): void
    {
        JsonApiResource::resolveIdUsing(fn (BasicModel $model): string => 'expected-id');
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'missing-id']))));

        $response = $this->get('test-route');

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
            ],
            'jsonapi' => [
                'version' => '1.0',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItExcludesEmptyResourceIdentifierMeta(): void
    {
        $relationship = new ResourceIdentifier('users', '5');

        $json = json_encode($relationship);

        self::assertSame('{"type":"users","id":"5"}', $json);
    }

    public function testItExcludesEmptyLinksMeta(): void
    {
        $link = Link::self('https://timacdonald.me', []);

        $json = json_encode($link);

        self::assertSame('{"href":"https:\/\/timacdonald.me"}', $json);
    }

    public function testItExcludesEmptyImplementationMeta(): void
    {
        $implementation = new ServerImplementation('1.5', []);

        $json = json_encode($implementation);

        self::assertSame('{"version":"1.5"}', $json);
    }

    public function testItCanSpecifyAnImplementation(): void
    {
        BasicJsonApiResource::resolveServerImplementationUsing(fn () => new ServerImplementation('1.4.3', [
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
            ],
            'jsonapi' => [
                'version' => '1.4.3',
                'meta' => [
                    'secure' => true,
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItExcludesEmptyRelationshipLinkMeta()
    {
        $resourceLink = RelationshipObject::toOne(
            new ResourceIdentifier('expected-type', 'expected-id')
        );

        $json = json_encode($resourceLink);

        self::assertSame('{"data":{"type":"expected-type","id":"expected-id"}}', $json);
    }

    public function testItCanPopulateAllTheMetasAndAllTheLinks()
    {
        // 1. Null resource ✅
        // 2. Single resource ✅
        // 3. Empty collection of resources.
        // 4. Collection of resources.
        JsonApiResource::resolveServerImplementationUsing(fn () => (new ServerImplementation('1.0'))->withMeta([
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
            public function toMeta($request): array
            {
                return [
                    'user-internal' => 'meta',
                ];
            }

            public function toLinks($request): array
            {
                return [
                    Link::self('user-internal.com')->withMeta(['user-internal.com' => 'meta']),
                ];
            }

            public function toRelationships($request): array
            {
                return [
                    'profile' => fn () => (new class (null) extends JsonApiResource {
                        public function toLinks($request): array
                        {
                            // This should not be present in the response.
                            return [
                                Link::self('profile-internal.com')->withMeta([
                                    'profile-internal.com' => 'meta',
                                ]),
                            ];
                        }

                        public function toMeta($request): array
                        {
                            // This should not be present in the response.
                            return [
                                'profile-internal' => 'meta',
                            ];
                        }

                        public function toResourceIdentifier($request): ResourceIdentifier
                        {
                            // This should not be present in the response...
                            return parent::toResourceIdentifier($request)->withMeta([
                                'profile-internal-resource-identifier' => 'meta',
                            ]);
                        }

                        public function toResourceLink($request): RelationshipObject
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
                        fn (RelationshipObject $link) => $link->withMeta([
                            'profile-external-resource-link' => 'meta',
                        ])->withLinks([
                            Link::related('profile-external-resource-link.com')->withMeta([
                                'profile-external-resource-link.com' => 'meta',
                            ]),
                        ])
                    ),
                    'avatar' => fn () => (new class ($this->resource->avatar) extends JsonApiResource {
                        public function toLinks($request): array
                        {
                            return [
                                Link::self('avatar-internal.com')->withMeta([
                                    'avatar-internal.com' => 'meta',
                                ]),
                            ];
                        }

                        public function toMeta($request): array
                        {
                            return [
                                'avatar-internal' => 'meta',
                            ];
                        }

                        public function toResourceIdentifier($request): ResourceIdentifier
                        {
                            return parent::toResourceIdentifier($request)->withMeta([
                                'avatar-internal-resource-identifier' => 'meta',
                            ]);
                        }

                        public function toResourceLink($request): RelationshipObject
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
                        fn (RelationshipObject $link) => $link->withMeta([
                            'avatar-external-resource-link' => 'meta',
                        ])->withLinks([
                            Link::related('avatar-external-resource-link.com')->withMeta([
                                'avatar-external-resource-link.com' => 'meta',
                            ]),
                        ])
                    ),
                    'posts' => fn () => (new class ($this->posts) extends JsonApiResource {
                        public function toMeta($request): array
                        {
                            return [
                                'posts-internal' => 'meta',
                            ];
                        }

                        public function toLinks($request): array
                        {
                            return [
                                Link::self('posts-internal.com')->withMeta([
                                    'posts-internal.com' => 'meta',
                                ]),
                            ];
                        }

                        public function toResourceIdentifier($request): ResourceIdentifier
                        {
                            return parent::toResourceIdentifier($request)->withMeta([
                                'posts-internal-resource-identifier' => 'meta',
                            ]);
                        }

                        public function toResourceLink($request): RelationshipObject
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
                                    ]),
                                ])->withMeta([
                                    'posts-internal-collection-resource-link' => 'meta',
                                ]))
                                ->map(
                                    fn (JsonApiResource $resource) => $resource->withMeta([
                                        'posts-internal-collection' => 'meta',
                                    ])->withLinks([
                                        Link::related('posts-internal-collection.com')->withMeta([
                                            'posts-internal-collection.com' => 'meta',
                                        ]),
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
                        ->map(fn ($post) => $post->withResourceIdentifier(static fn ($identifier) => $identifier->withMeta([
                            'posts-external-resource-identifier' => 'meta',
                        ]))->withMeta([
                            'posts-external' => 'meta',
                        ])->withLinks([
                            new Link('external', 'posts.com'),
                        ])),
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
                    'meta' => [
                        'posts-internal' => 'meta',
                        'posts-internal-collection' => 'meta',
                        'posts-external' => 'meta',
                    ],
                    'links' => [
                        'self' => [
                            'href' => 'posts-internal.com',
                            'meta' => [
                                'posts-internal.com' => 'meta',
                            ],
                        ],
                        'related' => [
                            'href' => 'posts-internal-collection.com',
                            'meta' => [
                                'posts-internal-collection.com' => 'meta',
                            ],
                        ],
                        'external' => [
                            'href' => 'posts.com',
                        ],
                    ],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'meta' => [
                        'posts-internal' => 'meta',
                        'posts-internal-collection' => 'meta',
                        'posts-external' => 'meta',
                    ],
                    'links' => [
                        'self' => [
                            'href' => 'posts-internal.com',
                            'meta' => [
                                'posts-internal.com' => 'meta',
                            ],
                        ],
                        'related' => [
                            'href' => 'posts-internal-collection.com',
                            'meta' => [
                                'posts-internal-collection.com' => 'meta',
                            ],
                        ],
                        'external' => [
                            'href' => 'posts.com',
                        ],
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
    }
}
