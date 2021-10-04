<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function explode;
use function is_array;

class Includes
{
    public static function parse(Request $request, string $prefix): Collection
    {
        return once(function () use ($request, $prefix): Collection {
            $includes = $request->query('include') ?? '';

            if (is_array($includes)) {
                throw new HttpException(400, 'The include parameter must be a comma seperated list of relationship paths.');
            }

            return Collection::make(explode(',', $includes))
            ->mapInto(Stringable::class)
            ->when($prefix !== '', function (Collection $includes) use ($prefix): Collection {
                return $includes->filter(fn (Stringable $include) => $include->startsWith($prefix));
            })
            ->map(fn (Stringable $include): string => (string) $include->after($prefix)->before('.'));
        });
    }
}
