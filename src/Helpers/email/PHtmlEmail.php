<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2017 Friendly-it, Inc. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace Friendlyit\Emailcloak\Helpers\email;
use Friendlyit\Emailcloak\Helpers\idna_convert\idna_convert;

/**
 * Utility class for cloaking email addresses
 *
 * @since  1.5
 */
abstract class PHtmlEmail
{

	/**
	 * Simple JavaScript email cloaker
	 *
	 * By default replaces an email with a mailto link with email cloaked
	 *
	 * @param   string   $mail    The -mail address to cloak.
	 * @param   boolean  $mailto  True if text and mailing address differ
	 * @param   string   $text    Text for the link
	 * @param   boolean  $email   True if text is an email address
	 *
	 * @return  string  The cloaked email.
	 *
	 * @since   1.5
	 */
	public static function cloak($mail, $mailto = true, $text = '', $email = true)
	{
		// Handle IDN addresses: punycode for href but utf-8 for text displayed.
		if ($mailto && (empty($text) || $email))
		{
			// Use dedicated $text whereas $mail is used as href and must be punycoded.
			$text = static::emailToUTF8($text ?: $mail);
		}
		elseif (!$mailto)
		{
			// In that case we don't use link - so convert $mail back to utf-8.
			$mail = static::emailToUTF8($mail);
		}

		// Convert mail
		$mail = static::convertEncoding($mail);

		// Random hash
		$rand = md5($mail . mt_rand(1, 100000));

		// Split email by @ symbol
		$mail       = explode('@', $mail);
		$mail_parts = explode('.', $mail[1]);

		if ($mailto)
		{
			// Special handling when mail text is different from mail address
			if ($text)
			{
				// Convert text - here is the right place
				$text = static::convertEncoding($text);

				if ($email)
				{
					// Split email by @ symbol
					$text = explode('@', $text);
					$text_parts = explode('.', $text[1]);
					$tmpScript = "var addy_text" . $rand . " = '" . @$text[0] . "' + '&#64;' + '" . implode("' + '&#46;' + '", @$text_parts)
						. "';";
				}
				else
				{
					$tmpScript = "var addy_text" . $rand . " = '" . $text . "';";
				}

				$tmpScript .= "document.getElementById('cloak" . $rand . "').innerHTML += '<a ' + path + '\'' + prefix + ':' + addy"
					. $rand . " + '\'>'+addy_text" . $rand . "+'<\/a>';";
			}
			else
			{
				$tmpScript = "document.getElementById('cloak" . $rand . "').innerHTML += '<a ' + path + '\'' + prefix + ':' + addy"
					. $rand . " + '\'>' +addy" . $rand . "+'<\/a>';";
			}
		}
		else
		{
			$tmpScript = "document.getElementById('cloak" . $rand . "').innerHTML += addy" . $rand . ";";
		}

		$script       = "
				document.getElementById('cloak" . $rand . "').innerHTML = '';
				var prefix = '&#109;a' + 'i&#108;' + '&#116;o';
				var path = 'hr' + 'ef' + '=';
				var addy" . $rand . " = '" . @$mail[0] . "' + '&#64;';
				addy" . $rand . " = addy" . $rand . " + '" . implode("' + '&#46;' + '", $mail_parts) . "';
				$tmpScript
		";

		// TODO: Use inline script for now
		$inlineScript = "<script type='text/javascript'>" . $script . "</script>";

		return '<span id="cloak' . $rand . '">' . __('This email address is being protected from spambots. You need JavaScript enabled to view it.') . '</span>' . $inlineScript;
	}

	/**
	 * Convert encoded text
	 *
	 * @param   string  $text  Text to convert
	 *
	 * @return  string  The converted text.
	 *
	 * @since   1.5
	 */
	protected static function convertEncoding($text)
	{
		$text = html_entity_decode($text);

		// Replace vowels with character encoding
		$text = str_replace('a', '&#97;', $text);
		$text = str_replace('e', '&#101;', $text);
		$text = str_replace('i', '&#105;', $text);
		$text = str_replace('o', '&#111;', $text);
		$text = str_replace('u', '&#117;', $text);
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8', false);

		return $text;
	}

	/**
	 * Transforms a Punycode string to a UTF-8 string
	 *
	 * @param   string  $punycodeString  The Punycode string to transform
	 *
	 * @return  string  The UF-8 URL
	 *
	 * @since   3.1.2
	 */
	public static function fromPunycode($punycodeString)
	{
		$idn = new idna_convert;

		return $idn->decode($punycodeString);
	}
	/**
	 * Transforms a Punycode email to a UTF-8 email
	 * This assumes a valid email address
	 *
	 * @param   string  $email  The punycode email to transform
	 *
	 * @return  string  The punycode email
	 *
	 * @since   3.1.2
	 */
	public static function emailToUTF8($email)
	{
		$explodedAddress = explode('@', $email);

		// Not addressing UTF-8 user names
		$newEmail = $explodedAddress[0];

		if (!empty($explodedAddress[1]))
		{
			$domainExploded = explode('.', $explodedAddress[1]);
			$newdomain = '';

			foreach ($domainExploded as $domainex)
			{
				$domainex = static::fromPunycode($domainex);
				$newdomain .= $domainex . '.';
			}

			$newdomain = substr($newdomain, 0, -1);
			$newEmail = $newEmail . '@' . $newdomain;
		}

		return $newEmail;
	}
}
