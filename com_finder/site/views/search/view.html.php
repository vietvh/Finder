<?php
/**
 * @version		$Id: view.html.php 1056 2010-09-21 19:00:13Z robs $
 * @package		JXtended.Finder
 * @subpackage	com_finder
 * @copyright	Copyright (C) 2007 - 2010 JXtended, LLC. All rights reserved.
 * @license		GNU General Public License
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * Search HTML view class for the Finder package.
 *
 * @package		JXtended.Finder
 * @subpackage	com_finder
 */
class FinderViewSearch extends JView
{
	/**
	 * Method to display the view.
	 *
	 * @param	string	A template file to load.
	 * @return	mixed	JError object on failure, void on success.
	 */
	public function display($tpl = null)
	{
		// Get view data.
		$state		= $this->get('State');
		$params		= $state->get('params');
		$query		= $this->get('Query');			JDEBUG ? $GLOBALS['_PROFILER']->mark('afterFinderQuery') : null;
		$results	= $this->get('Results');		JDEBUG ? $GLOBALS['_PROFILER']->mark('afterFinderResults') : null;
		$total		= $this->get('Total');			JDEBUG ? $GLOBALS['_PROFILER']->mark('afterFinderTotal') : null;
		$pagination	= $this->get('Pagination');		JDEBUG ? $GLOBALS['_PROFILER']->mark('afterFinderPagination') : null;

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		// Configure the pathway.
		if (!empty($query->input)) {
			JFactory::getApplication()->getPathWay()->addItem($this->escape($query->input));
		}

		// Push out the view data.
		$this->assignRef('state',		$state);
		$this->assignRef('params',		$params);
		$this->assignRef('query',		$query);
		$this->assignRef('results',		$results);
		$this->assignRef('total',		$total);
		$this->assignRef('pagination',	$pagination);

		// Check for a double quote in the query string.
		if (strpos($this->query->input, '"'))
		{
			// Get the application router.
			$router =& JFactory::getApplication()->getRouter();

			// Fix the q variable in the URL.
			if ($router->getVar('q') !== $this->query->input) {
				$router->setVar('q', $this->query->input);
			}
		}

		// Push out the query data.
		JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
		$this->assign('suggested',	JHtml::_('query.suggested', $query));
		$this->assign('explained',	JHtml::_('query.explained', $query));

		// Set the document title.
		$this->document->setTitle($params->get('page_title'));

		// Configure the document meta-description.
		if (!empty($this->explained)) {
			$explained = $this->escape(html_entity_decode(strip_tags($this->explained), ENT_QUOTES, 'UTF-8'));
			$this->document->setDescription($explained);
		}

		// Configure the document meta-keywords.
		if (!empty($query->highlight)) {
			$this->document->setMetadata('keywords', implode(', ', $query->highlight));
		}

		// Add feed link to the document head.
		if ($params->get('show_feed', 0))
		{
			// Add the RSS link.
			$props = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
			$route = JRoute::_($query->toURI().'&format=feed&type=rss');
			$this->document->addHeadLink($route, 'alternate', 'rel', $props);

			// Add the ATOM link.
			$props = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
			$route = JRoute::_($query->toURI().'&format=feed&type=atom');
			$this->document->addHeadLink($route, 'alternate', 'rel', $props);
		}

		JDEBUG ? $GLOBALS['_PROFILER']->mark('beforeFinderLayout') : null;

		parent::display($tpl);

		JDEBUG ? $GLOBALS['_PROFILER']->mark('afterFinderLayout') : null;
	}

	/**
	 * Method to get hidden input fields for a get form so that control variables
	 * are not lost upon form submission
	 *
	 * @return	string		A string of hidden input form fields
	 */
	protected function _getGetFields()
	{
		$fields = null;

		// Get the URI.
		$uri = JURI::getInstance(JRoute::_($this->query->toURI()));
		$uri->delVar('q');
		$uri->delVar('o');
		$uri->delVar('t');
		$uri->delVar('d1');
		$uri->delVar('d2');
		$uri->delVar('w1');
		$uri->delVar('w2');

		// Create hidden input elements for each part of the URI.
		foreach ($uri->getQuery(true) as $n => $v) {
			if (is_scalar($v)) {
				$fields .= '<input type="hidden" name="'.$n.'" value="'.$v.'" />';
			}
		}

		return $fields;
	}

	/**
	 * Method to get the layout file for a search result object.
	 *
	 * @param	string		The layout file to check.
	 * @return	string		The layout file to use.
	 */
	protected function _getLayoutFile($layout = null)
	{
		// Create and sanitize the file name.
		$file = $this->_layout.'_'.preg_replace('/[^A-Z0-9_\.-]/i', '', $layout);

		// Check if the file exists.
		jimport('joomla.filesystem.path');
		$filetofind	= $this->_createFileName('template', array('name' => $file));
		$exists = JPath::find($this->_path['template'], $filetofind);

		return ($exists ? $layout : 'result');
	}
}
