<?php

namespace Tawk\Test\TestFiles\Helpers;

use Exception;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;

class Webdriver {
	private static function get_chrome_capabilities( bool $is_headless ) {
		$capabilities = DesiredCapabilities::chrome();
		$capabilities->setCapability( WebDriverCapabilityType::APPLICATION_CACHE_ENABLED, false );

		if ( false === $is_headless ) {
			return $capabilities;
		}

		$options      = new ChromeOptions();
		$options->addArguments( array( '--headless' ) );
		$capabilities->setCapability( ChromeOptions::CAPABILITY, $options );

		return $capabilities;
	}

	public static function build_capabilities(
		string $browser,
		bool $is_headless
	) {
		switch ( $browser ) {
			case 'chrome':
				return self::get_chrome_capabilities( $is_headless );
			default:
				throw new Exception( 'Browser is not supported' );
		}
	}
}