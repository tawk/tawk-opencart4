<?php

namespace Tawk\Test\TestFiles\Modules;

use Tawk\Test\TestFiles\Helpers\Common;
use Tawk\Test\TestFiles\Types\TawkConfig;
use Tawk\Test\TestFiles\Types\WebConfiguration;
use Tawk\Test\TestFiles\Types\WebUserConfig;

class Web {
	private Webdriver $driver;
	private string $user_token;
	private string $base_url;
	private string $admin_url;
	private string $dashboard_url;
	private string $installer_url;
	private string $extension_url;
	private string $plugin_settings_url;
	private string $logout_url;

	private WebUserConfig $admin;
	private TawkConfig $tawk;

	private bool $logged_in;
	private bool $plugin_installed;
	private bool $plugin_activated;
	private bool $widget_set;

	public function __construct( Webdriver $driver, WebConfiguration $config ) {
		$this->driver = $driver;

		$this->base_url = Common::build_url( $config->web->url );
		$this->admin_url           = $this->base_url . 'administration/index.php';
		$this->dashboard_url = $this->base_url . '?route=common/dashboard';
		$this->installer_url  = $this->admin_url . '?route=marketplace/installer';
		$this->extension_url     = $this->admin_url . '?route=marketplace/extension';
		$this->plugin_settings_url = $this->admin_url . '?route=extension/tawkto/module/tawkto';
		$this->logout_url = $this->admin_url . '?route=common/logout';

		$this->admin = $config->web->admin;
		$this->tawk  = $config->tawk;

		$this->logged_in        = false;
		$this->plugin_installed = false;
		$this->plugin_activated = false;
		$this->widget_set       = false;
	}

	public function get_base_url(): string {
		return $this->base_url;
	}

	public function login() {
		if ( true === $this->logged_in ) {
			$this->driver->goto_page( $this->dashboard_url );
			return;
		}

		$this->driver->get_driver()->manage()->deleteAllCookies();

		$this->driver->goto_page( $this->admin_url );

		$this->driver->find_element_and_input( '#input-username', $this->admin->username );
		$this->driver->find_element_and_input( '#input-password', $this->admin->password );
		$this->driver->find_element_and_click( '#form-login button[type="submit"]' );

		$this->driver->wait_until_page_fully_loads();

		$this->driver->goto_page( $this->dashboard_url );

		$title_url = $this->driver->find_element_and_get_attribute_value('header a.navbar-brand', 'href');
		if ( false !== stripos($title_url, 'user_token') ) {
			$parsed_url = parse_url($title_url);
			$query_params = array();
			parse_str($parsed_url['query'], $query_params);

			$this->user_token = $query_params['user_token'];
		}

		$this->logged_in = true;
	}

	public function logout() {
		if ( false === $this->logged_in ) {
			return;
		}

		$this->driver->goto_page( $this->logout_url . "&user_token=" . $this->user_token );

		$this->logged_in = false;
		$this->user_token = '';
	}

	public function is_logged_in() {
		return true === $this->logged_in && '' !== $this->user_token;
	}

	public function get_plugin_status() {
		return array(
			"installed" => $this->plugin_installed,
			"activated" => $this->plugin_activated
		);
	}

	public function install_plugin() {
		if ( true === $this->plugin_installed ) {
			return;
		}

		$this->driver->goto_page( $this->installer_url . "&user_token=" . $this->user_token );

		$uninstall_xpath = '//a[contains(@href, "https://tawk.to")]/../following-sibling::td[3]/a[1]/i[contains(@class, "fa-minus-circle")]';
		$uninstall_link = $this->driver->find_and_check_element_by_xpath( $uninstall_xpath );

		if ( false === is_null( $uninstall_link ) ) {
			$this->plugin_installed = true;
			return;
		}

		$install_xpath = '//a[contains(@href, "https://tawk.to")]/../following-sibling::td[3]/a[1]/i[contains(@class, "fa-plus-circle")]';
		$this->driver->find_element_and_click_by_xpath( $install_xpath );

		$this->plugin_installed = true;
	}

