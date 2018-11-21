<?php
/**
 * @package     Techjoomla_Library
 * @subpackage  TjMedia
 * @author      Techjoomla <contact@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class TJMediaModelXref
 *
 * @since  1.0
 */
class TJMediaModelXref extends JModelList
{
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select(
							'a.id as xref_id,a.media_id,a.client,a.client_id,a.is_gallery,tj.*'
					);

		$query->from($db->quoteName('#__tj_media_files_xref', 'a'));

		$query->join('INNER', $db->quoteName('#__tj_media_files', 'tj') .
				' ON (' . $db->quoteName('a.media_id') . ' = ' . $db->quoteName('tj.id') . ')');

		$clientId = $this->getState('filter.clientId');

		if ($clientId)
		{
			$query->where($db->quoteName('a.client_id') . '=' . (int) $clientId);
		}

		$client = $this->getState('filter.client');

		if ($client)
		{
			$query->where($db->quoteName('a.client') . ' = ' . $db->q($client));
		}

		$mediaId = $this->getState('filter.mediaId');

		if ($mediaId)
		{
			$query->where($db->quoteName('a.media_id') . ' = ' . (int) $mediaId);
		}

		$isGallery = $this->getState('filter.isGallery');

		if ($isGallery)
		{
			$query->where($db->quoteName('a.is_gallery') . ' = ' . (int) $isGallery);
		}

		$id = $this->getState('filter.id');

		if ($id)
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) $id);
		}

		// Add the list ordering clause.
		$orderCol  = $this->getState('list.ordering');
		$orderDirn = $this->getState('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
