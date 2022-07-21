<?php

declare(strict_types=1);

namespace Tests\Performance;

include __DIR__.'../../../vendor/autoload.php';

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\Support\Fields;

use function count;

$numberOfResourcesReturned = 1_000;

$types = [
    'a',
    'b',
    'c',
    'd',
    'e',
    'f',
];

$query = collect($types)->map(static function ($type) {
    return "fields[{$type}]=a,b,c,d,e,f";
})->implode('&');

$request = Request::create("https://example.com/users?{$query}", 'GET');

$resources = [];
for ($i = 0; $i < $numberOfResourcesReturned; $i++) {
    $resources[] = $types[$i % count($types)];
}

$start = microtime(true);
foreach ($resources as $resource) {
    Fields::getInstance()->parse($request, $resource, true);
}
$end = microtime(true);

echo "Duration (milliseconds):".PHP_EOL;
echo($end - $start) * 1000;
