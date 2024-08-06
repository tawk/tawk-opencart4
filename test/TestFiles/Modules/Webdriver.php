<?php

namespace Tawk\Test\TestFiles\Modules;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverSelect;
use Tawk\Test\TestFiles\Helpers\Common;
use Tawk\Test\TestFiles\Types\SeleniumConfig;
use Tawk\Test\TestFiles\Types\WebdriverConfig;
use Tawk\Test\TestFiles\Helpers\Webdriver as WebdriverHelper;

use Exception;

class Webdriver {
	protected RemoteWebDriver $driver;
	protected SeleniumConfig $selenium;

	public function __construct( WebdriverConfig $config ) {
		$this->selenium = $config->selenium;

		$selenium_url = Common::build_selenium_url(
			$this->selenium->url,
			$this->selenium->hub_flag
		);

		$capabilities = WebdriverHelper::build_capabilities(
			$this->selenium->browser,
			$this->selenium->is_headless
		);

		$this->driver = RemoteWebDriver::create(
			$selenium_url,
			$capabilities,
			$this->selenium->session_timeout_ms,
			$this->selenium->request_timeout_ms
		);
	}

	public function get_driver(): RemoteWebDriver {
		return $this->driver;
	}

	public function goto_page( string $page_url ): void {
		if ( $page_url === $this->driver->getCurrentURL() ) {
			return;
		}

		$this->driver->get( $page_url );
		$this->wait_until_page_fully_loads();
	}

	public function find_element( string $selector ) {
		$this->wait_until_element_is_located( $selector );
		return $this->driver->findElement( WebDriverBy::cssSelector( $selector ) );
	}

	public function find_and_check_element( string $selector ) {
		try {
			return $this->driver->findElement( WebDriverBy::cssSelector( $selector ) );
		} catch ( Exception $err ) {
			return null;
		}
	}

	public function find_element_by_xpath( string $xpath ) {
		$this->wait_until_element_is_located_by_xpath( $xpath );
		return $this->driver->findElement( WebDriverBy::xpath( $xpath ) );
	}

	public function find_and_check_element_by_xpath( string $xpath ) {
		try {
			return $this->driver->findElement( WebDriverBy::xpath( $xpath ) );
		} catch ( Exception $err ) {
			return null;
		}
	}

	public function find_element_and_click_by_xpath( string $xpath ) {
		// BUG: doesn't work in headless mode
		// return $this->find_element_by_xpath( $xpath )->click();
		$element = $this->find_element_by_xpath( $xpath );
		$action = $this->driver->action();
		$action->click( $element )->perform();
	}

	public function find_dropdown_and_select( string $selector, string $option ) {
		$this->wait_until_element_is_located( $selector );

		$select = new WebDriverSelect( $this->driver->findElement( WebDriverBy::cssSelector( $selector ) ) );
		$select->selectByVisiblePartialText( $option );
	}

	public function find_element_and_click( string $selector ) {
		return $this->find_element( $selector )->click();
	}

	public function find_element_and_input( string $selector, string $input_value ) {
		return $this->find_element( $selector )->sendKeys( $input_value );
	}

	public function find_element_and_get_attribute_value( string $selector, string $attribute ) {
		return $this->find_element( $selector )->getAttribute( $attribute );
	}

	public function wait_until_element_is_located(
		string $selector,
		int $wait_sec = 60,
		int $interval_ms = 500
	) {
		return $this->driver->wait( $wait_sec, $interval_ms )->until(
			WebDriverExpectedCondition::presenceOfElementLocated(
				WebDriverBy::cssSelector( $selector )
			)
		);
	}

	public function wait_until_element_is_located_by_xpath(
		string $xpath,
		int $wait_sec = 60,
		int $interval_ms = 500
	) {
		return $this->driver->wait( $wait_sec, $interval_ms )->until(
			WebDriverExpectedCondition::presenceOfElementLocated(
				WebDriverBy::xpath( $xpath )
			)
		);
	}

	public function wait_for_alert_and_accept(): void {
		$this->driver->wait()->until( WebDriverExpectedCondition::alertIsPresent() );
		$this->driver->switchTo()->alert()->accept();
		$this->switch_to_default_frame();
	}

	public function wait_for_frame_and_switch(
		string $selector,
		int $wait_sec = 60,
		int $interval_ms = 500
	): void {
		$this->driver->wait( $wait_sec, $interval_ms )->until(
			WebDriverExpectedCondition::frameToBeAvailableAndSwitchToIt(
				$this->find_element( $selector )
			)
		);
	}

	public function switch_to_default_frame(): void {
		$this->driver->switchTo()->defaultContent();
	}

	public function wait_until_page_fully_loads(
		int $wait_sec = 60,
		int $interval_ms = 500
	) {
		return $this->driver->wait( $wait_sec, $interval_ms )->until(
			function() {
				return $this->driver->executeScript( 'return document.readyState' ) === 'complete';
			}
		);
	}

	public function wait_for_seconds( int $seconds = 5 ) {
		$this->driver->manage()->timeouts()->implicitlyWait( $seconds );
	}

	public function quit(): void {
		$this->driver->quit();
	}
}