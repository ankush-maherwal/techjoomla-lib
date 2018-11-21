<?php
/**
 * @package     Techjoomla_Library
 * @subpackage  TjMedia
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * TJMediaTableXref
 *
 * @since  1.0.0
 */
class TJMediaTableXref extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  Database object
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_media_files_xref', 'id', $db);
	}
}
