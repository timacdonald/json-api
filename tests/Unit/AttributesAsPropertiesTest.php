<?php

declare(strict_types=1);

namespace Tests\Unit;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Tests\Models\BasicModel;
use Tests\Resources\PostResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;

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

    public function testItDoesntTryToAccessMagicAttributeProperty()
    {
        $instance = new class extends Model {
            public function getAttributesAttribute()
            {
                throw new \Exception('xxxx');
            }
        };
        $resource = new class ($instance) extends JsonApiResource {
            //
        };

        $response = $resource->toResponse(Request::create('https://timacdonald.me'));

        $this->assertValidJsonApi($response->content());
        $this->assertSame([], $response->getData(true)['data']['attributes']);
    }
}
