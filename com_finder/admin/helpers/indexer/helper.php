<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

// Register dependent classes.
JLoader::register('FinderIndexerStemmer', dirname(__FILE__) . '/stemmer.php');
JLoader::register('FinderIndexerToken', dirname(__FILE__) . '/token.php');

/**
 * Helper class for the Finder indexer package.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 * @since       2.5
 */
class FinderIndexerHelper
{
	/**
	 * The token stemmer object. The stemmer is set by whatever class
	 * wishes to use it but it must be an instance of FinderIndexerStemmer.
	 *
	 * @var		FinderIndexerStemmer
	 * @since	2.5
	 */
	public static $stemmer;

	/**
	 * Method to parse input into plain text.
	 *
	 * @param   string  $input   The raw input.
	 * @param   string  $format  The format of the input. [optional]
	 *
	 * @return  string  The parsed input.
	 *
	 * @since   2.5
	 * @throws  Exception on invalid parser.
	 */
	public static function parse($input, $format = 'html')
	{
		// Get a parser for the specified format and parse the input.
		return FinderIndexerParser::getInstance($format)->parse($input);
	}

	/**
	 * Method to tokenize a text string.
	 *
	 * @param   string   $input   The input to tokenize.
	 * @param   string   $lang    The language of the input.
	 * @param   boolean  $phrase  Flag to indicate whether input could be a phrase. [optional]
	 *
	 * @return  array  An array of FinderIndexerToken objects.
	 *
	 * @since   2.5
	 */
	public static function tokenize($input, $lang, $phrase = false)
	{
		static $cache;
		$store = JString::strlen($input) < 128 ? md5($input . '::' . $lang . '::' . $phrase) : null;

		// Check if the string has been tokenized already.
		if ($store && isset($cache[$store]))
		{
			return $cache[$store];
		}

		$tokens = array();
		$terms = array();
		$quotes = html_entity_decode('&#8216;&#8217;&#39;', ENT_QUOTES, 'UTF-8');

		// Get the simple language key.
		$lang = FinderIndexerHelper::getPrimaryLanguage($lang);

		/*
		 * Parsing the string input into terms is a multi-step process.
		 *
		 * Regexes:
		 *	1. Remove everything except letters, numbers, quotes, apostrophe, plus, dash, period, and comma.
		 *	2. Remove plus, dash, period, and comma characters located before letter characters.
		 *  3. Remove plus, dash, period, and comma characters located after other characters.
		 *  4. Remove plus, period, and comma characters enclosed in alphabetical characters. Ungreedy.
		 *  5. Remove orphaned apostrophe, plus, dash, period, and comma characters.
		 *  6. Remove orphaned quote characters.
		 *  7. Replace the assorted single quotation marks with the ASCII standard single quotation.
		 *  8. Remove multiple space characters and replaces with a single space.
		 */
		$input = JString::strtolower($input);
		$input = preg_replace('#[^\pL\pM\pN\p{Pi}\p{Pf}\'+-.,]+#mui', ' ', $input);
		$input = preg_replace('#(^|\s)[+-.,]+([\pL\pM]+)#mui', ' $1', $input);
		$input = preg_replace('#([\pL\pM\pN]+)[+-.,]+(\s|$)#mui', '$1 ', $input);
		$input = preg_replace('#([\pL\pM]+)[+.,]+([\pL\pM]+)#muiU', '$1 $2', $input); // Ungreedy
		$input = preg_replace('#(^|\s)[\'+-.,]+(\s|$)#mui', ' ', $input);
		$input = preg_replace('#(^|\s)[\p{Pi}\p{Pf}]+(\s|$)#mui', ' ', $input);
		$input = preg_replace('#[' . $quotes . ']+#mui', '\'', $input);
		$input = preg_replace('#\s+#mui', ' ', $input);
		$input = JString::trim($input);

		// Explode the normalized string to get the terms.
		$terms = explode(' ', $input);

		/*
		 * If we have Unicode support and are dealing with Chinese text, Chinese
		 * has to be handled specially because there are not necessarily any spaces
		 * between the "words". So, we have to test if the words belong to the Chinese
		 * character set and if so, explode them into single glyphs or "words".
		 */
		if ($lang === 'zh')
		{
			// Iterate through the terms and test if they contain Chinese.
			for ($i = 0, $n = count($terms); $i < $n; $i++)
			{
				$charMatches = array();
				$charCount = preg_match_all('#[\p{Han}]#mui', $terms[$i], $charMatches);

				// Split apart any groups of Chinese characters.
				for ($j = 0; $j < $charCount; $j++)
				{
					$tSplit = JString::str_ireplace($charMatches[0][$j], '', $terms[$i], false);
					if (!empty($tSplit))
					{
						$terms[$i] = $tSplit;
					}
					else
					{
						unset($terms[$i]);
					}

					$terms[] = $charMatches[0][$j];
				}
			}

			// Reset array keys.
			$terms = array_values($terms);
		}

		/*
		 * If we have to handle the input as a phrase, that means we don't
		 * tokenize the individual terms and we do not create the two and three
		 * term combinations. The phrase must contain more than one word!
		 */
		if ($phrase === true && count($terms) > 1)
		{
			// Create tokens from the phrase.
			$tokens[] = new FinderIndexerToken($terms, $lang);
		}
		else
		{
			// Create tokens from the terms.
			for ($i = 0, $n = count($terms); $i < $n; $i++)
			{
				$tokens[] = new FinderIndexerToken($terms[$i], $lang);
			}

			// Create two and three word phrase tokens from the individual words.
			for ($i = 0, $n = count($tokens); $i < $n; $i++)
			{
				// Setup the phrase positions.
				$i2 = $i + 1;
				$i3 = $i + 2;

				// Create the two word phrase.
				if ($i2 < $n && isset($tokens[$i2]))
				{
					// Tokenize the two word phrase.
					$token = new FinderIndexerToken(array($tokens[$i]->term, $tokens[$i2]->term), $lang, $lang === 'zh' ? '' : ' ');
					$token->derived = true;

					// Add the token to the stack.
					$tokens[] = $token;
				}

				// Create the three word phrase.
				if ($i3 < $n && isset($tokens[$i3]))
				{
					// Tokenize the three word phrase.
					$token = new FinderIndexerToken(array($tokens[$i]->term, $tokens[$i2]->term, $tokens[$i3]->term), $lang, $lang === 'zh' ? '' : ' ');
					$token->derived = true;

					// Add the token to the stack.
					$tokens[] = $token;
				}
			}
		}

		if ($store)
		{
			$cache[$store] = count($tokens) > 1 ? $tokens : array_shift($tokens);
			return $cache[$store];
		}
		else
		{
			return count($tokens) > 1 ? $tokens : array_shift($tokens);
		}
	}

