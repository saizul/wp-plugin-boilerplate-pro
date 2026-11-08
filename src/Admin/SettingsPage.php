<?php
/**
 * Settings screen built on the Settings API.
 *
 * @package SaizulAmin\PluginBoilerplate
 */

declare(strict_types=1);

namespace SaizulAmin\PluginBoilerplate\Admin;

use SaizulAmin\PluginBoilerplate\Settings\Repository;
use SaizulAmin\PluginBoilerplate\Settings\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds Settings > WP Plugin Boilerplate Pro.
 *
 * Security notes:
 * - The menu page requires the manage_options capability and render() checks it again.
 * - settings_fields() prints the nonce and options.php verifies it before saving.
 * - Values go through Schema::sanitize() on save and are escaped on output.
 * - The "reset" button posts to admin-post.php and is protected by a nonce and a capability check.
 */
class SettingsPage {

	public const SLUG         = 'wp-plugin-boilerplate-pro';
	public const CAPABILITY   = 'manage_options';
	public const RESET_ACTION = 'wppbp_reset_settings';

	/**
	 * Settings repository.
	 */
	private Repository $settings;

	/**
	 * Plugin main file path.
	 */
	private string $plugin_file;

	/**
	 * Plugin version, used for asset cache busting.
	 */
	private string $version;

	/**
	 * Create the page.
	 *
	 * @param Repository $settings    Settings repository.
	 * @param string     $plugin_file  Plugin main file path.
	 * @param string     $version      Plugin version.
	 */
	public function __construct( Repository $settings, string $plugin_file = '', string $version = '0.0.0' ) {
		$this->settings    = $settings;
		$this->plugin_file = $plugin_file;
		$this->version     = $version;
	}

	/**
	 * Attach hooks.
	 */
	public function hook(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::RESET_ACTION, array( $this, 'handle_reset' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'action_links' ) );
	}

	/**
	 * Register the options page.
	 */
	public function add_menu(): void {
		add_options_page(
			__( 'WP Plugin Boilerplate Pro', 'wp-plugin-boilerplate-pro' ),
			__( 'Boilerplate Pro', 'wp-plugin-boilerplate-pro' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Register the option, section and fields.
	 */
	public function register_settings(): void {
		register_setting(
			Repository::GROUP,
			Repository::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Schema::class, 'sanitize' ),
				'default'           => Schema::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'wppbp_general',
			__( 'General', 'wp-plugin-boilerplate-pro' ),
			function (): void {
				echo '<p>' . esc_html__( 'Example settings that demonstrate the Settings API, sanitisation and escaping.', 'wp-plugin-boilerplate-pro' ) . '</p>';
			},
			self::SLUG
		);

		foreach ( $this->field_labels() as $key => $label ) {
			add_settings_field(
				'wppbp_' . $key,
				$label,
				array( $this, 'render_field' ),
				self::SLUG,
				'wppbp_general',
				array(
					'key'       => $key,
					'label_for' => 'wppbp_' . $key,
				)
			);
		}
	}

	/**
	 * Field labels, translated.
	 *
	 * @return array<string, string>
	 */
	private function field_labels(): array {
		return array(
			'enabled'             => __( 'Enable features', 'wp-plugin-boilerplate-pro' ),
			'greeting'            => __( 'Greeting', 'wp-plugin-boilerplate-pro' ),
			'contact_email'       => __( 'Contact email', 'wp-plugin-boilerplate-pro' ),
			'items_per_page'      => __( 'Items per page', 'wp-plugin-boilerplate-pro' ),
			'layout'              => __( 'Default layout', 'wp-plugin-boilerplate-pro' ),
			'retention_days'      => __( 'Keep trashed items (days)', 'wp-plugin-boilerplate-pro' ),
			'delete_on_uninstall' => __( 'Delete data on uninstall', 'wp-plugin-boilerplate-pro' ),
		);
	}

	/**
	 * Render one field. Every dynamic value is escaped at the point of output.
	 *
	 * @param array<string, string> $args Field arguments from add_settings_field().
	 */
	public function render_field( array $args ): void {
		$key    = $args['key'];
		$fields = Schema::fields();
		if ( ! isset( $fields[ $key ] ) ) {
			return;
		}

		$field = $fields[ $key ];
		$value = $this->settings->get( $key );
		$id    = 'wppbp_' . $key;
		$name  = Repository::OPTION . '[' . $key . ']';

		switch ( $field['type'] ) {
			case 'bool':
				printf(
					'<input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s />',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- checked() returns a safe attribute.
				);
				break;

			case 'int':
				printf(
					'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$d" min="%4$d" max="%5$d" />',
					esc_attr( $id ),
					esc_attr( $name ),
					(int) $value,
					(int) $field['min'],
					(int) $field['max']
				);
				break;

			case 'enum':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['choices'] as $choice ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $choice ),
						selected( $value, $choice, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() returns a safe attribute.
						esc_html( ucfirst( $choice ) )
					);
				}
				echo '</select>';
				break;

			default:
				printf(
					'<input type="%1$s" class="regular-text" id="%2$s" name="%3$s" value="%4$s" />',
					'email' === $field['type'] ? 'email' : 'text',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
		}//end switch
	}

	/**
	 * Load the admin stylesheet on this screen only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::SLUG !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			'wppbp-admin',
			plugins_url( 'assets/css/admin.css', $this->plugin_file ),
			array(),
			$this->version
		);
	}

	/**
	 * Output the page.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-plugin-boilerplate-pro' ), '', array( 'response' => 403 ) );
		}
		?>
		<div class="wrap wppbp-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['wppbp_reset'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings were reset to the defaults.', 'wp-plugin-boilerplate-pro' ); ?></p></div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( Repository::GROUP );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>

			<hr />
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::RESET_ACTION ); ?>" />
				<?php wp_nonce_field( self::RESET_ACTION ); ?>
				<?php submit_button( __( 'Reset to defaults', 'wp-plugin-boilerplate-pro' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the reset button: verify capability and nonce, act, redirect.
	 */
	public function handle_reset(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'wp-plugin-boilerplate-pro' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::RESET_ACTION );

		$this->settings->reset();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => self::SLUG,
					'wppbp_reset' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param array<int|string, string> $links Existing links.
	 * @return array<int|string, string>
	 */
	public function action_links( array $links ): array {
		$url = add_query_arg( 'page', self::SLUG, admin_url( 'options-general.php' ) );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wp-plugin-boilerplate-pro' ) . '</a>' );
		return $links;
	}
}
