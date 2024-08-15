<?php

/**
 * @package tawk.to Integration
 * @author tawk.to
 * @copyright (C) 2024 tawk.to
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Opencart\Catalog\Controller\Extension\Tawkto\Module;

use \Opencart\System\Engine\Controller;

class Tawkto extends Controller
{
	/**
	 * Entry point
	 */
	public function index()
	{
		$this->load->model('setting/setting');
		// get current plugin version in db
		$tawk_settings = $this->model_setting_setting->getSetting('module_tawkto'); // this gets the default store settings since that's where the version is stored.
		$plugin_version_in_db = '';
		if (isset($tawk_settings['module_tawkto_version'])) {
			$plugin_version_in_db = $tawk_settings['module_tawkto_version'];
		}

		$widget = $this->getWidget($plugin_version_in_db);

		$data = array();
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
	private function getWidget($plugin_version_in_db)
	{
		$store_id = $this->config->get('config_store_id');
		$settings = $this->model_setting_setting->getSetting('module_tawkto', $store_id);

		$visibility = false;
		if (isset($settings['module_tawkto_visibility'])) {
			$visibility = $settings['module_tawkto_visibility'];
		}

		$widget = null;
		if (!isset($settings['module_tawkto_widget'])) {
			return null;
		}
		$settings = $settings['module_tawkto_widget'];

		if (isset($settings['widget_config'])) {
			$widget = $settings['widget_config'];
		}

		// get visibility options
		if ($visibility) {
			$visibility = json_decode($visibility);

			// prepare visibility
			$request_uri = trim($_SERVER["REQUEST_URI"]);
			if (stripos($request_uri, '/') === 0) {
				$request_uri = substr($request_uri, 1);
			}
			$current_page = $this->config->get('config_url') . $request_uri;

			if (false == $visibility->always_display) {

				/**
				 * NOTE: commented lines because we haven't implement pattern matching
				 *
				 * Undefined property: stdClass::$show_oncustom
				 */

				// // custom pages
				// $show_pages = json_decode($visibility->show_oncustom);
				$show = false;
				// $current_page = (string) trim($current_page);

				// if ($this->matchPatterns($current_page, $show_pages, $plugin_version_in_db)) {
				// 	$show = true;
				// }

				// category page
				if (isset($this->request->get['route']) && stripos($this->request->get['route'], 'category') !== false) {
					if (false != $visibility->show_oncategory) {
						$show = true;
					}
				}

				// home
				$is_home = false;
				if (
					!isset($this->request->get['route'])
					|| (isset($this->request->get['route']) && $this->request->get['route'] == 'common/home')
				) {
					$is_home = true;
				}

				if ($is_home) {
					if (false != $visibility->show_onfrontpage) {
						$show = true;
					}
				}

				if (!$show) {
					return;
				}
			// } else {
			// 	$show = true;
			// 	$hide_pages = json_decode($visibility->hide_oncustom);
			// 	$current_page = (string) trim($current_page);

			// 	if ($this->matchPatterns($current_page, $hide_pages, $plugin_version_in_db)) {
			// 		$show = false;
			// 	}

			// 	if (!$show) {
			// 		return;
			// 	}
			}
		}

		return $widget;
	}

	/**
	 * Pattern matching
	 * TODO: add UrlPatternMatcher
	 *
	 * @return boolean
	 */
	private function matchPatterns($current_page, $pages, $plugin_version)
	{
		return true;
	}
}
