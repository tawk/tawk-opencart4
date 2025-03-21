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
	public const CREDENTIALS_FILE = DIR_EXTENSION . 'tawkto/system/config/credentials.json';

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
		$this->load->model('setting/setting');

		$data = array();

		$privacy_opts = $this->config->get('tawkto_privacy');
		$cart_opts = $this->config->get('tawkto_cart');
		$security_opts = $this->config->get('tawkto_security');
		$config_version = $this->config->get('tawkto_config_version');

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
		if (isset($settings['module_tawkto_config_version'])) {
			$config_version = $settings['module_tawkto_config_version'];
		}

		$data['visitor'] = $this->getVisitor(array(
			'enable_visitor_recognition' => $privacy_opts['enable_visitor_recognition'],
			'secure_mode_enabled' => $security_opts['secure_mode_enabled'],
			'js_api_key' => $security_opts['js_api_key'],
			'config_version' => $config_version,
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
		if ($params['enable_visitor_recognition'] === false) {
			return null;
		}

		$secure_mode_enabled = $params['secure_mode_enabled'];
		$encrypted_js_api_key = $params['js_api_key'];
		$config_version = $params['config_version'];

		$logged_in = $this->customer->isLogged();
		if ($logged_in) {
			$data = array(
					'name' => $this->customer->getFirstName().' '.$this->customer->getLastName(),
					'email' => $this->customer->getEmail(),
				);

			if ($secure_mode_enabled && !is_null($encrypted_js_api_key)) {
				$data['hash'] = $this->getVisitorHash(array(
					'email' => $this->customer->getEmail(),
					'js_api_key' => $encrypted_js_api_key,
					'config_version' => $config_version,
				));
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
	 * Get visitor hash
	 *
	 * @param array $params
	 * @return string
	 */
	private function getVisitorHash($params)
	{
		$js_api_key = $params['js_api_key'];

		if (empty($js_api_key)) {
			return '';
		}

		if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
			session_start();
		}

		$configVersion = $params['config_version'];
		$email = $params['email'];

		if (isset($_SESSION['tawkto_visitor_hash'])) {
			$currentSession = $_SESSION['tawkto_visitor_hash'];

			if (isset($currentSession['hash']) &&
				$currentSession['email'] === $email &&
				$currentSession['config_version'] === $configVersion) {
				return $currentSession['hash'];
			}
		}

		try {
			$jsApiKey = $this->decryptData($js_api_key);
		} catch (\Exception $e) {
			error_log($e->getMessage());

			return '';
		}

		$hash = hash_hmac('sha256', $email, $jsApiKey);

		$_SESSION['tawkto_visitor_hash'] = array(
			'hash' => $hash,
			'email' => $email,
			'config_version' => $configVersion,
		);

		return $hash;
	}

	/**
	 * Decrypt data
	 * @param mixed $data
	 * @return string Decrypted data
	 */
	private function decryptData($data)
	{
		if (!file_exists(self::CREDENTIALS_FILE)) {
			throw new \Exception('Credentials file not found');
		}

		$credentials = json_decode(file_get_contents(self::CREDENTIALS_FILE), true);

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
