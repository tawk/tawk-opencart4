<?php

namespace Tawk\Test\TestFiles\Modules;

use Tawk\Test\TestFiles\Helpers\Common;
use Tawk\Test\TestFiles\Types\WebConfiguration;
use Tawk\Test\TestFiles\Types\WebUserConfig;

class Web {
	private Webdriver $driver;
	private string $user_token;
	private string $base_url;
	private string $admin_url;
	private string $dashboard_url;
	private string $logout_url;

	private WebUserConfig $admin;

	private bool $logged_in;

	public function __construct( Webdriver $driver, WebConfiguration $config ) {
		$this->driver = $driver;

		$this->base_url = Common::build_url( $config->web->url );
		$this->admin_url           = $this->base_url . 'administration/index.php';
		$this->dashboard_url = $this->base_url . '?route=common/dashboard';
		$this->logout_url = $this->admin_url . '?route=common/logout';

		$this->admin = $config->web->admin;
		$this->tawk  = $config->tawk;

		$this->logged_in        = false;
		$this->plugin_installed = false;
		$this->plugin_activated = false;
		$this->widget_set       = false;
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

}