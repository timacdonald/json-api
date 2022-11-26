<?php

declare(strict_types=1);

namespace Tests\Unit;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\PostResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;

class RelationshipsTest extends TestCase
{
    public function testItThrowsWhenTheIncludeQueryParameterIsAnArray(): void
    {
        $post = (new BasicModel([]));
        Route::get('test-route', static fn () => PostResource::make($post));

        $response = $this->withExceptionHandling()->getJson('test-route?include[]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The include parameter must be a comma seperated list of relationship paths.',
        ]);
    }

    public function testItDoesntResolveRelationshipClosuresUnlessIncluded(): void
    {
        $post = (new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]));
        Route::get('test-route', static fn () => new class ($post) extends PostResource {
            public function toRelationships($request): array
            {
                return [
                    'author' => static fn () => throw new Exception('xxxx'),
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'post-id',
                'type' => 'basicModels',
                'attributes' => [
                    'title' => 'post-title',
                    'content' => 'post-content',
                ],
                'relationships' => [],
                'links' => [],
                'meta' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeASingleToOneResourceForASingleResource(): void
    {
        $post = (new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]));
        $post->author = (new BasicModel([
            'id' => 'author-id',
            'name' => 'author-name',
        ]));
        Route::get('test-route', static fn () => PostResource::make($post));

        $response = $this->getJson('test-route?include=author');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'post-id',
                'type' => 'basicModels',
                'attributes' => [
                    'title' => 'post-title',
                    'content' => 'post-content',
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'id' => 'author-id',
                            'type' => 'basicModels',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'links' => [],
                'meta' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'author-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeNestedToOneResourcesForASingleResource(): void
    {
        $post = (new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]));
        $post->author = (new BasicModel([
            'id' => 'author-id',
            'name' => 'author-name',
        ]));
        $post->author->avatar = (new BasicModel([
            'id' => 'avatar-id',
            'url' => 'https://example.com/avatar.png',
        ]));
        $post->author->license = (new BasicModel([
            'id' => 'license-id',
            'key' => 'license-key',
        ]));
        $post->feature_image = (new BasicModel([
            'id' => 'feature-image-id',
            'url' => 'https://example.com/doggo.png',
        ]));
        Route::get('test-route', static fn () => PostResource::make($post));

        $response = $this->getJson('test-route?include=author.avatar,author.license,featureImage');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'post-id',
                'type' => 'basicModels',
                'attributes' => [
                    'title' => 'post-title',
                    'content' => 'post-content',
                ],
                'relationships' => [
                    'author' => [
                        'data' => [
                            'id' => 'author-id',
                            'type' => 'basicModels',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                    'featureImage' => [
                        'data' => [
                            'id' => 'feature-image-id',
                            'type' => 'basicModels',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'links' => [],
                'meta' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'author-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name',
                    ],
                    'relationships' => [
                        'avatar' => [
                            'data' => [
                                'id' => 'avatar-id',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                        'license' => [
                            'data' => [
                                'id' => 'license-id',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'feature-image-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/doggo.png',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'license-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'key' => 'license-key',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'avatar-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/avatar.png',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeNestedResourcesWhenTheirKeyIsTheSame(): void
    {
        $parent = (new BasicModel([
            'id' => 'parent-id',
        ]))->setRelation('child', (new BasicModel([
            'id' => 'child-id-1',
        ]))->setRelation('child', (new BasicModel([
            'id' => 'child-id-2',
        ]))));
        Route::get('test-route', fn () => new class ($parent) extends JsonApiResource {
            public function toRelationships($request): array
            {
                return [
                    'child' => fn () => new class ($this->child) extends JsonApiResource {
                        public function toRelationships($request): array
                        {
                            return [
                                'child' => fn () => BasicJsonApiResource::make($this->child),
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=child.child');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'parent-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [
                    'child' => [
                        'data' => [
                            'id' => 'child-id-1',
                            'type' => 'basicModels',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'links' => [],
                'meta' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'child-id-1',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [
                        'child' => [
                            'data' => [
                                'id' => 'child-id-2',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'child-id-2',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeANestedCollectionOfResourcesWhenTheirKeyIsTheSame(): void
    {
        $parent = (new BasicModel([
            'id' => 'parent-id',
        ]))->setRelation('child', (new BasicModel([
            'id' => 'child-id-1',
        ]))->setRelation('child', [
            (new BasicModel(['id' => 'child-id-2'])),
            (new BasicModel(['id' => 'child-id-3'])),
        ]));
        Route::get('test-route', fn () => new class ($parent) extends JsonApiResource {
            public function toRelationships($request): array
            {
                return [
                    'child' => fn () => new class ($this->child) extends JsonApiResource {
                        public function toRelationships($request): array
                        {
                            return [
                                'child' => fn () => BasicJsonApiResource::collection($this->child),
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=child.child');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'parent-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [
                    'child' => [
                        'data' => [
                            'id' => 'child-id-1',
                            'type' => 'basicModels',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'child-id-1',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [
                        'child' => [
                            'data' => [
                                [
                                    'id' => 'child-id-2',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'child-id-3',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'child-id-2',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships'=> [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'child-id-3',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships'=> [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeToOneResourcesForACollectionOfResources(): void
    {
        $posts = [
            (new BasicModel([
                'id' => 'post-id-1',
                'title' => 'post-title-1',
                'content' => 'post-content-1',
            ]))->setRelation('author', (new BasicModel([
                'id' => 'author-id-1',
                'name' => 'author-name-1',
            ]))),
            (new BasicModel([
                'id' => 'post-id-2',
                'title' => 'post-title-2',
                'content' => 'post-content-2',
            ]))->setRelation('author', (new BasicModel([
                'id' => 'author-id-2',
                'name' => 'author-name-2',
            ]))),
        ];
        Route::get('test-route', static fn () => PostResource::collection($posts));

        $response = $this->getJson('test-route?include=author');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'post-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-1',
                        'content' => 'post-content-1',
                    ],
                    'relationships' => [
                        'author' => [
                            'data' => [
                                'id' => 'author-id-1',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-2',
                        'content' => 'post-content-2',
                    ],
                    'relationships' => [
                        'author' => [
                            'data' => [
                                'id' => 'author-id-2',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'author-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name-1',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'author-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name-2',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeACollectionOfResourcesForASingleResource(): void
    {
        $author = (new BasicModel([
            'id' => 'author-id',
            'name' => 'author-name',
        ]));
        $author->posts = [
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
        ];
        Route::get('test-route', static fn () => UserResource::make($author));

        $response = $this->getJson('test-route?include=posts');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'author-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'author-name',
                ],
                'relationships' => [
                    'posts' => [
                        'data' => [
                            [
                                'id' => 'post-id-1',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            [
                                'id' => 'post-id-2',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'post-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-1',
                        'content' => 'post-content-1',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-2',
                        'content' => 'post-content-2',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanIncludeAManyManyManyRelationship(): void
    {
        $posts = [
            (new BasicModel([
                'id' => 'post-id-1',
                'title' => 'post-title-1',
                'content' => 'post-content-1',
            ]))->setRelation('comments', [
                (new BasicModel([
                    'id' => 'comment-id-1',
                    'content' => 'comment-content-1',
                ]))->setRelation('likes', [
                    (new BasicModel([
                        'id' => 'like-id-1',
                    ])),
                    (new BasicModel([
                        'id' => 'like-id-2',
                    ])),
                ]),
                (new BasicModel([
                    'id' => 'comment-id-2',
                    'content' => 'comment-content-2',
                ]))->setRelation('likes', [
                    (new BasicModel([
                        'id' => 'like-id-3',
                    ])),
                    (new BasicModel([
                        'id' => 'like-id-4',
                    ])),
                ]),
            ]),
            (new BasicModel([
                'id' => 'post-id-2',
                'title' => 'post-title-2',
                'content' => 'post-content-2',
            ]))->setRelation('comments', [
                (new BasicModel([
                    'id' => 'comment-id-3',
                    'content' => 'comment-content-3',
                ]))->setRelation('likes', [
                    (new BasicModel([
                        'id' => 'like-id-5',
                    ])),
                    (new BasicModel([
                        'id' => 'like-id-6',
                    ])),
                ]),
                (new BasicModel([
                    'id' => 'comment-id-4',
                    'content' => 'comment-content-4',
                ]))->setRelation('likes', [
                    (new BasicModel([
                        'id' => 'like-id-7',
                    ])),
                    (new BasicModel([
                        'id' => 'like-id-8',
                    ])),
                ]),
            ]),
        ];
        Route::get('test-route', static fn () => PostResource::collection($posts));

        $response = $this->getJson('test-route?include=comments.likes');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'post-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-1',
                        'content' => 'post-content-1',
                    ],
                    'relationships' => [
                        'comments' => [
                            'data' => [
                                [
                                    'id' => 'comment-id-1',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'comment-id-2',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-2',
                        'content' => 'post-content-2',
                    ],
                    'relationships' => [
                        'comments' => [
                            'data' => [
                                [
                                    'id' => 'comment-id-3',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'comment-id-4',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'comment-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'content' => 'comment-content-1',
                    ],
                    'relationships' => [
                        'likes' => [
                            'data' => [
                                [
                                    'id' => 'like-id-1',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'like-id-2',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'comment-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'content' => 'comment-content-2',
                    ],
                    'relationships' => [
                        'likes' => [
                            'data' => [
                                [
                                    'id' => 'like-id-3',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'like-id-4',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'like-id-1',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-2',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-3',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-4',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'comment-id-3',
                    'type' => 'basicModels',
                    'attributes' => [
                        'content' => 'comment-content-3',
                    ],
                    'relationships' => [
                        'likes' => [
                            'data' => [
                                [
                                    'id' => 'like-id-5',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'like-id-6',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'comment-id-4',
                    'type' => 'basicModels',
                    'attributes' => [
                        'content' => 'comment-content-4',
                    ],
                    'relationships' => [
                        'likes' => [
                            'data' => [
                                [
                                    'id' => 'like-id-7',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                                [
                                    'id' => 'like-id-8',
                                    'type' => 'basicModels',
                                    'meta' => [],
                                ],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-5',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-6',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-7',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => 'like-id-8',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testRelationshipsClosuresGetTheRequestAsAnArgument(): void
    {
        $post = (new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]));
        Route::get('test-route', fn () => new class ($post) extends JsonApiResource {
            public function toRelationships($request): array
            {
                return [
                    'relation' => fn () => new class ($request) extends JsonApiResource {
                        public function toId($request): string
                        {
                            return 'relation-id';
                        }

                        public function toType($request): string
                        {
                            return 'relation-type';
                        }

                        public function toAttributes($request): array
                        {
                            return [
                                'name' => $this->resource->input('name'),
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=relation&name=expected%20name');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'post-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [
                    'relation' => [
                        'data' => [
                            'id' => 'relation-id',
                            'type' => 'relation-type',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'relation-id',
                    'type' => 'relation-type',
                    'attributes' => [
                        'name' => 'expected name',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItFiltersOutDuplicateIncludesForACollectionOfResources(): void
    {
        $users = [
            (new BasicModel([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ]))->setRelation('avatar', (new BasicModel([
                'id' => 'avatar-id',
                'url' => 'https://example.com/avatar.png',
            ]))),
            (new BasicModel([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ]))->setRelation('avatar', (new BasicModel([
                'id' => 'avatar-id',
                'url' => 'https://example.com/avatar.png',
            ]))),
        ];
        Route::get('test-route', static fn () => UserResource::collection($users));

        $response = $this->getJson('test-route?include=avatar');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'user-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-1',
                    ],
                    'relationships' => [
                        'avatar' => [
                            'data' => [
                                'id' => 'avatar-id',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2',
                    ],
                    'relationships' => [
                        'avatar' => [
                            'data' => [
                                'id' => 'avatar-id',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                            'links' => [],
                            'meta' => [],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'avatar-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/avatar.png',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItFiltersOutDuplicateResourceObjectsIncludesForASingleResource(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]))->setRelation('posts', [
            (new BasicModel([
                'id' => 'post-id',
                'title' => 'post-title',
                'content' => 'post-content',
            ])),
            (new BasicModel([
                'id' => 'post-id',
                'title' => 'post-title',
                'content' => 'post-content',
            ])),
        ]);
        Route::get('test-route', static fn () => UserResource::make($user));

        $response = $this->getJson('test-route?include=posts');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [
                    'posts' => [
                        'data' => [
                            [
                                'id' => 'post-id',
                                'type' => 'basicModels',
                                'meta' => [],
                            ],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' =>[],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => 'post-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title',
                        'content' => 'post-content',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' =>[],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItHasIncludedArrayWhenIncludeParameterIsPresentForASingleResource(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', static fn () => UserResource::make($user));

        $response = $this
            ->withoutMiddleware()
            ->getJson('test-route?include=');

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
                'links' =>[],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItHasIncludedArrayWhenIncludeParameterIsPresentForACollectionOfResources(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', static fn () => UserResource::collection([$user]));

        $response = $this
            ->withoutMiddleware()
            ->getJson('test-route?include=');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'user-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanReturnNullForEmptyToOneRelationships(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', static fn () => UserResource::make($user));

        $response = $this->get('test-route?include=avatar');

        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [
                    'avatar' => [
                        'data' => null,
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanReturnAnEmptyArrayForEmptyToManyRelationships(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]))->setRelation('posts', new Collection([]));
        Route::get('test-route', static fn () => UserResource::make($user));

        $response = $this->get('test-route?include=posts');

        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [
                    'posts' => [
                        "data" => [],
                        "links" => [],
                        "meta" => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItFlushesTheRelationshipCache(): void
    {
        $user = (new BasicModel(['id' => '1']))->setRelation('posts', [(new BasicModel(['id' => '2']))]);
        $resource = UserResource::make($user);
        Route::get('test-route', static fn () => $resource);

        $response = $this->get("test-route?include=posts");

        $response->assertOk();
        $this->assertValidJsonApi($response);
        $this->assertNull($resource->requestedRelationshipsCache());
    }

    public function testItRemovesPotentiallyMissingRelationships(): void
    {
        $user = new BasicModel([
            'id' => '1',
            'name' => 'user-name',
        ]);
        $resource = new class ($user) extends UserResource {
            public function toRelationships($request): array
            {
                return [
                    'relation' => fn () => $this->when(false, static fn () => ['hello' => 'world']),
                ];
            }
        };
        Route::get('test-route', static fn () => $resource);

        $response = $this->get('test-route?include=relation,relation_2');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '1',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItShowsPotentiallyMissingRelationships(): void
    {
        $user = new BasicModel([
            'id' => '1',
            'name' => 'user-name',
        ]);
        $resource = new class ($user) extends UserResource {
            public function toRelationships($request): array
            {
                return [
                    'relation' => fn () => $this->when(true, static fn () => new class (new BasicModel([
                        'id' => '2',
                        'name' => 'relation-name',
                    ])) extends UserResource {
                    }),
                ];
            }
        };
        Route::get('test-route', static fn () => $resource);

        $response = $this->get('test-route?include=relation');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '1',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [
                    'relation' => [
                        'data' => [
                            'type' => 'basicModels',
                            'id' => '2',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'relation-name',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testPotentiallyMissingValuesAreRespectedOverSparseFieldsets()
    {
        $user = new BasicModel([
            'id' => '1',
            'name' => 'user-name',
        ]);
        $resource = new class ($user) extends UserResource {
            public function toRelationships($request): array
            {
                return [
                    'relation_1' => fn () => $this->when(false, static fn () => ['hello' => 'world']),
                    'relation_2' => fn () => $this->when(true, static fn () => new class (new BasicModel([
                        'id' => '2',
                        'name' => 'relation-name',
                    ])) extends UserResource {
                    }),
                ];
            }
        };
        Route::get('test-route', static fn () => $resource);

        $response = $this->get('test-route?include=relation_1,relation_2');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '1',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [
                    'relation_2' => [
                        'data' => [
                            'type' => 'basicModels',
                            'id' => '2',
                            'meta' => [],
                        ],
                        'links' => [],
                        'meta' => [],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
            'included' => [
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'relation-name',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }
}
