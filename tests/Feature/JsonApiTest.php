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
    public function test_it_can_return_a_single_resource(): void
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
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_return_a_collection(): void
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
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_excludes_empty_attributes_and_relationships(): void
    {
        Route::get('test-route', fn () => UserResource::make((new BasicModel(['id' => 'user-id']))));

        $response = $this->getJson('test-route?fields[basicModels]=');

        self::assertStringNotContainsString('"attributes"', $response->content());
        self::assertStringNotContainsString('"relationships"', $response->content());
        self::assertStringNotContainsString('"meta"', $response->content());
        self::assertStringNotContainsString('"links"', $response->content());
        $this->assertValidJsonApi($response);
    }

    public function test_it_adds_meta_to_individual_resources(): void
    {
        Route::get('test-route', fn () => new class((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource
        {
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
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_handles_non_standard_links(): void
    {
        Route::get('test-route', fn () => new class((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource
        {
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
        ]);
        // Asserting non-standard links, so this won't pass validation. See testItHandlesSelfLinks
        // for standard links.
        // $this->assertValidJsonApi($response);
    }

    public function test_it_handles_self_links(): void
    {
        Route::get('test-route', fn () => new class((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource
        {
            public function toLinks($request): array
            {
                return [
                    Link::self('https://example.test/self', [
                        'some' => 'meta',
                    ]),
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
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_sets_the_content_type_header_for_a_single_resource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'xxxx']))));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
        $this->assertValidJsonApi($response);
    }

    public function test_it_sets_the_content_type_header_for_a_collection_of_resources(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::collection([(new BasicModel(['id' => 'xxxx']))]));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_customise_the_type_resolution(): void
    {
        JsonApiResource::resolveTypeUsing(fn (BasicModel $model): string => str_replace('\\', '_', $model::class));
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'expected-id']))));

        $response = $this->get('test-route');

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'Tests_Models_BasicModel',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_customise_the_id_resolution(): void
    {
        JsonApiResource::resolveIdUsing(fn (BasicModel $model): string => 'expected-id');
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'missing-id']))));

        $response = $this->get('test-route');

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_excludes_empty_resource_identifier_meta(): void
    {
        $relationship = new ResourceIdentifier('users', '5');

        $json = json_encode($relationship);

        self::assertSame('{"type":"users","id":"5"}', $json);
    }

    public function test_it_excludes_empty_links_meta(): void
    {
        $link = Link::self('https://timacdonald.me', []);

        $json = json_encode($link);

        self::assertSame('{"href":"https:\/\/timacdonald.me"}', $json);
    }

    public function test_it_excludes_empty_implementation_meta(): void
    {
        $implementation = new ServerImplementation('1.5', []);

        $json = json_encode($implementation);

        self::assertSame('{"version":"1.5"}', $json);
    }

    public function test_it_can_specify_an_implementation(): void
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

    public function test_it_excludes_empty_relationship_link_meta()
    {
        $resourceLink = RelationshipObject::toOne(
            new ResourceIdentifier('expected-type', 'expected-id')
        );

        $json = json_encode($resourceLink);

        self::assertSame('{"data":{"type":"expected-type","id":"expected-id"}}', $json);
    }

    public function test_it_can_populate_all_the_metas_and_all_the_links()
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
        Route::get('test-route', fn () => (new class($user) extends JsonApiResource
        {
            public function toMeta($request): array
            {
                return [
                    'user-internal' => 'meta',
                ];
            }

            public function toLinks($request): array
            {
                return [
                    Link::self('https://example.com/user-internal')->withMeta(['https___example_com_user-internal' => 'meta']),
                ];
            }

            public function toRelationships($request): array
            {
                return [
                    'profile' => fn () => (new class(null) extends JsonApiResource
                    {
                        public function toLinks($request): array
                        {
                            // This should not be present in the response.
                            return [
                                Link::self('https://example.com/profile-internal')->withMeta([
                                    'https___example_com_profile-internal' => 'meta',
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
                                Link::self('https://example.com/profile-internal-resource-link')->withMeta([
                                    'https___example_com_profile-internal-resource-link' => 'meta',
                                ]),
                            ]);
                        }
                    })->withMeta([
                        // This should not be in the response.
                        'profile-external' => 'meta',
                    ])->withLinks([
                        // This should not be in the response.
                        Link::related('https://example.com/profile-external')->withMeta([
                            'https___example_com_profile-external' => 'meta',
                        ]),
                    ])->pipeResourceIdentifier(
                        // This should not be in the response.
                        fn (ResourceIdentifier $identifier) => $identifier->withMeta([
                            'profile-external-resource-identifier' => 'meta',
                        ])
                    )->withRelationshipLink(
                        fn (RelationshipObject $link) => $link->withMeta([
                            'profile-external-resource-link' => 'meta',
                        ])->withLinks([
                            Link::related('https://example.com/profile-external-resource-link')->withMeta([
                                'https___example_com_profile-external-resource-link' => 'meta',
                            ]),
                        ])
                    ),
                    'avatar' => fn () => (new class($this->resource->avatar) extends JsonApiResource
                    {
                        public function toLinks($request): array
                        {
                            return [
                                Link::self('https://example.com/avatar-internal')->withMeta([
                                    'https___example_com_avatar-internal' => 'meta',
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
                                Link::self('https://example.com/avatar-internal-resource-link')->withMeta([
                                    'https___example_com_avatar-internal-resource-link' => 'meta',
                                ]),
                            ]);
                        }
                    })->withMeta([
                        'avatar-external' => 'meta',
                    ])->withLinks([
                        Link::related('https://example.com/avatar-external')->withMeta([
                            'https___example_com_avatar-external' => 'meta',
                        ]),
                    ])->pipeResourceIdentifier(
                        fn (ResourceIdentifier $identifier) => $identifier->withMeta([
                            'avatar-external-resource-identifier' => 'meta',
                        ])
                    )->withRelationshipLink(
                        fn (RelationshipObject $link) => $link->withMeta([
                            'avatar-external-resource-link' => 'meta',
                        ])->withLinks([
                            Link::related('https://example.com/avatar-external-resource-link')->withMeta([
                                'https___example_com_avatar-external-resource-link' => 'meta',
                            ]),
                        ])
                    ),
                    'posts' => fn () => (new class($this->posts) extends JsonApiResource
                    {
                        public function toMeta($request): array
                        {
                            return [
                                'posts-internal' => 'meta',
                            ];
                        }

                        public function toLinks($request): array
                        {
                            return [
                                Link::self('https://example.com/posts-internal')->withMeta([
                                    'https___example_com_posts-internal' => 'meta',
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
                                Link::self('https://example.com/posts-internal-resource-link')->withMeta([
                                    'https___example_com_posts-internal-resource-link' => 'meta',
                                ]),
                            ]);
                        }

                        public static function collection($resource): JsonApiResourceCollection
                        {
                            return parent::collection($resource)
                                ->withRelationshipLink(fn ($link) => $link->withLinks([
                                    Link::self('https://example.com/posts-collection-internal-resource-link', [
                                        'https___example_com_posts-collection-internal-resource-link' => 'meta',
                                    ]),
                                ])->withMeta([
                                    'posts-internal-collection-resource-link' => 'meta',
                                ]))
                                ->map(
                                    fn (JsonApiResource $resource) => $resource->withMeta([
                                        'posts-internal-collection' => 'meta',
                                    ])->withLinks([
                                        Link::related('https://example.com/posts-internal-collection')->withMeta([
                                            'https___example_com_posts-internal-collection' => 'meta',
                                        ]),
                                    ])->pipeResourceIdentifier(fn ($identifier) => $identifier->withMeta([
                                        'posts-internal-collection-resource-identifier' => 'meta',
                                    ]))
                                );
                        }
                    })::collection($this->posts)
                        ->withRelationshipLink(fn ($link) => $link->withLinks([
                            Link::related('https://example.com/posts-external-resource-link', [
                                'https___example_com_posts-external-resource-link' => 'meta',
                            ]),
                        ])->withMeta([
                            'posts-external-resource-link' => 'meta',
                        ]))
                        ->map(fn ($post) => $post->pipeResourceIdentifier(static fn ($identifier) => $identifier->withMeta([
                            'posts-external-resource-identifier' => 'meta',
                        ]))->withMeta([
                            'posts-external' => 'meta',
                        ])->withLinks([
                            new Link('external', 'https://example.com/posts'),
                        ])),
                ];
            }
        })->withMeta([
            'user-external' => 'meta',
        ])->withLinks([
            Link::related('https://example.com/user-external')->withMeta([
                'https___example_com_user-external' => 'meta',
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
                                'href' => 'https://example.com/profile-internal-resource-link',
                                'meta' => [
                                    'https___example_com_profile-internal-resource-link' => 'meta',
                                ],
                            ],
                            'related' => [
                                'href' => 'https://example.com/profile-external-resource-link',
                                'meta' => [
                                    'https___example_com_profile-external-resource-link' => 'meta',
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
                                'href' => 'https://example.com/avatar-internal-resource-link',
                                'meta' => [
                                    'https___example_com_avatar-internal-resource-link' => 'meta',
                                ],
                            ],
                            'related' => [
                                'href' => 'https://example.com/avatar-external-resource-link',
                                'meta' => [
                                    'https___example_com_avatar-external-resource-link' => 'meta',
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
                                'href' => 'https://example.com/posts-collection-internal-resource-link',
                                'meta' => [
                                    'https___example_com_posts-collection-internal-resource-link' => 'meta',
                                ],
                            ],
                            'related' => [
                                'href' => 'https://example.com/posts-external-resource-link',
                                'meta' => [
                                    'https___example_com_posts-external-resource-link' => 'meta',
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
                        'href' => 'https://example.com/user-internal',
                        'meta' => [
                            'https___example_com_user-internal' => 'meta',
                        ],
                    ],
                    'related' => [
                        'href' => 'https://example.com/user-external',
                        'meta' => [
                            'https___example_com_user-external' => 'meta',
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
                            'href' => 'https://example.com/avatar-internal',
                            'meta' => [
                                'https___example_com_avatar-internal' => 'meta',
                            ],
                        ],
                        'related' => [
                            'href' => 'https://example.com/avatar-external',
                            'meta' => [
                                'https___example_com_avatar-external' => 'meta',
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
                            'href' => 'https://example.com/posts-internal',
                            'meta' => [
                                'https___example_com_posts-internal' => 'meta',
                            ],
                        ],
                        'related' => [
                            'href' => 'https://example.com/posts-internal-collection',
                            'meta' => [
                                'https___example_com_posts-internal-collection' => 'meta',
                            ],
                        ],
                        'external' => [
                            'href' => 'https://example.com/posts',
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
                            'href' => 'https://example.com/posts-internal',
                            'meta' => [
                                'https___example_com_posts-internal' => 'meta',
                            ],
                        ],
                        'related' => [
                            'href' => 'https://example.com/posts-internal-collection',
                            'meta' => [
                                'https___example_com_posts-internal-collection' => 'meta',
                            ],
                        ],
                        'external' => [
                            'href' => 'https://example.com/posts',
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
        // TODO temporarily disabled. resource links should only contain "self" links. We currently allow anything.
        // Not sure I want to enforce this...?
        // $this->assertValidJsonApi($response);
    }
}
