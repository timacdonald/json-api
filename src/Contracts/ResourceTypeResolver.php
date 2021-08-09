<?php

namespace TiMacDonald\JsonApi\Contracts;

interface ResourceTypeResolver
{
    public function __invoke(mixed $resource): string;
}
