<?php
declare(strict_types=1);

require_once __DIR__ . '/../shopos-core/src/Modules/RestockNotify/legacy/includes/class-shopos-restock-ajax.php';

use PHPUnit\Framework\TestCase;

/**
 * Guards the storefront subscribe contract: the AJAX action the browser posts
 * (frontend.js) must be the exact action name the server registers a handler
 * for. A drift on either side silently 400s every restock signup — the bug this
 * test was written to catch (handler was `rsn_subscribe`, JS posts
 * `shopos_restock_subscribe`).
 *
 * @covers \ShopOS_Restock_Ajax
 */
final class RestockNotifySubscribeWiringTest extends TestCase {

	/** The `action:` value frontend.js sends to admin-ajax. */
	private function js_action(): string {
		$js = (string) file_get_contents(
			__DIR__ . '/../shopos-core/src/Modules/RestockNotify/assets/js/frontend.js'
		);
		$this->assertSame(
			1,
			preg_match( "/action:\\s*'([a-z0-9_]+)'/i", $js, $m ),
			'Could not find the AJAX action literal in frontend.js.'
		);
		return $m[1];
	}

	public function test_handler_is_registered_for_the_action_the_js_posts(): void {
		$GLOBALS['fr_hooks'] = array();
		new \ShopOS_Restock_Ajax();

		$action = $this->js_action();
		$this->assertArrayHasKey( "wp_ajax_{$action}", $GLOBALS['fr_hooks'], 'Missing logged-in handler.' );
		$this->assertArrayHasKey( "wp_ajax_nopriv_{$action}", $GLOBALS['fr_hooks'], 'Missing guest handler.' );
	}
}
