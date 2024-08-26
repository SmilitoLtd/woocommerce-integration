<?php

namespace SmilitoLtd;

/**
 * Handles the display of the integration.
 */
class Block
{
    private const BLOCK_PATH = '../block/build';

    public function registerHooks(): void
    {
        \add_action('init', [$this, 'actionInit']);
        \add_action('enqueue_block_editor_assets', [$this, 'actionEnqueueBlockEditorAssets']);
        \add_action('wp_enqueue_scripts', [$this, 'actionEnqueueBlockAssets']);
        \add_action('woocommerce_thankyou', [$this, 'actionWooCommerceThankYou'], 10, 1);
        if (version_compare(get_bloginfo('version'), '5.8', '>=')) {
            add_filter('block_categories_all', [$this, 'filterAddSmilitoCategory']);
        } else {
            add_filter('block_categories', [$this, 'filterAddSmilitoCategory']);
        }
    }

    public function filterAddSmilitoCategory($categories): array
    {
        $categories[] = [
            'slug' => 'smilito',
            'title' => 'Smilito'
        ];
        return $categories;
    }

    public function actionInit(): void
    {
        \register_block_type(__DIR__ . '/' . self::BLOCK_PATH . '/block.json');
    }

    public function actionEnqueueBlockEditorAssets(): void
    {
        wp_enqueue_script(
            'smilito-integration',
            plugins_url(self::BLOCK_PATH . '/index.js', __FILE__),
            ['wp-blocks', 'wp-element', 'wp-editor'],
            filemtime(plugin_dir_path(__FILE__) . self::BLOCK_PATH . '/index.js')
        );
    }

    public function actionEnqueueBlockAssets(): void
    {
        \wp_enqueue_script("jquery");
        \wp_enqueue_script(
            'smilito-integration-view',
            plugins_url(self::BLOCK_PATH . '/view.js', __FILE__),
            ['wp-element', 'jquery'],
            filemtime(
                \plugin_dir_path(__FILE__) . self::BLOCK_PATH . '/view.js'
            ),
            true
        );
    }

    public function actionWooCommerceThankYou($orderId): void
    {
        if ($orderId) {
            if ($orderId instanceof \WC_Order) {
                $orderId = $orderId->get_id();
            }
            ?>
            <script type="text/javascript">
                window.SmilitoIntegrationOrderId = '<?php echo $orderId; ?>';
                window.SmilitoIntegrationOrderSuccess = true;
            </script>
            <div id="smilito_integration"></div>
            <?php
        }
    }
}
