<?php

declare(strict_types=1);

namespace Tests\Performance;

include __DIR__.'../../../vendor/autoload.php';

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\Support\Includes;
use function count;

$numberOfResourcesReturned = 1_000;

$includes = [
    'a',
    'a.a',
    'a.b',
    'a.c',
    'a.b.a',
    'a.b.b',
    'a.b.c',
    'a.b.c.a',
    'a.b.c.b',
    'a.b.c.c',
    'a.b.c.d',
];

$query = implode(',', $includes);
$request = Request::create("https://example.com/users?include={$query}", 'GET');

$prefixes = [];
for ($i = 0; $i < $numberOfResourcesReturned; $i++) {
    $prefixes[] = $includes[$i % count($includes)].'.';
}

$start = microtime(true);
foreach ($prefixes as $prefix) {
    Includes::getInstance()->parse($request, $prefix);
}
$end = microtime(true);

echo "Duration (milliseconds):".PHP_EOL;
echo($end - $start) * 1000;
