<?php

/**
 * @package tawk.to Integration
 * @author tawk.to
 * @copyright (C) 2024 tawk.to
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Opencart\Admin\Controller\Extension\Tawkto\Module;

use \Opencart\System\Engine\Controller;

class Tawkto extends Controller
{
	public const CREDENTIALS_FILE = DIR_EXTENSION . 'tawkto/system/config/credentials.json';
	public const NO_CHANGE = 'nochange';

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
	public function index(): void
	{
		$this->load->language('extension/tawkto/module/tawkto');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('../extension/tawkto/admin/view/stylesheet/index.css');

		$this->load->model('setting/setting');
		$this->load->model('setting/store');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			// 'text_home' loaded by default from opencart (upload/admin/language/en-gb/default.php)
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/tawkto', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		);

		$data['base_url']   = $this->getBaseUrl();
		$data['iframe_url'] = $this->getIframeUrl();
		$data['hierarchy']  = $this->getStoreHierarchy();
		$data['url'] = array(
			'set_widget_url' => $this->url->link('extension/tawkto/module/tawkto.setwidget', '', 'SSL') . '&user_token=' . $this->session->data['user_token'],
			'remove_widget_url' => $this->url->link('extension/tawkto/module/tawkto.removewidget', '', 'SSL') . '&user_token=' . $this->session->data['user_token'],
			'set_options_url' => $this->url->link('extension/tawkto/module/tawkto.setoptions', '', 'SSL') . '&user_token=' . $this->session->data['user_token']
		);

		$data['current_user'] = $this->session->data['user_id'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/tawkto/module/tawkto', $data));
	}

	/**
	 * Install script when extension is enabled
	 */
	public function install(): void
	{
		$this->load->model('setting/event');
		$this->load->model('setting/setting');
		$this->load->model('setting/store');

		$data = array(
			'code' => 'tawkto_widget',
			'description' => 'tawk.to chat widget',
			'trigger' => 'catalog/view/common/content_bottom/after',
			'action' => 'extension/tawkto/module/tawkto',
			'status' => 1,
			'sort_order' => 0
		);

		$this->model_setting_event->addEvent($data);

		$currentSettings = $this->getCurrentSettingsFor(0);
		$currentSettings['module_tawkto_status'] = '1';
		$this->model_setting_setting->editSetting('module_tawkto', $currentSettings, 0);
	}

	/**
	 * Uninstall script when extension is disabled
	 */
	public function uninstall(): void
	{
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('tawkto_widget');
	}

	/**
	 * Iframe URL
	 *
	 * @return string
	 */
	private function getIframeUrl(): string
	{
		return $this->getBaseUrl()
			. '/generic/widgets'
			. '?selectText=Store';
	}

	/**
	 * Base URL
	 *
	 * @return string
	 */
	private function getBaseUrl(): string
	{
		return 'https://plugins.tawk.to';
	}

	/**
	 * Module supports multistore structure, each store can have different
	 * widgets
	 *
	 * @return Array
	 */
	private function getStoreHierarchy()
	{
		$stores = $this->model_setting_store->getStores();

		$hierarchy = array();

		$currentSettings = $this->getCurrentSettingsFor('0');

		$hierarchy[] = array(
			'id'      => '0',
			'name'    => 'Default store',
			'current' => $this->getWidgetOpts($currentSettings),
			'display_opts' => $this->getDisplayOpts($currentSettings),
			'privacy_opts' => $this->getPrivacyOpts($currentSettings),
			'cart_opts' => $this->getCartOpts($currentSettings),
			'security_opts' => $this->getSecurityOpts($currentSettings),
		);

		foreach ($stores as $store) {
			$currentSettings = $this->getCurrentSettingsFor($store['store_id']);

			$hierarchy[] = array(
				'id'      => $store['store_id'],
				'name'    => $store['name'],
				'current' => $this->getWidgetOpts($currentSettings),
				'display_opts' => $this->getDisplayOpts($currentSettings),
				'privacy_opts' => $this->getPrivacyOpts($currentSettings),
				'cart_opts' => $this->getCartOpts($currentSettings),
				'security_opts' => $this->getSecurityOpts($currentSettings),
			);
		}

		return $hierarchy;
	}

	/**
	 * Retrieves all tawkto settings for store
	 *
	 * @param  Int $id
	 * @return Array
	 */
	private function getCurrentSettingsFor($store_id)
	{

		return $this->model_setting_setting->getSetting('module_tawkto', $store_id);
	}

	/**
	 * Endpoint for setting page_id and widget_id
	 */
	public function setwidget()
	{
		header('Content-Type: application/json');

		if (!$this->validatePost() || !$this->checkPermission()) {
			echo json_encode(array('success' => false));
			die();
		}

		$store_id = intval($_POST['store']);
		$page_id = $this->db->escape($_POST['pageId']);
		$widget_id = $this->db->escape($_POST['widgetId']);

		$currentSettings = $this->getCurrentSettingsFor($store_id);
		$currentSettings['module_tawkto_widget'] = isset($currentSettings['module_tawkto_widget']) ? $currentSettings['module_tawkto_widget'] : array();
		$currentSettings['module_tawkto_widget']['widget_config'] = array(
			'page_id' => $page_id,
			'widget_id' => $widget_id,
			'user_id' => $this->session->data['user_id']
		);

		$this->model_setting_setting->editSetting('module_tawkto', $currentSettings, $store_id);

		echo json_encode(array('success' => true));
		die();
	}

	/**
	 * Endpoint for removing page_id and widget_id
	 */
	public function removewidget()
	{
		header('Content-Type: application/json');

		$store_id = isset($_POST['store']) ? intval($_POST['store']) : null;
		if (is_null($store_id) || !$this->checkPermission()) {
			echo json_encode(array('success' => false));
			die();
		}

		$currentSettings = $this->getCurrentSettingsFor($store_id);
		unset($currentSettings['module_tawkto_widget']['widget_config']);

		$this->model_setting_setting->editSetting('module_tawkto', $currentSettings, $store_id);

		echo json_encode(array('success' => true));
		die();
	}

	/*
	 * Endpoint for setting widget options
	 */
	public function setoptions()
	{
		header('Content-Type: application/json');

		$store_id = isset($_POST['store']) ? intval($_POST['store']) : null;
		if (is_null($store_id)) {
			echo json_encode(array('success' => false));
			die();
		}

		$visibilityOpts = $this->config->get('tawkto_visibility');
		$visibilityOpts['always_display'] = false; // account for absence of checkbox value

		$privacyOpts = $this->config->get('tawkto_privacy');

		$cartOpts = $this->config->get('tawkto_cart');

		$securityOpts = $this->config->get('tawkto_security');

		if (isset($_POST['options']) && !empty($_POST['options'])) {
			$options = explode('&', $_POST['options']);

			foreach ($options as $post) {
				list($key, $value) = explode('=', $post);
				switch ($key) {
					case 'show_oncustom':
					case 'hide_oncustom':
						// split by newlines, then remove empty lines
						$value = urldecode($value);
						$value = str_ireplace("\r", "\n", $value);
						$value = explode("\n", $value);
						$non_empty_values = array();
						foreach ($value as $str) {
							$trimmed = trim($str);
							if ($trimmed !== '') {
								$non_empty_values[] = $trimmed;
							}
						}
						$visibilityOpts[$key] = $non_empty_values;
						break;

					// serialize() only includes "successful controls"
					case 'always_display':
					case 'show_onfrontpage':
					case 'show_oncategory':
						$visibilityOpts[$key] = true;
						break;

					case 'enable_visitor_recognition':
						$privacyOpts[$key] = true;
						break;

					case 'monitor_customer_cart':
						$cartOpts[$key] = true;
						break;

					case 'secure_mode_enabled':
						$securityOpts[$key] = true;
						break;

					case 'js_api_key':
						if ($value === self::NO_CHANGE) {
							unset($securityOpts['js_api_key']);
							break;
						}

						if ($value === '') {
							break;
						}

						$value = trim($value);

						if (strlen($value) !== 40) {
							throw new \Exception('Invalid API key.');
						}

						try {
							$securityOpts['js_api_key'] = $this->encryptData($value);
						} catch (\Exception $e) {
							error_log($e->getMessage());

							unset($securityOpts['js_api_key']);

							echo json_encode(array('success' => false, 'message' => 'Error saving Javascript API Key.'));
							die();
						}
				}
			}
		}

		$currentSettings = $this->getCurrentSettingsFor($store_id);
		if (!isset($currentSettings['module_tawkto_visibility'])) {
			$currentSettings['module_tawkto_visibility'] = array();
		}
		if (!isset($currentSettings['module_tawkto_privacy'])) {
			$currentSettings['module_tawkto_privacy'] = array();
		}
		if (!isset($currentSettings['module_tawkto_cart'])) {
			$currentSettings['module_tawkto_cart'] = array();
		}
		if (!isset($currentSettings['module_tawkto_security'])) {
			$currentSettings['module_tawkto_security'] = array();
		}
		if (!isset($currentSettings['module_tawkto_config_version'])) {
			$currentSettings['module_tawkto_config_version'] = $this->config->get('tawkto_config_version');
		}

		$currentSettings['module_tawkto_visibility'] = array_merge($currentSettings['module_tawkto_visibility'], $visibilityOpts);
		$currentSettings['module_tawkto_privacy'] = array_merge($currentSettings['module_tawkto_privacy'], $privacyOpts);
		$currentSettings['module_tawkto_cart'] = array_merge($currentSettings['module_tawkto_cart'], $cartOpts);
		$currentSettings['module_tawkto_security'] = array_merge($currentSettings['module_tawkto_security'], $securityOpts);
		$currentSettings['module_tawkto_config_version'] = $currentSettings['module_tawkto_config_version'] + 1;
		$this->model_setting_setting->editSetting('module_tawkto', $currentSettings, $store_id);

		echo json_encode(array('success' => true));
		die();
	}

	/**
	 * Page id is mongodb object id and widget id is alpanumeric
	 * string
	 *
	 * @return boolean
	 */
	private function validatePost()
	{
		if (!isset($_POST['pageId']) || !isset($_POST['widgetId']) || !isset($_POST['store'])) {
			return false;
		}

		$page_id = $this->db->escape($_POST['pageId']);
		$widget_id = $this->db->escape($_POST['widgetId']);
		$store = isset($_POST['store']) ? intval($_POST['store']) : null;

		return (!empty($page_id) && !empty($widget_id) && !is_null($store))
			&& preg_match('/^[0-9A-Fa-f]{24}$/', $page_id) === 1
			&& preg_match('/^[a-z0-9]{1,50}$/i', $widget_id) === 1;
	}

	/**
	 * Check user has permission to modify extension
	 *
	 * @return boolean
	 */
	protected function checkPermission()
	{
		if (!$this->user->hasPermission('modify', 'extension/tawkto/module/tawkto')) {
			$this->error['warning'] = $this->language->get('error_permission');
			return false;
		}

		return true;
	}

	/**
	 * Get widget options from setting
	 *
	 * @return Array
	 */
	public function getWidgetOpts($settings) {
		if (isset($settings['module_tawkto_widget']['widget_config'])) {
			return $settings['module_tawkto_widget']['widget_config'];
		}

		return array();
	}

	/**
	 * Get display options from setting
	 *
	 * @return Array
	 */
	public function getDisplayOpts($settings)
	{
		$options = $this->config->get('tawkto_visibility');

		if (isset($settings['module_tawkto_visibility'])) {
			$options = $settings['module_tawkto_visibility'];
		}

		return $options;
	}

	/**
	 * Get privacy options from setting
	 *
	 * @return Array
	 */
	public function getPrivacyOpts($settings)
	{
		$options = $this->config->get('tawkto_privacy');

		if (isset($settings['module_tawkto_privacy'])) {
			$options = $settings['module_tawkto_privacy'];
		}

		return $options;
	}

	/**
	 * Get cart options from setting
	 *
	 * @return Array
	 */
	public function getCartOpts($settings)
	{
		$options = $this->config->get('tawkto_cart');

		if (isset($settings['module_tawkto_cart'])) {
			$options = $settings['module_tawkto_cart'];
		}

		return $options;
	}

	/**
	 * Get security options from setting
	 *
	 * @return Array
	 */
	public function getSecurityOpts($settings) {
		$options = $this->config->get('tawkto_security');

		if (isset($settings['module_tawkto_security'])) {
			$options = $settings['module_tawkto_security'];
		}

		if (!empty($options['js_api_key'])) {
			$options['js_api_key'] = self::NO_CHANGE;
		}

		return $options;
	}


	/**
	 * Get credentials
	 *
	 * @return Array
	 */
	private function getCredentials() {
		if (file_exists(self::CREDENTIALS_FILE)) {
			return json_decode(file_get_contents(self::CREDENTIALS_FILE), true);
		}

		$credentials = array(
			'encryption_key' => bin2hex(random_bytes(32)),
		);

		file_put_contents(self::CREDENTIALS_FILE, json_encode($credentials));

		return $credentials;
	}

	/**
	 * Encrypt data
	 *
	 * @param string $data Data to encrypt
	 *
	 * @return string Encrypted data
	 *
	 * @throws \Exception Error encrypting data
	 */
	private function encryptData($data) {
		try {
			$encryptionKey = $this->getCredentials()['encryption_key'];
		} catch (\Exception $e) {
			throw new \Exception('Failed to get encryption key');
		}

		try {
			$iv = random_bytes(16);
		} catch (\Exception $e) {
			throw new \Exception('Failed to generate IV');
		}

		$encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryptionKey, 0, $iv);

		if ($encrypted === false) {
			throw new \Exception('Failed to encrypt data');
		}

		$encrypted = base64_encode($iv . $encrypted);

		if ($encrypted === false) {
			throw new \Exception('Failed to encode data');
		}

		return $encrypted;
	}
}
