<?php

namespace SmilitoLtd;

/**
 * Manages the registration and functionality of the REST API.
 */
class Api
{

	private const REST_NAMESPACE = 'smilito-integration/v1';
	private const HTTP_GET = 'GET';
	private const HTTP_POST = 'POST';
	private const PERMISSION_ALLOW = '__return_true';

	private ConfigManager $configManager;
	private BasketManager $basketManager;

	public function __construct(
		ConfigManager $configManager,
		BasketManager $basketManager,
	)
	{
		$this->configManager = $configManager;
		$this->basketManager = $basketManager;
	}

	/**
	 * Called when plugin starts up.
	 * Allows us to register callbacks.
	 * @return void
	 */
	public function registerHooks(): void
	{
		\add_action('init', [$this, 'actionInit']);
	}

	public function actionInit(): void {
		// Register our routes at the appropriate time.
		add_action('rest_api_init', [$this, 'registerRoutes']);
	}

	/**
	 * Registers our REST routes.
	 * @return void
	 */
    public function registerRoutes(): void
    {
		$routes = [
			'/basket-data' => [
				'methods' => self::HTTP_GET,
				'callback' => [$this, 'handleGetBasketData'],
				'permission_callback' => self::PERMISSION_ALLOW,
			],
			'/login' => [
				'methods' => self::HTTP_POST,
				'callback' => [$this, 'handlePostLogin'],
				'permission_callback' => self::PERMISSION_ALLOW,
			],
		];

		foreach ($routes as $route => $args) {
			register_rest_route(self::REST_NAMESPACE, $route, $args);
		}
    }

	/**
	 * HTTP handler for GET /basket-data
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handleGetBasketData(\WP_REST_Request $request): \WP_REST_Response
	{
		$orderId = $request->get_param('order-id');
		if ($orderId) {
			$order = wc_get_order($orderId);
			if (!$order) {
				return new \WP_REST_Response(['error' => 'Invalid order id'], 400);
			}

			$basketId = $this->basketManager->getBasketId($order);
			$basketValue = $this->basketManager->getOrderTotals($order);
		} else {
			$basketId = $this->basketManager->getOrCreateBasketId();
			$basketValue = $this->basketManager->getBasketTotals();
		}

		$data = array(
			'basket_id' => $basketId,
			'basket_value' => $basketValue,
		);

		return new \WP_REST_Response($data, 200);
	}

	/**
	 * HTTP handler for /login
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handlePostLogin(\WP_REST_Request $request): \WP_REST_Response
	{
		$email = $this->configManager->getIntegrationEmail();
		$password = $this->configManager->getIntegrationPassword();

		if (empty($email) || empty($password)) {
			return new \WP_REST_Response(['error' => 'Integration credentials are not defined.'], 400);
		}

		$ch = curl_init("https://api.smilito.io/serve/v1/login");
		$payload = json_encode([
			'email' => $email,
			'password' => $password
		]);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);

		if ($result === false) {
			return new \WP_REST_Response(['error' => 'Unable to connect login API.'], 500);
		}

		return new \WP_REST_Response(json_decode($result, true), 200);
	}
}
