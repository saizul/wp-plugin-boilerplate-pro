<?php
/**
 * Thrown when an unknown service id is requested.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Container;

/**
 * No binding and no loadable class for the requested id.
 */
class NotFoundException extends ContainerException {
}
