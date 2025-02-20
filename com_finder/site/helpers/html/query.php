<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Query HTML behavior class for Finder.
 *
 * @package     Joomla.Site
 * @subpackage  com_finder
 * @since       2.5
 */
class JHtmlQuery
{
	/**
	 * Method to get the explained (human-readable) search query.
	 *
	 * @param   FinderIndexerQuery  $query  A FinderIndexerQuery object to explain.
	 *
	 * @return  mixed  String if there is data to explain, null otherwise.
	 *
	 * @since   2.5
	 */
	public static function explained(FinderIndexerQuery $query)
	{
		$parts = array();

		// Process the required tokens.
		foreach ($query->included as $token)
		{
			if ($token->required && (!isset($token->derived) || $token->derived == false))
			{
				$parts[] = '<span class="query-required">' . JText::sprintf('COM_FINDER_QUERY_TOKEN_REQUIRED', $token->term) . '</span>';
			}
		}

		// Process the optional tokens.
		foreach ($query->included as $token)
		{
			if (!$token->required && (!isset($token->derived) || $token->derived == false))
			{
				$parts[] = '<span class="query-optional">' . JText::sprintf('COM_FINDER_QUERY_TOKEN_OPTIONAL', $token->term) . '</span>';
			}
		}

		// Process the excluded tokens.
		foreach ($query->excluded as $token)
		{
			if (!isset($token->derived) || $token->derived == false)
			{
				$parts[] = '<span class="query-excluded">' . JText::sprintf('COM_FINDER_QUERY_TOKEN_EXCLUDED', $token->term) . '</span>';
			}
		}

		// Process the start date.
		if ($query->date1)
		{
			$date = JFactory::getDate($query->date1)->format('%B %e, %Y');
			$parts[] = '<span class="query-start-date">' . JText::sprintf('COM_FINDER_QUERY_START_DATE', $query->when1, $date) . '</span>';
		}

		// Process the end date.
		if ($query->date2)
		{
			$date = JFactory::getDate($query->date2)->format('%B %e, %Y');
			$parts[] = '<span class="query-end-date">' . JText::sprintf('COM_FINDER_QUERY_END_DATE', $query->when2, $date) . '</span>';
		}

		// Process the taxonomy filters.
		if (!empty($query->filters))
		{
			// Get the filters in the request.
			$t = JFactory::getApplication()->input->request->get('t', array(), 'array');

			// Process the taxonomy branches.
			foreach ($query->filters as $branch => $nodes)
			{
				// Process the taxonomy nodes.
				foreach ($nodes as $title => $id)
				{
					// Don't include the node if it is not in the request.
					if (!in_array($id, $t))
					{
						continue;
					}

					// Add the node to the explanation.
					$bv = JString::strtolower($branch);
					$nv = JString::strtolower($title);
					$parts[] = '<span class="query-taxonomy">' . JText::sprintf('COM_FINDER_QUERY_TAXONOMY_NODE', $title, $branch) . '</span>';
				}
			}
		}

		// Build the interpreted query.
		return count($parts) ? JText::sprintf('COM_FINDER_QUERY_TOKEN_INTERPRETED', implode(JText::_('COM_FINDER_QUERY_TOKEN_GLUE'), $parts)) : null;
	}

	/**
	 * Method to get the suggested search query.
	 *
	 * @param   FinderIndexerQuery  $query  A FinderIndexerQuery object.
	 *
	 * @return  mixed  String if there is a suggestion, false otherwise.
	 *
	 * @since   2.5
	 */
	public static function suggested(FinderIndexerQuery $query)
	{
		$suggested = false;

		// Check if the query input is empty.
		if (empty($query->input))
		{
			return $suggested;
		}

		// Check if there were any ignored or included keywords.
		if (count($query->ignored) || count($query->included))
		{
			$suggested = $query->input;

			// Replace the ignored keyword suggestions.
			foreach (array_reverse($query->ignored) as $token)
			{
				if (isset($token->suggestion))
				{
					$suggested = str_replace($token->term, $token->suggestion, $suggested);
				}
			}

			// Replace the included keyword suggestions.
			foreach (array_reverse($query->included) as $token)
			{
				if (isset($token->suggestion))
				{
					$suggested = str_replace($token->term, $token->suggestion, $suggested);
				}
			}

			// Check if we made any changes.
			if ($suggested == $query->input)
			{
				$suggested = false;
			}
		}

		return $suggested;
	}
}
