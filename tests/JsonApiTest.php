<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;

class JsonApiTest extends TestCase
{
    public function testItCanReturnASingleResource(): void
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
            ],
        ]);
    }

    public function testItCanReturnACollection(): void
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
                        'name' => 'user-name-1',
                    ],
                    'relationships' => [],
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2',
                    ],
                    'relationships' => [],
                ],
            ],
        ]);
    }

    public function testItCastsEmptyAttributesAndRelationshipsToAnObject(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make(BasicModel::make()));

        $response = $this->get('test-route');

        self::assertStringContainsString('"attributes":{},"relationships":{}', $response->content());
    }
}
