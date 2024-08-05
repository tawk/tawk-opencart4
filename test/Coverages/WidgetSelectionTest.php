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

	}

	public static function tearDownAfterClass(): void {
		self::$driver->quit();
	}

	#[Test]
	#[Group('widget_selection')]
	public function should_be_able_to_login_and_logout(): void {
		self::$web->login();
		$this->assertTrue(self::$web->is_logged_in());

		self::$web->logout();
		$this->assertFalse(self::$web->is_logged_in());
	}
}