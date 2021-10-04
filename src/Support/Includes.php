<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use function explode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use function is_array;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Includes
{
    public static function parse(Request $request, string $prefix): Collection
    {
        $includes = $request->query('include') ?? '';

        if (is_array($includes)) {
            throw new HttpException(400, 'The include parameter must be a comma seperated list of relationship paths.');
        }

        return Collection::make(explode(',', $includes))
            ->mapInto(Stringable::class)
            ->when($prefix !== '', static function (Collection $includes) use ($prefix): Collection {
                return $includes->filter(static fn (Stringable $include) => $include->startsWith($prefix));
            })
            ->map(static fn (Stringable $include): string => (string) $include->after($prefix)->before('.'));
    }
}
