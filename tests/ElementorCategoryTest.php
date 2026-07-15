<?php
declare(strict_types=1);

use ShopOS\Core\Core\Elementor\Category;
use PHPUnit\Framework\TestCase;

/**
 * The ShopOS Elementor panel-category registrar.
 *
 * @covers \ShopOS\Core\Core\Elementor\Category
 */
final class ElementorCategoryTest extends TestCase {

	public function test_register_adds_the_shopos_category(): void {
		$manager = new class() {
			public array $added = array();
			public function add_category( $slug, $args ) {
				$this->added[ $slug ] = $args;
			}
		};

		( new Category() )->register( $manager );

		$this->assertArrayHasKey( 'shopos', $manager->added );
		$this->assertSame( 'ShopOS', $manager->added['shopos']['title'] );
		$this->assertArrayHasKey( 'icon', $manager->added['shopos'] );
	}

	public function test_boot_wires_the_categories_registered_hook(): void {
		$category = new Category();
		$category->boot();

		$this->assertNotFalse(
			has_action( 'elementor/elements/categories_registered', array( $category, 'register' ) )
		);
	}
}
