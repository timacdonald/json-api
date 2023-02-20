# Upgrade Guide

## Upgrading To 1.x From 0.x

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
