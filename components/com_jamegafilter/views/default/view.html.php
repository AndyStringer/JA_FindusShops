<?php
/**
 * ------------------------------------------------------------------------
 * JA Megafilter Component
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2016 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */
 
// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

defined('_JEXEC') or die('Restricted access');

class JaMegaFilterViewDefault extends BaseHtmlView {

	public $_layout_path = array();
	public $_css_path = array();

  function accessProtected($obj, $prop) {
    $reflection = new ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue($obj);
  }

  function getProtectedValue($obj, $name) {
    $array = (array)$obj;
    $prefix = chr(0).'*'.chr(0);
    return $array[$prefix.$name];
  }

	function display($tpl = null) {
    $app = Factory::getApplication();
    JPluginHelper::importPlugin('jamegafilter');

    $menu = $app->getMenu()->getActive();
    if (version_compare(JVERSION, '4', 'ge')){
      if (version_compare(PHP_VERSION, '5.3.0', '>=')){
        $objParams = $this->accessProtected($menu, 'params');
        $this->document->setDescription(
          $this->accessProtected($objParams, 'data')->{'menu-meta_description'}
        );
      }else{
        $objParams = $this->getProtectedValue($menu, 'params');
        $this->document->setDescription(
          $this->getProtectedValue($objParams, 'data')->{'menu-meta_description'}
        );
      }
    }else{
      $objParams = $menu->params;
      $this->document->setDescription($objParams->get('menu-meta_description'));
    }

    $config = JFactory::getConfig();
    $robots = $this->getProtectedValue($config, 'data')->{'robots'};
    // $robots = $config->get('robots');

    if ($this->getProtectedValue($objParams, 'data')->{'robots'}) {
			$this->document->setMetadata('robots', $menu->params->get('robots'));
		} else {
			$this->document->setMetadata('robots', $robots);
		}

    $this->item = $this->get('Item');

		if (empty($this->item)) {
			$app->enqueueMessage(JText::_('COM_JAMEGAFILTER_UNDEFINED_MENU_ID'), 'error');
			return;
		}

		if (empty($this->item['published'])) {
			$app->enqueueMessage(JText::_('COM_JAMEGAFILTER_ITEM_UNPUBLISHED'), 'error');
			return;
		}

		if (!JaMegaFilterHelper::getComponentStatus('com_' . $this->item['type'])) {
			$app->enqueueMessage(JText::_('COM_JAMEGAFILTER_COMPONENT_NOT_FOUND'), 'error');
			return;
		}

		if ($menu) {
			$params = JComponentHelper::getParams('com_menus');
			$mparams = $params->merge($objParams);
			$this->item['mparams'] = $mparams;
			$page_title = $mparams->get('page_title');
			if ($page_title) {
				$this->document->setTitle($page_title);
			}
		}
		
		$jatype = $this->item['type'];

		$this->_addCss($jatype);

		$this->_addLayoutPath($jatype);

		$this->jstemplate = $this->_loadJsTemplate();

		$filter_config = $this->_getFilterConfig($this->item);

		if ($jatype === 'blank') {
			parent::display($tpl);
		} else {
			$app->triggerEvent('onBeforeDisplay' . ucfirst($jatype) . 'Items', array($this->jstemplate, $filter_config, $this->item ));
		}
	}

