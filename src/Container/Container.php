<?php
/**
 * A small dependency injection container.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Container;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Minimal container with PSR-11 style get()/has(), shared and factory bindings and
 * constructor auto-wiring for classes whose dependencies are type-hinted.
 */
class Container {

	/**
	 * Binding factories keyed by id.
	 *
	 * @var array<string, Closure>
	 */
	private array $factories = array();

	/**
	 * Ids that should be built once and reused.
	 *
	 * @var array<string, bool>
	 */
	private array $shared = array();

	/**
	 * Built shared instances.
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = array();

	/**
	 * Ids currently being resolved, used to detect circular dependencies.
	 *
	 * @var array<int, string>
	 */
	private array $resolving = array();

	/**
	 * Bind an id to a factory that runs on every get().
	 *
	 * @param string  $id      Service id, usually a class name.
	 * @param Closure $factory Receives the container and returns the service.
	 */
	public function factory( string $id, Closure $factory ): static {
		$this->factories[ $id ] = $factory;
		unset( $this->shared[ $id ], $this->instances[ $id ] );
		return $this;
	}

	/**
	 * Bind an id to a factory that runs once; later get() calls return the same object.
	 *
	 * @param string       $id      Service id.
	 * @param Closure|null $factory Factory, or null to auto-wire the class named by $id.
	 */
	public function singleton( string $id, ?Closure $factory = null ): static {
		$this->factories[ $id ] = $factory ?? fn( Container $c ) => $c->build( $id );
		$this->shared[ $id ]    = true;
		unset( $this->instances[ $id ] );
		return $this;
	}

	/**
	 * Store a ready-made value (object, string, array, ...).
	 *
	 * @param string $id    Service id.
	 * @param mixed  $value Value returned by get().
	 */
	public function instance( string $id, mixed $value ): static {
		$this->instances[ $id ] = $value;
		$this->shared[ $id ]    = true;
		return $this;
	}

	/**
	 * Whether the id can be resolved.
	 *
	 * @param string $id Service id.
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->instances )
			|| isset( $this->factories[ $id ] )
			|| class_exists( $id );
	}

	/**
	 * Resolve a service.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 * @throws NotFoundException   When nothing is bound and the class does not exist.
	 * @throws ContainerException  On circular dependencies or unresolvable parameters.
	 */
	public function get( string $id ): mixed {
		if ( array_key_exists( $id, $this->instances ) ) {
			return $this->instances[ $id ];
		}

		if ( in_array( $id, $this->resolving, true ) ) {
			throw new ContainerException(
				sprintf( 'Circular dependency detected: %s', implode( ' -> ', array_merge( $this->resolving, array( $id ) ) ) )
			);
		}

		$this->resolving[] = $id;
		try {
			$object = isset( $this->factories[ $id ] ) ? ( $this->factories[ $id ] )( $this ) : $this->build( $id );
		} finally {
			array_pop( $this->resolving );
		}

		if ( ! empty( $this->shared[ $id ] ) || ! isset( $this->factories[ $id ] ) ) {
			$this->instances[ $id ] = $object;
		}

		return $object;
	}

	/**
	 * Instantiate a class, resolving constructor parameters from the container.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return object
	 * @throws NotFoundException  When the class does not exist.
	 * @throws ContainerException When a parameter cannot be resolved.
	 */
	public function build( string $class_name ): object {
		if ( ! class_exists( $class_name ) ) {
			throw new NotFoundException( sprintf( 'No entry or class found for "%s".', $class_name ) );
		}

		$reflection = new ReflectionClass( $class_name );
		if ( ! $reflection->isInstantiable() ) {
			throw new ContainerException( sprintf( '"%s" is not instantiable.', $class_name ) );
		}

		$constructor = $reflection->getConstructor();
		if ( null === $constructor ) {
			return new $class_name();
		}

		$arguments = array();
		foreach ( $constructor->getParameters() as $parameter ) {
			$type = $parameter->getType();

			if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
				$type_name = $type->getName();
				if ( $this->has( $type_name ) ) {
					$arguments[] = $this->get( $type_name );
					continue;
				}
			}

			if ( $parameter->isDefaultValueAvailable() ) {
				$arguments[] = $parameter->getDefaultValue();
				continue;
			}

			if ( $type && $type->allowsNull() ) {
				$arguments[] = null;
				continue;
			}

			throw new ContainerException(
				sprintf( 'Cannot resolve parameter $%s of %s::__construct().', $parameter->getName(), $class_name )
			);
		}//end foreach

		return $reflection->newInstanceArgs( $arguments );
	}
}
