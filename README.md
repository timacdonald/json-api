# JSON:API Resource for Laravel

A lightweight Laravel implementation of JSON:API.

This is a WIP project currently being built out via livestream on [my YouTube channel](https://www.youtube.com/channel/UCXukwzJwxZG0NOtLhCBdEsQ). Come hang out next stream.

#### TODO

- [ ] Pagination tests
- [ ] collection counts
- [ ] allow filtering of attributes and relationships via "when" helpers
- [ ] Holistic naming
- [ ] Perf testing for recalled methods and recursion. Can we push some of the recursive work to the calling place in with()?
- [ ] Document that this is to be used in conjunction with Spatie Query Builder
- [ ] document why whenLoaded isn't great
- [ ] How to handle single resources loading / allow listing (can we PR Spatie Query Builder for this or does it already support it?).
- [ ] Test assertions?

# Basic usage

## Identification

[JSON:API docs: Identification](https://jsonapi.org/format/#document-resource-object-identification)

The `"id"` and `"type"` of a resource is automatically resolved for you under-the-hood if you are using resources solely with Eloquent models.

The default bindings resolve the `"id"` by calling `(string) $model->getKey()` and they resolves the `"type"` by using a camel case of the model's table name, e.g. `blog_posts` becomes `blogPosts`.

Nice. Well that was easy, so let's move onto...

## Attributes

[JSON:API docs: Attributes](https://jsonapi.org/format/#document-resource-object-attributes)

To provide a set of attributes for a resource, you can implement the `toAttributes(Request $request)` method...

```php
<?php

class UserResource extends JsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```


## Relationships

[JSON:API docs: Relationships](https://jsonapi.org/format/#document-resource-object-relationships)

Just like we saw with attributes above, we can specify relationships that should be available on the resource by using the `toRelationships(Request $request)` method, however with relationships you should _always_ wrap the values in a `Closure`.

```php
<?php

class UserResource extends JsonApiResource
{
    public function toRelationships(Request $request): array
    {
        return [
            'posts' => fn () => PostResource::collection($this->posts),
            'subscription' => fn () => SubscriptionResource::make($this->subscription),
        ];
    }
}
```

### Including relationships

[JSON:API docs: Inclusion of Related Resources](https://jsonapi.org/format/#fetching-includes)

These relationships however, are not included in the response unless the calling client requests them. To do this, the calling client needs to "include" them by utilising the `include` query parameter.

```sh
# Include the posts...
/api/users/8?include=posts

# Include the comments...
/api/users/8?include=comments

# Include both...
/api/users/8?include=posts,comments
```

_**Note**: In the advanced usage you can learn how to include relationships in the response without them being included by the client._

# Advanced usage

## Identification

### Customising the `"id"` resolver

You can change the `"id"` resolver via a service provider by binding your own implementation of the `ResourceIdResolver`, which can be fulfilled by any `callable`. The `callable` receives the Resource Object as it's first parameter.

```php
<?php

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ResourceIdResolver::class, fn () => function (mixed $resourceObject): string {
            if ($resourceObject instanceof Model) {
                return (string) $resourceObject->getKey();
            }

            if ($resourceObject instanceof ValueObject) {
                return (string) $resourceObject->getValue();
            }

            if (is_object($resourceObject)) {
                throw new RuntimeException('Unable to resolve Resource Object id for class '.$resourceObject::class);
            }

            throw new RuntimeException('Unable to resolve Resource Object id for type '.gettype($resourceObject));
        });
    }
}
```

### Customising the `"type"` resolver

You can change the `"type"` resolver via a service provider by binding your own implementation of the `ResourceTypeResolver`, which can be fulfilled by any `callable`. The `callable` receives the Resource Object as it's first parameter.

```php
<?php

class AppServiceProvider
{
    public function register()
    {
        $this->app->singleton(ResourceTypeResolver::class, fn () => function (mixed $resourceObject): string {
            if (! is_object($resourceObject)) {
                throw new RuntimeException('Unable to resolve Resource Object type for type '.gettype($resourceObject));
            }

            return match($resourceObject::class) {
                User::class => 'users',
                Post::class => 'posts',
                Comment::class => 'comments',
                default => throw new RuntimeException('Unable to resolve Resource Object type for class '.$resourceObject::class),
            };
        });
    }
}
```

## Attributes

### Sparse fieldsets

[JSON:API docs: Sparse fieldsets](https://jsonapi.org/format/#fetching-sparse-fieldsets)

Without any work, your response supports sparse fieldsets. If you are utilising sparse fieldsets and have some attributes that are expensive to create, it is a good idea to wrap them in a `Closure`. Under the hood, we only call the `Closure` if the attribute is to be included in the response.

```php
<?php

class UserResource extends JsonResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'profile_image' => fn () => base64_encode(
                // don't really download a file like this. It's just an example of a slow operation...
                file_get_contents('https://www.gravatar.com/avatar/'.md5($this->email)),
            ),
        ];
    }
}
```

The `Closure` is only called when the attribute is going to be included in the response, which improves performance of requests that don't require the returned value.

```sh
# The Closure is not called...
/api/users/8?fields[users]=name,email

# The Closure is called...
/api/users/8?fields[users]=name,profile_image
```

### Minimal attributes

Out of the box the resource provides a maximal attribute payload when sparse fieldsets are not used i.e. all specified attributes are returned by default. If you prefer to instead make it that spare fieldsets are required in order to retrieve any attributes, you can specify the use of minimal attributes in your applications service provider.

```php
<?php

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonApiResource::minimalAttributes();

        // ...
    }
}
```

### Include available attributes via "meta"

You are able to have the available attributes for a resource exposed via the objects meta key. This is an opt-in feature that you can turn on in your service provider. This will be mostly only useful in conjunction with 'minimal attributes' turned on.

```php
<?php

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonApiResource::includeAvailableAttributesViaMeta();

        // ...
    }
}
```

A user resource with a `"name"`, `"email"`, and `"location"` attribute may have the following representation...

```json
{
    "id": "5",
    "type": "users",
    "attributes": {},
    "relationships": {},
    "meta": {
        "availableAttributes": [
            "name",
            "email",
            "location"
        ]
    }
}
```

## Relationships

[JSON:API docs: Inclusion of Related Resources](https://jsonapi.org/format/#fetching-includes)

Relationships can be resolved deeply and also multiple relationship paths can be included. Of course you should be careful about n+1 issues, which is why we recommend using this package in conjunction with [Spatie's Query Builder](https://github.com/spatie/laravel-query-builder/).

```sh
# Including deeply nested relationships
/api/posts/8?include=author.comments

# Including multiple relationship paths
/api/posts/8?include=comments,author.comments
```
