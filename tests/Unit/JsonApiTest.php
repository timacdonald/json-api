<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Models\BasicModel;
use Tests\Resources\BasicJsonApiResource;
use Tests\Resources\UserResource;
use Tests\TestCase;
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\JsonApiServerImplementation;
use TiMacDonald\JsonApi\Link;
use TiMacDonald\JsonApi\RelationshipLink;
use TiMacDonald\JsonApi\ResourceIdentifier;
use TiMacDonald\JsonApi\Support\Fields;
use TiMacDonald\JsonApi\Support\Includes;
use function get_class;

class JsonApiTest extends TestCase
{
    public function testItCanReturnASingleResource(): void
    {
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCanReturnACollection(): void
    {
        $users = [
            (new BasicModel([
                'id' => 'user-id-1',
                'name' => 'user-name-1',
            ])),
            (new BasicModel([
                'id' => 'user-id-2',
                'name' => 'user-name-2',
            ])),
        ];
        Route::get('test-route', fn () => UserResource::collection($users));

        $response = $this->getJson('test-route');

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
                    'meta' => [],
                    'links' => [],
                ],
                [
                    'id' => 'user-id-2',
                    'type' => 'basicModels',
                    'attributes' => [
                        'name' => 'user-name-2',
                    ],
                    'relationships' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItCastsEmptyAttributesAndRelationshipsToAnObject(): void
    {
        Route::get('test-route', fn () => UserResource::make((new BasicModel(['id' => 'user-id']))));

        $response = $this->getJson('test-route?fields[basicModels]=');

        self::assertStringContainsString('"attributes":{},"relationships":{},"meta":{},"links":{}', $response->content());
        $this->assertValidJsonApi($response);
    }

    public function testItAddsMetaToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            protected function toMeta(Request $request): array
            {
                return [
                    'meta-key' => 'meta-value',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [
                    'meta-key' => 'meta-value',
                ],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItAddsArbitraryLinksToIndividualResources(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            protected function toLinks(Request $request): array
            {
                return [
                    'links-key' => 'links-value',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [],
                'links' => [
                    'links-key' => [
                        'href' => 'links-value',
                        'meta' => [],
                    ],
                ],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItHandlesSelfAndRelatedLinks(): void
    {
        Route::get('test-route', fn () => new class ((new BasicModel(['id' => 'expected-id']))) extends JsonApiResource {
            protected function toLinks(Request $request): array
            {
                return [
                    Link::self('https://example.test/self', [
                        'some' => 'meta',
                    ]),
                    Link::related('https://example.test/related'),
                    'home' => 'https://example.test',
                ];
            }
        });

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [],
                'links' => [
                    'self' => [
                        'href' => 'https://example.test/self',
                        'meta' => [
                            'some' => 'meta',
                        ],
                    ],
                    'related' => [
                        'href' => 'https://example.test/related',
                        'meta' => [],
                    ],
                    'home' => [
                        'href' => 'https://example.test',
                        'meta' => [],
                    ],
                ],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
    }

    public function testItSetsTheContentTypeHeaderForASingleResource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'xxxx']))));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
        $this->assertValidJsonApi($response);
    }

    public function testItSetsTheContentTypeHeaderForACollectionOfResources(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::collection([(new BasicModel(['id' => 'xxxx']))]));

        $response = $this->getJson('test-route');

        $response->assertHeader('Content-type', 'application/vnd.api+json');
        $this->assertValidJsonApi($response);
    }

