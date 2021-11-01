<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\UserResource;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database');
    }

    public function testItCanPaginate(): void
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = BasicModel::create([
                'name' => 'name-'.$i,
            ]);
        }
        Route::get('test-route', fn () => UserResource::collection(BasicModel::paginate(2)));

        $response = $this->withoutExceptionHandling()->getJson('test-route');

        $response->assertJson([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'name-0',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'name-1',
                    ],
                    'relationships' => [],
                    'links' => [],
                    'meta' => [],
                ],
            ],
            'included' => [],
            'links' => [
                'first' => 'http://localhost/test-route?page=1',
                'last' => 'http://localhost/test-route?page=3',
                'next' => 'http://localhost/test-route?page=2',
                'prev' => null,
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'to' => 2,
                'per_page' => 2,
                'last_page' => 3,
                'total' => 5,
                'path' => 'http://localhost/test-route',
                'links' => [],
            ],
        ]);
    }
}
