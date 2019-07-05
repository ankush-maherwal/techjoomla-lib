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

JLoader::register('TjModelHouseKeeping', JPATH_SITE . "/libraries/techjoomla/model/houseKeeping.php");

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

/**
 * HouseKeeping controller
 *
 * @since   __DEPLOY_VERSION__
 */
trait TjControllerHouseKeeping
{
	/**
	 * Function to initialise houseKeeping
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function init()
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$clientExtension = Factory::getApplication()->input->get('option', '', 'STRING');

		$tjHouseKeeping = new TjModelHouseKeeping;
		$data = $tjHouseKeeping->getHouseKeepingScripts($clientExtension);

		echo json_encode($data);
		jexit();
	}

	/**
	 * Function to execute houseKeeping script
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function executeHouseKeeping()
	{
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$input = Factory::getApplication()->input;
		$clientExtension = $input->get('client', '', 'STRING');
		$version = $input->get('version', '', 'STRING');
		$scriptFile = $input->get('script', '', 'STRING');

		$tjHouseKeeping = new TjModelHouseKeeping;
		$data = $tjHouseKeeping->executeHouseKeeping($clientExtension, $version, $scriptFile);

		echo json_encode($data);
		jexit();
	}
}