    public function testItCanCustomiseTheTypeResolution(): void
    {
        JsonApiResource::resolveTypeUsing(fn (BasicModel $model): string => get_class($model));
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'expected-id']))));

        $response = $this->get("test-route");

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'Tests\\Models\\BasicModel',
                'relationships' => [],
                'attributes' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);

        JsonApiResource::resolveTypeNormally();
    }

    public function testItCanCustomiseTheIdResolution(): void
    {
        JsonApiResource::resolveIdUsing(fn (BasicModel $model): string => 'expected-id');
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'missing-id']))));

        $response = $this->get("test-route");

        $response->assertExactJson([
            'data' => [
                'id' => 'expected-id',
                'type' => 'basicModels',
                'relationships' => [],
                'attributes' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);

        JsonApiResource::resolveIdNormally();
    }

    public function testItClearsTheHelperCachesAfterPreparingResponseForASingleResource(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::make((new BasicModel(['id' => 'missing-id']))));

        $response = $this->get("test-route?include=test&fields[basicModels]=a");

        $response->assertExactJson([
            'data' => [
                'id' => 'missing-id',
                'type' => 'basicModels',
                'relationships' => [],
                'attributes' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
        $this->assertCount(0, Fields::getInstance()->cache());
        $this->assertCount(0, Includes::getInstance()->cache());
    }

    public function testItClearsTheHelperCachesAfterPreparingResponseForACollectionOfResources(): void
    {
        Route::get('test-route', fn () => BasicJsonApiResource::collection([ (new BasicModel(['id' => 'missing-id'])) ]));

        $response = $this->get("test-route?include=test&fields[basicModels]=a");

        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'missing-id',
                    'type' => 'basicModels',
                    'relationships' => [],
                    'attributes' => [],
                    'meta' => [],
                    'links' => [],
                ],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);
        $this->assertCount(0, Fields::getInstance()->cache());
        $this->assertCount(0, Includes::getInstance()->cache());
    }

    public function testItCastsEmptyResourceIdentifierMetaToObject(): void
    {
        $relationship = new ResourceIdentifier('5', 'users');

        $json = json_encode($relationship);

        self::assertSame('{"id":"5","type":"users","meta":{}}', $json);
    }

    public function testItCastsEmptyLinksMetaToObject(): void
    {
        $link = Link::self('https://timacdonald.me', []);

        $json = json_encode($link);

        self::assertSame('{"href":"https:\/\/timacdonald.me","meta":{}}', $json);
    }

    public function testItCastsEmptyImplementationMetaToObject(): void
    {
        $implementation = new JsonApiServerImplementation('1.5', []);

        $json = json_encode($implementation);

        self::assertSame('{"version":"1.5","meta":{}}', $json);
    }

    public function testItCanSpecifyAnImplementation(): void
    {
        BasicJsonApiResource::resolveServerImplementationUsing(fn () => new JsonApiServerImplementation('1.4.3', [
            'secure' => true,
        ]));
        $user = new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]);
        Route::get('test-route', fn () => UserResource::make($user));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [
                    'name' => 'user-name',
                ],
                'relationships' => [],
                'meta' => [],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.4.3',
                'meta' => [
                    'secure' => true,
                ],
            ],
        ]);
        $this->assertValidJsonApi($response);

        BasicJsonApiResource::resolveServerImplementationNormally();
    }

    public function testItCastsEmptyRelationshipLinkMetaToJsonObject()
    {
        $resourceLink = new RelationshipLink(
            new ResourceIdentifier('expected-id', 'expected-type')
        );

        $json = json_encode($resourceLink);

        self::assertSame('{"data":{"id":"expected-id","type":"expected-type","meta":{}},"meta":{},"links":{}}', $json);
    }

    public function testItCanPopulateAllTheMetasForASingleInstance()
    {
        $this->markTestSkipped('Not yet implemented.');
        $user = (new BasicModel([
            'id' => 'user-id',
            'name' => 'user-name',
        ]));
        Route::get('test-route', fn () => new class($user) extends JsonApiResource {
            protected function toMeta(Request $request): array
            {
                return [
                    'instance' => 'meta',
                ];
            }

            protected function toRelationships(Request $request): array
            {
                return [
                    'relation' => fn () => new class($this->resource) extends JsonApiResource {
                        protected function toMeta(Request $request): array
                        {
                            return [
                                'nested-instance' => 'meta',
                            ];
                        }
                    },
                ];
            }
        });

        $response = $this->getJson('test-route?include=relation');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'user-id',
                'type' => 'basicModels',
                'attributes' => [],
                'relationships' => [],
                'meta' => [
                    'instance' => 'meta',
                ],
                'links' => [],
            ],
            'included' => [],
            'jsonapi' => [
                'version' => '1.0',
                'meta' => [],
            ],
        ]);
        $this->assertValidJsonApi($response);

    }
}
