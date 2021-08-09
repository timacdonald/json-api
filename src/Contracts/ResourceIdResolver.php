<?php

namespace TiMacDonald\JsonApi\Contracts;

interface ResourceIdResolver
{
    public function __invoke(object $resource): string;
}
