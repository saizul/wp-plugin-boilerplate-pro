<?php
/**
 * Outcome of a settings import.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Settings;

/**
 * Immutable result returned by Transfer::import(). When it is invalid, settings() is the unchanged
 * current settings and changes() is empty, so callers can never write a half-validated import.
 */
final class ImportResult {

	/**
	 * Create the result.
	 *
	 * @param array<string, mixed>                         $settings Complete settings after the import.
	 * @param array<string, array{from: mixed, to: mixed}> $changes  Changed values keyed by setting key.
	 * @param array<string, string>                        $errors   Error message keyed by setting key or envelope field.
	 */
	public function __construct(
		private readonly array $settings,
		private readonly array $changes = array(),
		private readonly array $errors = array()
	) {}

	/**
	 * Whether the payload passed validation.
	 */
	public function isValid(): bool {
		return array() === $this->errors;
	}

	/**
	 * Complete, sanitised settings to store.
	 *
	 * @return array<string, mixed>
	 */
	public function settings(): array {
		return $this->settings;
	}

	/**
	 * Values that differ from the current settings.
	 *
	 * @return array<string, array{from: mixed, to: mixed}>
	 */
	public function changes(): array {
		return $this->changes;
	}

	/**
	 * Whether the import would change anything.
	 */
	public function hasChanges(): bool {
		return array() !== $this->changes;
	}

	/**
	 * Validation errors.
	 *
	 * @return array<string, string>
	 */
	public function errors(): array {
		return $this->errors;
	}
}
