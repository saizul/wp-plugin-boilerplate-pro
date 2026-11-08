<?php
/**
 * Small classes used by the container tests.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Tests\Unit\Fixtures;

class Plain {
}

class NeedsPlain {

	public function __construct( public readonly Plain $plain ) {}
}

interface Marker {
}

class WithDefaults {

	public function __construct( public readonly string $label = 'fallback', public readonly ?Marker $marker = null ) {}
}

class NeedsScalar {

	public function __construct( public readonly string $required ) {}
}

abstract class AbstractThing {
}
