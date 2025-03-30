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
    public function test_it_can_specify_attributes_as_properties()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class($post) extends PostResource
        {
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
        ], $response->getData(true)['data']);
    }

    public function test_attributes_method_takes_precedence()
    {
        $post = BasicModel::make([
            'id' => 'post-id',
            'title' => 'post-title',
            'content' => 'post-content',
        ]);
        $class = new class($post) extends PostResource
        {
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
        ], $response->getData(true)['data']);
    }

    public function test_it_doesnt_try_to_access_magic_attribute_property()
    {
        $instance = new class extends Model
        {
            protected $table = 'model';

            public function getAttributesAttribute()
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
        $this->assertSame([], $response->getData(true)['data']['attributes'] ?? []);
    }
}
