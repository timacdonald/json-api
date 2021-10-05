# JSON:API Resource for Laravel

A lightweight Laravel implementation of JSON:API.

This is a WIP project currently being built out via livestream on [my YouTube channel](https://www.youtube.com/channel/UCXukwzJwxZG0NOtLhCBdEsQ). Come hang out next stream.

#### TODO
- [ ] review composer PHP version support
- [ ] mention whatever isn't documented is considered internal
- [ ] mention no hard promise to support named parameters 
- [ ] update docs
- [ ] Pagination tests
- [ ] collection counts
- [ ] allow filtering of attributes and relationships via "when" helpers. 
- [ ] Document that this is to be used in conjunction with Spatie Query Builder
- [ ] How to handle single resources loading / allow listing (can we PR Spatie Query Builder for this or does it already support it?).
- [ ] Filter `null` relationships
- [ ] Test assertions?
- [ ] decide how to handle top level keys for single and collections (static? should collections have to be extended to specify the values? or can there be static methods on the single resource for the collection?)

# Basic usage

## Identification

[JSON:API docs: Identification](https://jsonapi.org/format/#document-resource-object-identification)

The `"id"` and `"type"` of a resource is automatically resolved for you under-the-hood if you are using resources solely with Eloquent models.

The default behaviour when resolving the `"id"` is to call the `$model->getKey()` method and the `"type"` is resolved by using a camel case of the model's table name, e.g. `blog_posts` becomes `blogPosts`.

You can customise how this works to support other types of objects and behaviours, but that will follow in the [advanced usage](#advanced-usage) section.

Nice. Well that was easy, so let's move onto...

## Attributes

[JSON:API docs: Attributes](https://jsonapi.org/format/#document-resource-object-attributes)

To provide a set of attributes for a resource, you can implement the `toAttributes(Request $request)` method...

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

The [advanced usage](#advanded-usage) section covers [sparse fieldsets and handling expensive attribute calculation](#sparse-fieldsets) and [minimal attribute](#minimal-attributes) payloads.

## Relationships

[JSON:API docs: Relationships](https://jsonapi.org/format/#document-resource-object-relationships)

Just like we saw with attributes above, we can specify relationships that should be available on the resource by using the `toRelationships(Request $request)` method, however with relationships you should _always_ wrap the values in a `Closure`.

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toRelationships(Request $request): array
    {
        return [
            'posts' => fn () => PostResource::collection($this->posts),
            'subscription' => fn () => SubscriptionResource::make($this->subscription),
        ];
    }
}
```

Each `Closure` is only resolved when the relationship has been included by the client...

### Including relationships

[JSON:API docs: Inclusion of Related Resources](https://jsonapi.org/format/#fetching-includes)

As previously mentioned, relationships are not included in the response unless the calling client requests them. To do this, the calling client needs to "include" them by utilising the `include` query parameter.

```sh
# Include the posts...
/api/users/8?include=posts

# Include the subscription...
/api/users/8?include=subscription

# Include both...
/api/users/8?include=posts,subscription
```

# Advanced usage

## Identification

### Customising the resource `"id"`

You can customise the resolution of the `id` by implementing the `toId(Request $request)` method.

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toId(Request $request): string
    {
        // your custom resolution logic...
    }
}
```

### Customising the resource `"type"`

You can customise the resolution of the `type` by implementing the `toType(Request $request)` method.

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toType(Request $request): string
    {
        // your custom resolution logic...
    }
}
```

## Attributes

### Sparse fieldsets

[JSON:API docs: Sparse fieldsets](https://jsonapi.org/format/#fetching-sparse-fieldsets)

Without any work, your response supports sparse fieldsets. If you are utilising sparse fieldsets and have some attributes that are expensive to create, it is a good idea to wrap them in a `Closure`. Under the hood, we only resolve the `Closure` if the attribute is to be included in the response.

```php
<?php

class UserResource extends JsonResource
{
    protected function toAttributes(Request $request): array
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

The `Closure` is only resolved when the attribute is going to be included in the response, which can improve performance of requests that don't require the returned value.

```sh
# The Closure is not resolved...
/api/users/8?fields[users]=name,email

# The Closure is resolved...
/api/users/8?fields[users]=name,profile_image
```

### Minimal attributes

Out of the box the resource provides a maximal attribute payload when sparse fieldsets are not used i.e. all declared attributes in the resource are returned. If you prefer to instead make it that spare fieldsets are required in order to retrieve any attributes, you can specify the use of minimal attributes in your applications service provider.

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

## Relationships

[JSON:API docs: Inclusion of Related Resources](https://jsonapi.org/format/#fetching-includes)

Relationships can be resolved deeply and also multiple relationship paths can be included. Of course you should be careful about n+1 issues, which is why we recommend using this package in conjunction with [Spatie's Query Builder](https://github.com/spatie/laravel-query-builder/).

```sh
# Including deeply nested relationships
/api/posts/8?include=author.comments

# Including multiple relationship paths
/api/posts/8?include=comments,author.comments
```
