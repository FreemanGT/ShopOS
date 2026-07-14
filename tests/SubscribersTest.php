<?php
declare(strict_types=1);

// Shared stub for \ShopOS_Restock_Database — see tests/__stubs__/shopos_restock_database_stub.php.
require_once __DIR__ . '/__stubs__/shopos_restock_database_stub.php';

use ShopOS\Core\Modules\RestockNotify\Subscribers;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ShopOS\Core\Modules\RestockNotify\Subscribers
 */
final class SubscribersTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		\ShopOS_Restock_Database::$calls                          = array();
		\ShopOS_Restock_Database::$get_waiting_for_product_return = array();
		\ShopOS_Restock_Database::$mark_notified_return           = 1;
		\ShopOS_Restock_Database::$get_by_token_return            = null;
		\ShopOS_Restock_Database::$unsubscribe_return             = 1;
	}

	public function test_get_waiting_for_product_delegates_with_default_variation_id(): void {
		\ShopOS_Restock_Database::$get_waiting_for_product_return = array( (object) array( 'id' => 7 ) );

		$rows = Subscribers::get_waiting_for_product( 42 );

		$this->assertSame(
			array( array( 'method' => 'get_waiting_for_product', 'args' => array( 42, 0 ) ) ),
			\ShopOS_Restock_Database::$calls
		);
		$this->assertCount( 1, $rows );
		$this->assertSame( 7, $rows[0]->id );
	}

	public function test_get_waiting_for_product_passes_variation_id(): void {
		Subscribers::get_waiting_for_product( 42, 99 );

		$this->assertSame( array( 42, 99 ), \ShopOS_Restock_Database::$calls[0]['args'] );
	}

	public function test_mark_notified_delegates_and_returns_legacy_value(): void {
		\ShopOS_Restock_Database::$mark_notified_return = 1;

		$result = Subscribers::mark_notified( 5 );

		$this->assertSame( 1, $result );
		$this->assertSame( 'mark_notified', \ShopOS_Restock_Database::$calls[0]['method'] );
		$this->assertSame( array( 5 ), \ShopOS_Restock_Database::$calls[0]['args'] );
	}

	public function test_get_by_token_returns_null_pass_through(): void {
		\ShopOS_Restock_Database::$get_by_token_return = null;

		$row = Subscribers::get_by_token( 'abc123' );

		$this->assertNull( $row );
		$this->assertSame( array( 'abc123' ), \ShopOS_Restock_Database::$calls[0]['args'] );
	}

	public function test_get_by_token_returns_row_object(): void {
		\ShopOS_Restock_Database::$get_by_token_return = (object) array( 'id' => 11, 'customer_email' => 'x@y.test' );

		$row = Subscribers::get_by_token( 'tok' );

		$this->assertSame( 11, $row->id );
		$this->assertSame( 'x@y.test', $row->customer_email );
	}

	public function test_unsubscribe_delegates(): void {
		\ShopOS_Restock_Database::$unsubscribe_return = 1;

		$result = Subscribers::unsubscribe( 17 );

		$this->assertSame( 1, $result );
		$this->assertSame( 'unsubscribe', \ShopOS_Restock_Database::$calls[0]['method'] );
		$this->assertSame( array( 17 ), \ShopOS_Restock_Database::$calls[0]['args'] );
	}
}
