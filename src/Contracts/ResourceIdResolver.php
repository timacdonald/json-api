<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Contracts;

interface ResourceIdResolver
{
    public function __invoke(mixed $resourceObject): string;
}
