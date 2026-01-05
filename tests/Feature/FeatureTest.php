<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\UserResource;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database');
    }

    public function test_it_can_paginate(): void
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = BasicModel::create([
                'name' => 'name-'.$i,
            ]);
        }
        Route::get('test-route', fn () => UserResource::collection(BasicModel::paginate(2)));

        $response = $this->getJson('test-route');

        $excludes = version_compare(Application::VERSION, '12.23.0', '>=') ? [] : ['page'];

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'name-0',
                    ],
                ],
                [
                    'id' => '2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'name-1',
                    ],
                ],
            ],
            'links' => [
                'first' => 'http://localhost/test-route?page=1',
                'last' => 'http://localhost/test-route?page=3',
                'next' => 'http://localhost/test-route?page=2',
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'to' => 2,
                'per_page' => 2,
                'last_page' => 3,
                'total' => 5,
                'path' => 'http://localhost/test-route',
                'links' => [
                    Arr::except([
                        'active' => false,
                        'label' => '&laquo; Previous',
                        'page' => null,
                        'url' => null,
                    ], $excludes),
                    Arr::except([
                        'active' => true,
                        'label' => '1',
                        'page' => 1,
                        'url' => 'http://localhost/test-route?page=1',
                    ], $excludes),
                    Arr::except([
                        'active' => false,
                        'label' => '2',
                        'page' => 2,
                        'url' => 'http://localhost/test-route?page=2',
                    ], $excludes),
                    Arr::except([
                        'active' => false,
                        'label' => '3',
                        'page' => 3,
                        'url' => 'http://localhost/test-route?page=3',
                    ], $excludes),
                    Arr::except([
                        'active' => false,
                        'label' => 'Next &raquo;',
                        'page' => 2,
                        'url' => 'http://localhost/test-route?page=2',
                    ], $excludes),
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }
}
