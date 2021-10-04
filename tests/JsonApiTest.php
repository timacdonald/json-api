<?php

declare(strict_types=1);

namespace Tests;

use RuntimeException;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use SebastianBergmann\Environment\Runtime;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiResourceCollection;
use stdClass;

class JsonApiTest extends TestCase
{
    public function test_it_can_return_a_single_resource(): void
    {
        $user = BasicModel::make([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
            ]
        ]);
    }

    public function test_it_can_return_a_collection(): void
    {
        $users = [
            BasicModel::make([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ]),
            BasicModel::make([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ]),
        ];
        Route::get('test-route', fn () => UserResource::collection($users));

        $response = $this->get('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'user-id-1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-1'
                    ],
                    'relationships' => [],
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2'
                    ],
                    'relationships' => [],
                ]
            ]
        ]);
    }

    public function test_it_casts_empty_attributes_and_relationships_to_an_object(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make(BasicModel::make()));

        $response = $this->get('test-route');

        $this->assertStringContainsString('"attributes":{},"relationships":{}', $response->content());
    }

    // public function test_it_excludes_attributes_in_nested_resources(): void
    // {
    //     $this->markTestSkipped();
    // }



    public function test_it_has_test_assertions(): void
    {
        //assertResource(UserResource::class);
        $this->markTestIncomplete('TODO');
    }
}

/**
 * @property NestedResource $nested
 */
class NestedResource extends Model
{
    protected $guarded = [];

    protected $keyType = 'string';
}

class JsonResourceWithAttributes extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'location' => 'Melbourne',
        ];
    }
}
