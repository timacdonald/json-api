# JSON:API Resource for Laravel

A lightweight JSON Resource for Laravel that helps you adhere to the JSON:API standards and also implements features such as sparse fieldsets and compound documents, whilst also allowing you to extend the spec as needed for your project.

These docs are not designed to introduce you to the JSON:API spec and the associated concepts, instead you should [head over and read the spec](https:/jsonapi.org) to better learn more about the specification.

**Note:** This is a WIP project currently being built out via livestream on [my YouTube channel](https://www.youtube.com/channel/UCXukwzJwxZG0NOtLhCBdEsQ). Come hang out next stream.

#### TODO
- [ ] review composer PHP version support
- [ ] Pagination tests
- [ ] collection counts
- [ ] allow filtering of attributes and relationships via "when" helpers. 
- [ ] Filter `null` relationships
- [ ] Logo?

# To document
- [ ] Load things via Spatie Query Builder
- [ ] how you could handle type mapping in config file class > type

# Basic usage

## Resource Identification

[JSON:API docs: Identification](https://jsonapi.org/format/#document-resource-object-identification)

The `"id"` and `"type"` of a resource is automatically resolved for you under-the-hood if you are using resources solely with Eloquent models.

We have defined a sensible default for you so you can hit the ground running without having to fiddle with the small stuff. The default behaviour when resolving the `"id"` is to call the `$model->getKey()` method and the `"type"` is resolved by using a camel case of the model's table name, e.g. `blog_posts` becomes `blogPosts`. You can customise how this works to support other types of objects and behaviours, but that will follow in the [advanced usage](#advanced-usage) section.

Nice. Well that was easy, so let's move onto...

## Resource Attributes

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

The [advanced usage](#advanded-usage) section covers [sparse fieldsets and handling expensive attribute calculation](#sparse-fieldsets) and [minimal attribute](#minimal-attributes) payloads, but you can ignore those advanced features for now and continue on with...

## Resource Relationships

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

> Note: "links" and "meta" are not yet supported for relationships, but they are WIP. Resource linkage "meta" is not yet implemented. Let me know if you have a use-case you'd like to use it for!

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

## Resource Links

[JSON:API docs: Links](https://jsonapi.org/format/#document-resource-object-links)

To provide links for a resource, you can implement the `toLinks(Request $request)` method...

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toLinks(Request $request): array
    {
        return [
            'self' => route('users.show', $this->resource),
        ];
    }
}
```

## Resource Meta

[JSON:API docs: Meta](https://jsonapi.org/format/#document-meta)

To provide meta information for a resource, you can implement the `toMeta(Request $request)` method...

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toMeta(Request $request): array
    {
        return [
            'resourceDeprecated' => true,
        ];
    }
}
```

## Rationale behind inclusion of all top level object keys

`// TODO`

# Advanced usage

## Resource Identification

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

## Resource Attributes

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

### Minimal Resource Attributes

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

## Resource Relationships

[JSON:API docs: Inclusion of Related Resources](https://jsonapi.org/format/#fetching-includes)

Relationships can be resolved deeply and also multiple relationship paths can be included. Of course you should be careful about n+1 issues, which is why we recommend using this package in conjunction with [Spatie's Query Builder](https://github.com/spatie/laravel-query-builder/).

```sh
# Including deeply nested relationships
/api/posts/8?include=author.comments

# Including multiple relationship paths
/api/posts/8?include=comments,author.comments
```

# Support

- We do not promise named parameter support.
- If a method is not documented in these docs, you should assume it is an internal API that can change at any time.

# Coming soon...

- [ ] Top level links, jsonapi, etc.
- [ ] Test assertions?
  - decide how to handle top level keys for single and collections (static? should collections have to be extended to specify the values? or can there be static methods on the single resource for the collection?)
- [ ] Handle loading relations on a already in memory object with Spatie Query builder (PR)
- [ ] strict mode that throws when duplicates are found in includes
- [ ] make structure more deterministic by forcing included to always be available
- [ ] Resource identifier links and meta as a new concept different to normal resource links and relationships.
- [ ] Ability to send the resource identifier "id" and "type" for a belongsTo relationship, even if not included?
- [ ] Helper to define links
