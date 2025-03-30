<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Concerns;

use Illuminate\Http\Request;
use TiMacDonald\JsonApi\RelationshipObject;

trait RelationshipLinks
{
    /**
     * @internal
     *
     * @var array<int, (callable(RelationshipObject): void)>
     */
    private array $relationshipLinkCallbacks = [];

    /**
     * @api
     *
     * @param  (callable(RelationshipObject): void)  $callback
     * @return $this
     */
    public function withRelationshipLink(callable $callback)
    {
        $this->relationshipLinkCallbacks[] = $callback;

        return $this;
    }

    /**
     * @internal
     *
     * @return RelationshipObject
     */
    public function resolveRelationshipLink(Request $request)
    {
        return tap($this->toResourceLink($request), function (RelationshipObject $link): void {
            foreach ($this->relationshipLinkCallbacks as $callback) {
                $callback($link);
            }
        });
    }
}
