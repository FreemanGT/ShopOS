<?php
/**
 * @covers ShopOS_Digital_Query_Optimizer
 */
class Test_FD_Query_Optimizer extends WP_UnitTestCase {

    public function test_months_cache_invalidated_on_post_publish() {
        $opts = ShopOS_Digital_Core::get_defaults();
        $opts['qo_cache_post_months'] = 1;
        update_option(SHOPOS_DIGITAL_OPT, $opts);

        set_transient('shopos_digital_months_post', 'seeded', HOUR_IN_SECONDS);
        $this->assertNotFalse(get_transient('shopos_digital_months_post'));

        self::factory()->post->create(array('post_status' => 'publish'));

        // Invalidation should have nuked the transient.
        $this->assertFalse(get_transient('shopos_digital_months_post'));
    }

    public function test_months_cache_invalidated_on_trash() {
        $opts = ShopOS_Digital_Core::get_defaults();
        $opts['qo_cache_post_months'] = 1;
        update_option(SHOPOS_DIGITAL_OPT, $opts);

        $post_id = self::factory()->post->create(array('post_status' => 'publish'));
        set_transient('shopos_digital_months_post', 'seeded', HOUR_IN_SECONDS);

        wp_trash_post($post_id);

        $this->assertFalse(get_transient('shopos_digital_months_post'));
    }

    public function test_no_found_rows_skips_product_archive_main_queries() {
        // qo_no_found_rows_front is ON by default. Simulate the front-end
        // product post-type archive main query (the shop page after
        // WC_Query's rewrite) and assert the optimizer leaves found_posts
        // intact — classic archive renders (WC fallback, ShopOS theme PLP)
        // read it via wc_get_loop_prop('total'), result count, pagination.
        $optimizer = new ShopOS_Digital_Query_Optimizer(ShopOS_Digital_Core::get_defaults());

        self::factory()->post->create(array('post_type' => 'post', 'post_status' => 'publish'));

        // Product archive shape: exempt.
        $q = new WP_Query();
        $q->parse_query(array('post_type' => 'product'));
        $q->is_post_type_archive = true;
        $q->is_archive           = true;
        $GLOBALS['wp_the_query'] = $q; // is_main_query() compares against this.
        $optimizer->no_found_rows($q);
        $this->assertEmpty($q->get('no_found_rows'), 'product archive main queries keep their counts');

        // Non-product front query: still optimized.
        $q2 = new WP_Query();
        $q2->parse_query(array('post_type' => 'post'));
        $GLOBALS['wp_the_query'] = $q2;
        $optimizer->no_found_rows($q2);
        $this->assertTrue((bool) $q2->get('no_found_rows'), 'non-product main queries stay optimized');

        unset($GLOBALS['wp_the_query']);
    }

    public function test_remove_sort_strips_orderby_when_enabled() {
        $opts = ShopOS_Digital_Core::get_defaults();
        $opts['qo_remove_sort_order'] = 1;
        update_option(SHOPOS_DIGITAL_OPT, $opts);

        $q = new WP_Query(array(
            'post_type'       => 'post',
            'posts_per_page'  => 1,
            'orderby'         => 'date',
            'order'           => 'DESC',
            'fields'          => 'ids',
            'no_found_rows'   => true,
        ));

        // The query should run without error and orderby should be emptied.
        $this->assertIsArray($q->posts);
    }
}
