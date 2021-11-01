<?php

declare(strict_types=1);

namespace Tests\Unit;

use Exception;
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
        $post = BasicModel::make([]);
        Route::get('test-route', fn () => PostResource::make($post));

        $response = $this->withExceptionHandling()->getJson('test-route?include[]=name');

        $response->assertStatus(400);
        $response->assertExactJson([
            'message' => 'The include parameter must be a comma seperated list of relationship paths.',
        ]);
    }

    public function testItDoesntResolveRelationshipClosuresUnlessIncluded(): void
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        Route::get('test-route', fn () => new class ($post) extends PostResource {
            protected function toRelationships(Request $request): array
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
                'relationships' => [],
                'links' => [],
                'meta' => [],
            ],
            'included' => [],
        ]);
    }

    public function testItCanIncludeASingleToOneResourceForASingleResource(): void
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $post->author = BasicModel::make([
            'id' => 'author-id',
            'name' => 'author-name',
        ]);
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
                'links' => [],
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
    }

    public function testItCanIncludeNestedToOneResourcesForASingleResource(): void
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $post->author = BasicModel::make([
            'id' => 'author-id',
            'name' => 'author-name',
        ]);
        $post->author->avatar = BasicModel::make([
            'id' => 'avatar-id',
            'url' => 'https://example.com/avatar.png',
        ]);
        $post->author->license = BasicModel::make([
            'id' => 'license-id',
            'key' => 'license-key',
        ]);
        $post->feature_image = BasicModel::make([
            'id' => 'feature-image-id',
            'url' => 'https://example.com/doggo.png',
        ]);
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
                'links' => [],
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
                            ],
                        ],
                        'license' => [
                            'data' => [
                                'id' => 'license-id',
                                'type' => 'basicModels',
                            ],
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
    }

    public function testItCanIncludeNestedResourcesWhenTheirKeyIsTheSame(): void
    {
        $parent = BasicModel::make([
            'id' => 'parent-id',
        ])->setRelation('child', BasicModel::make([
            'id' => 'child-id-1',
        ])->setRelation('child', BasicModel::make(['id' => 'child-id-2'])));
        Route::get('test-route', fn () => new class ($parent) extends JsonApiResource {
            protected function toRelationships(Request $request): array
            {
                return [
                    'child' => fn () => new class ($this->child) extends JsonApiResource {
                        public function toRelationships(Request $request): array
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
                        ],
                    ],
                ],
                'links' => [],
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
                            ],
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
    }

    public function testItCanIncludeANestedCollectionOfResourcesWhenTheirKeyIsTheSame(): void
    {
        $parent = BasicModel::make([
            'id' => 'parent-id',
        ])->setRelation('child', BasicModel::make([
            'id' => 'child-id-1',
        ])->setRelation('child', [
            BasicModel::make(['id' => 'child-id-2']),
            BasicModel::make(['id' => 'child-id-3']),
        ]));
        Route::get('test-route', fn () => new class ($parent) extends JsonApiResource {
            protected function toRelationships(Request $request): array
            {
                return [
                    'child' => fn () => new class ($this->child) extends JsonApiResource {
                        public function toRelationships(Request $request): array
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
                        ],
                    ],
                ],
                'meta' => [],
                'links' => [],
            ],
            'included' => [
                [
                    'id' => 'child-id-1',
                    'type' => 'basicModels',
                    'attributes' => [],
                    'relationships' => [
                        'child' => [
                            [
                                'data' => [
                                    'id' => 'child-id-2',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'child-id-3',
                                    'type' => 'basicModels',
                                ],
                            ],
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
    }

    public function testItCanIncludeToOneResourcesForACollectionOfResources(): void
    {
        $posts = [
            BasicModel::make([
                'id' => 'post-id-1',
                'title' => 'post-title-1',
                'content' => 'post-content-1',
            ])->setRelation('author', BasicModel::make([
                'id' => 'author-id-1',
                'name' => 'author-name-1',
            ])),
            BasicModel::make([
                'id' => 'post-id-2',
                'title' => 'post-title-2',
                'content' => 'post-content-2',
            ])->setRelation('author', BasicModel::make([
                'id' => 'author-id-2',
                'name' => 'author-name-2',
            ])),
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
                            ],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
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
    }

    public function testItCanIncludeACollectionOfResourcesForASingleResource(): void
    {
        $author = BasicModel::make([
            'id' => 'author-id',
            'name' => 'author-name',
        ]);
        $author->posts = [
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
                'meta' => [],
                'links' => [],
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
    }

    public function testItCanIncludeAManyManyManyRelationship(): void
    {
        $posts = [
            BasicModel::make([
                'id' => 'post-id-1',
                'title' => 'post-title-1',
                'content' => 'post-content-1',
            ])->setRelation('comments', [
                BasicModel::make([
                    'id' => 'comment-id-1',
                    'content' => 'comment-content-1',
                ])->setRelation('likes', [
                    BasicModel::make([
                        'id' => 'like-id-1',
                    ]),
                    BasicModel::make([
                        'id' => 'like-id-2',
                    ]),
                ]),
                BasicModel::make([
                    'id' => 'comment-id-2',
                    'content' => 'comment-content-2',
                ])->setRelation('likes', [
                    BasicModel::make([
                        'id' => 'like-id-3',
                    ]),
                    BasicModel::make([
                        'id' => 'like-id-4',
                    ]),
                ]),
            ]),
            BasicModel::make([
                'id' => 'post-id-2',
                'title' => 'post-title-2',
                'content' => 'post-content-2',
            ])->setRelation('comments', [
                BasicModel::make([
                    'id' => 'comment-id-3',
                    'content' => 'comment-content-3',
                ])->setRelation('likes', [
                    BasicModel::make([
                        'id' => 'like-id-5',
                    ]),
                    BasicModel::make([
                        'id' => 'like-id-6',
                    ]),
                ]),
                BasicModel::make([
                    'id' => 'comment-id-4',
                    'content' => 'comment-content-4',
                ])->setRelation('likes', [
                    BasicModel::make([
                        'id' => 'like-id-7',
                    ]),
                    BasicModel::make([
                        'id' => 'like-id-8',
                    ]),
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
                            [
                                'data' => [
                                    'id' => 'comment-id-1',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'comment-id-2',
                                    'type' => 'basicModels',
                                ],
                            ],
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
                            [
                                'data' => [
                                    'id' => 'comment-id-3',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'comment-id-4',
                                    'type' => 'basicModels',
                                ],
                            ],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
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
                            [
                                'data' => [
                                    'id' => 'like-id-1',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'like-id-2',
                                    'type' => 'basicModels',
                                ],
                            ],
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
                            [
                                'data' => [
                                    'id' => 'like-id-3',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'like-id-4',
                                    'type' => 'basicModels',
                                ],
                            ],
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
                            [
                                'data' => [
                                    'id' => 'like-id-5',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'like-id-6',
                                    'type' => 'basicModels',
                                ],
                            ],
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
                            [
                                'data' => [
                                    'id' => 'like-id-7',
                                    'type' => 'basicModels',
                                ],
                            ],
                            [
                                'data' => [
                                    'id' => 'like-id-8',
                                    'type' => 'basicModels',
                                ],
                            ],
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
    }

    public function testRelationshipsClosuresGetTheRequestAsAnArgument(): void
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        Route::get('test-route', fn () => new class ($post) extends JsonApiResource {
            protected function toRelationships(Request $request): array
            {
                return [
                    'relation' => fn (Request $request) => new class ($request) extends JsonApiResource {
                        protected function toId(Request $request): string
                        {
                            return 'relation-id';
                        }

                        protected function toType(Request $request): string
                        {
                            return 'relation-type';
                        }

                        protected function toAttributes(Request $request): array
                        {
                            return [
                                'name' => $this->name,
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
                        ],
                    ],
                ],
                'meta' => [],
                'links' => [],
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
    }

    public function testItFiltersOutDuplicateIncludesForACollectionOfResources(): void
    {
        $users = [
            BasicModel::make([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ])->setRelation('avatar', BasicModel::make([
                'id' => 'avatar-id',
                'url' => 'https://example.com/avatar.png',
            ])),
            BasicModel::make([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ])->setRelation('avatar', BasicModel::make([
                'id' => 'avatar-id',
                'url' => 'https://example.com/avatar.png',
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
                                'id' => 'avatar-id',
                                'type' => 'basicModels',
                            ],
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
                            ],
                        ],
                    ],
                    'meta' => [],
                    'links' => [],
                ],
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
    }

    public function testItFiltersOutDuplicateIncludesForASingleResource(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ])->setRelation('posts', [
            BasicModel::make([
                'id' => 'post-id',
                'title' => 'post-title',
                'content' => 'post-content',
            ]),
            BasicModel::make([
                'id' => 'post-id',
                'title' => 'post-title',
                'content' => 'post-content',
            ]),
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
                        [
                            'data' => [
                                'id' => 'post-id',
                                'type' => 'basicModels',
                            ],
                        ],
                    ],
                ],
                'meta' => [],
                'links' =>[],
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
    }

    public function testItHasIncludedArrayWhenIncludeParameterIsPresentForASingleResource(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route?include=');

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
            'included' => [],
        ]);
    }

    public function testItHasIncludedArrayWhenIncludeParameterIsPresentForACollectionOfResources(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::collection([$user]));

        $response = $this->getJson('test-route?include=');

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
            'included' => [],
        ]);
    }

    public function testItCanReturnNullForEmptyToOneRelationships(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
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
                    'avatar' => null,
                ],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
        ]);
    }

    public function tsetItCanReturnAnEmptyArrayForEmptyToManyRelationships(): void
    {
        $this->markTestIncomplete();
    }
}
