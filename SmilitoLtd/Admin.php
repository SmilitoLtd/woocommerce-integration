<?php

namespace SmilitoLtd;

class Admin
{

	private const SETTINGS_PAGE = 'settings';
	private const AUTH_SECTION = 'auth';

	private const AUTH_FIELD_EMAIL = 'email';
	private const AUTH_FIELD_PASSWORD = 'password';

	public function setup(): void
	{
		\add_action('admin_menu', [$this, 'actionAdminMenu']);
	}

	public function actionAdminMenu(): void
	{
		\add_menu_page(
			'Smilito',
			'Smilito',
			'manage_options',
			'smilito',
			[$this, 'renderSettingsPage']
		);
	}

	public function renderSettingsPage(): void
	{
		if (!\current_user_can('manage_options')) {
			return;
		}

		if (isset($_GET['settings-updated'])) {
			\add_settings_error('smilito_messages', 'smilito_message', __('Settings Saved', 'smilito'), 'updated');
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="POST">
				<?php
				// output security fields for the registered setting "wporg"
				settings_fields( ConfigManager::CONFIG_GROUP );
				// output setting sections and their fields
				// (sections are registered for "wporg", each field is registered to a specific section)
				do_settings_sections( self::SETTINGS_PAGE );
				// output save settings button
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	public function registerHooks(): void
	{
		\add_action('admin_init', [$this, 'actionAdminInit']);
	}

	public function actionAdminInit(): void
	{
		\add_settings_section(
			self::AUTH_SECTION,
			__('Authentication', 'smilito'),
			[$this, 'renderAuthSection'],
			self::SETTINGS_PAGE,
		);
		\add_settings_field(
			self::AUTH_FIELD_EMAIL,
			__('Email', 'smilito'),
			[$this, 'renderTextInput'],
			self::SETTINGS_PAGE,
			self::AUTH_SECTION,
			[
				'option_group' => ConfigManager::CONFIG_GROUP,
				'option_name' => ConfigManager::CONFIG_KEY_INTEGRATION_EMAIL,
			],
		);
		\add_settings_field(
			self::AUTH_FIELD_PASSWORD,
			__('Password', 'smilito'),
			[$this, 'renderTextInput'],
			self::SETTINGS_PAGE,
			self::AUTH_SECTION,
			[
				'option_group' => ConfigManager::CONFIG_GROUP,
				'option_name' => ConfigManager::CONFIG_KEY_INTEGRATION_PASSWORD,
			],
		);
	}

	public function renderAuthSection($args): void
	{
		?>
		<p>Please enter your Smilito authentication details below.</p>
		<?php
	}

	public function renderTextInput($args): void
	{
		$value = \get_option($args['option_name'], '');
		?>
		<input
			type="text"
			id="<?php echo esc_attr($args['option_name']) ?>"
			name="<?php echo esc_attr($args['option_name']) ?>"
			value="<?php echo esc_attr( $value ); ?>"
			style="width: 100%; padding: 0.5em 1em;"
		/>
		<?php
	}

}
