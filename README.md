<p align="center"><img src="https://raw.githubusercontent.com/timacdonald/json-api/main/art/header.png" alt="JSON:API Resource: a Laravel package by Tim MacDonald"></p>

# JSON:API Resource for Laravel

A lightweight JSON Resource for Laravel that helps you adhere to the JSON:API standard with support for sparse fieldsets, compound documents, and more.

> **Note** These docs are not designed to introduce you to the JSON:API specification and the associated concepts, instead you should [head over and read the specification](https://jsonapi.org) if you are not yet familiar with it. The documentation that follows only contains information on _how_ to implement the specification via the package.

**Table of contents**
- [Version support](#version-support)
- [Installation](#installation)
- [Getting started](#getting-started)
    - [Creating your first JSON:API resource](#creating-your-first-jsonapi-resource)
    - [Adding relationships](#adding-relationships)
- [A note on eager loading](#a-note-on-eager-loading)
- [Digging deeper](#digging-deeper)
    - [Attributes](#attributes)
        - [Remapping `$attributes`](#remapping-attributes)
        - [`toAttributes()`](#toAttributes)
        - [Sparse fieldsets](#sparse-fieldsets)
        - [Lazy attribute evaluation](#lazy-attribute-evaluation)
        - [Minimal attributes](#minimal-attributes)

## Version support

- **PHP**: `8.0`, `8.1`, `8.2`
- **Laravel**: `^8.73.2`, `^9.0`, `10.x-dev`

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/json-api).

```sh
composer require timacdonald/json-api
```

## Getting started

The `JsonApiResource` class provided by this package is a specialisation of Laravel's [Eloquent API resources](https://laravel.com/docs/eloquent-resources). All the public facing APIs are still accessible. In a controller, for example, you interact with `JsonApiResource` classes as you would with Laravel's standard `JsonResource` class.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;

class UserController
{
    public function index()
    {
        $users = User::with([/* ... */])->paginate();

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        $user->load([/* ... */]);

        return UserResource::make($user);
    }
}
```

As we make our way through the examples you will notice that we have introduce new APIs for interacting with the class _internally_, e.g. you no longer implement the `toArray()` method.

### Creating your first JSON:API resource

To get started, let's create a `UserResource` that includes a few attributes. We will assume the underlying resource, in this example an Eloquent user model, has `$user->name`, `$user->website`, and `$user->twitter_handle` attributes that we want to expose.

To achieve this, we will create an `$attributes` property on the resource.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The available attributes.
     *
     * @var array<int, string>
     */
    public $attributes = [
        'name',
        'website',
        'twitter_handle',
    ];
}
```

When making a request to an endpoint that returns the `UserResource`, for example:

```
GET /users/74812
```

the following JSON:API formatted data will be returned:

```json
{
  "data": {
    "type": "users",
    "id": "74812",
    "attributes": {
      "name": "Tim",
      "website": "https://timacdonald.me",
      "twitter_handle": "@timacdonald87"
    },
    "relationships": {},
    "meta": {},
    "links": {}
  },
  "included": []
}
```

🎉 You have just created your first JSON:API resource 🎉

Congratulations...and what. a. rush!

We will now dive into returning relationships for your `UserResource`, but if you would like to explore more complex attribute features, you may like to jump ahead:

- [Remapping `$attributes`](#remapping-attributes)
- [`toAttributes()`](#toAttributes)
- [Sparse fieldsets](#sparse-fieldsets)
- [Lazy attribute evaluation](#lazy-attribute-evaluation)
- [Minimal attributes](#minimal-attributes)

### Adding relationships

Available relationships may be specified in a `$relationships` property, similar to the [`$attributes` property](#creating-your-first-jsonapi-resource). We will expose two relationships on our `UserResource`: a "toOne" relationship of `$user->license` and a "toMany" relationship of `$user->posts`. In this example, these are standard Eloquent relationships.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The available attributes.
     *
     * @var array<int, string>
     */
    public $attributes = [
        'name',
        'website',
        'twitter_handle',
    ];

    /**
     * The available relationships.
     *
     * @var array<string, class-string<JsonApiResource>>
     */
    public $relationships = [
        'license' => LicenseResource::class,
        'posts' => PostResource::class,
    ];
}
```

<details>
<summary>Example response</summary>

#### Request

`GET /users/74812?include=posts,license`

> **Note** Relationships are not included in the response unless the calling client specifically requests them via the `include` query parameter. This is intended and is part of the JSON:API specification.

#### Response

```json
{
  "data": {
    "id": "74812",
    "type": "users",
    "attributes": {
      "name": "Tim",
      "website": "https://timacdonald.me",
      "twitter_handle": "@timacdonald87"
    },
    "relationships": {
      "posts": {
        "data": [
          {
            "type": "posts",
            "id": "25240",
            "meta": {}
          },
          {
            "type": "posts",
            "id": "39974",
            "meta": {}
          }
        ],
        "meta": {},
        "links": {}
      },
      "license": {
        "data": {
          "type": "licenses",
          "id": "18986",
          "meta": {}
        },
        "meta": {},
        "links": {}
      }
    },
    "meta": {},
    "links": {}
  },
  "included": [
    {
      "id": "25240",
      "type": "posts",
      "attributes": {
        "title": "So what is JSON:API all about anyway?",
        "content": "...",
        "excerpt": "..."
      },
      "relationships": {},
      "meta": {},
      "links": {}
    },
    {
      "id": "39974",
      "type": "posts",
      "attributes": {
        "title": "Building an API with Laravel, using the JSON:API specification.",
        "content": "...",
        "excerpt": "..."
      },
      "relationships": {},
      "meta": {},
      "links": {}
    },
    {
      "id": "18986",
      "type": "licenses",
      "attributes": {
        "key": "lic_CNlpZVVrsLlChLBSgS1GK7zJR8EFdupW"
      },
      "relationships": {},
      "meta": {},
      "links": {}
    }
  ]
}
```
</details>

> **Note** Whether to return a `toOne` or `toMany` relationship is be handled automatically based on the resolved relationship type 🤖

To learn about more complex relationship features, you may like to jump ahead:

- [Remapping `$relationships`](#remapping-relationships)
- [`toRelationships()`](#toRelationships)

## A note on eager loading

This package does not handle [eager loading](https://laravel.com/docs/eloquent-relationships#eager-loading) your Eloquent relationships. If a relationship is not eagerly loaded, the package will lazy load the relationship on the fly. I _highly_ recommend using [Spatie's query builder](https://spatie.be/docs/laravel-query-builder/) which is built for eager loading against the JSON:API query parameter standards. Spatie provide comprehensive documentation on how to use the package, but I will briefly give an example of how you might use this in a controller.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Spatie\QueryBuilder\QueryBuilder;

class UserController
{
    public function index()
    {
        $users = QueryBuilder::for(User::class)
            ->allowedIncludes(['license', 'posts'])
            ->paginate();

        return UserResource::collection($users);
    }

    public function show(int $id)
    {
        $user = QueryBuilder::for(User::class)
            ->allowedIncludes(['license', 'posts'])
            ->findOrFail($id);

        return UserResource::make($user);
    }
}
```

## Digging deeper

We have now covered the basics of exposing attributes and relationships, so we will dive into some more advanced topics to give you even more control over your API responses.

### Attributes

As we saw in the [Creating your first JSON:API resource](#creating-your-first-jsonapi-resource) section, the `$attributes` property is the fastest way to expose resource attributes. However, in some scenarios more complex configurations are required.

#### Remapping `$attributes`

You may remap the response key of an attribute by creating a key / value pair in the `$attributes` array. The key should be the attribute on the underlying resource, such as the user model, and the value is what will be used for the response.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The available attributes.
     *
     * @var array<array-key, string>
     */
    public $attributes = [
        'name',
        'website',
        'twitter_handle' => 'twitterHandle',
    ];
}
```

The `twitter_handle` attribute will now be exposed as camel case, i.e. `twitterHandle`, instead of snake case.

```json
{
  "data": {
    "type": "users",
    "id": "74812",
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

#### `toAttributes()`

In some scenarios you may need complete control over the attributes you are exposing or access to the current request. If that is the case, you may implement the `toAttributes()` method.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The available attributes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            'name' => $this->name,
            'website' => $this->website,
            'twitterHandle' => $this->twitter_handle,
            'email' => $this->when($this->email_is_public, $this->email, '<private>'),
            'address' => [
                'city' => $this->address('city'),
                'country' => $this->address('country'),
            ],
        ];
    }
}
```

<details>
<summary>Example response</summary>

##### Request

`GET /users/74812`

##### Response

```json
{
  "data": {
    "id": "74812",
    "type": "users",
    "attributes": {
      "name": "Tim",
      "website": "https://timacdonald.me",
      "twitterHandle": "@timacdonald87",
      "email": "<private>",
      "address": {
        "city": "Melbourne",
        "country": "Australia"
      }
    },
    "relationships": {},
    "meta": {},
    "links": {}
  },
  "included": []
}
```
</details>

#### Sparse fieldsets

Sparse fieldsets allows clients to receive deterministic responses while also improving server-side performance and reducing payload sizes. They do this by enabling the client to limit the attributes returned for a given resource type. Sparse fieldsets are part of the JSON:API specification and work out of the box for your resources. We will cover them briefly here, but we recommend reading the specification to learn more.

As an example, say we are building out an index page for our blog posts where we show the post title, excerpt, and the authors name. If the client wishes, they may limit the response to only include these attributes for the returned resources.

To achieve this we will send the following request.

> **Note** The include query parameter is `author` while the sparse fieldset parameter is `users`. This is because authors _are_ users, e.g. the Eloquent `author()` relationship returns a `User` model.

```
GET /posts?include=author&fields[posts]=title,excerpt&fields[users]=name
```

<details>
<summary>Example response</summary>

```json
{
  "data": [
    {
      "id": "25240",
      "type": "posts",
      "attributes": {
        "title": "So what is JSON:API all about anyway?",
        "excerpt": "..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "users",
            "id": "74812",
            "meta": {}
          },
          "meta": {},
          "links": {}
        }
      },
      "meta": {},
      "links": {}
    },
    {
      "id": "39974",
      "type": "posts",
      "attributes": {
        "title": "Building an API with Laravel, using the JSON:API specification.",
        "excerpt": "..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "users",
            "id": "74812",
            "meta": {}
          },
          "meta": {},
          "links": {}
        }
      },
      "meta": {},
      "links": {}
    }
  ],
  "included": [
    {
      "type": "users",
      "id": "74812",
      "attributes": {
        "name": "Tim"
      },
      "relationships": {},
      "meta": {},
      "links": {}
    }
  ]
}
```
</details>

#### Lazy attribute evaluation

To help improve performance for attributes that are expensive to calculate, it is possible to specify attributes that should be lazily evaluated. This is useful if you are making requests to the database or making HTTP requests in your resource.

As an example, let's imagine that we expose a base64 encoded avatar for each user. Our implementation downloads the avatar from our avatar microservice.

```php
<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Http;
use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The available attributes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            /* ... */
            'avatar' => Http::get('https://avatar.example.com', [
                'email' => $this->email,
            ])->body(),
        ];
    }
}
```

This implementation would make a HTTP request to our microservice even when the client is excluding the `avatar` attribute via [sparse fieldsets](#sparse-fieldsets), however if we wrap this attribute in a Closure it will only be evaluated when the `avatar` is to be returned in the response. This means we can remove the need for a HTTP request and improve performance.

```php
<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Http;
use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The available attributes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            /* ... */
            'avatar' => fn () => Http::get('https://avatar.example.com', [
                'email' => $this->email,
            ])->body(),
        ];
    }
}
```

#### Minimal attributes

Out of the box resources expose a maximal attribute payload when [sparse fieldsets](#sparse-fieldsets) are not used i.e. all declared attributes in the resource are returned. If you prefer to instead make it that sparse fieldsets are required in order to retrieve any attributes, you can specify the use of minimal attributes in your applications service provider.

```php
<?php

namespace App\Providers;

use TiMacDonald\JsonApi\JsonApiResource;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonApiResource::minimalAttributes();

        // ...
    }
}
```

//----- WIP------- //

## Resource Identification

[JSON:API docs: Identification](https://jsonapi.org/format/#document-resource-object-identification)

We have defined a sensible default for you so you can hit the ground running without having to fiddle with the small stuff.

The `"id"` and `"type"` of a resource is automatically resolved for you under-the-hood if you are using resources solely with Eloquent models.

`"id"` is resolved by calling the `$model->getKey()` method and the `"type"` is resolved by using a camel case of the model's table name, e.g. `blog_posts` becomes `blogPosts`.

You can customise how this works to support other types of objects and behaviours, but that will follow in the [advanced usage](#advanced-usage) section.

Nice. Well that was easy, so let's move onto...


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

ad
# v1 todo
- Server implementation rethink.
- Rethink naming of objects and properties
- Guess relationship class for relationships.
- Support mapping `$attributes` values to different keys.
- Support dot notation of both the key and value of `$attributes`.
- Camel case everything
