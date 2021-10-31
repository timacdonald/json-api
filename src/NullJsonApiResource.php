<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NullJsonApiResource
{
    public function withIncludePrefix(string $prefix): self
    {
        return $this;
    }

    public function toResourceIdentifier(Request $request): void
    {
        return;
    }

    public function included(Request $request): Collection
    {
        return new Collection([]);
    }
}
