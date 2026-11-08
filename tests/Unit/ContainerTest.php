<?php
/**
 * Container tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SaizulAmin\PluginBoilerplate\Container\Container;
use SaizulAmin\PluginBoilerplate\Container\ContainerException;
use SaizulAmin\PluginBoilerplate\Container\NotFoundException;
use stdClass;

final class ContainerTest extends TestCase {

	public function test_instance_returns_stored_value(): void {
		$container = ( new Container() )->instance( 'answer', 42 );

		$this->assertTrue( $container->has( 'answer' ) );
		$this->assertSame( 42, $container->get( 'answer' ) );
	}

	public function test_factory_builds_new_object_each_time(): void {
		$container = ( new Container() )->factory( 'obj', static fn() => new stdClass() );

		$this->assertNotSame( $container->get( 'obj' ), $container->get( 'obj' ) );
	}

	public function test_singleton_builds_once(): void {
		$calls     = 0;
		$container = ( new Container() )->singleton(
			'obj',
			static function () use ( &$calls ) {
				++$calls;
				return new stdClass();
			}
		);

		$this->assertSame( $container->get( 'obj' ), $container->get( 'obj' ) );
		$this->assertSame( 1, $calls );
	}

	public function test_factory_receives_container(): void {
		$container = new Container();
		$container->instance( 'base', 'http://example.test' );
		$container->factory( 'url', static fn( Container $c ) => $c->get( 'base' ) . '/api' );

		$this->assertSame( 'http://example.test/api', $container->get( 'url' ) );
	}

	public function test_rebinding_replaces_cached_instance(): void {
		$container = new Container();
		$container->singleton( 'v', static fn() => 1 );
		$this->assertSame( 1, $container->get( 'v' ) );

		$container->singleton( 'v', static fn() => 2 );
		$this->assertSame( 2, $container->get( 'v' ) );
	}

	public function test_unknown_id_throws_not_found(): void {
		$this->expectException( NotFoundException::class );
		( new Container() )->get( 'missing.service' );
	}

	public function test_has_is_false_for_unknown_id(): void {
		$this->assertFalse( ( new Container() )->has( 'nope' ) );
	}

	public function test_autowires_class_without_constructor(): void {
		$this->assertInstanceOf( Fixtures\Plain::class, ( new Container() )->get( Fixtures\Plain::class ) );
	}

	public function test_autowires_typed_dependencies(): void {
		$service = ( new Container() )->get( Fixtures\NeedsPlain::class );

		$this->assertInstanceOf( Fixtures\Plain::class, $service->plain );
	}

	public function test_autowired_dependencies_are_shared(): void {
		$container = new Container();

		$this->assertSame(
			$container->get( Fixtures\Plain::class ),
			$container->get( Fixtures\NeedsPlain::class )->plain
		);
	}

	public function test_autowire_uses_default_and_nullable_values(): void {
		$service = ( new Container() )->get( Fixtures\WithDefaults::class );

		$this->assertSame( 'fallback', $service->label );
		$this->assertNull( $service->marker );
	}

	public function test_autowire_prefers_bound_dependency(): void {
		$custom    = new Fixtures\Plain();
		$container = ( new Container() )->instance( Fixtures\Plain::class, $custom );

		$this->assertSame( $custom, $container->get( Fixtures\NeedsPlain::class )->plain );
	}

	public function test_singleton_without_factory_autowires_the_id(): void {
		$container = ( new Container() )->singleton( Fixtures\NeedsPlain::class );

		$this->assertSame( $container->get( Fixtures\NeedsPlain::class ), $container->get( Fixtures\NeedsPlain::class ) );
	}

	public function test_unresolvable_scalar_parameter_throws(): void {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( '$required' );
		( new Container() )->get( Fixtures\NeedsScalar::class );
	}

	public function test_abstract_class_is_not_instantiable(): void {
		$this->expectException( ContainerException::class );
		( new Container() )->get( Fixtures\AbstractThing::class );
	}

	public function test_circular_dependency_is_detected(): void {
		$container = new Container();
		$container->singleton( 'a', static fn( Container $c ) => $c->get( 'b' ) );
		$container->singleton( 'b', static fn( Container $c ) => $c->get( 'a' ) );

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'a -> b -> a' );
		$container->get( 'a' );
	}

	public function test_resolution_stack_is_cleaned_after_failure(): void {
		$container = new Container();
		$container->factory( 'boom', static fn() => throw new \RuntimeException( 'x' ) );

		try {
			$container->get( 'boom' );
			$this->fail( 'Expected exception' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'x', $e->getMessage() );
		}

		// A second call must fail with the original error, not a false circular-dependency error.
		$this->expectException( \RuntimeException::class );
		$container->get( 'boom' );
	}

	public function test_not_found_is_a_container_exception(): void {
		$this->assertInstanceOf( ContainerException::class, new NotFoundException() );
	}
}
