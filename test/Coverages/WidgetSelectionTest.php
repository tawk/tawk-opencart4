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

	public static function setUpBeforeClass(): void {
		$config = Config::get_config();

		$webdriver_config = new WebdriverConfig();
		$webdriver_config->selenium = $config->selenium;
		self::$driver = new Webdriver($webdriver_config);

		$web_config = new WebConfiguration();
		$web_config->tawk = $config->tawk;
		$web_config->web = $config->web;
		self::$web = new Web( self::$driver, $web_config );

		self::$web->login();
	}

	public static function tearDownAfterClass(): void {
		self::$web->uninstall_plugin();

		self::$driver->quit();
	}

	#[Test]
	#[Group('widget_selection')]
	public function should_be_able_to_install_and_uninstall_widget(): void {
		self::$web->install_plugin();

		self::$web->activate_plugin();

		$plugin_status = self::$web->get_plugin_status();
		$this->assertEqualsCanonicalizing($plugin_status, array(
			"installed" => true,
			"activated" => true,
		));

		self::$web->deactivate_plugin();

		self::$web->uninstall_plugin();

		$plugin_status = self::$web->get_plugin_status();
		$this->assertEqualsCanonicalizing($plugin_status, array(
			"installed" => false,
			"activated" => false,
		));
	}
}