	public function uninstall_plugin() {
		if ( false === $this->plugin_installed ) {
			return;
		}

		$this->driver->goto_page( $this->installer_url . "&user_token=" . $this->user_token );

		$install_xpath = '//a[contains(@href, "https://tawk.to")]/../following-sibling::td[3]/a[1]/i[contains(@class, "fa-plus-circle")]';
		$install_link = $this->driver->find_and_check_element_by_xpath( $install_xpath );

		if ( false === is_null( $install_link ) ) {
			$this->plugin_installed = false;
			return;
		}

		$uninstall_xpath = '//a[contains(@href, "https://tawk.to")]/../following-sibling::td[3]/a[1]/i[contains(@class, "fa-minus-circle")]';
		$this->driver->find_element_and_click_by_xpath( $uninstall_xpath );

		$this->plugin_installed = false;
	}

	public function activate_plugin() {
		if ( true === $this->plugin_activated ) {
			return;
		}

		$this->driver->goto_page( $this->extension_url . "&user_token=" . $this->user_token );

		$this->driver->find_dropdown_and_select( '#input-type', 'Modules' );

		$this->driver->wait_for_seconds( 1 );

		$deactivate_xpath = '//a[contains(@href, "tawkto")]/i[contains(@class, "fa-minus-circle")]';
		$deactivate_link = $this->driver->find_and_check_element_by_xpath( $deactivate_xpath );

		if ( false === is_null( $deactivate_link ) ) {
			$this->plugin_activated = true;
			return;
		}

		$activate_xpath = '//a[contains(@href, "tawkto")]/i[contains(@class, "fa-plus-circle")]';
		$this->driver->find_element_and_click_by_xpath( $activate_xpath );

		$this->plugin_activated = true;
	}

	public function deactivate_plugin() {
		if ( false === $this->plugin_activated ) {
			return;
		}

		$this->driver->goto_page( $this->extension_url . "&user_token=" . $this->user_token );

		$this->driver->find_dropdown_and_select( '#input-type', 'Modules' );

		$this->driver->wait_for_seconds( 1 );

		$activate_xpath = '//a[contains(@href, "tawkto")]/i[contains(@class, "fa-plus-circle")]';
		$activate_link = $this->driver->find_and_check_element_by_xpath( $activate_xpath );

		if ( false === is_null( $activate_link ) ) {
			$this->plugin_activated = false;
			return;
		}

		$deactivate_xpath = '//a[contains(@href, "tawkto")]/i[contains(@class, "fa-minus-circle")]';
		$this->driver->find_element_and_click_by_xpath( $deactivate_xpath );
		$this->driver->wait_for_alert_and_accept();

		$this->plugin_activated = false;
	}

	public function set_widget( string $property_id, string $widget_id ) {
		if ( $this->widget_set ) {
			return;
		}

		$this->driver->goto_page( $this->plugin_settings_url . "&user_token=" . $this->user_token );

		$this->driver->wait_for_frame_and_switch( '#tawkIframe', 10 );

		// driver currently on tawkIframe frame
		$login_form_id = '#loginForm';
		$login_form    = $this->driver->find_and_check_element( $login_form_id );
		if ( false === is_null( $login_form ) ) {
			$this->driver->find_element_and_input( '#email', $this->tawk->username );
			$this->driver->find_element_and_input( '#password', $this->tawk->password );
			$this->driver->find_element_and_click( '#login-button' );
		}

		$property_form_id = '#propertyForm';
		$this->driver->wait_until_element_is_located( $property_form_id );
		$this->driver->find_element_and_click( '#property' );
		$this->driver->find_element_and_click( 'li[data-id="' . $property_id . '"]' );
		$this->driver->find_element_and_click( '#widget-' . $property_id );
		$this->driver->find_element_and_click( 'li[data-id="' . $widget_id . '"]' );
		$this->driver->find_element_and_click( '#addWidgetToPage' );

		// ensures widget is added.
		$this->driver->wait_for_seconds( 1 );

		$this->widget_set = true;

		// go back to original frame.
		$this->driver->switch_to_default_frame();
	}

	public function remove_widget() {
		if ( false === $this->widget_set ) {
			return;
		}

		$this->driver->goto_page( $this->plugin_settings_url . "&user_token=" . $this->user_token );

		$this->driver->wait_for_frame_and_switch( '#tawkIframe', 10 );

		$this->driver->wait_until_element_is_located( '#propertyForm' );
		$this->driver->find_element_and_click( '#removeCurrentWidget' );

		// ensures widget is removed.
		$this->driver->wait_for_seconds( 1 );

		$this->widget_set = false;

		// go back to original frame.
		$this->driver->switch_to_default_frame();
	}
}