<?php
/**
 * @package     Pagekit Extension
 * @subpackage  Content.emailcloak
 *
 * @copyright   Copyright (C) 2017 Friendly-it, Inc. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Friendlyit\Emailcloak\Plugin;

use Pagekit\Content\Event\ContentEvent;
use Pagekit\Event\EventSubscriberInterface;
use Friendlyit\Emailcloak\Helpers\email\PHtmlEmail;
use Pagekit\Application as App; 

class EmailCloakPlugin implements EventSubscriberInterface
{
	/**
	* EmailcloakExtension parameters
	*
    * @var integer
    */
	
    protected $params;
	
	/**
    * Constructor.
    * 
    */
    public function __construct()
    {
        $this->params = $this->getIfSet(App::module('friendlyit/emailcloak')->config('mode'));
    }
	
	
    /**
     * Content plugins callback.
     *
     * @param ContentEvent $event
     */
    public function onContentPlugins(ContentEvent $event)
    {
		$params 	= $this->params;
		$content 	= $event->getContent();
		$event->setContent($this->_cloak($content, $params));
    }

	/**
	* Generate a search pattern based on link and text.
	*
	* @param   string  $link  The target of an email link.
	* @param   string  $text  The text enclosed by the link.
	*
	* @return  string	A regular expression that matches a link containing the parameters.
	*/
	protected function _getPattern ($link, $text)
	{
		$pattern = '~(?:<a ([^>]*)href\s*=\s*"mailto:' . $link . '"([^>]*))>' . $text . '</a>~i';

		return $pattern;
	}

	/**
	* Adds an attributes to the js cloaked email.
	*
	* @param   string  $jsEmail  Js cloaked email.
	* @param   string  $before   Attributes before email.
	* @param   string  $after    Attributes after email.
	*
	* @return string Js cloaked email with attributes.
	*/
	protected function _addAttributesToEmail($jsEmail, $before, $after)
	{
		if ($before !== '')
		{
			$before = str_replace("'", "\'", $before);
			$jsEmail = str_replace(".innerHTML += '<a '", ".innerHTML += '<a {$before}'", $jsEmail);
		}

		if ($after !== '')
		{
			$after = str_replace("'", "\'", $after);
			$jsEmail = str_replace("'\'>'", "'\'{$after}>'", $jsEmail);
		}

		return $jsEmail;
	}
	
	/**
	* Cloak all emails in text from spambots via Javascript.
	*
	* @param   string  &$text    The string to be cloaked.
	* @param   mixed   &$params  Additional parameters. Parameter "mode" (integer, default 1)
	*                             replaces addresses with "mailto:" links if nonzero.
	*
	* @return  boolean  True on success.
	*/
	protected function _cloak(&$text, &$params)
	{
		/*
		 * Check for presence of {emailcloak=off} which is explicits disables this
		 * bot for the item.
		 */
		if ($this->strpos($text, '{emailcloak=off}') !== false) //utf8_strpos
		{
			$text = str_ireplace('{emailcloak=off}', '', $text); //utf8_ireplace

			return $text;
		}
		// Simple performance check to determine whether bot should process further.
		if (strpos($text, '@') === false)
		{
			return $text;
		}
		
		
		//$mode = $this->params->def('mode', 1);
		$mode = (bool) $params;
		// Example: any@example.org
		$searchEmail = '([\w\.\'\-\+]+\@(?:[a-z0-9\.\-]+\.)+(?:[a-zA-Z0-9\-]{2,10}))';

		// Example: any@example.org?subject=anyText
		$searchEmailLink = $searchEmail . '([?&][\x20-\x7f][^"<>]+)';

		// Any Text
		$searchText = '((?:[\x20-\x7f]|[\xA1-\xFF]|[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF4][\x80-\xBF]{3})[^<>]+)';

		// Any Image link
		$searchImage = '(<img[^>]+>)';

		// Any Class link
		//$searchClass = '(<class[^>]+>)';
		
		// Any Text with <span or <strong
		$searchTextSpan = '(<span[^>]+>|<span>|<strong>|<strong><span[^>]+>|<strong><span>)' . $searchText . '(</span>|</strong>|</span></strong>)';

		// Any address with <span or <strong
		$searchEmailSpan = '(<span[^>]+>|<span>|<strong>|<strong><span[^>]+>|<strong><span>)' . $searchEmail . '(</span>|</strong>|</span></strong>)';

		/*
		 * Search and fix derivatives of link code <a href="http://mce_host/ourdirectory/email@example.org"
		 * >email@example.org</a>. This happens when inserting an email in TinyMCE, cancelling its suggestion to add
		 * the mailto: prefix...
		 */
		$pattern = $this->_getPattern($searchEmail, $searchEmail);
		$pattern = str_replace('"mailto:', '"http://mce_host([\x20-\x7f][^<>]+/)', $pattern);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[3][0];
			$mailText = $regs[5][0];

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search and fix derivatives of link code <a href="http://mce_host/ourdirectory/email@example.org"
		 * >anytext</a>. This happens when inserting an email in TinyMCE, cancelling its suggestion to add
		 * the mailto: prefix...
		 */
		$pattern = $this->_getPattern($searchEmail, $searchText);
		$pattern = str_replace('"mailto:', '"http://mce_host([\x20-\x7f][^<>]+/)', $pattern);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[3][0];
			$mailText = $regs[5][0];

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org"
		 * >email@example.org</a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchEmail);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = $regs[4][0];

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@amail.com"
		 * ><anyspan >email@amail.com</anyspan></a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchEmailSpan);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = $regs[4][0] . $regs[5][0] . $regs[6][0];

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@amail.com">
		 * <anyspan >anytext</anyspan></a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchTextSpan);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = $regs[4][0] . addslashes($regs[5][0]) . $regs[6][0];

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org">
		 * anytext</a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchText);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = addslashes($regs[4][0]);

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org">
		 * <img anything></a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchImage);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = $regs[4][0];

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org">
		 * <img anything>email@example.org</a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchImage . $searchEmail);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = $regs[4][0] . $regs[5][0];

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org">
		 * <img anything>any text</a>
		 */
		$pattern = $this->_getPattern($searchEmail, $searchImage . $searchText);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = $regs[4][0] . addslashes($regs[5][0]);

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org?
		 * subject=Text">email@example.org</a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchEmail);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0] . $regs[3][0];
			$mailText = $regs[5][0];

			// Needed for handling of Body parameter
			$mail = str_replace('&amp;', '&', $mail);

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org?
		 * subject=Text">anytext</a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchText);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0] . $regs[3][0];
			$mailText = addslashes($regs[5][0]);

			// Needed for handling of Body parameter
			$mail = str_replace('&amp;', '&', $mail);

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@amail.com?subject= Text"
		 * ><anyspan >email@amail.com</anyspan></a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchEmailSpan);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0] . $regs[3][0];
			$mailText = $regs[5][0] . $regs[6][0] . $regs[7][0];

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak( $mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@amail.com?subject= Text">
		 * <anyspan >anytext</anyspan></a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchTextSpan);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0] . $regs[3][0];
			$mailText = $regs[5][0] . addslashes($regs[6][0]) . $regs[7][0];

			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code
		 * <a href="mailto:email@amail.com?subject=Text"><img anything></a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchImage);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[1][0] . $regs[2][0] . $regs[3][0];
			$mailText = $regs[5][0];

			// Needed for handling of Body parameter
			$mail = str_replace('&amp;', '&', $mail);

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code
		 * <a href="mailto:email@amail.com?subject=Text"><img anything>email@amail.com</a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchImage . $searchEmail);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[1][0] . $regs[2][0] . $regs[3][0];
			$mailText = $regs[4][0] . $regs[5][0] . $regs[6][0];

			// Needed for handling of Body parameter
			$mail = str_replace('&amp;', '&', $mail);

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code
		 * <a href="mailto:email@amail.com?subject=Text"><img anything>any text</a>
		 */
		$pattern = $this->_getPattern($searchEmailLink, $searchImage . $searchText);

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[1][0] . $regs[2][0] . $regs[3][0];
			$mailText = $regs[4][0] . $regs[5][0] . addslashes($regs[6][0]);

			// Needed for handling of Body parameter
			$mail = str_replace('&amp;', '&', $mail);

			// Check to see if mail text is different from mail addy
			$replacement = PHtmlEmail::cloak($mail, $mode, $mailText, 0);

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = html_entity_decode($this->_addAttributesToEmail($replacement, $regs[1][0], $regs[4][0]));

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}

		/*
		 * Search for derivatives of link code <a href="mailto:email@example.org">
		 * Nonetext</a>
		 */
		$pattern = $this->_getPattern($searchEmail, '');

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[2][0];
			$mailText = '';//addslashes($regs[4][0]);

			$replacement = PHtmlEmail::cloak($mail, $mode, ' ', 0);
			

			// Ensure that attributes is not stripped out by email cloaking
			$replacement = $this->_addAttributesToEmail($replacement, $regs[1][0], $regs[3][0]);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[0][1], strlen($regs[0][0]));
		}
		
		
		/*
		 * Search for plain text email addresses, such as email@example.org but not within HTML tags:
		 * <img src="..." title="email@example.org"> or <input type="text" placeholder="email@example.org">
		 * The negative lookahead '(?![^<]*>)' is used to exclude this kind of occurrences
		 */
		$pattern = '~(?![^<>]*>)' . $searchEmail . '~i';

		while (preg_match($pattern, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = $regs[1][0];
			$replacement = PHtmlEmail::cloak($mail, $mode);

			// Replace the found address with the js cloaked email
			$text = substr_replace($text, $replacement, $regs[1][1], strlen($mail));
		}

		return $text;
	}
	// Local Helpers
	
	/**
	* UTF-8 aware alternative to strpos()
	*
	* Find position of first occurrence of a string.
	*
	* @param   string   $str     String being examined
	* @param   string   $search  String being searched for
	* @param   integer  $offset  Optional, specifies the position from which the search should be performed
	*
	* @return  mixed  Number of characters before the first match or FALSE on failure
	*
	* @see     http://www.php.net/strpos
	* @since   1.3.0
	*/
	public static function strpos($str, $search, $offset = false)
	{
		if ($offset === false)
		{
			return mb_strpos($str, $search);
		}

		return mb_strpos($str, $search, $offset);
	}
	/*
	* Return var if set, or null .
	*/
	public function getIfSet(& $var) {
    	if (isset($var)) {
    		return $var;
    	}
    	return null;
    }
	
    /**
     * {@inheritdoc}
     */
    public function subscribe()
    {
        return [
            'content.plugins' => ['onContentPlugins', 15]
        ];
    }
}
