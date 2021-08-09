<?php

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TiMacDonald\JsonApi\ResourceIdentiferMap;

class ResourceTypeMapTest extends TestCase
{
    public function test_it_can_add_types_on_instantiation(): void
    {
        $instance = new ResourceIdentiferMap([
            Post::class => 'Post',
            Comment::class => 'Comment',
        ]);

        self::assertSame($instance->resolveType(new Post), 'Post');
        self::assertSame($instance->resolveType(new Comment), 'Comment');
    }

    public function test_it_ensures_types_are_unique_on_instantiation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to register type \'Post\' as it has already been registered');

        $instance = new ResourceIdentiferMap([
            Post::class => 'Post',
            Comment::class => 'Post',
        ]);
    }

    public function test_it_can_add_types(): void
    {
        $instance = new ResourceIdentiferMap([
            Post::class => 'Post',
        ]);

        $instance = $instance->add([
            Comment::class => 'Comment'
        ]);

        self::assertSame($instance->resolveType(new Post), 'Post');
        self::assertSame($instance->resolveType(new Comment), 'Comment');
    }

    public function test_it_ensures_classes_are_unique_when_adding(): void
    {
        $instance = new ResourceIdentiferMap([
            Post::class => 'Post',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to register class \'Tests\\Post\' as it has already been registered');

        $instance = $instance->add([
            Post::class => 'Comment'
        ]);
    }

    public function test_it_throws_an_exception_when_finding_a_type_that_doesnt_exist(): void
    {
        $instance = new ResourceIdentiferMap([
            Post::class => 'Post',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to find resource details for class Tests\\Comment. Did you forget to register it in the service provider?');

        $instance->resolveType(new Comment);
    }

    public function test_it_resolves_id_with_provided_type_resolver(): void
    {
        $instance = new ResourceIdentiferMap([
            Post::class => ['Post', fn () => 'expected id'],
        ]);

        self::assertSame($instance->resolveId(new Post), 'expected id');
    }

    public function test_it_throws_when_no_id_resolver_is_provided_for_type_and_no_default_is_provided(): void
    {
        $instance = new ResourceIdentiferMap([
            Post::class => 'Post',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No id resolver provided for class Tests\\Post.');

        $instance->resolveId(new Post);
    }
}

class Post
{
    //
}

class Comment
{
    //
}
