<?php

namespace SmilitoLtd;

/**
 * ConfigManager handles integration with the Wordpress Settings API.
 * @see https://developer.wordpress.org/plugins/settings/using-settings-api/
 */
class ConfigManager
{

	public const CONFIG_GROUP = 'smilito';
	public const CONFIG_KEY_INTEGRATION_EMAIL = 'smilito_integration_email';
	public const CONFIG_KEY_INTEGRATION_PASSWORD = 'smilito_integration_password';

	/**
	 * Make sure wordpress knows about our settings.
	 * @return void
	 */
	public function setup(): void
	{
		\register_setting(self::CONFIG_GROUP, self::CONFIG_KEY_INTEGRATION_EMAIL, [
			'type' => 'string',
		]);
		\register_setting(self::CONFIG_GROUP, self::CONFIG_KEY_INTEGRATION_PASSWORD, [
			'type' => 'string',
		]);
	}

	/**
	 * Returns the integration email option from the database.
	 * @return string
	 */
	public function getIntegrationEmail(): string
	{
		return get_option(self::CONFIG_KEY_INTEGRATION_EMAIL, '');
	}

	/**
	 * Returns the integration password option from the database.
	 * @return string
	 */
	public function getIntegrationPassword(): string
	{
		return get_option(self::CONFIG_KEY_INTEGRATION_PASSWORD, '');
	}

}