	function _getFilterConfig($item) {	
		$config = new stdClass();
		$jinput = JFactory::getApplication()->input;
		$itp = $jinput->get('itemperrow', 3, 'INT');
		$column = $jinput->get('itempercol', 5, 'INT');
		$show_more = $jinput->get('show_more', 0, 'INT');
		$show_more = $jinput->get('show_more', 0, 'INT');
		$default_result_view = $jinput->get('default_result_view', 'grid', 'STRING');
		if ($default_result_view == 'list') $column=1;
		$itp = $itp*$column;
		$paginate = array($itp, $itp +($column*1), $itp +($column*2), $itp +($column*3), $itp +($column*4));

		$params = json_decode($item['params']);
		$fields = array();
		$sorts = array();
// altered default sort of "position" as of 2022-08-08, see https://www.joomlart.com/forums/d/41809-megafilter-layout/2
		$sorts[] = array('field' => 'name', 'title' => JText::_('JTITLE'));
		$default_sort = 'name';
		$sort_by = 'desc';
		$layout_addition = !empty($params->filterfields->layout_addition) ? $params->filterfields->layout_addition : "";
		$columns = !empty($params->filterfields->jacolumn) ? $params->filterfields->jacolumn : "";
		if (!empty($params->filterfields)) {
			$sort_by = $params->filterfields->sort_by ? $params->filterfields->sort_by : 'name';
			foreach ((array) $params->filterfields as $filters) {
				foreach ((array) $filters as $filter) {
					if (!is_object($filter)) continue;

					if (!empty($filter->sort))
						$sorts[] = array(
							'field' => $filter->field,
							'title' => $filter->title
						);
					
					if (!empty($filter->published)) {
						$fields[] = array(
							'type' => str_replace('select','dropdown',$filter->type),
							'title' => $filter->title,
							'multiple' => preg_match('/select/',$filter->type) ? 'multiple':'',
							'field' => $filter->field,
							'frontend_field' => str_replace('.value', '.frontend_value', $filter->field));
					}
					
					if ((!empty($filter->sort) && $filter->sort == 1) && (!empty($filter->sort_default) && $filter->sort_default == 1))
						$default_sort=$filter->field;
				}
			}
		}

    $langs = LanguageHelper::getKnownLanguages();
		$lang_tag = JFactory::getLanguage()->getTag();
		$lang_suffix = str_replace('-', '_', strtolower($lang_tag));
    # fetch json data
		$json = JPATH_ROOT . '/media/com_jamegafilter/' . $lang_suffix . '/' . $item['id'] . '.json';
		if (file_exists($json)) {
			$config->json = '/media/com_jamegafilter/' . $lang_suffix . '/' . $item['id'] . '.json';
		} else {
			foreach ($langs as $lang ) {
				$alter_suffix = str_replace('-', '_', strtolower($lang['tag']));
				$alter_json = JPATH_ROOT . '/media/com_jamegafilter/' . $alter_suffix . '/' . $item['id'] . '.json';
				if ($lang['tag'] != $lang_tag && file_exists($alter_json)) {
					$config->json = '/media/com_jamegafilter/' . $alter_suffix . '/' . $item['id'] . '.json';
					break;
				}
			}
		}

		$option = $jinput->get('option');
		if (!empty($option) && $option === 'com_jamegafilter') {
			$config->isComponent = true;
		}

		$filter_order = array();
		if (!empty($params->filterfields->filter_order->order)) {
			$filter_order = $params->filterfields->filter_order->order;
		}

		$custom_order = array();
		if (!empty($params->filterfields->filter_order->custom_order)) {
			$custom_order = $params->filterfields->filter_order->custom_order;
		}

		if (!empty($params->filterfields->filter_order->sort)) {
			$newOrder = [];
			foreach ($params->filterfields->filter_order->sort AS $ord) {
				foreach ($fields AS $f) {
					if ($f['field'] == $ord) {
						$newOrder[] = $f;
					}
				}
			}
			$fields = $newOrder;
		}

		$config->fullpage = $jinput->get('fullpage', 1);
		$config->autopage = $jinput->get('autopage',0);
		$config->sticky = $jinput->get('sticky',0);
		$config->paginate = $paginate;
		$config->sorts = $sorts;
		$config->sort_by = $sort_by;
		$config->layout_addition = $layout_addition;
		$config->jacolumn = $columns;
		$config->default_sort = str_replace('.value', '.frontend_value', $default_sort);
		$config->fields = $fields;
		$config->direction = $jinput->get('direction','vertical');
    // hide sticky sidebar function when config direction is Horizontal
    if ($config->direction == 'sb-horizontal'){
      $config->sticky = 0;
    }
    
		$document = JFactory::getDocument();
		$document->addScriptDeclaration('
				var jamegafilter_baseprice = "'.JText::_('COM_JAMEGAFILTER_BASE_PRICE').'";
				var jamegafilter_desc = "'.JText::_('COM_JAMEGAFILTER_DESC').'";
				var jamegafilter_thumb = "'.JText::_('COM_JAMEGAFILTER_THUMB').'";
				var jamegafilter_to = "'.JText::_('COM_JAMEGAFILTER_TO').'";
				var jamegafilter_show_more = "'.JText::_('COM_JAMEGAFILTER_SHOW_MORE').'";
				var ja_show_more = '.$show_more.';
				var jamegafilter_default_result_view = "'.$jinput->get('default_result_view', 'grid').'";
				var ja_fileter_field_order = '.json_encode($filter_order).';
				var ja_custom_ordering = ' . json_encode($custom_order) .' || {};
		');

		JText::script('COM_JAMEGAFILTER_MULTIPLE_SELECT_PLACEHOLDER');

		return $config;
	}

	function _loadJsTemplate() {
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		$template_names = array();

		$layouts_path = JPATH_SITE . '/components/com_jamegafilter/layouts';
		$filter_path = $layouts_path . '/filter';

		$base_files = JFolder::files($layouts_path);
		foreach ($base_files as $base) {
			$template_names[] = JFile::stripExt($base);
		}

		$filter_files = JFolder::files($filter_path);
		foreach ($filter_files as $filter) {
			$template_names[] = JFile::stripExt($filter);
		}

		$jstemplate = new stdClass();
		foreach ($template_names as $name) {
			$jstemplate->{ $name } = $this->_loadLayout($name);
		}

		return $jstemplate;
	}

	function _addLayoutPath($jatype) {
		$app = JFactory::getApplication();
		
		$input = $app->input;

		$jalayout = $input->get('jalayout', 'default');

		$layouts_path = JPATH_SITE . '/components/com_jamegafilter/layouts';

		$filter_path = $layouts_path . '/filter';

		$template_path = JPATH_THEMES . '/' . $app->getTemplate() . '/html/layouts/jamegafilter/' . $jatype . '/' . $jalayout;

		$filter_template_path = $template_path . '/filter';

		$plugin_path_default = JPATH_PLUGINS . '/jamegafilter/' . $jatype . '/layouts/default';

		$filter_plugin_path_default = $plugin_path_default . '/filter';
		
		$plugin_path = JPATH_PLUGINS . '/jamegafilter/' . $jatype . '/layouts/' . $jalayout;

		$filter_plugin_path = $plugin_path . '/filter';

		// add template path
		array_unshift($this->_layout_path, $filter_path);

		array_unshift($this->_layout_path, $layouts_path);
		
		array_unshift($this->_layout_path, $filter_plugin_path_default);
		
		array_unshift($this->_layout_path, $plugin_path_default);

		array_unshift($this->_layout_path, $filter_plugin_path);

		array_unshift($this->_layout_path, $plugin_path);

		array_unshift($this->_layout_path, $filter_template_path);

		array_unshift($this->_layout_path, $template_path);

		return;
	}

	function _loadLayout($name) {
		// Clear prior output
		$this->_output = null;

		// Load the template script
		jimport('joomla.filesystem.path');

		$filename = preg_replace('/[^A-Z0-9_\.-]/i', '', $name);

		$file = JPath::find($this->_layout_path, $filename . '.php');

		if ($file != false) {

			ob_start();

			include $file;

			$this->_output = ob_get_contents();
			ob_end_clean();

			return $this->_output;
		} else {
			throw new Exception(JText::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $name . '.php'), 500);
		}
	}

	function _addCss($jatype) {
		$app = JFactory::getApplication();
		$doc = JFactory::getDocument();

		if (file_exists(JPATH_SITE . '/components/com_jamegafilter/assets/css/style.css')) {
			$doc->addStyleSheet(JURI::root(true) . '/components/com_jamegafilter/assets/css/style.css');
		}

		if (file_exists(JPATH_PLUGINS . '/jamegafilter/' . $jatype . '/assets/css/style.css')) {
			$doc->addStyleSheet(JURI::root(true) . '/plugins/jamegafilter/' . $jatype . '/assets/css/style.css');
		}
		
		if (file_exists(JPATH_THEMES . '/' . $app->getTemplate() . '/css/jamegafilter.css')) {
			$doc->addStyleSheet('templates/' . $app->getTemplate()  . '/css/jamegafilter.css');
		}
	}

}
