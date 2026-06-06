<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\ClassName;
use Switon\Core\Naming;
use Switon\Core\Tests\TestCase;

use function basename;

class ClassNameTest extends TestCase
{
    public function testIsInterface(): void
    {
        $this->assertTrue(ClassName::isInterface('App\\Foo\\BarInterface'));
        $this->assertFalse(ClassName::isInterface('App\\Foo\\Bar'));
    }

    public function testIsAutoMapPair(): void
    {
        $this->assertTrue(ClassName::isAutoMapPair('App\\ClockInterface', 'App\\Clock'));
        $this->assertFalse(ClassName::isAutoMapPair('App\\Clock', 'App\\ClockInterface'));
        $this->assertFalse(ClassName::isAutoMapPair('App\\ClockInterface', 42));
    }

    public function testNamespace(): void
    {
        $this->assertSame('App\Entity', ClassName::namespace('App\Entity\User'));
        $this->assertSame('', ClassName::namespace('User'));
    }

    public function testShort(): void
    {
        $this->assertSame('User', ClassName::short('App\Entity\User'));
        $this->assertSame('User', ClassName::short('User'));
    }

    public function testSplit(): void
    {
        $result = ClassName::split('App\Entity\User');
        $this->assertSame('App\Entity', $result['namespace']);
        $this->assertSame('User', $result['className']);

        $bare = ClassName::split('User');
        $this->assertSame('', $bare['namespace']);
        $this->assertSame('User', $bare['className']);
    }

    public function testJoin(): void
    {
        $this->assertSame('App\Entity\User', ClassName::join('App\Entity', 'User'));
        $this->assertSame('User', ClassName::join('', 'User'));
        $this->assertSame('App\Entity\User', ClassName::join('App\Entity\\', 'User'));
    }

    public function testDotId(): void
    {
        // Basic conversion
        $this->assertSame('app.entity.user', ClassName::dotId('App\\Entity\\User'));

        // With excludes (fictional FQCN — no dependency on other Switon packages)
        $this->assertSame(
            'acme.telemetry.observability.registered',
            ClassName::dotId('Acme\\Telemetry\\Event\\ObservabilityRegistered', ['\\Event\\'])
        );

        // Deduplication - singular/plural (keeps first occurrence)
        $this->assertSame(
            'app.users.list',
            ClassName::dotId('App\\Users\\User\\listUsers')
        );
        $this->assertSame(
            'app.user.get.list',
            ClassName::dotId('App\\User\\Users\\getUserList')
        );

        // Deduplication - exact match
        $this->assertSame(
            'app.product.controller.list',
            ClassName::dotId('App\\Product\\ProductController\\list')
        );

        // Snake case handling
        $this->assertSame(
            'app.user.profile.get.data',
            ClassName::dotId('App\\User_Profile\\getUserData')
        );

        // Empty namespace
        $this->assertSame('user', ClassName::dotId('User'));

        // Multiple excludes (excludes are substring replacements)
        $this->assertSame(
            'app.created',
            ClassName::dotId('App\\Event\\User\\UserCreated', ['\\Event\\', 'User'])
        );

        // Exclude only namespace segments, not class name parts
        $this->assertSame(
            'app.user.created',
            ClassName::dotId('App\\Event\\UserCreated', ['\\Event\\'])
        );
    }

    public function testCompactDotId(): void
    {
        $this->assertSame('user', ClassName::dotId('App\\Controller\\UserController', compact: true));
        $this->assertSame('menu.item', ClassName::dotId('App\\Areas\\Menu\\Controller\\ItemController', compact: true));
        $this->assertSame('rbac.role-permission', ClassName::dotId('App\\Areas\\Rbac\\Controller\\RolePermissionController', compact: true));
        $this->assertSame('my', ClassName::dotId('App\\Controller\\MyController', compact: true));
        $this->assertSame('jobs.foo', ClassName::dotId('App\\Jobs\\Task\\FooTask', compact: true));
    }

    public function testHttpHandlerIdComposition(): void
    {
        $this->assertSame('user::index', self::composeHttpHandlerId('App\Controller\UserController', 'indexAction'));
        $this->assertSame('menu.item::index', self::composeHttpHandlerId('App\Areas\Menu\Controller\ItemController', 'indexAction'));
        $this->assertSame('admin.user::create', self::composeHttpHandlerId('App\Areas\Admin\Controller\UserController', 'createAction'));
        $this->assertSame('user::list', self::composeHttpHandlerId('App\Controller\UserController', 'list'));
        $this->assertSame('rbac.role-permission::assign', self::composeHttpHandlerId('App\Areas\Rbac\Controller\RolePermissionController', 'assignAction'));
        $this->assertSame('menu.menu::index', self::composeHttpHandlerId('App\Areas\Menu\Controller\MenuController', 'indexAction'));
    }

    public function testHttpHandlerIdCompositionEdgeCases(): void
    {
        $this->assertSame('my::action', self::composeHttpHandlerId('App\Controller\MyController', 'action'));
        $this->assertSame('user::create-user', self::composeHttpHandlerId('App\Controller\UserController', 'createUserAction'));
        $this->assertSame('admin.user-role::assign', self::composeHttpHandlerId('App\Areas\Admin\Controller\UserRoleController', 'assignAction'));
    }

    public function testNormalizeCollapsesEscapedBackslashesAndTrimsFqcnSeparators(): void
    {
        $this->assertSame('', ClassName::normalize('   '));
        $this->assertSame('Foo\Bar', ClassName::normalize('Foo\\\\Bar'));
        $this->assertSame('Acme\Service', ClassName::normalize('\\Acme\Service\\'));
    }

    public function testCompactDotIdReturnsEmptyWhenNoControllerSegmentRemains(): void
    {
        $this->assertSame('', ClassName::dotId('App\\Areas', compact: true));
    }

    public function testDotIdDeduplicatesRepeatedWords(): void
    {
        $this->assertSame('app.cd.ab', ClassName::dotId('App\\Cd\\Ab\\Ab'));
    }

    /** Same rule as {@see \Switon\Http\HandlerId::getId()} (core tests avoid a dependency on package http). */
    private static function composeHttpHandlerId(string $controller, string $action): string
    {
        return ClassName::dotId($controller, compact: true) . '::' . Naming::kebab(basename($action, 'Action'));
    }

}
