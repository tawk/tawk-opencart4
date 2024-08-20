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
	private function getWidget()
	{
		$store_id = $this->config->get('config_store_id');
		$settings = $this->model_setting_setting->getSetting('module_tawkto', $store_id);

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
}
