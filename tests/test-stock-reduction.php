<?php
// ABOUTME: Tests that sample line items never consume product stock — sample-only
// ABOUTME: orders skip stock reduction entirely, mixed orders zero the sample lines.

class MockStockOrderItem
{
    private $quantity;
    private $sample;

    public function __construct($quantity, $sample = false)
    {
        $this->quantity = $quantity;
        $this->sample = $sample;
    }

    public function get_meta($key, $single = true)
    {
        if ($key === 'Sample' && $this->sample) {
            return 1;
        }
        return '';
    }

    public function get_quantity()
    {
        return $this->quantity;
    }
}

class MockStockOrder
{
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function get_items()
    {
        return $this->items;
    }
}

class Test_Sample_Stock_Reduction
{
    private $plugin;

    public function setUp()
    {
        $this->plugin = new WooCommerce_Sample();
    }

    public function test_sample_only_order_skips_stock_reduction()
    {
        $order = new MockStockOrder([
            new MockStockOrderItem(1, true),
            new MockStockOrderItem(1, true),
        ]);

        ws_assert_same(false, $this->plugin->skip_stock_reduction_for_sample_only_orders(true, $order));
    }

    public function test_mixed_order_still_reduces_stock()
    {
        $order = new MockStockOrder([
            new MockStockOrderItem(1, true),
            new MockStockOrderItem(4),
        ]);

        ws_assert_same(true, $this->plugin->skip_stock_reduction_for_sample_only_orders(true, $order));
    }

    public function test_regular_order_still_reduces_stock()
    {
        $order = new MockStockOrder([
            new MockStockOrderItem(2),
        ]);

        ws_assert_same(true, $this->plugin->skip_stock_reduction_for_sample_only_orders(true, $order));
    }

    public function test_empty_order_left_alone()
    {
        $order = new MockStockOrder([]);

        ws_assert_same(true, $this->plugin->skip_stock_reduction_for_sample_only_orders(true, $order));
    }

    public function test_reduction_already_disallowed_stays_disallowed()
    {
        $order = new MockStockOrder([
            new MockStockOrderItem(2),
        ]);

        ws_assert_same(false, $this->plugin->skip_stock_reduction_for_sample_only_orders(false, $order));
    }

    public function test_sample_line_reduces_by_zero()
    {
        $order = new MockStockOrder([]);
        $item = new MockStockOrderItem(1, true);

        ws_assert_same(0, $this->plugin->sample_item_stock_quantity(1, $order, $item));
    }

    public function test_real_line_quantity_untouched()
    {
        $order = new MockStockOrder([]);
        $item = new MockStockOrderItem(3);

        ws_assert_same(3, $this->plugin->sample_item_stock_quantity(3, $order, $item));
    }

    public function test_stock_filters_registered_on_init()
    {
        $GLOBALS['ws_registered_hooks'] = [];
        $plugin = new WooCommerce_Sample();
        $plugin->init();

        $tags = [];
        foreach ($GLOBALS['ws_registered_hooks'] as $hook) {
            if (is_array($hook['callback']) && $hook['callback'][0] instanceof WooCommerce_Sample) {
                $tags[] = $hook['tag'];
            }
        }

        ws_assert_same(true, in_array('woocommerce_can_reduce_order_stock', $tags, true), 'can_reduce_order_stock hook');
        ws_assert_same(true, in_array('woocommerce_order_item_quantity', $tags, true), 'order_item_quantity hook');
    }
}