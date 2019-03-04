<?php
/**
 * @version     SVN: <svn_id>
 * @package     Techjoomla.Libraries
 * @subpackage  FileViewer
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

defined('JPATH_PLATFORM') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * File Viewer
 *
 * @package     Joomla.Libraries
 * @subpackage  FileViewer
 * @since       1.0.0
 */

abstract class TJFileViewer
{
	CONST POLLY_FILL_JS_VERSION = 'v2';

	CONST BOX_API_VERSION = '1.62.1';

	public static $boxPreviewPolyfillJs;

	public static $boxPreviewJs;

	public static $boxPreviewCss;

	/**
	 * Get viewer
	 *
	 * @param   string        $file       File URL or File Id
	 * @param   string        $viewer     Viewer name e.g. Google Docs, Microsoft Office Web Apps
	 * @param   string        $name       The target attribute to use (Name of the Iframe).
	 * @param   array|string  $attribs    Attributes to be added to the `<iframe>` element
	 * @param   string        $container  Id of the preview container
	 * @param   string        $token      Api token if needed
	 *
	 * @return  mixed  Returns viewer html or void
	 */
	public static function _($file, $viewer = null, $name = 'fileViewer', $attribs = null, $container = '', $token = '')
	{
		if (!empty($file))
		{
			switch ($viewer)
			{
				case 'google':
					return static::_renderGoogleDocViewer($file, $name, $attribs);
					break;

				case 'microsoft':
					return static::_renderMicrosoftWebAppsViewer($file, $name, $attribs);
					break;

				case 'box':
					return static::_renderBoxViewer($file, $container, $token, $attribs);
					break;

				default:
					return static::_renderGoogleDocViewer($file, $name, $attribs);
					break;
			}
		}
	}

	/**
	 * Get google docs viewer
	 *
	 * @param   string        $file     File URL or File Id
	 * @param   string        $name     The target attribute to use (Name of the Iframe).
	 * @param   array|string  $attribs  Attributes to be added to the `<iframe>` element
	 *
	 * @return  mixed  Returns viewer html or void
	 */
	public static function _renderGoogleDocViewer($file, $name, $attribs = null)
	{
		$url = 'https://docs.google.com/viewer?embedded=true&url=' . urlencode($file);

		return HTMLHelper::iframe($url, $name, $attribs);
	}

	/**
	 * Get microsoft office web apps viewer
	 *
	 * @param   string        $file     File URL or File Id
	 * @param   string        $name     The target attribute to use (Name of the Iframe).
	 * @param   array|string  $attribs  Attributes to be added to the `<iframe>` element
	 *
	 * @return  mixed  Returns viewer html or void
	 */
	public static function _renderMicrosoftWebAppsViewer($file, $name, $attribs = null)
	{
		$url = 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($file);

		return HTMLHelper::iframe($url, $name, $attribs);
	}

	/**
	 * Get box viewer
	 *
	 * @param   string        $file       File URL or File Id
	 * @param   string        $container  Id of the preview container
	 * @param   string        $token      Box Api Token
	 * @param   array|string  $attribs    Attributes to be added in the container and also contains box Api Token
	 *
	 * @return  mixed  Returns viewer html or void
	 */
	public static function _renderBoxViewer($file, $container, $token, $attribs = null)
	{
		$boxViewerHtml = '';

		// Get polyfill JS
		if (empty(static::$boxPreviewPolyfillJs))
		{
			static::$boxPreviewPolyfillJs = '<script src="https://cdn.polyfill.io/' . self::POLLY_FILL_JS_VERSION .
			'/polyfill.min.js?features=Promise"></script>';

			$boxViewerHtml .= static::$boxPreviewPolyfillJs;
		}

		// Get box preview JS
		if (empty(static::$boxPreviewJs))
		{
			static::$boxPreviewJs = '<script src="https://cdn01.boxcdn.net/platform/preview/' . self::BOX_API_VERSION . '/en-US/preview.js"></script>';

			$boxViewerHtml .= static::$boxPreviewJs;
		}

		// Get box preview CSS
		if (empty(static::$boxPreviewCss))
		{
			static::$boxPreviewCss = '<link rel="stylesheet" href="https://cdn01.boxcdn.net/platform/preview/'
			. self::BOX_API_VERSION . '/en-US/preview.css" />';

			$boxViewerHtml .= static::$boxPreviewCss;
		}

		// Render Attribs
		if (is_array($attribs))
		{
			$attribs = ArrayHelper::toString($attribs);
		}

		$boxViewerHtml .= <<<EOT
		<div id="{$container}" {$attribs}></div>
		<script>
		var {$container} = new Box.Preview();
			{$container}.show("{$file}", "{$token}", {
			container: "#{$container}"
		});
		</script>
EOT;

		return $boxViewerHtml;
	}
}
