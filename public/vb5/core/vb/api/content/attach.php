<?php
if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
|| # ---------------------------------------------------------------- # ||
|| # Copyright ?2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
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
class vB_Api_Content_Attach extends vB_Api_Content
{
	protected $types;

	protected $extension_map;

	//override in client- the text name
	protected $contenttype = 'vBForum_Attach';

	//The table for the type-specific data.
	protected $tablename = 'attach';

	//Control whether this record will display on a channel page listing.
	protected $inlist = 0;

	//Image processing functions
	protected $imageHandler;

	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Content_Attach');

		$this->imageHandler = vB_Image::instance();
	}


	/*** This validates that a user can upload attachments. Currently that's just verifying that they are logged in.
	 *
	 *
	***/
	protected function checkPermission($userid)
	{
		if (!intval($userid))
		{
			if (isset($_FILES['tmp_name']))
			{
				@unlink($_FILES['tmp_name']);
			}

			throw new vB_Exception_Api('session_timed_out_login');
		}
	}


	/** fetch image information about an attachment
	 *
	 * 	@param 	int		node id
	 * 	@param	bool	thumbnail version requested?
	 * 	@param	bool	should we include the image content
	 *
	 *	@return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid

	 **/
	public function fetchImage($id, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		if (empty($id) OR !intval($id))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);
		$userContext = vB::getUserContext();

		if (!$userContext->getChannelPermission('forumpermissions', 'cangetattachment', $id))
		{
			$node = vB_Api::instanceInternal('node')->getNode($id);
			if ($node['userid'] != $userContext->fetchUserId())
			{
				throw new vB_Exception_Api('no_view_permissions');
			}
		}
		$attachdata = vB::getDbAssertor()->getRow('vBForum:attach', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'nodeid' => $id
		));

		//If the record belongs to this user, or if this user can view attachments
		//in this section, then this is O.K.

		if (!empty($attachdata) && $attachdata['filedataid'])
		{
			$params = array('filedataid' => $attachdata['filedataid'], 'type' => $type);
			$record = vB::getDbAssertor()->getRow('vBForum:getFiledataContent', $params);
		}

		if (empty($record))
		{
			return false;
		}
		return vB_Image::instance()->loadFileData($record, $type, true);
	}

	/**
	*
	*
	***/
	protected function saveUpload($filearray, $fileContents, $filesize, $extension, $imageOnly = false)
	{
		$userid = vB::getCurrentSession()->get('userid');

		return $this->library->saveUpload($userid, $filearray, $fileContents, $filesize, $extension, $imageOnly);
	}

	/** sets the main logo for a file
	 *
	 *	@param 	int		filedataid
	 *	@param 	string	which style (or styles) to update. 'current', 'default', or 'all'. see switch case in implementation for details.
	 *
	 *	@return	mixed	array of data, includes error message or an int- normally 1.
	 **/
	public function setLogo($filedataid, $styleselection = 'current')
	{
		$userContext = vB::getUserContext();

		if (!intval($filedataid))
		{
			throw new Exception('invalid_data');
		}

		$this->checkHasAdminPermission('canadminstyles');

		//validdate that the filedata record exists;
		$assertor = vB::getDbAssertor();
		$check = $assertor->getRow('filedata', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			'filedataid' => $filedataid));
		if (empty($check) OR !empty($check['errors']))
		{
			throw new Exception('invalid_data');
		}
		$styleVar = vB_Api::instanceInternal('Stylevar');
		$var = $styleVar->get("titleimage");

		$curLogoId = intval(substr($var['titleimage']['url'], strrpos($var['titleimage']['url'], '=')+1));
		if ($curLogoId > 0)
		{
			$assertor->assertQuery('decrementFiledataRefcount', array('filedataid' => $curLogoId));
		}

		$assertor->assertQuery('incrementFiledataRefcount', array('filedataid' => $filedataid));

		$userinfo = vB::getCurrentSession()->fetch_userinfo();
		$stylevarid = 'titleimage';
		$updateStyleids = array();
		$removeFromStyleids = array();
		switch ($styleselection)
		{
			case 'all':
				// Set the logo for all top level styles. Remove the titleimage stylevar for children of those styles.
				$styles = vB_Library::instance('Style')->fetchStyles(false, false);
				foreach($styles AS $style)
				{
					if ($style['parentid'] == -1)
					{
						$updateStyleids[] = $style['styleid'];
					}
					else
					{
						$removeFromStyleids[] = $style['styleid'];
					}
				}
				break;
			case 'default':
				// Set the logo for the default style.
				$updateStyleids = array(vB::getDatastore()->getOption('styleid'));
				break;
			case 'current':
			default:
				// Set the logo for the current style being used by the user.
				$currentStyleid = vB::getCurrentSession()->get('styleid');
				if (empty($currentStyleid) OR $currentStyleid < 1)
				{
					// In the event there is no styleid passed or we try to update the master,
					// update the user's selected style instead.
					$updateStyleids = array($userinfo['styleid']);
				}
				else
				{
					$updateStyleids = array($currentStyleid);
				}
				break;
		}
		foreach($updateStyleids AS $styleid)
		{
			//Can the stylecache from above be used for this? And can we just switch out the styleid for every style?
			$existing = $assertor->getRow('vBForum:stylevar', array(
				'styleid' => $styleid,
				'stylevarid' => $stylevarid
			));
			$dm = new vB_DataManager_StyleVarImage();
			if (!empty($existing))
			{
				$dm->set_existing(array(
					'styleid' => $styleid,
					'stylevarid' => $stylevarid
				));
			}
			else
			{
				$dm->set('styleid', $styleid);
				$dm->set('stylevarid', $stylevarid);
			}
			$value = array('url' => '/filedata/fetch?filedataid=' . $filedataid);
			$dm->set('value', $value);
			$dm->set('dateline',vB::getRequest()->getTimeNow());
			$dm->set('username', $userinfo['username']);
			$dm->save();

			if ($dm->has_errors(false))
			{
				throw $dm->get_exception();
			}
		}
		foreach($removeFromStyleids AS $styleid)
		{
			$assertor->delete('vBForum:stylevar', array('stylevarid' => $stylevarid, 'styleid' => $styleid));
		}

		vB_Library::instance('Style')->buildStyleDatastore();

		return true;

	}

	/** Uploads a file
	 *
	 * 	@param 	mixed	data from $_FILES
	 *
	 *	@return	mixed	array of data, which will include either error info or a filedataid
	 **/
	public function upload($file)
	{
		return $this->uploadAttachment($file);
	}

	/** Uploads a photo. Only use for images.
	 *
	 * 	@param 	mixed	data from $_FILES
	 *
	 *	@return	mixed	array of data, which will include either error info or a filedataid
	 **/
	public function uploadPhoto($file)
	{
		return $this->uploadAttachment($file, true, true);
	}

	/** Uploads a file without dimension check - to be cropped later. Only use for images.
	 *
	 * 	@param 	mixed	data from $_FILES
	 *
	 *	@return	mixed	array of data, which will include either error info or a filedataid
	 **/
	public function uploadProfilePicture($file)
	{
		return $this->uploadAttachment($file, false, true);
	}

	protected function uploadAttachment($file, $cheperms = true, $imageOnly = false)
	{
		//Only logged-in-users can upload files
		$userid = vB::getCurrentSession()->get('userid');
		$this->checkPermission($userid);

		return $this->library->uploadAttachment($userid, $file, $cheperms, $imageOnly);
	}

	/** Upload an image based on the url
	 *
	 * 	@param 	string	remote url
	 *  @param	bool	save as attachment
	 *
	 *	@return	mixed	array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 **/
	public function uploadUrl($url, $attachment = false, $uploadfrom = '')
	{
		//Only logged-in-users can upload files
		$userid = vB::getCurrentSession()->get('userid');
		$this->checkPermission($userid);

		return $this->library->uploadUrl($userid, $url, $attachment, $uploadfrom);
	}

	/** fetch image information about an attachment based on file data id
	 *
	 *      @param  int             filedataid
	 *      @param  bool    thumbnail version requested?
	 *      @param  bool    should we include the image content
	 *
	 *      @return mixed   array of data, includes filesize, dateline, htmltype, filename, extension, and filedataid
	 * */
	public function fetchImageByFiledataid($id, $type = vB_Api_Filedata::SIZE_FULL, $includeData = true)
	{
		if (empty($id) OR !intval($id))
		{
			throw new Exception('invalid_request');
		}

		$type = vB_Api::instanceInternal('filedata')->sanitizeFiletype($type);

		//If the record belongs to this user, or if this user can view attachments
		//in this section, then this is O.K.
		$userinfo = vB::getCurrentSession()->fetch_userinfo();

		$params = array('filedataid' => $id, 'type' => $type);
		$record = vB::getDbAssertor()->getRow('vBForum:getFiledataContent', $params);

		if (empty($record))
		{
			return false;
		}

		if (($userinfo['userid'] == $record['userid']) OR ($record['publicview'] > 0))
		{
			return vB_Image::instance()->loadFileData($record, $type, true);
		}
		throw new vB_Exception_Api('no_view_permissions');
	}

	/**
	 * Fetch information of attachments without data
	 *
	 * @param array $filedataids Array of file data ID
	 *
	 * @return array
	 */
	public function fetchAttachByFiledataids(array $filedataids)
	{
		$userContext = vB::getUserContext();

		$attachments = vB::getDbAssertor()->getRows('vBForum:fetchAttach2', array(
			'filedataid' => $filedataids
		), false, 'filedataid');

		foreach ($attachments as $k => $v)
		{
			// Permission check
			if (!$userContext->getChannelPermission('forumpermissions', 'cangetattachment', $v['nodeid']))
			{
				unset($attachments[$k]);
			}
		}
		return $attachments;
	}

	/** Remove an attachment
	* 	@param	INT	nodeid
	*
	**/
	public function delete($nodeid)
	{
		$data = array();

		if ($this->validate($data, $action = vB_Api_Content::ACTION_DELETE, $nodeid))
		{
			return $this->library->delete($nodeid);
		}
	}

	/** Remove an attachment
	 * 	@param	INT	nodeid
	 *
	 **/
	public function deleteAttachment($id)
	{
		if (empty($id) OR !intval($id))	{
			throw new Exception('invalid_request');
		}

		//Only the owner or an admin can delete an attachment.
		$userContext = vB::getUserContext();

		if (!$userContext->getChannelPermission('moderatorpermissions', 'canmoderateattachments', $id))
		{
			$node = vB_Library::instance('node')->getNodeBare($id);
			$userinfo = vB::getCurrentSession()->fetch_userinfo();

			if ($node['userid'] != $userinfo['userid'])
			{
				throw new vB_Exception_Api('no_permission');
			}
		}
		return $this->library->removeAttachment($id);
	}

	/**
	 *	See base class for information
	 */
	public function getIndexableFromNode($node, $include_attachments = true)
	{
		//deliberately don't call the parent class.  We don't want to load the content
		//twice and there isn't good way to get at the loaded content object.
		//merge in the attachments if any
		if($include_attachments)
		{
			$indexableContent = $this->getIndexableContentForAttachments($node['nodeid']);
		}
		else
		{
			$indexableContnet = array();
		}

		$indexableContent['title'] = $node['title'];
		$indexableContent['description'] = $node['description'];

		return $indexableContent;
	}

	/** Retrieves the permissions for the specified file type and upload method
	 *	@param array data:
	 *					uploadFrom *required
	 *					extension *required
	 *					channelid	optional	nodeid of channel which this attachment will be a descendant of
	 *	@param bool imageonly
	 *
	 *	@return array   $results
	 *
	 */
	public function getAttachmentPermissions($data)
	{
		//Leave for consistency with admincp
		if (!defined('ATTACH_AS_FILES_NEW'))
		{
			define('ATTACH_AS_FILES_NEW', 2);
		}

		//Only logged-in-users can upload files
		$userid = vB::getCurrentSession()->get('userid');
		$this->checkPermission($userid);
		$uploadFrom = !empty($data['uploadFrom']) ? $data['uploadFrom'] : null;
		$usercontext = vB::getUserContext();
		$totalLimit = intval($usercontext->getUsergroupLimit('attachlimit'));
		$options = vB::getDatastore()->getValue('options');

		// Check if we are not exceeding the quota
		if ($options['attachtotalspace'] > 0)
		{
			if ($totalLimit > 0)
			{
				$totalLimit = min($totalLimit, $options['attachtotalspace']);
			}
			else
			{
				$totalLimit = $options['attachtotalspace'];
			}
		}

		//check to see if this user has their limit already.
		if ($totalLimit > 0)
		{
			$usedSpace = intval(vB::getDbAssertor()->getField('vBForum:getUserFiledataFilesizeSum', array('userid' => $userid)));

			if ($usedSpace > $totalLimit)
			{
				return array('errors' => vB_Phrase::fetchSinglePhrase('upload_attachfull_user', $usedSpace - $totalLimit));
			}
			$spaceAvailable = $totalLimit - $usedSpace;
		}
		else
		{
			$spaceAvailable = false;
		}

		$result = array();

		// Usergroup permissions
		if ($uploadFrom === 'profile')
		{
			$usergroupattachlimit = $usercontext->getLimit('attachlimit');
			$albumpicmaxheight = $usercontext->getLimit('albumpicmaxheight');
			$albumpicmaxwidth = $usercontext->getLimit('albumpicmaxwidth');
			$result['max_size'] = $usergroupattachlimit;
			$result['max_height'] = $albumpicmaxheight;
			$result['max_width'] = $albumpicmaxwidth;

			if ($spaceAvailable !== false)
			{
				$result['max_size'] = min($result['max_size'], $spaceAvailable);
				$result['attachlimit'] = $totalLimit;
			}
		}
		// Default to attachment permissions
		else
		{
			$extension = !empty($data['extension']) ? $data['extension'] : null;
			if ($extension != null)
			{
				// Fetch the parent channel or topic just in case we need to check group in topic.
				// The actual parent may not exist since we may be creating a new post/topic.
				$nodeid = (!empty($data['parentid'])) ? intval($data['parentid']) : false;
				$attachPerms = $usercontext->getAttachmentPermissions($extension, $nodeid);
				if ($attachPerms !== false)
				{
					$result['max_size'] = $attachPerms['size'];
					$result['max_height'] = $attachPerms['width'];
					$result['max_width'] = $attachPerms['height'];

					if ($spaceAvailable !== false)
					{
						$result['max_size'] = min($result['max_size'], $spaceAvailable);
						$result['attachlimit'] = $totalLimit;
					}
				}
				else
				{
					$result['errors'][] = 'invalid_file';
				}
			}
			else
			{
				$result['errors'][] = 'invalid_file';
			}
		}

		return $result;
	}

	/*** validates that the current can create a node with these values
	 *
	 *	@param	mixed		Array of field => value pairs which define the record.
	 *	@param	action		Parameters to be checked for permission
	 *
	 * 	@return	bool
	 ***/
	public function validate(&$data, $action = self::ACTION_ADD, $nodeid = false, $nodes = false)
	{
		if (parent::validate($data, $action, $nodeid, $nodes) == false)
		{
			return false;
		}

		$userContext = vB::getUserContext();
		switch ($action)
		{
			case vB_Api_Content::ACTION_ADD:
				if (empty($data['filedataid']))
				{
					return false;
				}
				break;
		}

		return true;
	}

	/** Does basic input cleaning for input data
	 	@param	mixed	array of fieldname => data pairs

	 	@return	mixed	the same data after cleaning.
	 */
	public function cleanInput(&$data, $nodeid = false)
	{
		parent::cleanInput($data, $nodeid);

		$data['filedataid'] = intval($data['filedataid']);

		$cleaner = vB::getCleaner();
		$data['filename'] = $cleaner->clean($data['filename'], vB_Cleaner::TYPE_NOHTML);
	}
}
