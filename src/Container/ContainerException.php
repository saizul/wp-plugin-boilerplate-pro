<?php
/**
 * Thrown when a service cannot be built.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Container;

use RuntimeException;

/**
 * Container failure (circular dependency, unresolvable parameter, ...).
 */
class ContainerException extends RuntimeException {
}
