<p align="center"><img src="/art/header.png" alt="JSON:API Resource: a Laravel package by Tim MacDonald"></p>

# JSON:API Resource for Laravel

A lightweight JSON Resource for Laravel that helps you adhere to the JSON:API standard and also implements features such as sparse fieldsets and compound documents.

These docs are not designed to introduce you to the JSON:API specification and the associated concepts, instead you should [head over and read the spec](https:/jsonapi.org) if you are not familiar with it. The documentation that follows only contains information on _how_ to implement the specification via the package.

# Version support

- **PHP**: 7.4, 8.0, 8.1
- **Laravel**: 8.0

# Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/json-api).

```sh
composer require timacdonald/json-api
```

# Getting started

The `JsonApiResource` class provided by this package is a specialisation of Laravel's `JsonResource` class. All the underlying API's are still there, thus in your controller you can still interact with `JsonApiResource` classes as you would with the base `JsonResource` class. However, you will notice that we introduce new APIs for interacting with the class internally, e.g. you no longer implement the `toArray` method.

## Creating your first JSON:API resource

To get started, let's create a `UserResource` that includes a few attributes. We will assume the underlying resource, perhaps an Eloquent model, has `$user->name`, `$user->website`, and `$user->twitterHandle` attributes.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    protected $attributes = [
        'name',
        'website',
        'twitterHandle',
    ];
}
```

Just like Laravel's standard `JsonResource` class, you may return the resource directly from your controller.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;

class UserController
{
    public function index()
    {
        return UserResource::collection(User::all());
    }

    public function show(User $user)
    {
        return UserResource::make($user);
    }
}
```

The following JSON:API formatted data will be returned.

```json
{
    "data": {
        "type": "users",
        "id": "d1e57be0-f213-42c5-bef3-7e26e733caa1",
        "attributes": {
            "name": "Tim",
            "website": "https://timacdonald.me",
            "twitterHandle": "@timacdonald87"
        },
        "relationships": {},
        "meta": {},
        "links": {}
    },
    "included": []
}
```

We will cover more complex attribute usages later on.

You have just created your first JSON:API resource. There are plenty of other features available, so let's augment this resource to explore them.

## Adding relationships

Similar to the `$attributes` property seen above, relationships may be specified in a `$relationships` property. The key should reference the underlying resource relationship property and the value should be the `JsonApiResource` class to use for that relationship.

We will specify two relationships: a "toOne" relationship of `$user->license` and a "toMany" relationship of `$user->posts`.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    protected $attributes = [ /* ... */ ];

    protected $relationships = [
        'license' => LicenseResource::class,
        'posts' => PostResource::class,
    ];
}
```

<details>
<summary>Response example</summary>

```json5
{
    // TODO
}
```
</details>
## Resource Identification

[JSON:API docs: Identification](https://jsonapi.org/format/#document-resource-object-identification)

We have defined a sensible default for you so you can hit the ground running without having to fiddle with the small stuff.

The `"id"` and `"type"` of a resource is automatically resolved for you under-the-hood if you are using resources solely with Eloquent models.

`"id"` is resolved by calling the `$model->getKey()` method and the `"type"` is resolved by using a camel case of the model's table name, e.g. `blog_posts` becomes `blogPosts`. 

You can customise how this works to support other types of objects and behaviours, but that will follow in the [advanced usage](#advanced-usage) section.

Nice. Well that was easy, so let's move onto...

## Resource Attributes

[JSON:API docs: Attributes](https://jsonapi.org/format/#document-resource-object-attributes)

To provide a set of attributes for a resource, you can implement the `toAttributes($request)` method...

```php
<?php

