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

#[TestDox('Visibility Options Test')]
class VisibilityOptionsTest extends TestCase {
	private static Webdriver $driver;
	private static Web $web;
	private static string $property_id;
	private static string $widget_id;
	private static string $script_selector;

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

		self::$script_selector = '#tawk-script';

		self::$driver->wait_for_seconds();

		self::$web->login();

		self::$web->install_plugin();
		self::$web->activate_plugin();

		self::$web->set_widget( self::$property_id, self::$widget_id );
	}

	public static function tearDownAfterClass(): void {
		self::$web->deactivate_plugin();
		self::$web->uninstall_plugin();

		self::$web->logout();

		self::$driver->quit();
	}

	public function setUp(): void {
		self::$web->goto_plugin_settings();
	}

	private function check_widget_not_on_page( $url ) {
		self::$driver->goto_page( $url );

		$script = self::$driver->find_and_check_element( self::$script_selector );

		$this->assertNull( $script );
	}

	private function check_widget_on_page( $url ) {
		self::$driver->goto_page( $url );

		$script = self::$driver->find_and_check_element( self::$script_selector );

		$this->assertNotNull( $script );
	}

	#[Test]
	#[Group('visibility_opts_always_display_enabled_exclude_url')]
	public function should_not_display_widget_on_excluded_page_while_always_display_is_enabled() {
		$excluded_url = self::$web->get_base_url() . 'en-gb/catalog/desktops';
		self::$web->toggle_checkbox( '#always_display', true );
		self::$driver->find_element_and_input( '#hide_oncustom', $excluded_url );

		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_not_on_page( $excluded_url );
	}

	#[Test]
	#[Group('visibility_opts_always_display_enabled_exclude_url')]
	public function should_not_display_widget_on_excluded_pages_match_by_wildcard_while_always_display_is_enabled() {
		$excluded_urls = join(
			"\n",
			array(
				self::$web->get_base_url() . 'catalog/*',
				'*/product/macbook',
				'/*/product/iphone',
				'/product/*/apple-cinema',
				'*/product/*/canon-eos-5d',
				'/product/*/nikon-d300/*',
				'/product/*/htc-touch-hd/*/'
			)
		);
		self::$web->toggle_checkbox( '#always_display', true );
		self::$driver->find_element_and_input( '#hide_oncustom', $excluded_urls );
		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		// assertion for '<host>/category/*'.
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'catalog/desktops' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'catalog/laptop-notebook' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'catalog/desktops/mac' );

		// assertion for '*/product/macbook'
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'some/product/macbook' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'some/other/product/macbook' );

		// assertion for '*/product/iphone'
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'other/product/iphone' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'some/other/product/iphone' );

		// assertion for '/product/*/apple-cinema'
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/apple-cinema/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/other/apple-cinema/' );

		// assertion for '*/product/*/canon-eos-5d'
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'some/product/some/canon-eos-5d/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'some/other/product/some/canon-eos-5d/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'some/product/some/other/canon-eos-5d/' );

		// assertion for '/product/*/nikon-d300/*'.
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/nikon-d300/some/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/nikon-d300/some/other/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/other/nikon-d300/some/' );

		// assertion for '/product/*/htc-touch-hd/*/'.
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/htc-touch-hd/some/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/htc-touch-hd/some/other/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/other/htc-touch-hd/some/' );
	}

	#[Test]
	#[Group('visibility_opts_always_display_enabled_exclude_url')]
	public function should_display_widget_on_non_excluded_pages_while_always_display_is_enabled() {
		$excluded_url = self::$web->get_base_url() . 'catalog/*';

		self::$web->toggle_checkbox( '#always_display', true );
		self::$driver->find_element_and_input( '#hide_oncustom', $excluded_url );
		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_on_page( self::$web->get_base_url() . 'product/macbook' );
	}

	#[Test]
	#[Group('visibility_opts_always_display_disabled')]
	public function should_not_display_when_always_display_is_disabled() {
		self::$web->toggle_checkbox( '#always_display', false );

		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_not_on_page( self::$web->get_base_url() );
	}

	#[Test]
	#[Group('visibility_opts_always_display_disabled_include_url_enabled')]
	public function should_display_widget_on_included_page_while_always_display_is_disabled() {
		$included_url = self::$web->get_base_url() . 'catalog/desktops';
		self::$web->toggle_checkbox( '#always_display', false );
		self::$driver->find_element_and_input( '#show_oncustom', $included_url );

		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_on_page( $included_url );
	}

	#[Test]
	#[Group('visibility_opts_always_display_disabled_include_url_enabled')]
	public function should_display_widget_on_included_pages_matched_by_wildcard_while_always_display_is_disabled() {
		$included_urls = join(
			"\n",
			array(
				self::$web->get_base_url() . 'catalog/*',
				'*/product/macbook',
				'/*/product/iphone',
				'/product/*/apple-cinema',
				'*/product/*/canon-eos-5d',
				'/product/*/nikon-d300/*',
				'/product/*/htc-touch-hd/*/'
			)
		);
		self::$web->toggle_checkbox( '#always_display', false );
		self::$driver->find_element_and_input( '#show_oncustom', $included_urls );
		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		// assertion for '<host>/category/*'.
		$this->check_widget_on_page( self::$web->get_base_url() . 'catalog/desktops' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'catalog/laptop-notebook' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'catalog/desktops/mac' );

		// assertion for '*/product/macbook'
		$this->check_widget_on_page( self::$web->get_base_url() . 'some/product/macbook' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'some/other/product/macbook' );

		// assertion for '*/product/iphone'
		$this->check_widget_on_page( self::$web->get_base_url() . 'other/product/iphone' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'some/other/product/iphone' );

		// assertion for '/product/*/apple-cinema'
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/apple-cinema/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/other/apple-cinema/' );

		// assertion for '*/product/*/canon-eos-5d'
		$this->check_widget_on_page( self::$web->get_base_url() . 'some/product/some/canon-eos-5d/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'some/other/product/some/canon-eos-5d/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'some/product/some/other/canon-eos-5d/' );

		// assertion for '/product/*/nikon-d300/*'.
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/nikon-d300/some/' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/nikon-d300/some/other/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/other/nikon-d300/some/' );

		// assertion for '/product/*/htc-touch-hd/*/'.
		$this->check_widget_on_page( self::$web->get_base_url() . 'product/some/htc-touch-hd/some/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/htc-touch-hd/some/other/' );
		$this->check_widget_not_on_page( self::$web->get_base_url() . 'product/some/other/htc-touch-hd/some/' );
	}

	#[Test]
	#[Group('visibility_opts_always_display_disabled_include_url_enabled')]
	public function should_display_widget_on_non_included_pages_while_always_display_is_disabled() {
		$included_url = self::$web->get_base_url() . 'catalog/*';
		self::$web->toggle_checkbox( '#always_display', false );
		self::$driver->find_element_and_input( '#show_oncustom', $included_url );
		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_not_on_page( self::$web->get_base_url() . '?route=information/contact' );
	}

	#[Test]
	#[Group('visibility_opts_always_display_disabled_show_on_front_page_enabled')]
	public function should_display_widget_on_front_page_if_show_on_front_page_is_enabled_and_always_display_is_disabled() {
		self::$web->toggle_checkbox( '#always_display', false );
		self::$driver->wait_until_element_is_clickable( '#show_onfrontpage' );
		self::$web->toggle_checkbox( '#show_onfrontpage', true );

		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_on_page( self::$web->get_base_url() );
	}

	#[Test]
	#[Group('visibility_opts_always_display_disabled_show_on_category_pages_enabled')]
	public function should_display_widget_on_category_pages_if_show_on_category_pages_is_enabled_and_always_display_is_disabled() {
		self::$web->toggle_checkbox( '#always_display', false );
		self::$driver->wait_until_element_is_clickable( '#show_oncategory' );
		self::$web->toggle_checkbox( '#show_oncategory', true );

		self::$driver->find_element_and_click( '#module_form_submit_btn' );

		$this->check_widget_on_page( self::$web->get_base_url() . 'en-gb/catalog/desktops' );
		$this->check_widget_on_page( self::$web->get_base_url() . 'en-gb/catalog/laptop-notebook' );
	}
}