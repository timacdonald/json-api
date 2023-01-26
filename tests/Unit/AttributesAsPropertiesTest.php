<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\Models\BasicModel;
use Tests\Resources\PostResource;
use Tests\TestCase;

class AttributesAsPropertiesTest extends TestCase
{
    public function testItCanSpecifyAttributesAsProperties()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class ($post) extends PostResource {
            protected $attributes = [
                'content',
            ];

            public function toAttributes($request)
            {
                return [];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'id' => 'post-id',
            'type' => 'basicModels',
            'attributes' => [
                'content' => 'post-content',
            ],
            'relationships' => [],
            'meta' => [],
            'links' => [],
        ], $response->getData(true)['data']);
    }

    public function testAttributesMethodTakesPrecedence()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class ($post) extends PostResource {
            protected $attributes = [
                'title',
            ];

            public function toAttributes($request)
            {
                return [
                    'title' => 'expected-title',
                ];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'id' => 'post-id',
            'type' => 'basicModels',
            'attributes' => [
                'title' => 'expected-title',
            ],
            'relationships' => [],
            'meta' => [],
            'links' => [],
        ], $response->getData(true)['data']);
    }

    public function testItCanRemapAttributes()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class ($post) extends PostResource {
            protected $attributes = [
                'content' => 'body',
            ];

            public function toAttributes($request)
            {
                return [];
            }
        };

        $response = $class->toResponse(Request::create('https://timacdonald.me'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([
            'id' => 'post-id',
            'type' => 'basicModels',
            'attributes' => [
                'body' => 'post-content',
            ],
            'relationships' => [],
            'meta' => [],
            'links' => [],
        ], $response->getData(true)['data']);

    }
}
