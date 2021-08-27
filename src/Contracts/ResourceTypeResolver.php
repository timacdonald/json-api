<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Contracts;

interface ResourceTypeResolver
{
    public function __invoke(mixed $resourceObject): string;
}
