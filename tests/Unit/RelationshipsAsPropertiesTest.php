<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\Models\BasicModel;
use Tests\Resources\CommentResource;
use Tests\Resources\LicenseResource;
use Tests\Resources\PostResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;

class RelationshipsAsPropertiesTest extends TestCase
{
    public function testItCanSpecifyRelationshipsAsProperties()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ])->setRelation('author', BasicModel::make([
            'id' => 'author-id',
            'name' => 'author-name',
        ]));
        $class = new class ($post) extends PostResource {
            protected array $relationships = [
                'author' => UserResource::class,
            ];

            public function toRelationships($request)
            {
                return [];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me?include=author'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'author' => [
                'data' => [
                    'type' => 'basicModels',
                    'id' => 'author-id',
                    'meta' => [],
                ],
                'meta' => [],
                'links' => [],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'author-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'author-name',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
        ], $response->getData(true)['included']);
    }

    public function testItCanSpecifyCollectionBasedRelationshipsAsProperties()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ])->setRelation('comments', [BasicModel::make([
            'id' => 'comment-id',
            'content' => 'comment-content',
        ])]);
        $class = new class ($post) extends PostResource {
            protected array $relationships = [
                'comments' => CommentResource::class,
            ];

            public function toRelationships($request)
            {
                return [];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me?include=comments'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'comments' => [
                'data' => [[
                    'type' => 'basicModels',
                    'id' => 'comment-id',
                    'meta' => [],
                ]],
                'meta' => [],
                'links' => [],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'comment-id',
                'type' => 'basicModels',
                'attributes' => [
                    'content' => 'comment-content',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
        ], $response->getData(true)['included']);
    }

    public function testRelationshipMethodTakesPrecedence()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class ($post) extends PostResource {
            protected array $relationships = [
                'comments[]' => LicenseResource::class,
            ];

            public function toRelationships($request)
            {
                return [
                    'comments' => fn () => CommentResource::collection([BasicModel::make([
                        'id' => 'comment-id',
                        'content' => 'comment-content',
                    ])]),
                ];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me?include=comments'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'comments' => [
                'data' => [[
                    'type' => 'basicModels',
                    'id' => 'comment-id',
                    'meta' => [],
                ]],
                'meta' => [],
                'links' => [],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'comment-id',
                'type' => 'basicModels',
                'attributes' => [
                    'content' => 'comment-content',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
        ], $response->getData(true)['included']);

    }
}
