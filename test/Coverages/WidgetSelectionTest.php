<?php declare(strict_types=1);

namespace Tawk\Test\Coverages;

use PHPUnit\Framework\TestCase;
use Tawk\Test\TestFiles\Config;
use Tawk\Test\TestFiles\Modules\Web;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tawk\Test\TestFiles\Modules\Webdriver;
use Tawk\Test\TestFiles\Types\WebdriverConfig;
use Tawk\Test\TestFiles\Types\WebConfiguration;

#[TestDox('Widget Selection Test')]
class WidgetSelectionTest extends TestCase {
	private static Webdriver $driver;
	private static Web $web;
	private static string $property_id;
	private static string $widget_id;

	public static function setUpBeforeClass(): void {
		$config = Config::get_config();

		$webdriver_config = new WebdriverConfig();
		$webdriver_config->selenium = $config->selenium;
		self::$driver = new Webdriver($webdriver_config);

		$web_config = new WebConfiguration();
		$web_config->tawk = $config->tawk;
		$web_config->web = $config->web;
		self::$web = new Web( self::$driver, $web_config );

		self::$property_id = $config->tawk->property_id;
		self::$widget_id = $config->tawk->widget_id;

		self::$web->login();

		self::$web->install_plugin();
		self::$web->activate_plugin();
	}

	public static function tearDownAfterClass(): void {
		self::$web->deactivate_plugin();
		self::$web->uninstall_plugin();

		self::$driver->quit();
	}

	#[Test]
	#[Group('widget_selection')]
	public function should_be_able_to_set_and_remove_widget(): void {
		$script_selector = '#tawk-script';

		self::$web->set_widget( self::$property_id, self::$widget_id );

		self::$driver->goto_page( self::$web->get_base_url() );

		$script = self::$driver->find_and_check_element( $script_selector );

		$this->assertNotNull( $script );

		self::$web->remove_widget();

		self::$driver->goto_page( self::$web->get_base_url() );

		$script = self::$driver->find_and_check_element( $script_selector );

		$this->assertNull( $script );
	}
}