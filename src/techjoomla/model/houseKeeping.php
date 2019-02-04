<?php
/**
 * @package    TechJoomla.library
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;

/**
 * TJ HouseKeeping model
 *
 * @since  __DEPLOY_VERSION__
 */
class TjModelHouseKeeping
{
	/**
	 * Variable to hold houseKeeping title
	 * @var String
	 */
	public $title;

	/**
	 * Variable to hold houseKeeping description
	 * @var String
	 */
	public $description;

	/**
	 * Variable to hold errors occured while executing the houseKeeping scripts
	 * @var String
	 */
	public $error;

	/**
	 * Get the from version from houseKeepings table
	 *
	 * @param   STRING  $clientExtension  extension name
	 *
	 * @return ARRAY
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getHouseKeepingFromVersion($clientExtension)
	{
		if (empty($clientExtension))
		{
			return false;
		}

		$db = Factory::getDbo();

		// Get last failed houseKeeping record
		$query = $db->getQuery(true);
		$query->select('MIN(' . $db->quoteName('id') . ')');
		$query->select($db->quoteName('version'));
		$query->from($db->quoteName('#__tj_houseKeeping'));
		$query->where($db->quoteName('client') . ' = ' . $db->quote($clientExtension));
		$query->where($db->quoteName('status') . ' = 0');
		$query->group($db->quoteName('version'));

		$db->setQuery($query);

		$result = $db->loadAssoc();

		if (!isset($result['version']) && empty($result['version']))
		{
			// Get last success houseKeeping record
			$query = $db->getQuery(true);
			$query->select('MAX(' . $db->quoteName('id') . ')');
			$query->select($db->quoteName('version'));
			$query->from($db->quoteName('#__tj_houseKeeping'));
			$query->where($db->quoteName('client') . ' = ' . $db->quote($clientExtension));
			$query->where($db->quoteName('status') . ' = 1');
			$query->group($db->quoteName('version'));

			$db->setQuery($query);

			$result = $db->loadAssoc();
		}

		$fromVersion = (isset($result['version']) && !empty($result['version'])) ? $result['version'] : '0.0.0';

		return $fromVersion;
	}

	/**
	 * Get the scripts to be executed for houseKeeping
	 *
	 * @param   STRING  $clientExtension  client extension.
	 * 
	 * @param   STRING  $fromVersion      version from which the houseKeepings scripts to be executed.
	 *
	 * @return ARRAY
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getHouseKeepingScripts($clientExtension, $fromVersion = null)
	{
		$return = array();
		$houseKeepingScripts = array();

		if (empty($fromVersion))
		{
			$fromVersion = $this->getHouseKeepingFromVersion($clientExtension);
		}

		if (!empty($fromVersion) && !empty($clientExtension))
		{
			$files = $this->getHouseKeepingScriptFiles($clientExtension, $fromVersion);

			foreach ($files as $file)
			{
				$scriptInstance = $this->getHouseKeepingScriptInstance($file);

				$scriptData = array();
				$scriptData[] = $clientExtension;
				$file = substr($file, strlen(JPATH_ADMINISTRATOR . '/components/' . $clientExtension . '/houseKeeping/'));
				$file = explode('/', $file);
				$scriptData[] = $file[0];
				$scriptData[] = $file[1];
				$scriptData[] = $scriptInstance->title;
				$scriptData[] = $scriptInstance->description;
				$houseKeepingScripts[] = $scriptData;
			}
		}

		$return['count'] = count($houseKeepingScripts);
		$return['scripts'] = $houseKeepingScripts;

		return $return;
	}

	/**
	 * Get the list of the houseKeeping scripts to be executed
	 *
	 * @param   STRING  $clientExtension  client extension.
	 * 
	 * @param   STRING  $fromVersion      version from which the houseKeepings scripts to be executed.
	 *
	 * @return ARRAY
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getHouseKeepingScriptFiles($clientExtension, $fromVersion = null)
	{
		$scripts = array();

		if (empty($fromVersion) || empty($clientExtension))
		{
			return $scripts;
		}

		if ($fromVersion !== null)
		{
			$folders = Folder::folders(JPATH_ADMINISTRATOR . '/components/' . $clientExtension . '/houseKeeping');

			if (!empty($folders))
			{
				foreach ($folders as $folder)
				{
					// Get list of folders greater than the from version
					if (version_compare($folder, $fromVersion, 'ge'))
					{
						$path = JPATH_ADMINISTRATOR . '/components/' . $clientExtension . '/houseKeeping/' . $folder;

						$scripts = array_merge($scripts, Folder::files($path, '.php$', false, true));
					}
				}
			}
		}

		// If script is already executed and marked as success then dont execute the script
		foreach ($scripts as $k => $script)
		{
			if ($this->scriptExecuted($script))
			{
				unset($scripts[$k]);
			}
		}

		return $scripts;
	}

	/**
	 * Function to check if script is already executed
	 *
	 * @param   STRING  $script  script path
	 *
	 * @return BOOLEAN
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function scriptExecuted($script)
	{
		$script = substr($script, strlen(JPATH_ADMINISTRATOR . '/components/'));
		$script = explode("/", $script);

		$client = $script[0];
		$version = $script[2];
		$title = $script[3];

		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tj_houseKeeping'));
		$query->where($db->quoteName('client') . ' = ' . $db->quote($client));
		$query->where($db->quoteName('version') . ' = ' . $db->quote($version));
		$query->where($db->quoteName('title') . ' = ' . $db->quote($title));
		$db->setQuery($query);
		$record = $db->loadAssoc();

		$result = (isset($record['status']) && !empty($record['status'])) ? true : false;

		return $result;
	}

	/**
	 * Get the instance of houseKeeping script class
	 *
	 * @param   STRING  $file  path of houseKeeping script file
	 *
	 * @return OBJECT
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getHouseKeepingScriptInstance($file)
	{
		if (!File::exists($file))
		{
			return false;
		}

		$filename = basename($file, '.php');
		$classname = 'TjHouseKeeping' . $filename;

		JLoader::register($classname, $file);

		$obj = new $classname;

		return $obj;
	}

	/**
	 * Function to execute the houseKeeping script
	 *
	 * @param   STRING  $clientExtension  client extension
	 * @param   STRING  $version          script for version
	 * 
	 * @param   STRING  $scriptFile       script file name
	 *
	 * @return OBJECT
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function executeHouseKeeping($clientExtension, $version, $scriptFile)
	{
		$status = false;

		if (empty($clientExtension) || empty($version) || empty($scriptFile))
		{
			return false;
		}

		$user = Factory::getUser();
		$authorise = $user->authorise('core.admin', $clientExtension);

		// Return false if user is not allowed to execute the houseKeeping
		if (empty($authorise))
		{
			return false;
		}

		$file = JPATH_ADMINISTRATOR . '/components/' . $clientExtension . '/houseKeeping/' . $version . '/' . $scriptFile;
		$scriptInstance = $this->getHouseKeepingScriptInstance($file);

		if ($scriptInstance !== false)
		{
			$houseKeepingStatus = ($scriptInstance->migrate()) ? 1 : 0;
		}

		$this->updateHouseKeepingStatus($clientExtension, $version, $scriptFile, $houseKeepingStatus);
		$status = ($houseKeepingStatus) ? true : false;

		return $status;
	}

	/**
	 * Function to add/update the houseKeeping record
	 * 
	 * @param   STRING   $client   client extension
	 * 
	 * @param   STRING   $version  houseKeeping for version
	 * 
	 * @param   STRING   $title    houseKeeping title
	 * 
	 * @param   BOOLEAN  $status   houseKeeping status
	 *
	 * @since  __DEPLOY_VERSION__
	 * 
	 * @return null
	 */
	protected function updateHouseKeepingStatus($client, $version, $title, $status = 0)
	{
		if (empty($client) || empty($title) || empty($version))
		{
			return false;
		}

		$db = Factory::getDbo();

		// Get the houseKeeping according to houseKeeping title, version and client
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tj_houseKeeping'));
		$query->where($db->quoteName('client') . ' = ' . $db->quote($client));
		$query->where($db->quoteName('title') . ' = ' . $db->quote($title));
		$query->where($db->quoteName('version') . ' = ' . $db->quote($version));
		$db->setQuery($query);

		$record = $db->loadObject();

		$record->status = $status;
		$record->lastExecutedOn = Factory::getDate()->toSql();
		$result = false;

		if (empty($record->id))
		{
			$record->client = $client;
			$record->version = $version;
			$record->title = $title;
			$db->insertObject('#__tj_houseKeeping', $record, 'id');
			$result = $db->insertid();
		}
		else
		{
			$db->updateObject('#__tj_houseKeeping', $record, 'id');
			$result = $record->id;
		}

		return $result;
	}

	/**
	 * Function to get errors occured while executing the scripts
	 * 
	 * @param   STRING  $msg  Error message
	 *
	 * @since  __DEPLOY_VERSION__
	 * 
	 * @return null
	 */
	protected function setError($msg)
	{
		$this->error = $msg;
	}

	/**
	 * Function to get errors occured while executing the scripts
	 *
	 * @since  __DEPLOY_VERSION__
	 * 
	 * @return ARRAY    The errors
	 */
	public function getError()
	{
		return $this->error;
	}
}
