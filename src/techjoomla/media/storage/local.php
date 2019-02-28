<?php
/**
 * @package     Techjoomla_Library
 * @subpackage  TjMedia
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;

defined('JPATH_PLATFORM') or die();

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.user.helper');
jimport('techjoomla.media.tjmedia');
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);
jimport('techjoomla.object.object');

/* load language file for plugin frontend */
$lang = JFactory::getLanguage();
$lang->load('lib_techjoomla', JPATH_SITE, '', true);

define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);
define('TB', 1099511627776);

/**
 * TJMediaStorageLocal class.
 *
 * @since  1.0.0
 */
class TJMediaStorageLocal extends JObject implements TjMedia
{
	// Media table id
	public $id = 0;

	public $title = null;

	public $type = null;

	public $realtive_path = null;

	public $absolute_path = null;

	public $mediaUploadPath = 'images/tjmedia';

	public $state = 0;

	public $source = 0;

	public $original_filename = null;

	protected $size = 0;

	protected $storage = 0;

	public $created_by = 0;

	public $access = 0;

	public $created_date = null;

	public $params = null;

	public $allowedExtension = array (
			'bmp', 'csv', 'doc', 'gif', 'ico',
			'jpg','jpeg', 'odg', 'odp', 'ods',
			'odt', 'pdf','png', 'ppt', 'txt',
			'xcf', 'xls', 'mp4', 'webm'
		);

