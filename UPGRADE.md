# Upgrade Guide

## Upgrading To 1.x From 0.x

## Empty properties are now removed

Previously, optional keys were always included. Optional keys are now removed from the response. This includes empty `attributes`, `relationships`, `links`, `meta`, `included`.

This may impact consumers of your API if they are expecting those keys to be present even when empty.

### Method visibility

The following methods should now me marked as `public`:

- `toAttributes`
- `toRelationships`
- `toLinks`
- `toMeta`
- `toId`
- `toType`
- `toResourceLink`

### `toRelationships` should always return a resource

Even if the relationships is `null` or an empty collection, a resource should still be returned from a relationship closure. Previously, you may have done the following:

```php
protected function toRelationships(Request $request): array
{
    return [
        'avatar' => function () {
            if ($this->avatar === null) {
                return null;
            }

            return AvatarResource::make($this->avatar);
        },
        'posts' => function () {
            if ($this->posts->isEmpty()) {
                return null;
            }

            return PostResource::collection($this->posts);
        },
    ];
}
```

Now you should always return a resource and pass `null` or the empty collection through to the resource constructor:

```php
public function toRelationships(Request $request): array
{
    return [
        'avatar' => fn () => AvatarResource::collection($this->avatar),
        'posts' => fn () => PostResource::collection($this->posts),
    ];
}
```

## `RelationshipLink` in now `ResourceObject`

This class has been renamed.

### Format fix

There was an issue with the formatting of Collection identifiers that was in conflict with the JSON:API standard. Previously an collection's value was formatted as an array:

```json
{
    "data": {
        "type": "...",
        "id": "...",
        "attributes": {},
        "relationships": {
            "posts": [
                { "data": { "type": "...", "id": "..."}},
                { "data": { "type": "...", "id": "..."}},
                { "data": { "type": "...", "id": "..."}}
            ]
        }
    }
}
```

Now collections are formatted correctly. The `posts` value is now an object and the `posts.data` key is an array:

```json
{
    "data": {
        "type": "...",
        "id": "...",
        "attributes": {},
        "relationships": {
            "posts": {
                "data": [
                    { "type": "...", "id": "..."},
                    { "type": "...", "id": "..."},
                    { "type": "...", "id": "..."}
                ]
            }
        }
    }
}
```

## Server implementation no longer included by default

Previously, the top level `jsonapi` property was included in every response...

```json5
{
    // ...
    "jsonapi": {
        "version": "1.0"
    }
}
```

This is no longer the case. If you would like to include a server implementation in your API responses you may call the `JsonApiResource::resolveServerImplementationUsing` method in a service provider or middleware:

```php
use TiMacDonald\JsonApi\JsonApiResource;
use TiMacDonald\JsonApi\ServerImplementation;


JsonApiResource::resolveServerImplementationUsing(function () {
    return new ServerImplementation(version: '1.4.3', meta: [
        'secure' => true,
    ]);
});
```
