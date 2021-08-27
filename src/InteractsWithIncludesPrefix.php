<?php

namespace TiMacDonald\JsonApi;

use Illuminate\Support\Str;

trait InteractsWithIncludesPrefix
{
    private string $includePrefix = '';

    private function withIncludePrefix(string $prefix): self
    {
        $this->includePrefix = Str::finish($prefix, '.');

        return $this;
    }

    private function nestedIncludePrefix(string $prefix): string
    {
        return "{$this->includePrefix}{$prefix}";
    }

    private function hasIncludePrefix(): bool
    {
        return $this->includePrefix !== '';
    }
}
