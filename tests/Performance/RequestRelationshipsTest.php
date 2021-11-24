<?php

declare(strict_types=1);

namespace Tests\Performance;

include __DIR__.'../../../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\Models\BasicModel;
use Tests\Resources\UserResource;

$numberOfResourcesReturned = 500;
$modelFactory = fn () => new BasicModel(['id' => Str::random()]);
Container::getInstance()->bind(ResponseFactory::class, fn () => new class () {
    public function json(array $data): object
    {
        $data = json_encode($data);

        return new class ($data) {
            public function __construct(public string|bool $data)
            {
                //
            }

            public function header(): void
            {
                //
            }
        };
    }
});

$users = [];
for ($i = 0; $i < $numberOfResourcesReturned; $i++) {
    $users[] = $modelFactory()
        ->setRelation('posts', [
            $modelFactory()->setRelation('author', $modelFactory())->setRelation('avatar', $modelFactory()),
            $modelFactory()->setRelation('author', $modelFactory())->setRelation('avatar', $modelFactory()),
            $modelFactory()->setRelation('author', $modelFactory())->setRelation('avatar', $modelFactory()),
        ])
        ->setRelation('comments', [
            $modelFactory()->setRelation('author', $modelFactory())->setRelation('avatar', $modelFactory()),
            $modelFactory()->setRelation('author', $modelFactory())->setRelation('avatar', $modelFactory()),
            $modelFactory()->setRelation('author', $modelFactory())->setRelation('avatar', $modelFactory()),
        ]);
}
$request = Request::create("https://example.com/users?include=posts.author.avatar,comments.author.avatar", 'GET');
Container::getInstance()->bind('request', fn () => $request);
$resource = UserResource::collection($users);

$start = microtime(true);
$resource->toResponse($request);
$end = microtime(true);

echo "Duration (milliseconds):".PHP_EOL;
echo($end - $start) * 1000;
