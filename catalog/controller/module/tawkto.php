<?php

/**
 * @package tawk.to Integration
 * @author tawk.to
 * @copyright (C) 2024 tawk.to
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Opencart\Catalog\Controller\Extension\Tawkto\Module;

require_once DIR_EXTENSION . 'tawkto/vendor/autoload.php';

use \Opencart\System\Engine\Controller;
use \Tawk\Modules\UrlPatternMatcher;

class Tawkto extends Controller
{
	/**
	 * __construct
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->config->addPath(DIR_EXTENSION . 'tawkto/system/config/');
		$this->config->load('tawkto');
	}

	/**
	 * Entry point
	 */
	public function index()
	{
		if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
			session_start();
		}

		$this->load->model('setting/setting');

		$data = array();

		$privacy_opts = $this->config->get('tawkto_privacy');
		$cart_opts = $this->config->get('tawkto_cart');
		$security_opts = $this->config->get('tawkto_security');

		$settings = $this->getCurrentSettings();
		if (isset($settings['module_tawkto_privacy'])) {
			$privacy_opts = $settings['module_tawkto_privacy'];
		}
		if (isset($settings['module_tawkto_cart'])) {
			$cart_opts = $settings['module_tawkto_cart'];
		}
		if (isset($settings['module_tawkto_security'])) {
			$security_opts = $settings['module_tawkto_security'];
		}

		$data['visitor'] = $this->getVisitor(array(
			'enable_visitor_recognition' => $privacy_opts['enable_visitor_recognition'],
			'secure_mode_enabled' => $security_opts['secure_mode_enabled'],
			'js_api_key' => $security_opts['js_api_key'],
		));
		$data['can_monitor_customer_cart'] = $cart_opts['monitor_customer_cart'];

		$widget = $this->getWidget();

		if (isset($widget['page_id']) && isset($widget['widget_id'])) {
			$data['page_id'] = $widget['page_id'];
			$data['widget_id'] = $widget['widget_id'];
		}

		return $this->load->view('extension/tawkto/module/tawkto', $data);
	}

	/**
	 * Get page_id and widget_id and check visibility
	 *
	 * @return object|null
	 */
	private function getWidget()
	{
		$settings = $this->getCurrentSettings();

		if (!isset($settings['module_tawkto_widget']['widget_config'])) {
			return null;
		}

		$visibility = $this->config->get('tawkto_visibility');

		if (isset($settings['module_tawkto_visibility'])) {
			$visibility = $settings['module_tawkto_visibility'];
		}

		// prepare visibility
		$request_uri = trim($_SERVER["REQUEST_URI"]);
		if (stripos($request_uri, '/') === 0) {
			$request_uri = substr($request_uri, 1);
		}
		$current_page = $this->config->get('config_url') . $request_uri;

		$show = false;
		if (false == $visibility['always_display']) {

			// custom pages
			$show_pages = $visibility['show_oncustom'];

			if ($this->matchPatterns($current_page, $show_pages)) {
				$show = true;
			}

			// category page
			if (isset($this->request->get['route']) && stripos($this->request->get['route'], 'category') !== false) {
				if ($visibility['show_oncategory']) {
					$show = true;
				}
			}

			// home
			if (
				!isset($this->request->get['route'])
				|| (isset($this->request->get['route']) && $this->request->get['route'] == 'common/home')
			) {
				if ($visibility['show_onfrontpage']) {
					$show = true;
				}
			}

		} else {
			$show = true;

			$hide_pages = $visibility['hide_oncustom'];

			if ($this->matchPatterns($current_page, $hide_pages)) {
				$show = false;
			}
		}

		if (!$show) {
			return;
		}

		return $settings['module_tawkto_widget']['widget_config'];
	}

	/**
	 * Pattern matching
	 *
	 * @return boolean
	 */
	private function matchPatterns($current_page, $pages)
	{
		return UrlPatternMatcher::match($current_page, $pages);
	}

	/**
	 * Get visitor details
	 *
	 * @param array $params
	 * @return string|null
	 */
	private function getVisitor($params)
	{
		$enable_visitor_recognition = $params['enable_visitor_recognition'];
		$secure_mode_enabled = $params['secure_mode_enabled'];
		$encrypted_js_api_key = $params['js_api_key'];

		if (!$enable_visitor_recognition) {
			return null;
		}

		$logged_in = $this->customer->isLogged();
		if ($logged_in) {
			$data = array(
					'name' => $this->customer->getFirstName().' '.$this->customer->getLastName(),
					'email' => $this->customer->getEmail(),
				);

			if ($secure_mode_enabled && !is_null($encrypted_js_api_key)) {
				try {
					$js_api_key = $this->getJsApiKey($encrypted_js_api_key);
				} catch (\Exception $e) {
					return null;
				}

				$data['hash'] = hash_hmac('sha256', $this->customer->getEmail(), $js_api_key);
			}

			return json_encode($data);
		}

		return null;
	}

	/**
	 * Get current settings
	 *
	 * @return array
	 */
	private function getCurrentSettings()
	{
		$store_id = $this->config->get('config_store_id');
		return $this->model_setting_setting->getSetting('module_tawkto', $store_id);
	}

	/**
	 * Get js_api_key
	 * @param string $encrypted_js_api_key
	 * @return string JS API key
	 */
	private function getJsApiKey($encrypted_js_api_key)
	{
		if (isset($_SESSION['tawkto_js_api_key'])) {
			return $_SESSION['tawkto_js_api_key'];
		}

		$js_api_key = $this->decryptData($encrypted_js_api_key);

		$_SESSION['tawkto_js_api_key'] = $js_api_key;

		return $js_api_key;
	}

	/**
	 * Decrypt data
	 * @param mixed $data
	 * @return string Decrypted data
	 */
	private function decryptData($data)
	{
		$filePath = DIR_EXTENSION . 'tawkto/system/config/credentials.json';

		if (!file_exists($filePath)) {
			throw new \Exception('Credentials file not found');
		}

		$credentials = json_decode(file_get_contents($filePath), true);

		if (!isset($credentials['encryption_key'])) {
			throw new \Exception('Encryption key not found');
		}

		$decoded = base64_decode($data);

		if ($decoded === false) {
			throw new \Exception('Failed to decode data');
		}

		$iv = substr($decoded, 0, 16);
		$encrypted_data = substr($decoded, 16);

		$decrypted_data = openssl_decrypt($encrypted_data, 'AES-256-CBC', $credentials['encryption_key'], 0, $iv);

		if ($decrypted_data === false) {
			throw new \Exception('Failed to decrypt data');
		}

		return $decrypted_data;
	}
}