	/**
	 * Method to initialise class based on global setting
	 *
	 * @param   array  $configs  An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct($configs = array())
	{
		$imageResizeSize = array();
		$imageResizeSize['small']['small_width'] = '128';
		$imageResizeSize['small']['small_height'] = '128';
		$imageResizeSize['medium']['medium_width'] = '240';
		$imageResizeSize['medium']['medium_height'] = '240';
		$imageResizeSize['large']['large_width'] = '400';
		$imageResizeSize['large']['large_height'] = '400';

		// Default title
		$this->title = (array_key_exists('title', $configs) && !empty($configs['title'])) ? $configs['title'] : "default_title";

		// Default path
		$this->uploadPath = (array_key_exists('uploadPath', $configs) &&
		!empty($configs['uploadPath'])) ? $configs['uploadPath'] : JPATH_SITE . '/' . $this->mediaUploadPath;

		// Delete old data or not
		$this->oldData = (array_key_exists('oldData', $configs)) ? $configs['oldData'] : 0;

		// To add data in database or not
		$this->saveData = (array_key_exists('saveData', $configs)) ? $configs['saveData'] : '1';

		// Default image resize size for different sizes
		$this->imageResizeSize = (array_key_exists('imageResizeSize', $configs)
		&& !empty($configs['imageResizeSize'])) ? $configs['imageResizeSize'] : $imageResizeSize;

		// Max Size
		$this->maxsize = (array_key_exists('size', $configs) && !empty($configs['size'])) ? $configs['size'] * MB : "";

		// Default storage
		$this->storage = (array_key_exists('storage', $configs) && !empty($configs['storage'])) ? $configs['storage'] : "local";

		// Default State
		$this->state = (array_key_exists('state', $configs)) ? $configs['state'] : 0;

		// Default Access
		$this->access = (array_key_exists('access', $configs)) ? $configs['access'] : 1;

		$this->params = (array_key_exists('params', $configs) && !empty($configs['params'])) ? $configs['params'] : "";

		// Array of default "type of media" config to restrict the media upload by checking mime types
		// Example "image/png, image/jpeg etc"
		$this->default_type = (array_key_exists('type', $configs) && !empty($configs['type'])) ? $configs['type'] : "";

		// Check is authorized user adding media.
		$this->auth = (array_key_exists('auth', $configs) && !empty($configs['auth'])) ? $configs['auth'] : "";

		// Check for allowed extensions.
		if ((array_key_exists('allowedExtension', $configs)	&& !empty($configs['allowedExtension'])))
		{
			$this->allowedExtension = array_map('strtolower', $configs['allowedExtension']);
		}

		if (!empty($configs['id']))
		{
			$this->load($configs['id']);
		}
	}

	/**
	 * Method to load a Media object
	 *
	 * @param   mixed  $id  The id of the object to get.
	 *
	 * @return  boolean  True on success and set the properties to object
	 *
	 * @since   1.0.0
	 */
	public function load($id)
	{
		JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
		$table = Table::getInstance('Files', 'TJMediaTable');

		// Load the object based on the id or throw a warning.
		if (! $table->load($id))
		{
			$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_NO_MEDIA_FILE_IN_MEDIA_TABLE"));

			return false;
		}

		$mediaType = explode(".", $table->type);

		$mediaPath = JUri::root() . $this->uploadPath;

		$table->media = '';

		if ($mediaType[0] == 'image')
		{
			// Example = {JUri::root()}/learning/media/com_jticketing/venues/images/L_1527498289_69506906-volleyball-wallpapers.jpg
			$table->media = $mediaPath . '/' . $table->source;
			$table->media_s = $mediaPath . '/S_' . $table->source;
			$table->media_m = $mediaPath . '/M_' . $table->source;
			$table->media_l = $mediaPath . '/L_' . $table->source;
		}
		elseif ($mediaType[0] == 'video')
		{
			if ($mediaType[1] == 'youtube')
			{
				$table->media = $table->source;
			}
			else
			{
				$table->media = $mediaPath . '/' . $table->source;
			}
		}
		else
		{
			$table->media = $mediaPath . '/' . $table->source;
		}

		// Assuming all is well at this point let's bind the data
		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Returns the global media object with default configs if not set
	 *
	 * @param   array  $options  options to set to default configs and object to instantiate
	 *
	 * @return  object Object of the media
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($options = array())
	{
		if (empty($options))
		{
			return new TJMediaStorageLocal;
		}

		// @TODO Load from cache
		return new TJMediaStorageLocal($options);
	}

	/**
	 * Method to upload media.
	 *
	 * @param   array  $files  field name to upload one or more medias
	 *
	 * @return array|boolean  Array of data of the media after upload
	 *
	 * @since   1.0.0
	 */
	public function upload($files = array())
	{
		foreach ($files as $file)
		{
			// Orginal file name
			$this->original_filename = $file['name'];

			// Convert name to lowercase
			$this->original_filename = strtolower($file['name']);

			// Check if file is without extension
			$fileDetails = pathinfo($this->original_filename);

			if (!isset($fileDetails['extension']) || !in_array($fileDetails['extension'], $this->allowedExtension))
			{
				$this->setError(JText::_("LIB_TECHJOOMLA_MEDIA_INVALID_FILE_TYPE_ERROR"));

				return false;
			}

			// Replace "spaces" with "_" in filename
			$this->original_filename = preg_replace('/\s/', '_', $this->original_filename);
			$fileTmpName = $file['tmp_name'];
			$this->type = $this->getMimeType($fileTmpName);
			$this->size = $file['size'];
			$fileError = $file['error'];

			// Check $file_error value.
			switch ($fileError)
			{
				case 0:
					break;
				case 4:
					$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_NO_FILE_SENT_ERROR"));

					return false;
				case 1:
					$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_EXEEDED_FILE_SIZE_LIMIT_ERROR_INI"));

					return false;
				case 2:
					$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_EXEEDED_FILE_SIZE_LIMIT_ERROR"));

					return false;
				default:
					$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_UNKOWN"));

					return false;
			}

			if (!empty($this->default_type))
			{
				if (is_array($this->default_type))
				{
					if (! in_array($this->type, $this->default_type))
					{
						$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_INVALID_FILE_TYPE_ERROR"));

						return false;
					}
				}
			}

			// Checking the maximum size
			if ($this->maxsize)
			{
				if ($this->size > $this->maxsize)
				{
					$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_EXEEDED_FILE_SIZE_LIMIT_ERROR"));

					return false;
				}
			}

			$temp = explode(".", $this->original_filename);
			reset($temp);
			$first = current($temp);
			$this->source = '';
			$this->source = round(microtime(true)) . "_" . JUserHelper::genRandomPassword(5) . "_" . $first . '.' . $fileDetails['extension'];

			// If folder is not present create it
			if (!Folder::exists($this->uploadPath))
			{
				Folder::create($this->uploadPath);
			}

			$uploadPath = $this->uploadPath . '/' . $this->source;

			// If media Id is present and if user want to delete the old data then delete the old media form the server
			if ($this->id && $this->oldData == 0)
			{
				File::delete($this->uploadPath . "/" . $this->source);
			}

			// Upload the image
			if (! File::upload($fileTmpName, $uploadPath))
			{
				$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_ERROR_MOVING_FILE"));

				return false;
			}
			else
			{
				$type = explode("/", $this->type);

				if ($type[0] == 'image')
				{
					$this->resizeImage($uploadPath, $this->uploadPath, $this->source);
				}

				$this->uploadPath = str_replace(JPATH_SITE . "/", "", $this->uploadPath);

				$this->created_date = Factory::getDate()->toSql();

				$this->bind($this->getProperties());
				$returnData = array();

				if ($this->saveData)
				{
					JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
					$tjMediaTable = Table::getInstance('Files', 'TJMediaTable');

					if (!$tjMediaTable->save($this->getProperties()))
					{
						$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_ERROR_SAVING_FILE"));

						return false;
					}

					if ($tjMediaTable->id)
					{
						$returnData['id'] = $tjMediaTable->id;
					}
				}

				$returnData['path'] = $uploadPath;

				// File original name
				$returnData['name']              = $this->original_filename;
				$returnData['original_filename'] = $this->original_filename;
				$returnData['type']              = $this->type;
				$returnData['params']            = $this->params;

				// Source is replace original file name with date
				$returnData['source'] = $this->source;
				$returnData['size'] = $this->size;

				$mediaType = explode(".", $returnData['type']);
				$mediaPath = Uri::root() . $this->uploadPath;

				if ($mediaType[0] == 'image')
				{
					// Example = {JUri::root()}/learning/media/com_jticketing/venues/images/L_1527498289_69506906-volleyball-wallpapers.jpg
					$returnData['media'] = $mediaPath . '/' . $returnData['source'];
					$returnData['media_s'] = $mediaPath . '/S_' . $returnData['source'];
					$returnData['media_m'] = $mediaPath . '/M_' . $returnData['source'];
					$returnData['media_l'] = $mediaPath . '/L_' . $returnData['source'];
				}
				else
				{
					$returnData['media'] = $mediaPath . '/' . $returnData['source'];
				}

				$returnDataArray[] = $returnData;
			}
		}

		return $returnDataArray;
	}

	/**
	 * Method to bind an associative array of data
	 *
	 * @param   array  $data  array of data that is to be bind with object
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function bind($data = array())
	{
		$user = Factory::getUser();
		$isAdmin = $user->authorise('core.admin');

		$type = explode("/", $data['type']);

		if ($type[0] == "image")
		{
			$data['type'] = $type[0];
		}
		else
		{
			$data['type'] = $type[0] . "." . $type['1'];
		}

		if ($data['id'])
		{
			if (!$isAdmin)
			{
				if ($data['created_by'] != $user->id)
				{
					if (!$data['auth'])
					{
						$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

						return false;
					}
				}
			}
		}
		else
		{
			if (!$isAdmin)
			{
				if (!$data['auth'])
				{
					$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

					return false;
				}
			}

			if (!$data['created_by'])
			{
				$data['created_by'] = $user->id;
			}
		}

		$this->setProperties($data);

		return true;
	}

	/**
	 * Method to delete the media from table whose object has been created
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function delete()
	{
		Table::addIncludePath(JPATH_SITE . '/libraries/techjoomla/media/tables');
		$mediaFilesTable = JTable::getInstance('Files', 'TJMediaTable');

		if (!$mediaFilesTable->delete($this->id))
		{
			$this->setError($mediaFilesTable->getError());

			return false;
		}
		else
		{
			$type = explode('.', $this->type);
			$folderPath = $this->uploadPath;

			if ($this->deleteFile($this->source, $folderPath, $type[0]))
			{
				return true;
			}
		}
	}

	/**
	 * Method to delete the media from table whose object has been created
	 *
	 * @param   STRING  $filename     File name
	 *
	 * @param   STRING  $storagePath  Path where file is stored
	 *
	 * @param   STRING  $type         Type of file, default set to image
	 *
	 * @return  boolean  True on success
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function deleteFile($filename, $storagePath, $type = 'image')
	{
		if ($type === 'image')
		{
			$deleteData = array();
			$deleteData[] = JPATH_SITE . '/' . $storagePath . "/" . $filename;
			$deleteData[] = JPATH_SITE . '/' . $storagePath . "/S_" . $filename;
			$deleteData[] = JPATH_SITE . '/' . $storagePath . "/M_" . $filename;
			$deleteData[] = JPATH_SITE . '/' . $storagePath . "/L_" . $filename;

			return File::delete($deleteData);
		}

		return  File::delete(JPATH_SITE . '/' . $storagePath . "/" . $filename);
	}

	/**
	 * Method to create small, medium and large images of original image
	 *
	 * @param   string  $src       source path with file name
	 *
	 * @param   string  $imgPath   destination path
	 *
	 * @param   string  $fileName  new file name
	 *
	 * @return	boolean
	 *
	 * @since   1.0.0
	 */
	public function resizeImage($src, $imgPath, $fileName)
	{
		$file = explode(".", $fileName);
		$destPath = $imgPath . '/';
		$format = '';

		if ($file[1] == 'jpeg' || $file[1] == 'jpg')
		{
			$format = IMAGETYPE_JPEG;
		}
		elseif ($file[1] == 'png')
		{
			$format = IMAGETYPE_PNG;
		}
		/*elseif ($file[1] == 'gif')
		{
			$format = IMAGETYPE_GIF;
		}*/

		if ($format)
		{
			// Creating a new JImage object, passing it an image path
			$image = new JImage($src);

			// Small image
			$smallWidth = $this->imageResizeSize['small']['small_width'];
			$smallHeight = $this->imageResizeSize['small']['small_height'];
			$smallDestFile = 'S_' . $fileName;

			// Resize the image using the SCALE_INSIDE method
			$smallImage = $image->resize($smallWidth, $smallHeight);
			$smallImage->toFile($destPath . $smallDestFile, $format);

			// Medium image
			$mediumWidth = $this->imageResizeSize['medium']['medium_width'];
			$mediumHeight = $this->imageResizeSize['medium']['medium_height'];
			$mediumDestFile = 'M_' . $fileName;

			// Resize the image using the SCALE_INSIDE method
			$mediumImage = $image->resize($mediumWidth, $mediumHeight);
			$mediumImage->toFile($destPath . $mediumDestFile, $format);

			// Large image
			$largeWidth = $this->imageResizeSize['large']['large_height'];
			$largeHeight = $this->imageResizeSize['large']['large_height'];
			$destFile = 'L_' . $fileName;

			// Resize the image using the SCALE_INSIDE method
			$largeImage = $image->resize($largeWidth, $largeHeight);
			$largeImage->toFile($destPath . $destFile, $format);
		}

		return true;
	}

	/**
	 * Download the file
	 *
	 * @param   STRING  $file             - file path eg /var/www/j30/media/com_quick2cart/qtc_pack.zip
	 * @param   STRING  $filename_direct  - for direct download it will be file path like http://
	 * localhost/j30/media/com_quick2cart/qtc_pack.zip  -- for FUTURE SCOPE
	 * @param   STRING  $extern           - Remote url or a local file specified by $url
	 *
	 * @return  integer
	 */
	public function downloadMedia($file, $filename_direct = '', $extern = '')
	{
		jimport('joomla.filesystem.file');

		clearstatcache();

		if (!$extern)
		{
			if (!JFile::exists($file))
			{
				return 2;
			}
			else
			{
				$len = filesize($file);
			}
		}
		else
		{
			/* Return the size of a remote url or a local file specified by $url.
			 $thereturn specifies the unit returned (either bytes "", MiB "mb" or KiB
			 "kb"). */
			$len = filesize($file);
		}

		$filename       = basename($file);

		$file_extension = strtolower(substr(strrchr($filename, "."), 1));
		$ctype = $this->getMime($file_extension);

		ob_end_clean();

		//  Needed for MS IE - otherwise content disposition is not used?
		if (ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}

		header("Cache-Control: public, must-revalidate");
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		header("Expires: 0");
		header("Content-Description: File Transfer");
		header("Content-Type: " . $ctype);
		header("Content-Length: " . (string) $len);
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		//  set_time_limit doesn't work in safe mode
		if (!ini_get('safe_mode'))
		{
			@set_time_limit(0);
		}

		$fp = fopen($file, "r");

		if ($fp !== false)
		{
			while (!feof($fp))
			{
				$buff = fread($fp, $len);
				print $buff;
			}

			fclose($fp);
		}

		exit;
	}

	/**
	 * GetMime tyoe
	 *
	 * @param   STRING  $filetype  filetype
	 *
	 * @return  string
	 */
	public function getMime($filetype)
	{
		switch ($filetype)
		{
			case "ez":
				$mime = "application/andrew-inset";
				break;
			case "hqx":
				$mime = "application/mac-binhex40";
				break;
			case "cpt":
				$mime = "application/mac-compactpro";
				break;
			case "doc":
				$mime = "application/msword";
				break;
			case "bin":
				$mime = "application/octet-stream";
				break;
			case "dms":
				$mime = "application/octet-stream";
				break;
			case "lha":
				$mime = "application/octet-stream";
				break;
			case "lzh":
				$mime = "application/octet-stream";
				break;
			case "exe":
				$mime = "application/octet-stream";
				break;
			case "class":
				$mime = "application/octet-stream";
				break;
			case "dll":
				$mime = "application/octet-stream";
				break;
			case "oda":
				$mime = "application/oda";
				break;
			case "pdf":
				$mime = "application/pdf";
				break;
			case "ai":
				$mime = "application/postscript";
				break;
			case "eps":
				$mime = "application/postscript";
				break;
			case "ps":
				$mime = "application/postscript";
				break;
			case "xls":
				$mime = "application/vnd.ms-excel";
				break;
			case "ppt":
				$mime = "application/vnd.ms-powerpoint";
				break;
			case "pptx":
				$mime = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
				break;
			case "wbxml":
				$mime = "application/vnd.wap.wbxml";
				break;
			case "wmlc":
				$mime = "application/vnd.wap.wmlc";
				break;
			case "wmlsc":
				$mime = "application/vnd.wap.wmlscriptc";
				break;
			case "vcd":
				$mime = "application/x-cdlink";
				break;
			case "pgn":
				$mime = "application/x-chess-pgn";
				break;
			case "csh":
				$mime = "application/x-csh";
				break;
			case "dvi":
				$mime = "application/x-dvi";
				break;
			case "spl":
				$mime = "application/x-futuresplash";
				break;
			case "gtar":
				$mime = "application/x-gtar";
				break;
			case "hdf":
				$mime = "application/x-hdf";
				break;
			case "js":
				$mime = "application/x-javascript";
				break;
			case "nc":
				$mime = "application/x-netcdf";
				break;
			case "cdf":
				$mime = "application/x-netcdf";
				break;
			case "swf":
				$mime = "application/x-shockwave-flash";
				break;
			case "tar":
				$mime = "application/x-tar";
				break;
			case "tcl":
				$mime = "application/x-tcl";
				break;
			case "tex":
				$mime = "application/x-tex";
				break;
			case "texinfo":
				$mime = "application/x-texinfo";
				break;
			case "texi":
				$mime = "application/x-texinfo";
				break;
			case "t":
				$mime = "application/x-troff";
				break;
			case "tr":
				$mime = "application/x-troff";
				break;
			case "roff":
				$mime = "application/x-troff";
				break;
			case "man":
				$mime = "application/x-troff-man";
				break;
			case "me":
				$mime = "application/x-troff-me";
				break;
			case "ms":
				$mime = "application/x-troff-ms";
				break;
			case "ustar":
				$mime = "application/x-ustar";
				break;
			case "src":
				$mime = "application/x-wais-source";
				break;
			case "zip":
				$mime = "application/x-zip";
				break;
			case "au":
				$mime = "audio/basic";
				break;
			case "docx":
				$mime = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
				break;
			case "snd":
				$mime = "audio/basic";
				break;
			case "mid":
				$mime = "audio/midi";
				break;
			case "midi":
				$mime = "audio/midi";
				break;
			case "kar":
				$mime = "audio/midi";
				break;
			case "mpga":
				$mime = "audio/mpeg";
				break;
			case "mp2":
				$mime = "audio/mpeg";
				break;
			case "mp3":
				$mime = "audio/mpeg";
				break;
			case "aif":
				$mime = "audio/x-aiff";
				break;
			case "aiff":
				$mime = "audio/x-aiff";
				break;
			case "aifc":
				$mime = "audio/x-aiff";
				break;
			case "m3u":
				$mime = "audio/x-mpegurl";
				break;
			case "ram":
				$mime = "audio/x-pn-realaudio";
				break;
			case "rm":
				$mime = "audio/x-pn-realaudio";
				break;
			case "rpm":
				$mime = "audio/x-pn-realaudio-plugin";
				break;
			case "ra":
				$mime = "audio/x-realaudio";
				break;
			case "wav":
				$mime = "audio/x-wav";
				break;
			case "pdb":
				$mime = "chemical/x-pdb";
				break;
			case "xyz":
				$mime = "chemical/x-xyz";
				break;
			case "bmp":
				$mime = "image/bmp";
				break;
			case "gif":
				$mime = "image/gif";
				break;
			case "ief":
				$mime = "image/ief";
				break;
			case "jpeg":
				$mime = "image/jpeg";
				break;
			case "jpg":
				$mime = "image/jpeg";
				break;
			case "jpe":
				$mime = "image/jpeg";
				break;
			case "png":
				$mime = "image/png";
				break;
			case "tiff":
				$mime = "image/tiff";
				break;
			case "tif":
				$mime = "image/tiff";
				break;
			case "wbmp":
				$mime = "image/vnd.wap.wbmp";
				break;
			case "ras":
				$mime = "image/x-cmu-raster";
				break;
			case "pnm":
				$mime = "image/x-portable-anymap";
				break;
			case "pbm":
				$mime = "image/x-portable-bitmap";
				break;
			case "pgm":
				$mime = "image/x-portable-graymap";
				break;
			case "ppm":
				$mime = "image/x-portable-pixmap";
				break;
			case "rgb":
				$mime = "image/x-rgb";
				break;
			case "xbm":
				$mime = "image/x-xbitmap";
				break;
			case "xpm":
				$mime = "image/x-xpixmap";
				break;
			case "xwd":
				$mime = "image/x-xwindowdump";
				break;
			case "msh":
				$mime = "model/mesh";
				break;
			case "mesh":
				$mime = "model/mesh";
				break;
			case "silo":
				$mime = "model/mesh";
				break;
			case "wrl":
				$mime = "model/vrml";
				break;
			case "vrml":
				$mime = "model/vrml";
				break;
			case "css":
				$mime = "text/css";
				break;
			case "asc":
				$mime = "text/plain";
				break;
			case "txt":
				$mime = "text/plain";
				break;
			case "gpg":
				$mime = "text/plain";
				break;
			case "rtx":
				$mime = "text/richtext";
				break;
			case "rtf":
				$mime = "text/rtf";
				break;
			case "wml":
				$mime = "text/vnd.wap.wml";
				break;
			case "wmls":
				$mime = "text/vnd.wap.wmlscript";
				break;
			case "etx":
				$mime = "text/x-setext";
				break;
			case "xsl":
				$mime = "text/xml";
				break;
			case "flv":
				$mime = "video/x-flv";
				break;
			case "mpeg":
				$mime = "video/mpeg";
				break;
			case "mpg":
				$mime = "video/mpeg";
				break;
			case "mpe":
				$mime = "video/mpeg";
				break;
			case "qt":
				$mime = "video/quicktime";
				break;
			case "mov":
				$mime = "video/quicktime";
				break;
			case "mxu":
				$mime = "video/vnd.mpegurl";
				break;
			case "avi":
				$mime = "video/x-msvideo";
				break;
			case "movie":
				$mime = "video/x-sgi-movie";
				break;
			case "asf":
				$mime = "video/x-ms-asf";
				break;
			case "asx":
				$mime = "video/x-ms-asf";
				break;
			case "wm":
				$mime = "video/x-ms-wm";
				break;
			case "wmv":
				$mime = "video/x-ms-wmv";
				break;
			case "wvx":
				$mime = "video/x-ms-wvx";
				break;
			case "ice":
				$mime = "x-conference/x-cooltalk";
				break;
			case "rar":
				$mime = "application/x-rar";
				break;
			case "csv":
				$mime = "text/csv";
				break;
			case "odg":
				$mime = "application/vnd.oasis.opendocument.graphics";
				break;
			case "odp":
				$mime = "application/vnd.oasis.opendocum";
				break;
			case "ods":
				$mime = "application/vnd.oasis.opendocum";
				break;
			case "odt":
				$mime = "application/vnd.oasis.opendocum";
				break;
			default:
				$mime = "application/octet-stream";
				break;
		}

		return $mime;
	}

	/**
	 * Method to upload video file link
	 *
	 * @param   string  $uploadLink  post data
	 *
	 * @return	mixed
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function uploadLink($uploadLink)
	{
		$returnData = array();
		$regExpYoutube = "/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/";
		$regExpVimeo = "#(?:https?://player.vimeo.com/video|vimeo.com)/([0-9]+)#i";

		$originalFileName = $uploadLink['name'];

		if (preg_match($regExpYoutube, $uploadLink['name'], $match))
		{
			if (strpos($match[4], "embed"))
			{
				$uploadLink['name'] = $uploadLink['name'];
			}
			else
			{
				$uploadLink['name'] = 'https://www.youtube.com/embed/' . $match[5] . '?enablejsapi=1';
			}
		}
		elseif (preg_match($regExpVimeo, $uploadLink['name'], $match))
		{
			$uploadLink['name'] = 'https://player.vimeo.com/video/' . $match[1];
		}
		else
		{
			return false;
		}

		$returnData['path'] = $uploadLink['name'];

		// File original name
		$returnData['title'] = $uploadLink['name'];
		$returnData['original_filename'] = $originalFileName;
		$returnData['type'] = 'video.' . $uploadLink['type'];
		$returnData['source'] = $uploadLink['name'];
		$returnData['valid'] = 1;
		$returnData['size'] = '';
		$returnData['access'] = $this->access;
		$returnData['created_by'] = Factory::getUser()->id;
		$returnData['created_date'] = Factory::getDate()->toSql();
		$returnData['storage'] = $this->storage;
		$returnData['params'] = $this->params;

		JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
		$tjMediaTable = Table::getInstance('Files', 'TJMediaTable');

		if (!$tjMediaTable->save($returnData))
		{
			$this->setError(Text::_("LIB_TECHJOOMLA_MEDIA_ERROR_SAVING_FILE"));

			return false;
		}

		$returnData['id'] = $tjMediaTable->id;

		return $returnData;
	}

	/**
	 * Get the Mime type. Copied from libraries/src/Helper/MediaHelper.php
	 *
	 * @param   string   $file     The link to the file to be checked
	 * @param   boolean  $isImage  True if the passed file is an image else false
	 *
	 * @return  mixed    the mime type detected false on error
	 *
	 * @since   3.7.2
	 */
	public function getMimeType($file, $isImage = false)
	{
		// If we can't detect anything mime is false
		$mime = false;

		try
		{
			if ($isImage && function_exists('exif_imagetype'))
			{
				$mime = image_type_to_mime_type(exif_imagetype($file));
			}
			elseif ($isImage && function_exists('getimagesize'))
			{
				$imagesize = getimagesize($file);
				$mime      = isset($imagesize['mime']) ? $imagesize['mime'] : false;
			}
			elseif (function_exists('mime_content_type'))
			{
				// We have mime magic.
				$mime = mime_content_type($file);
			}
			elseif (function_exists('finfo_open'))
			{
				// We have fileinfo
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mime  = finfo_file($finfo, $file);
				finfo_close($finfo);
			}
		}
		catch (\Exception $e)
		{
			// If we have any kind of error here => false;
			return false;
		}

		// If we can't detect the mime try it again
		if ($mime === 'application/octet-stream' && $isImage === true)
		{
			$mime = $this->getMimeType($file, false);
		}

		// We have a mime here
		return $mime;
	}
}
