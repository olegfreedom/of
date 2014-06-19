<?php
if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
   || #################################################################### ||
   || # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
   || # ---------------------------------------------------------------- # ||
   || # Copyright �2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
   || # This file may not be redistributed in whole or significant part. # ||
   || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
   || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
   || #################################################################### ||
   \*======================================================================*/


/**
 * vB_Api_Content_Attach
 *
 * @package vBApi
 * @access public
 */
class vB_Library_Content_Attach extends vB_Library_Content
{
	protected $types;

	protected $extension_map;

	//override in client- the text name
	protected $contenttype = 'vBForum_Attach';

	//The table for the type-specific data.
	protected $tablename = 'attach';

	//list of fields that are included in the index
	protected $index_fields = array('description');

	//Control whether this record will display on a channel page listing.
	protected $inlist = 0;

	//Image processing functions
	protected $imageHandler;

	protected function __construct()
	{
		parent::__construct();
		$this->imageHandler = vB_Image::instance();
	}

	/*** Adds a new node.
	 *
	 *	@param	mixed		Array of field => value pairs which define the record.
	 *  @param	array		Array of options for the content being created.
	 *						Available options include:
	 *
	 * 	@return	integer		the new nodeid
	 ***/
	public function add($data, array $options = array())
	{
		//todo -- lock the caption to the description until we collapse the fields.  Remove when caption goes away
		if (isset($data['caption']))
		{
			$data['description'] = $data['caption'];
		}
		else if (isset($data['description']))
		{
			$data['caption'] = $data['description'];
		}

		try
		{
			$this->assertor->beginTransaction();
			$options['skipTransaction'] = true;
			$result = parent::add($data, $options);

			if ($result)
			{
				$this->assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'nodeid' => $data['parentid'], 'hasphoto' => 1 ));
			}
			$this->assertor->commitTransaction();
		}
		catch(exception $e)
		{
			$this->assertor->rollbackTransaction();
			throw $e;
		}
		return $result;
	}


	/** Remove an attachment
	 * 	@param	INT	nodeid
	 *
	 **/
	public function delete($nodeid)
	{
		//We need the parent id. After deletion we may need to set hasphoto = 0;
		$existing =	$this->nodeApi->getNode($nodeid);
		$this->removeAttachment($nodeid);
		parent::delete($nodeid);
		$photo = $this->assertor->getRow('vBForum:node', array('contenttypeid' => $this->contenttypeid, 'parentid' => $existing['parentid']));

		//If we got empty or error, there are no longer any attachments.
		if (!empty($existing['parentid']) AND (empty($photo) OR !empty($photo['errors'])))
		{
			$this->assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'hasphoto' => 0, vB_dB_Query::CONDITIONS_KEY => array(
				array(
						'field' => 'nodeid',
						'value' => $existing['parentid'],
						'operator' => vB_dB_Query::OPERATOR_EQ
					))));
		}
		$this->nodeApi->clearCacheEvents(array($nodeid, $existing['parentid']));
	}
	
	/**
	 * Delete the records without updating the parent info. It is used when deleting a whole channel and it's children need to be removed
	 * @param array $childrenIds - list of node ids
	 */
	public function deleteChildren($childrenIds)
	{
		//existing attach data 
		$attachdata = vB::getDbAssertor()->getColumn('vBForum:attach', 'filedataid', array('nodeid' => $childrenIds), false, 'nodeid');
		//the number of times an attachment is used in the list of nodes
		$refcounts = array_count_values($attachdata);
		//the individual existing filedata records
		$filedata = vB::getDbAssertor()->getColumn('filedata', 'refcount', array('filedataid' => array_keys($refcounts)), false, 'filedataid');
		foreach ($filedata as $filedataid => $nr)
		{
			//the new value of the existing refcount
			$refCount = max($nr - $refcounts[$filedataid], 0);
			$this->assertor->update("vBForum:filedata", array('refcount' => $refCount), array('filedataid' => $filedataid));
		}
		
		//delete the main tables
		parent::deleteChildren($childrenIds);
	}
	
	/*** updates a record
	 *
	 *	@param	mixed		array of nodeid's
	 *	@param	mixed		array of permissions that should be checked.
	 *
	 * 	@return	boolean
	 ***/
	public function update($nodeid, $data)
	{
		$existing = $this->assertor->getRow('vBForum:attach', array('nodeid' => $nodeid));

		//todo -- lock the caption to the description until we collapse the fields.  Remove when caption goes away
		if (isset($data['caption']))
		{
			$data['description'] = $data['caption'];
		}
		else if (isset($data['description']))
		{
			$data['caption'] = $data['description'];
		}

		if (parent::update($nodeid, $data))
		{
			//We need to update the filedata ref counts
			if (!empty($data['filedataid']) AND ($existing['filedataid'] != $data['filedataid']))
			{
				//Remove the existing
				$filedata = vB::getDbAssertor()->getRow('filedata', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'filedataid' => $existing['filedataid']
				));

				if ($filedata['refcount'] > 1)
				{
					$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'filedataid' => $existing['filedataid'],
					'refcount' => $filedata['refcount'] - 1);
				}
				else
				{
					$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
					'filedataid' => $existing['filedataid']);
					$this->assertor->assertQuery('vBForum:filedataresize', $params);
				}

				$this->assertor->assertQuery('filedata', $params);

				//add the new
				$filedata = vB::getDbAssertor()->getRow('filedata', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
						'filedataid' => $data['filedataid']
				));

				if (!empty($filedata) AND empty($filedata['errors']))
				{
					$params = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'filedataid' => $data['filedataid'],
					'refcount' => $filedata['refcount'] + 1);

					$this->assertor->assertQuery('filedata', $params);
				}
			}
		}
		$this->nodeApi->clearCacheEvents(array($nodeid, $existing['parentid']));
	}

	/**
	 *	See base class for information
	 */
	public function getIndexableFromNode($node, $include_attachments = true)
	{
		$indexableContent = parent::getIndexableFromNode($node, $include_attachments);

		if (!empty($node['description']))
		{
			$indexableContent['description'] = $node['description'];
		}

		return $indexableContent;
	}


	/** Remove an attachment
	 * 	@param	INT	nodeid
	 *
	 **/
	public function removeAttachment($id)
	{
		if (empty($id) OR !intval($id))	{
			throw new Exception('invalid_request');
		}

		$attachdata = vB::getDbAssertor()->getRow('vBForum:attach', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'nodeid' => $id
			));

		if (!empty($attachdata) AND $attachdata['filedataid'])
		{
			$filedata = vB::getDbAssertor()->getRow('filedata', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'filedataid' => $attachdata['filedataid']
			));

			if ($filedata['refcount'] > 1)
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'filedataid' => $attachdata['filedataid'],
				'refcount' => $filedata['refcount'] - 1);
			}
			else
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'filedataid' => $attachdata['filedataid']);
				vB::getDbAssertor()->assertQuery('vBForum:filedataresize', $data);
			}

			vB::getDbAssertor()->assertQuery('vBForum:filedata', $data);
		}

		return true;
	}

	public function removeSignaturePicture($userid)
	{
		$sigpic = vB::getDbAssertor()->getRow('vBForum:sigpicnew', array('userid' => intval($userid)));

		if (empty($sigpic))
		{
			return;
		}

		vB::getDbAssertor()->delete('vBForum:sigpicnew', array('userid' => intval($userid)));

		if ($sigpic['filedataid'])
		{
			$filedata = vB::getDbAssertor()->getRow('filedata', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
					'filedataid' => $sigpic['filedataid']
			));

			if ($filedata['refcount'] > 1)
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				'filedataid' => $sigpic['filedataid'],
				'refcount' => $filedata['refcount'] - 1);
			}
			else
			{
				$data = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE,
				'filedataid' => $sigpic['filedataid']);
				vB::getDbAssertor()->assertQuery('vBForum:filedataresize', $data);
			}

			vB::getDbAssertor()->assertQuery('vBForum:filedata', $data);
		}
	}

	/** Get attachments for a content type
	 * 	@param	INT	nodeid
	 *
	 **/
	public function getAttachmentsFromType($typeid)
	{
		$attachdata = vB::getDbAssertor()->getRows('attachmentsByContentType', array('ctypeid' => $typeid));

		return $attachdata;
	}

	/** Remove all attachments for content type
	 * 	@param	INT	Content Type id
	 *
	 **/
	public function zapAttachmentType($typeid)
	{
		$list = $this->getAttachmentsFromType($typeid);

		foreach($list AS $attachment)
		{
			$this->removeAttachment($attachment['attachmentid']);
		}
	}

	/** Get array of http headers for this attachment file extension
	 * 	@param	STRING	file extension, e.g. 'pdf'
	 *
	 **/
	public function getAttachmentHeaders($extension)
	{
		$headers = array('Content-type: application/octet-stream');
		if (!empty($extension))
		{
			$attach_meta = vB::getDbAssertor()->getRows('vBForum:fetchAttachPermsByExtension', array('extension' => $extension));
			if (!empty($attach_meta) AND !empty($attach_meta[0]['mimetype']))
			{
				$headers = unserialize($attach_meta[0]['mimetype']);
			}
		}
		return $headers;
	}

	public function uploadAttachment($userid, $file, $cheperms = true, $imageOnly = false)
	{
		//Leave for consistency with admincp
		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}
		$uploadFrom = '';
		//We can get either an uploaded file or an object. If we have an object let's make it into an array.

		if (is_object($file) AND isset($file->name))
		{
			$filearray = array('name' => $file->name, 'size' => $file->size,'type' => $file->type);
			$filebits = explode('.', $file->name);
			$extension = end($filebits);
			if (isset($file->contents) AND !empty($file->contents))
			{
				$filesize = strlen ($file->contents);
				$tempdir = sys_get_temp_dir();
				$filename = $tempdir . '/' . time() . $filesize . '.' . $extension;
				file_put_contents($filename, $file->contents);
				$filearray['tmp_name'] = $filename;
				$fileContents = $file->contents;
				list($width, $height) = getimagesize($filename);
				$filewidth = $width;
				$fileheight = $height;

				if (!empty($file->uploadfrom))
				{
					$uploadFrom = $file->uploadfrom;
				}

				if (!empty($file->parentid))
				{
					$parentid = $file->parentid;
					$filearray['parentid'] = $file->parentid;
				}
			}
		}
		else
		{

			if (!file_exists($file['tmp_name']))
			{
				// Encountered PHP upload error
				if (!($maxupload = @ini_get('upload_max_filesize')))
				{
					$maxupload = 10485760;
				}
				$maxattachsize = vb_number_format($maxupload, 1, true);

				switch($file['error'])
				{
					case '1': // UPLOAD_ERR_INI_SIZE
					case '2': // UPLOAD_ERR_FORM_SIZE
						throw new vB_Exception_Api('upload_file_exceeds_php_limit', $maxattachsize);
						break;
					case '3': // UPLOAD_ERR_PARTIAL
						throw new vB_Exception_Api('upload_file_partially_uploaded');
						break;
					case '4':
						throw new vB_Exception_Api('upload_file_failed');
						break;
					case '6':
						throw new vB_Exception_Api('missing_temporary_folder');
						break;
					case '7':
						throw new vB_Exception_Api('upload_writefile_failed');
						break;
					case '8':
						throw new vB_Exception_Api('upload_stopped_by_extension');
						break;
					default:
						throw new Exception('Upload failed. PHP upload error: ' . intval($file['error']));
				}
			}
			$filearray = $file;
			$filebits = explode('.', $file['name']);
			$extension = end($filebits);
			$filesize = filesize($file['tmp_name']);
			$fileContents = file_get_contents($file['tmp_name']);
			list($width, $height) = getimagesize($file['tmp_name']);
			$filewidth = $width;
			$fileheight = $height;

			if (!empty($file['uploadFrom']))
			{
				$uploadFrom = $file['uploadFrom'];
				unset($file['uploadFrom']);
			}

			if (!empty($file['parentid']))
			{
				$parentid = intval($file['parentid']);
			}
		}
		//make sure there's a valid file here
		if (empty($fileContents))
		{
			throw new vB_Exception_Api('invalid_file_data');
		}

		if (empty($uploadFrom))
		{
			$uploadFrom = 'newContent';
		}

		if (empty($parentid))
		{
			$parentid = false;
		}

		//check the usergroup permission for total space.
		$usergroupattachlimit = vB::getUserContext()->getUsergroupLimit('attachlimit');

		if ($usergroupattachlimit > 0 AND  ($filesize > $usergroupattachlimit))
		{
			throw new vB_Exception_Api('upload_file_exceeds_limit', array(
				$filesize, $usergroupattachlimit
			));
		}


		// Usergroup permissions
		if ($uploadFrom === 'profile')
		{
			$usercontext = vB::getUserContext();

			if ($cheperms)
			{
				$albumpicmaxheight = $usercontext->getLimit('albumpicmaxheight');
				$albumpicmaxwidth = $usercontext->getLimit('albumpicmaxwidth');


				if (($albumpicmaxwidth > 0 AND $filewidth > $albumpicmaxwidth) OR ($albumpicmaxheight > 0 AND $fileheight > $albumpicmaxheight))
				{
					throw new vB_Exception_Api('upload_exceeds_dimensions', array(
						$albumpicmaxwidth, $albumpicmaxheight, $filewidth, $fileheight
					));
				}
			}
		}

		// Channel icon permissions
		if ($uploadFrom === 'sgicon')
		{
			vB_Api::instanceInternal('content_channel')->validateIcon($parentid, array('filedata' => $fileContents, 'filesize' => $filesize));
		}

		// Signature picture
		if ($uploadFrom === 'signature')
		{
			$usercontext = vB::getUserContext();
			// Check if user has permission to upload signature picture
			if (!$usercontext->hasPermission('signaturepermissions', 'cansigpic'))
			{
				throw new vB_Exception_Api('no_permission');
			}

			$imageOnly = true;
			$filearray['is_sigpic'] = 1;
		}

		// Attachment permissions
		if ($cheperms AND $uploadFrom === 'newContent')
		{
			$results = vB_Api::instanceInternal('content_attach')->getAttachmentPermissions(array(
				'uploadFrom' => $uploadFrom,
				'extension' => $extension,
				'parentid' => $parentid,
			));

			if (empty($results['errors']))
			{
				if (($results['max_size'] > 0) AND ($filesize > $results['max_size']))
				{
					throw new vB_Exception_Api('upload_file_exceeds_limit', array(
						$filesize, $results['max_size']
					));
				}
				if (($results['max_width'] > 0 AND $filewidth > $results['max_width']) OR ($results['max_height'] > 0 AND $fileheight > $results['max_height']))
				{
					throw new vB_Exception_Api('upload_exceeds_dimensions', array(
						$results['max_width'], $results['max_height'], $filewidth, $fileheight
					));
				}
			}
			else
			{
				throw new vB_Exception_Api('invalid_file');
			}
		}

		$result = $this->saveUpload($userid, $filearray, $fileContents, $filesize, $extension, $imageOnly);

		if (file_exists($filearray['tmp_name']))
		{
			@unlink($filearray['tmp_name']);
		}
		return $result;
	}


	/** Upload an image based on the url
	 *
	 *  @param  int     user ID
	 * 	@param 	string	remote url
	 *  @param	bool	save as attachment
	 *
	 *	@return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 **/
	public function uploadUrl($userid, $url, $attachment = false, $uploadfrom = '')
	{
		//Leave for consistency with admincp
		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}

		//Did we get a valid url?
		if (empty($url))
		{
			throw new vB_Exception_Api('upload_invalid_url');
		}

		$fileContents = @file_get_contents($url);
		if (empty($fileContents))
		{
			throw new vB_Exception_Api('upload_invalid_url');
		}

		$pathinfo = pathinfo($url);
		if (empty($pathinfo))
		{
			throw new vB_Exception_Api('upload_invalid_url');
		}

		// if there's no extension here try get one from imageHandler
		if (!$pathinfo['extension'])
		{
			$extension = $this->imageHandler->fetchImageExtension($url);
			$name = $pathinfo['basename'] . '.' . $extension;
		}
		else
		{
			$extension = $pathinfo['extension'];
			$name = $pathinfo['basename'];
		}

		$tempdir = sys_get_temp_dir();
		$filename = $tempdir . '/' . time() . $extension;
		file_put_contents($filename, $fileContents);
		$filesize = strlen($fileContents);
		$extension_map = $this->imageHandler->getExtensionMap();

		//Make a local copy
		$filearray = array(
			'name'     => $name,
			'size'     => $filesize,
			'type'     => 'image/' . $extension_map[$extension],
			'tmp_name' => $filename
		);

		if (!empty($uploadfrom))
		{
			$filearray['uploadFrom'] = $uploadfrom;
		}

		if ($attachment)
		{
			return $this->uploadAttachment($userid, $filearray);
		}

		$result = $this->saveUpload($userid, $filearray, $fileContents, $filesize, $extension, true);

		if (file_exists($filearray['tmp_name']))
		{
			@unlink($filearray['tmp_name']);
		}

		return $result;
	}

	public function saveUpload($userid, $filearray, $fileContents, $filesize, $extension, $imageOnly = false)
	{
		$assertor = vB::getDbAssertor();
		$datastore = vB::getDatastore();
		$options = $datastore->getValue('options');
		$config = vB::getConfig();
		$usercontext = vB::getUserContext($userid);

		//make sure there's a place to put attachments.
		if ($options['attachfile'] AND
			(empty($options['attachpath']) OR !file_exists($options['attachpath']) OR !is_writable($options['attachpath']) OR !is_dir($options['attachpath'])))
		{
			throw new vB_Exception_Api('invalid_attachment_storage');
		}

		//make sure the file is good.
		if (! $this->imageHandler->verifyImageFile($fileContents, $filearray['tmp_name']))
		{
			@unlink($filearray['tmp_name']);
			throw new vB_Exception_Api('dangerous_image_rejected');
		}

		// Check if this is an image extension we're dealing with for displaying later.
		// exif_imagetype() will check the validity of image
		$isImage = $this->imageHandler->isValidInfoExtension($extension);
		if ($isImage AND function_exists('exif_imagetype'))
		{
			$imageType = @exif_imagetype($filearray['tmp_name']);
			$isImage = (bool)$imageType;
		}

		//We check to see if this file already exists.
		$filehash = md5($fileContents);

		$fileCheck = $assertor->getRow('vBForum:getFiledataWithThumb', array(
			'filehash' => $filehash,
			'filesize' => $filesize
		));

		// Does filedata already exist?
		if (empty($fileCheck) OR ($fileCheck['userid'] != $userid))
		{
			// Check if we are not exceeding the quota
			if ($options['attachtotalspace'] > 0)
			{
				$usedSpace = $assertor->getField('vBForum:getUserFiledataFilesizeSum', array('userid' => $userid));

				$overage = $usedSpace + $filesize - $options['attachtotalspace'];
				if ($overage > 0)
				{
					$overage = vb_number_format($overage, 1, true);
					$userinfo = vB::getCurrentSession()->fetch_userinfo();

					$maildata = vB_Api::instanceInternal('phrase')->
							fetchEmailPhrases('attachfull', array($userinfo['username'], $options['attachtotalspace'], $options['bburl'], $config['Misc']['admincpdir']), array($options['bbtitle']), 0);
					vB_Mail::vbmail($options['webmasteremail'], $maildata['subject'], $maildata['message']);

					throw new vB_Exception_Api('upload_attachfull_total', $overage);
				}
			}

			if (!$usercontext->canUpload($filesize, $extension, (!empty($filearray['parentid'])) ? $filearray['parentid'] : false))
			{
				@unlink($filearray['tmp_name']);
				throw new vB_Exception_Api('cannot_create_file');
			}

			if ($imageOnly AND !$isImage)
			{
				throw new vB_Exception_Api('upload_invalid_image');
			}

			//Get the image size information.
			$imageInfo = $this->imageHandler->fetchImageInfo($filearray['tmp_name']);

			$timenow =  vB::getRequest()->getTimeNow();

			if ($isImage)
			{
				$sizes = @unserialize($options['attachresizes']);
				if (!isset($sizes['thumb']) OR empty($sizes['thumb']))
				{
					$sizes['thumb'] = 100;
				}
				$thumbnail = $this->imageHandler->fetchThumbnail(
					$filearray['name'],
					$filearray['tmp_name'],
					$sizes['thumb'],
					$sizes['thumb'],
					$options['thumbquality']
				);
			}
			else
			{
				$thumbnail = array('filesize' => 0, 'width' => 0, 'height' => 0, 'filedata' => null);
			}

			$thumbnail_data = array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'resize_type'     => 'thumb',
				'resize_dateline' => $timenow,
				'resize_filesize' => $thumbnail['filesize'],
				'resize_width'    => $thumbnail['width'],
				'resize_height'   => $thumbnail['height'],
			);

			$data = array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'userid'    => $userid,
				'dateline'  => $timenow,
				'filesize'  => $filesize,
				'filehash'  => $filehash,
				'extension' => $extension,
				'refcount'  => 0,
			);
			if ($imageInfo)
			{
				$data['width'] = $imageInfo[0];
				$data['height'] = $imageInfo[1];
			}

			//Looks like we're ready to store. But do we put it in the database or the filesystem?
			if ($options['attachfile'])
			{
				//We name the files based on the filedata record, but we don't have that until we create the record. So we need
				// to do an insert, then create/move the files.
				$filedataid = $assertor->assertQuery('filedata', $data);

				if (is_array($filedataid))
				{
					$filedataid = $filedataid[0];
				}

				if (!intval($filedataid))
				{
					throw new vB_Exception_Api('unable_to_add_filedata');
				}

				$path = $this->verifyAttachmentPath($userid);
				if (!$path)
				{
					throw new vB_Exception_Api('attachpathfailed');
				}

				if (!is_writable($path))
				{
					throw new vB_Exception_Api('upload_file_system_is_not_writable_path', array(htmlspecialchars($path)));
				}

				if (!empty($thumbnail['filedata']))
				{
					file_put_contents($path . $filedataid . '.thumb', $thumbnail['filedata']);
				}
				rename($filearray['tmp_name'] , $path . $filedataid . '.attach');
			}
			else
			{
				//We put the file contents into the data record.
				$data['filedata'] = $fileContents;
				$filedataid = $assertor->assertQuery('filedata', $data);

				if (is_array($filedataid))
				{
					$filedataid = $filedataid[0];
				}
				$thumbnail_data['resize_filedata'] = $thumbnail['filedata'];
			}

			$thumbnail_data['filedataid'] = $filedataid;
			if ($isImage)
			{
				$assertor->assertQuery('vBForum:filedataresize', $thumbnail_data);
			}

			if (!empty( $filearray['name']))
			{
				 $filename = $filearray['name'];
			}
			else
			{
				$filename = '';
			}

			$result = array(
				'filedataid' => $filedataid,
				'filesize'   => $filesize,
				'thumbsize'  => $thumbnail['filesize'],
				'extension'  => $extension,
				'filename'   => $filename,
				'headers'    => $this->getAttachmentHeaders(strtolower($extension)),
				'isimage'    => $isImage,
			);

			if (!empty($filearray['is_sigpic']))
			{
				$assertor->assertQuery('replaceSigpic', array('userid' => $userid, 'filedataid' => $filedataid));
				$assertor->assertQuery('incrementFiledataRefcount', array('filedataid' => $filedataid));
			}
		}
		else
		{
			// file already exists so we are not going to insert a new one
			$filedataid = $fileCheck['filedataid'];

			if (!empty($filearray['is_sigpic']))
			{
				// Get old signature picture data and decrease refcount
				$oldfiledata = vB::getDbAssertor()->getRow('vBForum:sigpicnew', array('userid' => $userid));
				if ($oldfiledata)
				{
					vB::getDbAssertor()->assertQuery('decrementFiledataRefcount', array('filedataid' => $oldfiledata['filedataid']));
				}

				$assertor->assertQuery('replaceSigpic', array('userid' => $fileCheck['userid'], 'filedataid' => $filedataid));
				$assertor->assertQuery('incrementFiledataRefcount', array('filedataid' => $filedataid));
			}

			$result = array(
				'filedataid' => $filedataid,
				'filesize'   => $fileCheck['filesize'] ,
				'thumbsize'  => $fileCheck['resize_filesize'],
				'extension'  => $extension,
				'filename'   => $filearray['name'],
				'headers'    => $this->getAttachmentHeaders(strtolower($extension)),
				'isimage'    => $isImage,
			);
		}

		return $result;
	}

	protected function verifyAttachmentPath($userid)
	{
		// Allow userid to be 0 since vB2 allowed guests to post attachments
		$userid = intval($userid);

		$path = $this->fetchAttachmentPath($userid);
		if (vB_Library_Functions::vbMkdir($path))
		{
			return $path;
		}
		else
		{
			return false;
		}
	}

	protected function fetchAttachmentPath($userid, $attachmentid = 0, $thumb = false, $overridepath = '')
	{
		$options =  vB::getDatastore()->get_value('options');
		$attachpath = !empty($overridepath) ? $overridepath : $options['attachpath'];

		if ($options['attachfile'] == ATTACH_AS_FILES_NEW) // expanded paths
		{
			$path = $attachpath . '/' . implode('/', preg_split('//', $userid,  -1, PREG_SPLIT_NO_EMPTY)) . '/';
		}
		else
		{
			$path = $attachpath . '/' . $userid . '/';
		}

		if ($attachmentid)
		{
			if ($thumb)
			{
				$path .= '/' . $attachmentid . '.thumb';
			}
			else
			{
				$path .= '/' . $attachmentid . '.attach';
			}
		}

		return $path;
	}
}
