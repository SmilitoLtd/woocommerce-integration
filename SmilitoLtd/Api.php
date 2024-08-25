<?php

namespace SmilitoLtd;

use SmilitoLtd\Client\Client;

/**
 * Manages the registration and functionality of the REST API.
 */
class Api
{

    private const REST_NAMESPACE = 'smilito-integration/v1';
    private const PERMISSION_ALLOW = '__return_true';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var BasketManager
     */
    private $basketManager;

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        ConfigManager $configManager,
        BasketManager $basketManager,
        Client $client
    )
    {
        $this->client = $client;
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

    public function actionInit(): void
    {
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
            '/integration-data' => [
                'methods' => 'POST',
                'callback' => [$this, 'handleGetIntegrationData'],
                'permission_callback' => self::PERMISSION_ALLOW,
            ],
        ];

        foreach ($routes as $route => $args) {
            \register_rest_route(self::REST_NAMESPACE, $route, $args);
        }
    }

    /**
     * @throws \Exception
     */
    private function getBasketData($orderId): array
    {
        if ($orderId) {
            $order = \wc_get_order($orderId);
            if (!$order) {
                throw new \Exception('Invalid order id');
            }

            $basketId = $this->basketManager->getBasketId($order);
            $basketValue = $this->basketManager->getOrderTotals($order);
        } else {
            $basketId = $this->basketManager->getOrCreateBasketId();
            $basketValue = $this->basketManager->getBasketTotals();
        }

        return array(
            'basket_id' => $basketId,
            'basket_value' => $basketValue,
        );
    }

    /**
     * HTTP handler for POST /integration-data
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handleGetIntegrationData(\WP_REST_Request $request): \WP_REST_Response
    {
        $orderId = $request->get_param('order-id');
        $orderSuccess = $request->get_param('order-success') === 'true';
        try {
            $resp = $this->client->login($this->configManager->getIntegrationEmail(), $this->configManager->getIntegrationPassword());
            $basket = $this->getBasketData($orderId);

            if ($orderSuccess) {
                $order = \wc_get_order($orderId);
                if (!$order) {
                    error_log('Invalid order id. Cannot send claimable reward email');
                    throw new \Exception('Invalid order id');
                }

                $this->client->sendClaimableRewardEmail(
                    $order->get_billing_email(),
                    $order->get_billing_first_name(),
                    $basket['basket_id'],
                    $basket['basket_value']
                );
            }

            return new \WP_REST_Response([
                'jwt' => $resp->jwt,
                'basket_id' => $basket['basket_id'],
                'basket_value' => $basket['basket_value'],
            ], 200);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new \WP_REST_Response(['error' => 'Could not get basket data'], 400);
        }
    }

}
