<?php
/**
 * @package		JXtended.Finder
 * @copyright	Copyright (C) 2007 - 2010 JXtended, LLC. All rights reserved.
 * @license		GNU General Public License
 */

defined('_JEXEC') or die;

/**
 * HTML behavior class for Finder.
 *
 * @package		JXtended.Finder
 * @subpackage	com_finder
 * @version		1.0
 */
abstract class JHtmlFinder
{
	static function typeslist()
	{
		$lang = &JFactory::getLanguage();

		// Load the finder types.
		$db = &JFactory::getDBO();
		$db->setQuery('SELECT DISTINCT t.title AS text, t.id AS value FROM #__finder_types AS t' .
			' JOIN #__finder_links AS l ON l.type_id = t.id' .
			' ORDER BY t.title ASC');
		$rows = $db->loadObjectList();

		// Check for database errors.
		if ($db->getErrorNum()) {
			return;
		}

		// Compile the options.
		$options	= array();

		foreach ($rows as $row) {
			$key		= $lang->hasKey('COM_FINDER_TYPE_P_'.strtoupper(str_replace(' ', '_', $row->text))) ? 'COM_FINDER_TYPE_P_'.strtoupper(str_replace(' ', '_', $row->text)) : $row->text;
			$string		= JText::sprintf('COM_FINDER_ITEM_X_ONLY', JText::_($key));
			$options[]	= JHtml::_('select.option', $row->value, $string);
		}

		return $options;
	}

	static function mapslist($branches = true)
	{
		$lang = &JFactory::getLanguage();

		// Load the finder types.
		$db = &JFactory::getDBO();
		$db->setQuery('SELECT title AS text, id AS value FROM #__finder_taxonomy WHERE parent_id = 1 ORDER BY ordering, title ASC');
		$rows = $db->loadObjectList();

		// Check for database errors.
		if ($db->getErrorNum()) {
			return;
		}

		// Compile the options.
		$options	= array();
		$options[]	= JHtml::_('select.option', '1', JText::_('COM_FINDER_MAPS_BRANCHES'));

		foreach ($rows as $row) {
			$key		= $lang->hasKey('COM_FINDER_TYPE_P_'.strtoupper($row->text)) ? 'COM_FINDER_TYPE_P_'.strtoupper(str_replace(' ', '_', $row->text)) : $row->text;
			$string		= JText::sprintf('COM_FINDER_ITEM_X_ONLY', JText::_($key));
			$options[]	= JHtml::_('select.option', $row->value, $string);
		}

		return $options;
	}

	static function statelist()
	{
		$options	= array();
		$options[]	= JHtml::_('select.option', '1', JText::sprintf('COM_FINDER_ITEM_X_ONLY', JText::_('JPUBLISHED')));
		$options[]	= JHtml::_('select.option', '0', JText::sprintf('COM_FINDER_ITEM_X_ONLY', JText::_('JUNPUBLISHED')));

		return $options;
	}

	/**
	 * Method to render a given parameters form.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	string	$name	The name of the array for form elements.
	 * @param	string	$ini	An INI formatted string.
	 * @param	string	$file	The XML file to render.
	 * @return	string	A HTML rendered parameters form.
	 */
	static function params($name, $ini, $file)
	{
		jimport('joomla.html.parameter');

		// Load and render the parameters
		$path	= JPATH_COMPONENT.DS.$file;
		$params	= new JParameter($ini, $path);
		$output	= $params->render($name);

		return $output;
	}
}
