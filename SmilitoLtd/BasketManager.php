<?php

namespace SmilitoLtd;

use WC_Order;

/**
 * Provides access to basket data.
 * Handles sessions/cart/order data.
 */
class BasketManager
{

    private const SESSION_KEY_BASKET_ID = 'smilito_basket_id';
    private const ORDER_KEY_BASKET_ID = '_smilito_basket_id';

    public function __construct()
    {
    }

    public function registerHooks(): void
    {
        $wcVersion = \wc()->version;
        if (version_compare($wcVersion, '6.3.0', '<')) {
            \add_action('__experimental_woocommerce_blocks_checkout_update_order_meta', [$this, 'actionWoocommerceBlocksCheckoutUpdateOrderMeta']);
        } elseif (version_compare($wcVersion, '7.2.0', '<')) {
            \add_action('woocommerce_blocks_checkout_update_order_meta', [$this, 'actionWoocommerceBlocksCheckoutUpdateOrderMeta']);
        } else {
            \add_action('woocommerce_store_api_checkout_update_order_meta', [$this, 'actionWoocommerceCheckoutUpdateOrderMeta']);
        }
        \add_action('woocommerce_checkout_create_order', [$this, 'actionWoocommerceCheckoutCreateOrder'], 20, 2);
        \add_action('woocommerce_checkout_update_order_meta', [$this, 'actionCheckoutUpdateOrderMeta'], 10, 2);
        \add_action('woocommerce_payment_complete', [$this, 'actionWoocommerceOrderStatusCompleted']);
        \add_action('woocommerce_order_status_completed', [$this, 'actionWoocommerceOrderStatusCompleted']);
        \add_action('woocommerce_order_status_processing', [$this, 'actionWoocommerceOrderStatusCompleted']);
        \add_action('woocommerce_order_status_on', [$this, 'actionWoocommerceOrderStatusCompleted']);
        \add_action('woocommerce_order_status_changed', [$this, 'actionWoocommerceOrderStatusChanged'], 10, 3);
    }

    /**
     * Returns the total basket value in pence.
     * @return int
     */
    public function getBasketTotals(): int
    {
        $totals = \WC()->session->get('cart_totals');
        if (!$totals) {
            return 0;
        }
        return (int)($totals['cart_contents_total'] * 100);
    }

    /**
     * Returns the order subtotal in pence.
     * @param WC_Order|string $order
     * @return int
     */
    public function getOrderTotals($order): int
    {
        if (is_string($order)) {
            $order = \wc_get_order($order);
        }

        if (!($order instanceof WC_Order)) {
            return 0;
        }

        $order_total = $order->get_subtotal();
        return (int)($order_total * 100);
    }

    /**
     * Returns the basket id.
     * @param WC_Order|string|null $order
     * @return string|null
     */
    public function getBasketId($order = null): ?string
    {
        if ($order === null) {
            return $this->getBasketIdFromSession();
        }

        return $this->getBasketIdFromOrder($order);
    }

    /**
     * @return string|null
     */
    private function getBasketIdFromSession(): ?string
    {
        $basketId = \WC()->session->get(self::SESSION_KEY_BASKET_ID);

        if (is_array($basketId)) {
            if (count($basketId) > 0) return $basketId[0];
            return null;
        }

        if (is_string($basketId)) return $basketId;

        return null;
    }

    /**
     * @param WC_Order|string $order
     * @return string|null
     */
    private function getBasketIdFromOrder($order): ?string
    {
        if (is_string($order)) {
            $order = \wc_get_order($order);
        }

        if (!($order instanceof WC_Order)) {
            return null;
        }

        $basketId = $order->get_meta(self::ORDER_KEY_BASKET_ID, true);

        if (is_array($basketId)) {
            if (count($basketId) > 0) return $basketId[0];
            return null;
        }

        if (is_string($basketId)) return $basketId;

        return null;
    }

    /**
     * Returns the current basket id.
     * If one doesn't exist, create one first then return that.
     * @return string
     */
    public function getOrCreateBasketId(): string
    {
        $basketId = $this->getBasketId();
        if ($basketId === null) {
            $basketId = $this->generateBasketId();
            \WC()->session->set(self::SESSION_KEY_BASKET_ID, $basketId);
        }
        return $basketId;
    }

    public function deleteBasketId(): void
    {
        $basketId = $this->getBasketId();
        if ($basketId === null) {
            return;
        }
        \WC()->session->set(self::SESSION_KEY_BASKET_ID, null);
    }

    /**
     * Generates a new basket id.
     * @return string
     */
    private function generateBasketId(): string
    {
        $data = \random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Executed when starting the checkout process.
     * We need to hook into this step to allow us to copy over integration related data.
     * @param WC_Order $order
     * @return void
     */
    public function actionWoocommerceCheckoutUpdateOrderMeta(WC_Order $order): void
    {
        $this->addBasketIdToOrder($order);
    }

    public function actionWoocommerceBlocksCheckoutUpdateOrderMeta(WC_Order $order): void
    {
        $this->addBasketIdToOrder($order);
    }

    /**
     * Executed when starting the checkout process.
     * We need to hook into this step to allow us to copy over integration related data.
     * @param WC_Order|string $order
     * @param $data
     * @return void
     */
    public function actionCheckoutUpdateOrderMeta($order, $data): void
    {
        $this->addBasketIdToOrder($order);
    }

    public function actionWoocommerceCheckoutCreateOrder($order, $data)
    {
        $this->addBasketIdToOrder($order);
    }

    private function addBasketIdToOrder($order): void
    {
        if (is_string($order)) {
            $order = \wc_get_order($order);
        }
        if (!($order instanceof WC_Order)) {
            return;
        }
        if ($this->getBasketIdFromOrder($order)) {
            return;
        }
        $order->update_meta_data(self::ORDER_KEY_BASKET_ID, $this->getBasketId());
        $order->save();
    }

    public function actionWoocommerceOrderStatusCompleted($orderId): void
    {
        $this->deleteBasketId();
    }

    public function actionWoocommerceOrderStatusChanged($orderId, $from, $to): void
    {
        if ($from === 'pending') {
            $this->deleteBasketId();
        }
    }

}
