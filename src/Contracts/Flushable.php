<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Contracts;

interface Flushable
{
    public function flush(): void;
}
