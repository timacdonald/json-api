<p align="center"><img src="https://raw.githubusercontent.com/timacdonald/json-api/main/art/header.png" alt="JSON:API Resource: a Laravel package by Tim MacDonald"></p>

# `JSON:API` Resource for Laravel

A lightweight API resource for Laravel that helps you adhere to the `JSON:API` standard with support for sparse fieldsets, compound documents, and more baked in.

> **Note** These docs are not designed to introduce you to the `JSON:API` specification and the associated concepts, instead you should [head over and read the specification](https://jsonapi.org) if you are not yet familiar with it. The documentation that follows only covers _how_ to implement the specification via the package.

**Table of contents**
- [Installation](#installation)
- [Getting started](#getting-started)
    - [Creating your first `JSON:API` resource](#creating-your-first-jsonapi-resource)
    - [Adding attributes](#adding-attributes)
    - [Adding relationships](#adding-relationships)
- [A note on eager loading](#a-note-on-eager-loading)
- [Digging deeper](#digging-deeper)
    - [Attributes](#attributes)
        - [`toAttributes()`](#toAttributes)
        - [Sparse fieldsets](#sparse-fieldsets)
        - [Minimal attributes](#minimal-attributes)
        - [Lazy attribute evaluation](#lazy-attribute-evaluation)
    - [Relationships](#relationships)
        - [`toRelationships()`](#toRelationships)
        - [Customising the relationship resource class guessing](#customising-the-relationship-resource-class-guessing)

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/timacdonald/json-api).

```sh
composer require timacdonald/json-api
```

## Getting started

The `JsonApiResource` class provided by this package is a specialisation of Laravel's [Eloquent API resource](https://laravel.com/docs/eloquent-resources). All public facing APIs are still accessible; in a controller, for example, you interact with a `JsonApiResource` as you would with Laravel's `JsonResource` class.

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

As we make our way through the examples you will see that new APIs are introduced when interacting with the class _internally_, for example, the `toArray()` method is no longer used.

### Creating your first `JSON:API` resource

To get started, let's create a `UserResource` for our `User` model. In our user resource will expose the user's `name`, `website`, and `twitter_handle` in the response.

First we will create a new API resource that extends `TiMacDonald\JsonApi\JsonApiResource`.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    //
}
```

### Adding attributes

We will now create an `$attributes` property and list the model's attributes we want to expose.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    public $attributes = [
        'name',
        'website',
        'twitter_handle',
    ];
}
```

When making a request to an endpoint that returns the `UserResource`, for example:

```php
Route::get('users/{user}', fn (User $user) => UserResource::make($user));
```

The following `JSON:API` formatted data would be returned:

```json
{
  "data": {
    "type": "users",
    "id": "74812",
    "attributes": {
      "name": "Tim",
      "website": "https://timacdonald.me",
      "twitter_handle": "@timacdonald87"
    }
  }
}
```

🎉 You have just created your first `JSON:API` resource 🎉

Congratulations...and what. a. rush!

We will now dive into adding relationships to your resources, but if you would like to explore more complex attribute features you may like to jump ahead:

- [`toAttributes()`](#toAttributes)
- [Sparse fieldsets](#sparse-fieldsets)
- [Minimal attributes](#minimal-attributes)
- [Lazy attribute evaluation](#lazy-attribute-evaluation)

### Adding relationships

Available relationships may be specified in a `$relationships` property, similar to the [`$attributes` property](#adding-attributes), however you may use a key / value pair to provide the resource class that should be used for the given relationship.

We will make two relationships available on the resource:

- `$user->team`: a "toOne" / `HasOne` relationship.
- `$user->posts`: a "toMany" / `HasMany` relationship.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    public $attributes = [
        'name',
        'website',
        'twitter_handle',
    ];

    public $relationships = [
        'team' => TeamResource::class,
        'posts' => PostResource::class,
    ];
}
```

Assuming the key / value pair follows the convention `'{myKey}' => {MyKey}Resource::class`, the class may be omitted to streamline things further.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    public $attributes = [
        'name',
        'website',
        'twitter_handle',
    ];

    public $relationships = [
        'team',
        'posts',
    ];
}
```

##### Example request and response

The client may now request these relationships via the `include` query parameter.

`GET /users/74812?include=posts,team`

> **Note** Relationships are not exposed in the response unless they are requested by the calling client via the `include` query parameter. This is intended and is part of the `JSON:API` specification.

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
            "id": "25240"
          },
          {
            "type": "posts",
            "id": "39974"
          }
        ]
      },
      "team": {
        "data": {
          "type": "teams",
          "id": "18986"
        }
      }
    }
  },
  "included": [
    {
      "id": "25240",
      "type": "posts",
      "attributes": {
        "title": "So what is `JSON:API` all about anyway?",
        "content": "...",
        "excerpt": "..."
      }
    },
    {
      "id": "39974",
      "type": "posts",
      "attributes": {
        "title": "Building an API with Laravel, using the `JSON:API` specification.",
        "content": "...",
        "excerpt": "..."
      }
    },
    {
      "id": "18986",
      "type": "teams",
      "attributes": {
        "name": "Laravel"
      }
    }
  ]
}
```

To learn about more complex relationship features you may like to jump ahead:

- [`toRelationships()`](#toRelationships)
- [Customising the relationship resource class guessing](#customising-the-relationship-resource-class-guessing)

## A note on eager loading

This package does not [eager load](https://laravel.com/docs/eloquent-relationships#eager-loading) Eloquent relationships. If a relationship is not eagerly loaded, the package will lazy load the relationship on the fly. I _highly_ recommend using [Spatie's query builder](https://spatie.be/docs/laravel-query-builder/) package which will eager load your models against the `JSON:API` query parameter standards.

Spatie provide comprehensive documentation on how to use the package, but I will briefly give an example of how you might use this in a controller.

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
            ->allowedIncludes(['team', 'posts'])
            ->paginate();

        return UserResource::collection($users);
    }

    public function show($id)
    {
        $user = QueryBuilder::for(User::class)
            ->allowedIncludes(['team', 'posts'])
            ->findOrFail($id);

        return UserResource::make($user);
    }
}
```

## Digging deeper

We have now covered the basics of exposing attributes and relationships on your resources. We will now cover more advanced topics to give you even greater control.

### Attributes

#### `toAttributes()`

As we saw in the [adding attributes](#adding-attributes) section, the `$attributes` property is the fastest way to expose attributes for a resource. In some scenarios you may need greater control over the attributes you are exposing. If that is the case, you may implement the `toAttributes()` method. This will grant you access to the current request and allow for conditional logic.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            'name' => $this->name,
            'website' => $this->website,
            'twitter_handle' => $this->twitter_handle,
            'email' => $this->when($this->email_is_public, $this->email, '<private>'),
            'address' => [
                'city' => $this->address('city'),
                'country' => $this->address('country'),
            ],
        ];
    }
}
```

##### Example response

```json
{
  "data": {
    "id": "74812",
    "type": "users",
    "attributes": {
      "name": "Tim",
      "website": "https://timacdonald.me",
      "twitter_handle": "@timacdonald87",
      "email": "<private>",
      "address": {
        "city": "Melbourne",
        "country": "Australia"
      }
    }
  }
}
```

#### Sparse fieldsets

Sparse fieldsets are a feature of the `JSON:API` specification that allows clients to specify which attributes, for any given resource type, they would like to receive. This allows for more deterministic responses, while also improving server-side performance and reducing payload sizes. Sparse fieldsets work out of the box for your resources.

We will cover them briefly here, but we recommend reading the specification to learn more.

As an example, say we are building out an index page for a blog. The page will show each post's `title` and `excerpt`, and also the `name` of the post's author. If the client wishes, they may limit the response to _only_ include the required attributes for each resource type, and exclude the other attributes, such as the post's `content` and the authors `twitter_handle`.

To achieve this we will send the following request.

```
GET /posts?include=author&fields[posts]=title,excerpt&fields[users]=name
```

> **Note** The `include` query parameter key is `author`, while the sparse fieldset parameter key is `users`. This is because authors _are_ users, e.g. the Eloquent `author()` relationship returns a `User` model.

##### Example response

```json
{
  "data": [
    {
      "id": "25240",
      "type": "posts",
      "attributes": {
        "title": "So what is `JSON:API` all about anyway?",
        "excerpt": "..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "users",
            "id": "74812"
          }
        }
      }
    },
    {
      "id": "39974",
      "type": "posts",
      "attributes": {
        "title": "Building an API with Laravel, using the `JSON:API` specification.",
        "excerpt": "..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "users",
            "id": "74812"
          }
        }
      }
    }
  ],
  "included": [
    {
      "type": "users",
      "id": "74812",
      "attributes": {
        "name": "Tim"
      }
    }
  ]
}
```

#### Minimal attributes

Resources return a maximal attribute payload when [sparse fieldsets](#sparse-fieldsets) are not in use i.e. all declared attributes on the resource are returned. If you prefer you can make the use of sparse fieldsets required in order to retrieve _any_ attributes.

You may call the `useMinimalAttributes()` method in an application service provider.

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TiMacDonald\JsonApi\JsonApiResource;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        JsonApiResource::useMinimalAttributes();

        // ...
    }
}
```

#### Lazy attribute evaluation

For attributes that are expensive to calculate, it is possible to have them evaluated _only_ when they are to be included in the response, i.e. they have not been excluded via [sparse fieldsets](#sparse-fieldsets) or [minimal attributes](#minimal-attributes). This may be useful if you are interacting with a database or making HTTP requests in a resource.

As an example, let's imagine that we expose a base64 encoded avatar for each user. Our implementation downloads the avatar from our in-house avatar microservice.

```php
<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Http;
use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            // ...
            'avatar' => Http::get("https://avatar.example.com/{$this->id}")->body(),
        ];
    }
}
```

The above implementation would make a HTTP request to our microservice even when the client is excluding the `avatar` attribute via sparse fieldsets or minimal attributes. To improve performance when this attribute is not being returned we can wrap the value in a `Closure`. The `Closure` will only be evaluated when the `avatar` is to be returned.

```php
<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Http;
use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toAttributes($request)
    {
        return [
            // ...
            'avatar' => fn () => Http::get("https://avatar.example.com/{$this->id}")->body(),
        ];
    }
}
```

### Relationships

#### `toRelationships()`

As we saw in the [adding relationships](#adding-relationships) section, the `$relationships` property is the fastest way to specify the available relationships for a resource. In some scenarios you may need greater control over the relationships you are making available. If that is the case, you may implement the `toRelationships()` method. This will grant you access to the current request and allow for conditional logic.

The value must always be wrapped in a `Closure`, which will only be called if the relationships is requested by the client.

```php
<?php

namespace App\Http\Resources;

use TiMacDonald\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, (callable(): \TiMacDonald\JsonApi\JsonApiResource|\TiMacDonald\JsonApi\JsonApiResourceCollection|\Illuminate\Http\Resources\PotentiallyMissing)>
     */
    public function toRelationships($request)
    {
        return [
            'team' => fn () => TeamResource::make($this->team),
            'posts' => fn () => $request->user()->is($this->resource)
                ? PostResource::collection($this->posts)
                : PostResource::collection($this->posts->where('published', true)),
        ];
    }
}
```

#### Customising the relationship resource class guessing

```php

```

//----- Everything that follows is WIP and should be ignored ------- //

## Resource Identification

`[JSON:API` docs: Identification](https://jsonapi.org/format/#document-resource-object-identification)

We have defined a sensible default for you so you can hit the ground running without having to fiddle with the small stuff.

The `"id"` and `"type"` of a resource is automatically resolved for you under-the-hood if you are using resources solely with Eloquent models.

`"id"` is resolved by calling the `$model->getKey()` method and the `"type"` is resolved by using a camel case of the model's table name, e.g. `blog_posts` becomes `blogPosts`.

You can customise how this works to support other types of objects and behaviours, but that will follow in the [advanced usage](#advanced-usage) section.

Nice. Well that was easy, so let's move onto...

## Resource Links

`[JSON:API` docs: Links](https://jsonapi.org/format/#document-resource-object-links)

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

`[JSON:API` docs: Meta](https://jsonapi.org/format/#document-meta)

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

## Resource Relationships

`[JSON:API` docs: Inclusion of Related Resources](https://jsonapi.org/format/#fetching-includes)

Relationships can be resolved deeply and also multiple relationship paths can be included. Of course you should be careful about n+1 issues, which is why we recommend using this package in conjunction with [Spatie's Query Builder](https://github.com/spatie/laravel-query-builder/).

```sh
# Including deeply nested relationships
/api/posts/8?include=author.comments

# Including multiple relationship paths
/api/posts/8?include=comments,author.comments
```

- Using "whenLoaded is an anti-pattern"

## Credits

- [Tim MacDonald](https://github.com/timacdonald)
- [Jess Archer](https://github.com/jessarcher) for co-creating our initial in-house version and the brainstorming
- [All Contributors](../../contributors)

And a special (vegi) thanks to [Caneco](https://twitter.com/caneco) for the logo ✨

# v1 todo
- Server implementation rethink.
- Rethink naming of objects and properties
- Camel case everything
- Allow resources to specify their JsonResource class.
- Make all caches WeakMaps.
- Things that "must" need to be first in the __consstructor. See Links:href
- Should it be withResourceIdentifier or mapResourceIdentifier. Feel like we are mapping. or pipeResourceIdentifier
- Should all caches use weakmap with request key?
