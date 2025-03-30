<?php

declare(strict_types=1);

namespace Tests\Feature;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\PostResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;

class RelationshipsTest extends TestCase
{
    public function test_it_throws_when_the_include_query_parameter_is_an_array(): void
    {
        $post = (new BasicModel([]));
        Route::get('test-route', fn () => PostResource::make($post));

        $response = $this->withExceptionHandling()->getJson('test-route?include[]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The include parameter must be a comma seperated list of relationship paths.',
        ]);
    }

    public function test_it_doesnt_resolve_relationship_closures_unless_included(): void
    {
        $post = (new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]));
        Route::get('test-route', fn () => new class($post) extends PostResource
        {
            public function toRelationships($request): array
            {
                return [
                    'author' => fn () => throw new Exception('xxxx'),
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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_a_single_to_one_resource_for_a_single_resource(): void
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
        Route::get('test-route', fn () => PostResource::make($post));

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
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'author-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_nested_to_one_resources_for_a_single_resource(): void
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
        Route::get('test-route', fn () => PostResource::make($post));

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
                        ],
                    ],
                    'featureImage' => [
                        'data' => [
                            'id' => 'feature-image-id',
                            'type' => 'basicModels',
                        ],
                    ],
                ],
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
                            ],
                        ],
                        'license' => [
                            'data' => [
                                'id' => 'license-id',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'feature-image-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/doggo.png',
                    ],
                ],
                [
                    'id' => 'license-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'key' => 'license-key',
                    ],
                ],
                [
                    'id' => 'avatar-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/avatar.png',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_nested_resources_when_their_key_is_the_same(): void
    {
        $parent = (new BasicModel([
            'id' => 'parent-id',
        ]))->setRelation('child', (new BasicModel([
            'id' => 'child-id-1',
        ]))->setRelation('child', (new BasicModel([
            'id' => 'child-id-2',
        ]))));
        Route::get('test-route', fn () => new class($parent) extends JsonApiResource
        {
            public function toRelationships($request): array
            {
                return [
                    'child' => fn () => new class($this->child) extends JsonApiResource
                    {
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
                'relationships' => [
                    'child' => [
                        'data' => [
                            'id' => 'child-id-1',
                            'type' => 'basicModels',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'child-id-1',
                    'type' => 'basicModels',
                    'relationships' => [
                        'child' => [
                            'data' => [
                                'id' => 'child-id-2',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'child-id-2',
                    'type' => 'basicModels',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_a_nested_collection_of_resources_when_their_key_is_the_same(): void
    {
        $parent = (new BasicModel([
            'id' => 'parent-id',
        ]))->setRelation('child', (new BasicModel([
            'id' => 'child-id-1',
        ]))->setRelation('child', [
            (new BasicModel(['id' => 'child-id-2'])),
            (new BasicModel(['id' => 'child-id-3'])),
        ]));
        Route::get('test-route', fn () => new class($parent) extends JsonApiResource
        {
            public function toRelationships($request): array
            {
                return [
                    'child' => fn () => new class($this->child) extends JsonApiResource
                    {
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
                'relationships' => [
                    'child' => [
                        'data' => [
                            'id' => 'child-id-1',
                            'type' => 'basicModels',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'child-id-1',
                    'type' => 'basicModels',
                    'relationships' => [
                        'child' => [
                            'data' => [
                                [
                                    'id' => 'child-id-2',
                                    'type' => 'basicModels',
                                ],
                                [
                                    'id' => 'child-id-3',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'child-id-2',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'child-id-3',
                    'type' => 'basicModels',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_to_one_resources_for_a_collection_of_resources(): void
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
        Route::get('test-route', fn () => PostResource::collection($posts));

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
                            ],
                        ],
                    ],
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
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'author-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name-1',
                    ],
                ],
                [
                    'id' => 'author-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name-2',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_a_collection_of_resources_for_a_single_resource(): void
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
        Route::get('test-route', fn () => UserResource::make($author));

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
                        'content' => 'post-content-1',
                    ],
                ],
                [
                    'id' => 'post-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title-2',
                        'content' => 'post-content-2',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_a_many_many_many_relationship(): void
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
        Route::get('test-route', fn () => PostResource::collection($posts));

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
                                ],
                                [
                                    'id' => 'comment-id-2',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
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
                                ],
                                [
                                    'id' => 'comment-id-4',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
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
                                ],
                                [
                                    'id' => 'like-id-2',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
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
                                ],
                                [
                                    'id' => 'like-id-4',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'like-id-1',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'like-id-2',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'like-id-3',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'like-id-4',
                    'type' => 'basicModels',
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
                                ],
                                [
                                    'id' => 'like-id-6',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
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
                                ],
                                [
                                    'id' => 'like-id-8',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'like-id-5',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'like-id-6',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'like-id-7',
                    'type' => 'basicModels',
                ],
                [
                    'id' => 'like-id-8',
                    'type' => 'basicModels',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_relationships_closures_get_the_request_as_an_argument(): void
    {
        $post = (new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]));
        Route::get('test-route', fn () => new class($post) extends JsonApiResource
        {
            public function toRelationships($request): array
            {
                return [
                    'relation' => fn () => new class($request) extends JsonApiResource
                    {
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
                'relationships' => [
                    'relation' => [
                        'data' => [
                            'id' => 'relation-id',
                            'type' => 'relation-type',
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'relation-id',
                    'type' => 'relation-type',
                    'attributes' => [
                        'name' => 'expected name',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_filters_out_duplicate_includes_for_a_collection_of_resources(): void
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
        Route::get('test-route', fn () => UserResource::collection($users));

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
                            ],
                        ],
                    ],
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
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'avatar-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/avatar.png',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_filters_out_duplicate_resource_objects_includes_for_a_single_resource(): void
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
        Route::get('test-route', fn () => UserResource::make($user));

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
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'post-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'post-title',
                        'content' => 'post-content',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_has_included_array_when_include_parameter_is_present_for_a_single_resource(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => UserResource::make($user));

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
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_has_included_array_when_include_parameter_is_present_for_a_collection_of_resources(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => UserResource::collection([$user]));

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
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_return_null_for_empty_to_one_relationships(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => UserResource::make($user));

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
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_return_an_empty_array_for_empty_to_many_relationships(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]))->setRelation('posts', new Collection([]));
        Route::get('test-route', fn () => UserResource::make($user));

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
                        'data' => [],
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_flushes_the_relationship_cache(): void
    {
        $user = (new BasicModel(['id' => '1']))->setRelation('posts', [(new BasicModel(['id' => '2']))]);
        $resource = UserResource::make($user);
        Route::get('test-route', fn () => $resource);

        $response = $this->get('test-route?include=posts');

        $response->assertOk();
        $this->assertValidJsonApi($response);
        $this->assertNull($resource->requestedRelationshipsCache());
    }

    public function test_collection_includes_doesnt_become_numeric_keyed_object_after_filtering_duplicate_records(): void
    {
        $users = [
            BasicModel::make([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ])->setRelation('avatar', BasicModel::make([
                'id' => 1,
                'url' => 'https://example.com/avatar1.png',
            ])),
            BasicModel::make([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ])->setRelation('avatar', BasicModel::make([
                'id' => 1,
                'url' => 'https://example.com/avatar1.png',
            ])),
            BasicModel::make([
                'id' => 'user-id-3',
                'name' => 'user-name-3',
            ])->setRelation('avatar', BasicModel::make([
                'id' => 2,
                'url' => 'https://example.com/avatar2.png',
            ])),
        ];
        Route::get('test-route', fn () => UserResource::collection($users));

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
                                'id' => '1',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
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
                                'id' => '1',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'user-id-3',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-3',
                    ],
                    'relationships' => [
                        'avatar' => [
                            'data' => [
                                'id' => '2',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => '1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/avatar1.png',
                    ],
                ],
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'url' => 'https://example.com/avatar2.png',
                    ],
                ],
            ],
        ]);
    }

    public function test_single_resource_includes_doesnt_become_numeric_keyed_object_after_filtering_duplicate_records(): void
    {
        $user = BasicModel::make([
            'id' => 1,
            'name' => 'user-name-1',
        ])->setRelation('posts', [
            BasicModel::make([
                'id' => 2,
                'title' => 'Title 1',
                'content' => 'Content 1',
            ])->setRelation('comments', [
                BasicModel::make([
                    'id' => 3,
                    'content' => 'Comment 1',
                ])->setRelation('author', BasicModel::make([
                    'id' => 1,
                    'name' => 'user-name-1',
                ])),
            ]),
            BasicModel::make([
                'id' => 2,
                'title' => 'Title 2',
                'content' => 'Content 2',
            ])->setRelation('comments', new Collection),
        ]);

        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route?include=posts,posts.comments,posts.comments.author');

        $response->assertOk();
        $response->assertExactJson(
            [
                'data' => [
                    'id' => '1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-1',
                    ],
                    'relationships' => [
                        'posts' => [
                            'data' => [
                                [
                                    'id' => '2',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'id' => '2',
                        'type' => 'basicModels',
                        'attributes' => [
                            'title' => 'Title 1',
                            'content' => 'Content 1',
                        ],
                        'relationships' => [
                            'comments' => [
                                'data' => [
                                    [
                                        'id' => '3',
                                        'type' => 'basicModels',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => '3',
                        'type' => 'basicModels',
                        'attributes' => [
                            'content' => 'Comment 1',
                        ],
                    ],
                ],
            ]
        );
    }

    public function test_it_removes_potentially_missing_relationships(): void
    {
        $user = new BasicModel([
            'id' => '1',
            'name' => 'user-name',
        ]);
        $resource = new class($user) extends UserResource
        {
            public function toRelationships($request): array
            {
                return [
                    'relation' => fn () => $this->when(false, fn () => ['hello' => 'world']),
                ];
            }
        };
        Route::get('test-route', fn () => $resource);

        $response = $this->get('test-route?include=relation,relation_2');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => '1',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_shows_potentially_missing_relationships(): void
    {
        $user = new BasicModel([
            'id' => '1',
            'name' => 'user-name',
        ]);
        $resource = new class($user) extends UserResource
        {
            public function toRelationships($request): array
            {
                return [
                    'relation' => fn () => $this->when(true, fn () => new class(new BasicModel(['id' => '2', 'name' => 'relation-name'])) extends UserResource {}),
                ];
            }
        };
        Route::get('test-route', fn () => $resource);

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
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'relation-name',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_potentially_missing_values_are_respected_over_sparse_fieldsets()
    {
        $user = new BasicModel([
            'id' => '1',
            'name' => 'user-name',
        ]);
        $resource = new class($user) extends UserResource
        {
            public function toRelationships($request): array
            {
                return [
                    'relation_1' => fn () => $this->when(false, fn () => ['hello' => 'world']),
                    'relation_2' => fn () => $this->when(true, fn () => new class(new BasicModel(['id' => '2', 'name' => 'relation-name'])) extends UserResource {}),
                ];
            }
        };
        Route::get('test-route', fn () => $resource);

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
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'relation-name',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function test_it_can_include_deep_nested_resources_for_a_single_resource(): void
    {
        $post = new BasicModel([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $post->author = new BasicModel([
            'id' => 'author-id',
            'name' => 'author-name',
        ]);
        $post->author->license = new BasicModel([
            'id' => 'license-id',
            'key' => 'license-key',
        ]);
        $post->author->license->user = new BasicModel([
            'id' => 'user-id',
            'name' => 'Average Joe',
        ]);
        $post->author->license->user->posts = Collection::make([
            new BasicModel([
                'id' => 'nested-post-id',
                'title' => 'Hello world!',
            ]),
        ]);
        $post->author->license->user->posts[0]->author = new BasicModel([
            'id' => 'nested-post-author-id',
            'name' => 'Tim Mac',
        ]);
        $post->author->license->user->posts[0]->comments = Collection::make([
            new BasicModel([
                'id' => 'nested-post-comment-id',
                'content' => 'Oh hey there!',
            ]),
        ]);
        Route::get('test-route', fn () => PostResource::make($post));

        $response = $this->getJson('test-route?include=author.license.user.posts.comments,author.license.user.posts.author');

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
                        ],
                    ],
                ],
            ],
            'included' => [
                [
                    'id' => 'author-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'author-name',
                    ],
                    'relationships' => [
                        'license' => [
                            'data' => [
                                'id' => 'license-id',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'license-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'key' => 'license-key',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'id' => 'user-id',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'user-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'Average Joe',
                    ],
                    'relationships' => [
                        'posts' => [
                            'data' => [
                                [
                                    'id' => 'nested-post-id',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'nested-post-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'title' => 'Hello world!',
                        'content' => null,
                    ],
                    'relationships' => [
                        'author' => [
                            'data' => [
                                'id' => 'nested-post-author-id',
                                'type' => 'basicModels',
                            ],
                        ],
                        'comments' => [
                            'data' => [
                                [
                                    'id' => 'nested-post-comment-id',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'nested-post-author-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'Tim Mac',
                    ],
                ],
                [
                    'id' => 'nested-post-comment-id',
                    'type' => 'basicModels',
                    'attributes' => [
                        'content' => 'Oh hey there!',
                    ],
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }
}
