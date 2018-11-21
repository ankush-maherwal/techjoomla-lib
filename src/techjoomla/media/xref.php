<?php
/**
 * @package     Techjoomla_Library
 * @subpackage  TjMedia
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_PLATFORM') or die();
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('techjoomla.object.object');
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/* load language file for plugin frontend */
$lang = JFactory::getLanguage();
$lang->load('lib_techjoomla', JPATH_SITE, '', true);

/**
 * TJMediaXref class.
 *
 * @since  1.0.0
 */
class TJMediaXref extends JObject
{
	// Xref table id
	public $id = 0;

	// Media table id
	public $media_id = 0;

	public $client_id = null;

	protected $client = null;

	public $is_gallery = null;

	/**
	 * Method to initialise class based on global setting
	 *
	 * @param   array  $configs  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($configs = array())
	{
		if (isset($configs['mediaId']))
		{
			$mediaConfig = array();
			$mediaConfig['id'] = $configs['mediaId'];
		}

		if (isset($configs['id']))
		{
			$this->load($configs['id']);

			$data = $this->getProperties();

			$mediaConfig['id'] = $data['media_id'];
		}

		$this->media = TJMediaStorageLocal::getInstance($mediaConfig);
	}

	/**
	 * Method to load a media xref object by xref id 
	 *
	 * @param   mixed  $id  The id of the object to get.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function load($id)
	{
		JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);

		$table = JTable::getInstance('Xref', 'TJMediaTable');

		// Load the object based on the id or throw a warning.
		if (! $table->load($id))
		{
			$this->setError("LIB_TECHJOOMLA_MEDIA_NO_MEDIA_FILE_IN_XREF_TABLE");

			return false;
		}

			// Assuming all is well at this point let's bind the data
			$this->setProperties($table->getProperties());

			return true;
	}

	/**
	 * Returns the global media object
	 *
	 * @param   array  $options  media xref object to instantiate
	 *
	 * @return  object
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($options = array())
	{
		if (empty($options))
		{
			return new TJMediaXref;
		}

		// @TODO Load from cache
		return new TJMediaXref($options);
	}

	/**
	 * Method to save the data.
	 *
	 * @return  boolean  True on success
	 *
	 * @since  1.0.0
	 */
	public function save()
	{
		JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);

		$tjmediaXrefTable = JTable::getInstance('Xref', 'TJMediaTable');

		$data = $this->getProperties();

		$tjmediaXrefTable->load(array('media_id' => (int) $data['media_id'], 'client_id' => (int) $data['client_id'], 'client' => $data['client']));

		$this->id = $tjmediaXrefTable->id;

		if (!$tjmediaXrefTable->save($this->getProperties()))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Method to bind an associative array of data to a user object
	 *
	 * @param   array  $data  The associative array to bind to the object
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function bind($data = array())
	{
		// Bind the array
		if (!$this->setProperties($data))
		{
			$this->setError(JText::_('MEDIA_LIBRARY_ERROR_BIND_JOB'));

			return false;
		}
	}

	/**
	 * Method to delete the media from table
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function delete()
	{
		JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);
		$mediaXrefTable = JTable::getInstance('Xref', 'TJMediaTable');

		if ($this->id)
		{
			if (!$mediaXrefTable->delete($this->id))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		elseif ($this->media->id)
		{
			// To check if the media is in use whith other client then don't delete
			$xrefMedia = $mediaXrefTable->load(array('media_id' => $this->media->id));

			if (!$xrefMedia)
			{
				if (!$this->media->delete())
				{
					return false;
				}
				else
				{
					return true;
				}
			}
			else
			{
				$this->setError(JText::_('LIB_TECHJOOMLA_MEDIA_IN_USE_UNABLE_TO_DELETE'));

				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to retrive the media using the getItems
	 *
	 * @param   array  $data  Array of data that has to be set using setState
	 *
	 * @return  array Array of data based on the required data provided
	 *
	 * @since   1.0.0
	 */
	public function retrive($data = array())
	{
		JModelLegacy::addIncludePath(JPATH_SITE . '/libraries/techjoomla/media/models');
		$tjMediaXrefsModel = JModelLegacy::getInstance('Xref', 'TJMediaModel',  array("ignore_request" => false));

		if (isset($data['id']))
		{
			$tjMediaXrefsModel->setState('filter.isGallery', $data['id']);
		}

		if (isset($data['isGallery']))
		{
			$tjMediaXrefsModel->setState('filter.isGallery', $data['isGallery']);
		}

		if (isset($data['client']))
		{
			$tjMediaXrefsModel->setState('filter.client', $data['client']);
		}

		if (isset($data['clientId']))
		{
			$tjMediaXrefsModel->setState('filter.clientId', $data['clientId']);
		}

		if (isset($data['mediaId']))
		{
			$tjMediaXrefsModel->setState('filter.mediaId', $data['mediaId']);
		}

		return $tjMediaXrefsModel->getItems();
	}
}
