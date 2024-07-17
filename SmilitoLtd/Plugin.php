<?php

namespace SmilitoLtd;

use TestBlock\SmilitoLtd\Updater;

/**
 * The base of the plugin.
 * Creates and initialises dependencies as needed.
 */
class Plugin
{

	private ConfigManager $configManager;
	private BasketManager $basketManager;
	private Api $api;
	private Block $block;
	private Admin $adminSettings;
	private Updater $updater;

	public function __construct(string $file)
	{
		// Create instances of all our classes.
		$this->configManager = new ConfigManager();
		$this->basketManager = new BasketManager();
		$this->api = new Api($this->configManager, $this->basketManager);
		$this->block = new Block();
		$this->adminSettings = new Admin();
		$this->updater = new Updater($file);
	}

	/**
	 * The entrypoint for the plugin.
	 * @return void
	 */
	public function setup(): void
	{
		$this->configManager->setup();
		$this->adminSettings->setup();
		$this->registerHooks();
		$this->updater->setup();
	}

	/**
	 * Ensures we have a woocommerce session available.
	 * @return void
	 */
	private function setupWpSession(): void
	{
		if (!isset(\WC()->session)) {
			\WC()->session = new \WC_Session_Handler();
			\WC()->session->init();
		}
	}

	/**
	 * Register hooks for internal classes.
	 * @return void
	 */
	private function registerHooks(): void
	{
		add_action( 'woocommerce_init', [$this, 'actionWoocommerceLoaded'], 10, 1);

		$this->adminSettings->registerHooks();
		$this->basketManager->registerHooks();
		$this->api->registerHooks();
		$this->block->registerHooks();
	}

	public function actionWoocommerceLoaded(): void
	{
		$this->setupWpSession();
	}

}
