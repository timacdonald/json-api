<?php

declare(strict_types=1);

namespace Tests\Performance;

include __DIR__.'../../../vendor/autoload.php';

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\Support\Includes;
use function count;

/**
 * This performance test is in place to determine if caching withing the
 * includes helper class is worthwhile.
 */

$numberOfResourcesReturned = 1_000;

$includes = [
    'a',
    'a.b',
    'a.b.c',
    'a.b.c.d',
    'a.b.c.d.e',
    'a.b.c.d.e.f',
];

$availablePrefixes = array_merge($includes, ['']);
$prefixes = [];
for ($i = 0; $i < $numberOfResourcesReturned; $i++) {
    $prefixes[] = $availablePrefixes[$i % count($availablePrefixes)].'.';
}

$includeCarry = 'z';
$requests = array_map(function (string $include) use (&$includeCarry): Request {
    $includeCarry = implode(',', [$includeCarry, $include]);

    return Request::create("https://example.com/users?include={$includeCarry}", 'GET');
}, $includes);

$start = microtime(true);
foreach ($requests as $request) {
    foreach ($prefixes as $prefix) {
        Includes::getInstance()->parse($request, $prefix);
    }
}
$end = microtime(true);

echo "Duration (milliseconds):".PHP_EOL;
echo($end - $start) * 1000;
