<?php
/**
 * @package    Techjoomla_Library
 *
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * TjMedia class.
 *
 * @since  1.0.0
 */
interface TjMedia
{
	/**
	 * Method to Add record on respective data source
	 *
	 * @param   array  $files  field name
	 * 
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	public function upload($files = array());

	/**
	 * Method to Delete record on respective data source
	 * 
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	public function delete();

	/**
	 * Download the file
	 *
	 * @param   STRING  $file             - file path eg /var/www/j30/media/com_quick2cart/qtc_pack.zip
	 * @param   STRING  $filename_direct  - for direct download it will be file path like http://
	 * localhost/j30/media/com_quick2cart/qtc_pack.zip  -- for FUTURE SCOPE
	 * @param   STRING  $extern           - Remote url or a local file specified by $url
	 * @param   STRING  $exitHere         - To exit from here
	 *
	 * @return  integer
	 */
	public function downloadMedia($file, $filename_direct = '', $extern = '', $exitHere = 1);
}