class UserResource extends JsonApiResource
{
    protected function toAttributes($request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

The [advanced usage](#advanced-usage) section covers [sparse fieldsets and handling expensive attribute calculation](#sparse-fieldsets) and [minimal attribute](#minimal-attributes) payloads, but you can ignore those advanced features for now and continue on with...

## Resource Relationships

[JSON:API docs: Relationships](https://jsonapi.org/format/#document-resource-object-relationships)

Just like we saw with attributes above, we can specify relationships that should be available on the resource by using the `toRelationships(Request $request)` method, however with relationships you should _always_ wrap the values in a `Closure`.

```php
<?php

class UserResource extends JsonApiResource
{
    public function toRelationships($request): array
    {
        return [
            'posts' => fn () => PostResource::collection($this->posts),
            'subscription' => fn () => SubscriptionResource::make($this->subscription),
            'profileImage' => fn () => optional($this->profileImage, fn (ProfileImage $profileImage) => ProfileImageResource::make($profileImage)),
            // if the relationship has been loaded and is null, can we not just return the resource still and have a nice default? That way you never have to handle any of this 
            // optional noise?
            // also is there a usecase for returning a resource linkage right from here and not a full resource?
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

To provide links for a resource, you can implement the `toLinks($request)` method...

```php
<?php

use TiMacDonald\JsonApi\Link;

class UserResource extends JsonApiResource
{
    public function toLinks($request): array
    {
        return [
            Link::self(route('users.show', $this->resource)),
            'related' => 'https://example.com/related'
        ];
    }
}
```

## Resource Meta

[JSON:API docs: Meta](https://jsonapi.org/format/#document-meta)

To provide meta information for a resource, you can implement the `toMeta($request)` method...

```php
<?php

class UserResource extends JsonApiResource
{
    public function toMeta($request): array
    {
        return [
            'resourceDeprecated' => true,
        ];
    }
}
```

## Refactoring to the JSON:API standard

If you have an existing API that utilises Laravel's `JsonApiResource` or other values that you would like to migrate over to the JSON:API standard via this package, it might be a big job. For this reason, we've enabled you to migrate piece by piece so you can slowly refactor your API.

From a relationship `Closure` you can return anything. If what you return is not a `JsonApiResource` or `JsonApiResourceCollection`, then the value will be "inlined" in the relationships object.

```php
<?php

class UserResource extends JsonApiResource
{
    public function toRelationships($request): array
    {
        return [
            'nonJsonApiResource' => fn (): JsonResource => LicenseResource::make($this->license),
        ];
    }
}

```

Here is what that response might look like. Notice that the resource is "inlined" and is not moved out to the "included" section of the payload.

```json
{
    "data": {
        "id": "1",
        "type": "users",
        "attributes": {},
        "relationships": {
            "nonJsonApiResource": {
                "id": "5", 
                "key": "4h29kaKlWja)99ja72kafj&&jalkfh",
                "created_at": "2020-01-04 12:44:12"
            }
        },
        "meta": {},
        "links": {}
    },
    "included": []
}
```

## Rationale behind inclusion of all top level object keys

`// TODO`

# Advanced usage

## Resource Identification

### Customising the resource `"id"`

You can customise the resolution of the `id` by specifying an id resolver in your service provider.

```php
<?php

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonApiResource::resolveIdUsing(function (mixed $resource, Request $request): string {
            // your custom resolution logic...
        });
    }
}
```

Although it is not recommended, you can also override the `toId(Request $request): string` method on a resource by resource basis.

### Customising the resource `"type"`

You can customise the resolution of the `type` by specifying a type resolver in your service provider.

```php
<?php

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonApiResource::resolveTypeUsing(function (mixed $resource, Request $request): string {
            // your custom resolution logic...
        });
    }
}
```

Although it is not recommended, you can also override the `toType(Request $request): string` method on a resource by resource basis.

## Resource Attributes

### Sparse fieldsets

[JSON:API docs: Sparse fieldsets](https://jsonapi.org/format/#fetching-sparse-fieldsets)

Without any work, your response supports sparse fieldsets. If you are utilising sparse fieldsets and have some attributes that are expensive to create, it is a good idea to wrap them in a `Closure`. Under the hood, we only resolve the `Closure` if the attribute is to be included in the response.

```php
<?php

class UserResource extends JsonResource
{
    protected function toAttributes($request): array
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

## Naming

# Support

- We do not promise named parameter support.

## Credits

- [Tim MacDonald](https://github.com/timacdonald)
- [Jess Archer](https://github.com/jessarcher) for co-creating our initial in-house version and the brainstorming
- [All Contributors](../../contributors)

And a special (vegi) thanks to [Caneco](https://twitter.com/caneco) for the logo ✨

- Using "whenLoaded is an anti-pattern"

# v1 todo
- Server implementation rethink.
- Rethink naming of objects and properties
- Guess relationship class for relationships.
