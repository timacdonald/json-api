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
    public function test_it_can_specify_relationships_as_properties()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ])->setRelation('author', BasicModel::make([
            'id' => 'author-id',
            'name' => 'author-name',
        ]));
        $class = new class($post) extends PostResource
        {
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

    public function test_it_can_specify_collection_based_relationships_as_properties()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ])->setRelation('comments', [BasicModel::make([
            'id' => 'comment-id',
            'content' => 'comment-content',
        ])]);
        $class = new class($post) extends PostResource
        {
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

    public function test_relationship_method_takes_precedence()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class($post) extends PostResource
        {
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

    public function test_it_can_specify_relationships_as_properties_without_a_class()
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
        $class = new class($user) extends UserResource
        {
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

    public function test_it_doesnt_try_to_access_magic_attribute_property()
    {
        $instance = new class extends Model
        {
            protected $table = 'model';

            public function getRelationshipsAttribute()
            {
                throw new Exception('xxxx');
            }
        };
        $resource = new class($instance) extends JsonApiResource
        {
            //
        };

        $response = $resource->toResponse(Request::create('https://timacdonald.me'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([], $response->getData(true)['data']['relationships'] ?? []);
    }
}
