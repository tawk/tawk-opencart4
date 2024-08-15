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

		$data['same_user'] = true;
		if (isset($data['widget_config']['user_id'])) {
			$data['same_user']  = ($data['widget_config']['user_id'] == $this->session->data['user_id']);
		}

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

		$data = array(
			'code' => 'tawkto_widget',
			'description' => 'tawk.to chat widget',
			'trigger' => 'catalog/view/common/content_bottom/after',
			'action' => 'extension/tawkto/module/tawkto',
			'status' => 1,
			'sort_order' => 0
		);

		$this->model_setting_event->addEvent($data);
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

		$hierarchy[] = array(
			'id'      => '0',
			'name'    => 'Default store',
			'current' => $this->getCurrentSettingsFor('0'),
			'display_opts' => $this->getDisplayOpts('0'),
		);

		foreach ($stores as $store) {
			$hierarchy[] = array(
				'id'      => $store['store_id'],
				'name'    => $store['name'],
				'current' => $this->getCurrentSettingsFor($store['store_id']),
				'display_opts' => $this->getDisplayOpts($store['store_id']),
			);
		}

		return $hierarchy;
	}

	/**
	 * Will retrieve widget settings for supplied item in hierarchy
	 * It can be store, store + language or store+language+layout
	 *
	 * @param  Int $id
	 * @return Array
	 */
	private function getCurrentSettingsFor($store_id)
	{

		$currentSettings = $this->model_setting_setting->getSetting('module_tawkto', $store_id);

		if (isset($currentSettings['module_tawkto_widget']['widget_config'])) {
			$settings = $currentSettings['module_tawkto_widget']['widget_config'];

			return array(
				'pageId'   => $settings['page_id'],
				'widgetId' => $settings['widget_id']
			);
		} else {
			return array();
		}
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

		$currentSettings = $this->model_setting_setting->getSetting('module_tawkto', $store_id);
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

		$currentSettings = $this->model_setting_setting->getSetting('module_tawkto');
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

		$jsonOpts = array(
			'always_display' => false,
			'show_onfrontpage' => false,
			'show_oncategory' => false,
		);

		if (isset($_REQUEST['options']) && !empty($_REQUEST['options'])) {
			$options = str_ireplace('amp;', '', $_REQUEST['options']);
			$options = explode('&', $options);

			foreach ($options as $post) {
				list($key, $value) = explode('=', $post);
				switch ($key) {
					case 'always_display':
					case 'show_onfrontpage':
					case 'show_oncategory':
						$jsonOpts[$key] = $value == 1;
						break;
				}
			}
		}

		$current_settings = $this->model_setting_setting->getSetting('module_tawkto', $store_id);
		$current_settings['module_tawkto_visibility'] = json_encode($jsonOpts);
		$this->model_setting_setting->editSetting('module_tawkto', $current_settings, $store_id);

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
	 * Get display options for store
	 *
	 * @return Array
	 */
	public function getDisplayOpts($store_id = 0)
	{
		$current_settings = $this->model_setting_setting->getSetting('module_tawkto', $store_id);

		$options = array(
			'always_display' => true,
			'show_onfrontpage' => false,
			'show_oncategory' => false,
		);
		if (isset($current_settings['module_tawkto_visibility'])) {
			$options = $current_settings['module_tawkto_visibility'];
			$options = json_decode($options, true);
		}

		return $options;
	}
}
