<?php

declare(strict_types=1);

namespace Tests\Unit;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
                ],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'author-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'author-name',
                ],
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
                ]],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'comment-id',
                'type' => 'basicModels',
                'attributes' => [
                    'content' => 'comment-content',
                ],
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
                ]],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'comment-id',
                'type' => 'basicModels',
                'attributes' => [
                    'content' => 'comment-content',
                ],
            ],
        ], $response->getData(true)['included']);
    }

    public function testItCanSpecifyRelationshipsAsPropertiesWithoutAClass()
    {
        JsonApiResource::guessRelationshipResourceUsing(
            fn (string $relationship): string => 'Tests\\Resources\\'.Str::of($relationship)->singular()->studly().'Resource'
        );
        $user = BasicModel::make([
            'id' => 'author-id',
            'name' => 'author-name',
        ])->setRelation('posts', [
            BasicModel::make([
                'id' => 'post-id',
                'title' => 'post-title',
                'content' => 'post-content',
            ]),
        ]);
        $class = new class ($user) extends UserResource {
            protected array $relationships = [
                'posts',
            ];

            public function toRelationships($request)
            {
                return [];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me?include=posts'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'posts' => [
                'data' => [
                    [
                        'type' => 'basicModels',
                        'id' => 'post-id',
                    ],
                ],
            ],
        ], $response->getData(true)['data']['relationships']);
        $this->assertSame([
            [
                'id' => 'post-id',
                'type' => 'basicModels',
                'attributes' => [
                    'title' => 'post-title',
                    'content' => 'post-content',
                ],
            ],
        ], $response->getData(true)['included']);
        JsonApiResource::guessRelationshipResourceUsing(null);
    }

    public function testItDoesntTryToAccessMagicAttributeProperty()
    {
        $instance = new class () extends Model {
            public function getRelationshipsAttribute()
            {
                throw new Exception('xxxx');
            }
        };
        $resource = new class ($instance) extends JsonApiResource {
            //
        };

        $response = $resource->toResponse(Request::create('https://timacdonald.me'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([], $response->getData(true)['data']['relationships'] ?? []);
    }
}