	/**
	 * Method to get the base word of a token. This method uses the public
	 * {@link FinderIndexerHelper::$stemmer} object if it is set. If no stemmer is set,
	 * the original token is returned.
	 *
	 * @param   string  $token  The token to stem.
	 * @param   string  $lang   The language of the token.
	 *
	 * @return  string  The root token.
	 *
	 * @since   2.5
	 */
	public static function stem($token, $lang)
	{
		// Trim apostrophes at either end of the token.
		$token = JString::trim($token, '\'');

		// Trim everything after any apostrophe in the token.
		if (($pos = JString::strpos($token, '\'')) !== false)
		{
			$token = JString::substr($token, 0, $pos);
		}

		// Stem the token if we have a valid stemmer to use.
		if (self::$stemmer instanceof FinderIndexerStemmer)
		{
			return self::$stemmer->stem($token, $lang);
		}
		else
		{
			return $token;
		}
	}

	/**
	 * Method to add a content type to the database.
	 *
	 * @param   string  $title  The type of content. For example: PDF
	 * @param   string  $mime   The mime type of the content. For example: PDF [optional]
	 *
	 * @return  integer  The id of the content type.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function addContentType($title, $mime = null)
	{
		static $types;

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Check if the types are loaded.
		if (empty($types))
		{
			// Build the query to get the types.
			$query->select('*');
			$query->from($db->quoteName('#__finder_types'));

			// Get the types.
			$db->setQuery($query);
			$types = $db->loadObjectList('title');

			// Check for a database error.
			if ($db->getErrorNum())
			{
				// Throw database error exception.
				throw new Exception($db->getErrorMsg(), 500);
			}
		}

		// Check if the type already exists.
		if (isset($types[$title]))
		{
			return (int) $types[$title]->id;
		}

		// Add the type.
		$query->clear();
		$query->insert($db->quoteName('#__finder_types') . ' (' . $db->quoteName('title') . ', ' . $db->quoteName('mime') . ')');
		$query->values($db->quote($title) . ', ' . $db->quote($mime));
		$db->setQuery($query);
		$db->query();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			// Throw database error exception.
			throw new Exception($db->getErrorMsg(), 500);
		}

		// Return the new id.
		return (int) $db->insertid();
	}

	/**
	 * Method to check if a token is common in a language.
	 *
	 * @param   string  $token  The token to test.
	 * @param   string  $lang   The language to reference.
	 *
	 * @return  boolean  True if common, false otherwise.
	 *
	 * @since   2.5
	 */
	public static function isCommon($token, $lang)
	{
		static $data;

		// Load the common tokens for the language if necessary.
		if (!isset($data[$lang]))
		{
			$data[$lang] = FinderIndexerHelper::getCommonWords($lang);
		}

		// Check if the token is in the common array.
		if (in_array($token, $data[$lang]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get an array of common terms for a language.
	 *
	 * @param   string  $lang  The language to use.
	 *
	 * @return  array  Array of common terms.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function getCommonWords($lang)
	{
		$db = JFactory::getDBO();

		// Create the query to load all the common terms for the language.
		$query = $db->getQuery(true);
		$query->select($db->quoteName('term'));
		$query->from($db->quoteName('#__finder_terms_common'));
		$query->where($db->quoteName('language') . ' = ' . $db->quote($lang));

		// Load all of the common terms for the language.
		$db->setQuery($query);
		$results = $db->loadColumn();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			// Throw database error exception.
			throw new Exception($db->getErrorMsg(), 500);
		}

		return $results;
	}

	/**
	 * Method to get the default language for the site.
	 *
	 * @return  string  The default language string.
	 *
	 * @since   2.5
	 */
	public static function getDefaultLanguage()
	{
		static $lang;

		// Get the default language.
		if (empty($lang))
		{
			$lang = JComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		}

		return $lang;
	}

	/**
	 * Method to parse a language/locale key and return a simple language string.
	 *
	 * @param   string  $lang  The language/locale key. For example: en-GB
	 *
	 * @return  string  The simple language string. For example: en
	 *
	 * @since   2.5
	 */
	public static function getPrimaryLanguage($lang)
	{
		static $data;

		// Only parse the identifier if necessary.
		if (!isset($data[$lang]))
		{
			if (is_callable(array('Locale', 'getPrimaryLanguage')))
			{
				// Get the language key using the Locale package.
				$data[$lang] = Locale::getPrimaryLanguage($lang);
			}
			else
			{
				// Get the language key using string position.
				$data[$lang] = JString::substr($lang, 0, JString::strpos($lang, '-'));
			}
		}

		return $data[$lang];
	}

	/**
	 * Method to get the path (SEF route) for a content item.
	 *
	 * @param   string  $url  The non-SEF route to the content item.
	 *
	 * @return  string  The path for the content item.
	 *
	 * @since   2.5
	 */
	public static function getContentPath($url)
	{
		static $router;

		// Only get the router once.
		if (!($router instanceof JRouter))
		{
			jimport('joomla.application.router');
			include_once JPATH_SITE . '/includes/application.php';

			// Get and configure the site router.
			$config = JFactory::getConfig();
			$router = JRouter::getInstance('site');
			$router->setMode($config->get('sef', 1));
		}

		// Build the relative route.
		$uri = $router->build($url);
		$route = $uri->toString(array('path', 'query', 'fragment'));
		$route = str_replace(JURI::base(true) . '/', '', $route);

		return $route;
	}

	/**
	 * Method to get extra data for a content before being indexed. This is how
	 * we add Comments, Tags, Labels, etc. that should be available to Finder.
	 *
	 * @param   FinderIndexerResult  &$item  The item to index as an FinderIndexerResult object.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public static function getContentExtras(FinderIndexerResult &$item)
	{
		// Get the event dispatcher.
		$dispatcher = JDispatcher::getInstance();

		// Load the finder plugin group.
		JPluginHelper::importPlugin('finder');

		try
		{
			// Trigger the event.
			$results = $dispatcher->trigger('onPrepareFinderContent', array(&$item));

			// Check the returned results. This is for plugins that don't throw
			// exceptions when they encounter serious errors.
			if (in_array(false, $results))
			{
				throw new Exception($dispatcher->getError(), 500);
			}
		}
		catch (Exception $e)
		{
			// Handle a caught exception.
			throw $e;
		}

		return true;
	}

	/**
	 * Method to process content text using the onContentPrepare event trigger.
	 *
	 * @param   string     $text    The content to process.
	 * @param   JRegistry  $params  The parameters object. [optional]
	 *
	 * @return  string  The processed content.
	 *
	 * @since   2.5
	 */
	public static function prepareContent($text, $params = null)
	{
		// Get the dispatcher.
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		// Instantiate the parameter object if necessary.
		if (!($params instanceof JRegistry))
		{
			$registry = new JRegistry;
			$registry->loadString($params);
			$params = $registry;
		}

		// Create a mock content object.
		$content = JTable::getInstance('Content');
		$content->text = $text;

		// Fire the onContentPrepare event.
		$dispatcher->trigger('onContentPrepare', array('com_finder.indexer', &$content, &$params, 0));

		return $content->text;
	}
}
