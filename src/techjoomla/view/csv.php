<?php
/**
 * @version    SVN: <svn_id>
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
jimport('joomla.application.component.model');
jimport('techjoomla.tjcsv.csv');

/**
 * TjCsv
 *
 * @package     Techjoomla.Libraries
 * @subpackage  TjCsv
 * @since       1.0
 */
class TjExportCsv extends JViewLegacy
{
	/**
	 *  seperator specifies the field separator, default value is comma(,) .
	 *
	 * @var  STRING
	 */
	protected $seperator = ',';

	/**
	 *  enclosure specifies the field enclosure character, default value is " .
	 *
	 * @var  STRING
	 */
	protected $enclosure = '"';

	/**
	 *  Limit start for getData for CSV
	 *
	 * @var  INT
	 */
	protected $limitStart = 0;

	/**
	 *  Total count of data.
	 *
	 * @var  INT
	 */
	protected $recordCnt = 0;

	/**
	 * The filename of the downloaded CSV file.
	 *
	 * @var  STRING
	 */
	protected $fileName = '';

	/**
	 * The data for CSV file.
	 *
	 * @var  MIXED
	 */
	protected $data = null;

	/**
	 * The headers for CSV file.
	 *
	 * @var  MIXED
	 */
	protected $headers = null;

	/**
	 * Function get the limit start and total records count for CSV export
	 *
	 * @param   STRING  $tpl  file name if empty then default set component name view name date and rand number
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$input      = $app->input;
		$returnFileName = $input->get('file_name');

		$this->fileName = $this->fileName ? JFile::stripExt($this->fileName) : substr($input->get('option'), 4) . "_" .
		$input->get('view') . "_" . date("Y-m-d_H-i-s", time());
		$this->fileName .= '_' . rand() . '.' . 'csv';

		if (!$this->data)
		{
			$this->limitStart = $input->get('limitstart', 0, 'INT');
			$model = JModelLegacy::getInstance($input->get('view'), substr($input->get('option'), 4) . 'Model');
			$app->setUserState($input->get('option') . '.' . $input->get('view') . '.limitstart', $this->limitStart);
			$model->setState("list.limit", $model->getState('list.limit'));
			$this->data = $model->getItems();
			$this->recordCnt = $model->getTotal();
		}

		$TjCsv = new TjCsv;
		$TjCsv->limitStart  = $this->limitStart;
		$TjCsv->recordCnt   = $this->recordCnt;
		$TjCsv->seperator   = $this->seperator;
		$TjCsv->enclosure   = $this->enclosure;
		$TjCsv->headers     = $this->headers;
		$TjCsv->csvFilename = $returnFileName ? $returnFileName : $this->fileName;
		$returnData = $TjCsv->CsvExport($this->data);

		echo json_encode($returnData);
		jexit();
	}
}
