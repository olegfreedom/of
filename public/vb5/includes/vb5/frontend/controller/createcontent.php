<?php

class vB5_Frontend_Controller_CreateContent extends vB5_Frontend_Controller
{
	function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		$input = array(
			'title' => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text' => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'nodeid' => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'parentid' => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid' => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'ret' => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'tags' => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'reason' => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
			'iconid' => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid' => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput' => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'enable_comments' => (isset($_POST['enable_comments']) ? (bool)$_POST['enable_comments'] : false), // Used only when entering blog posts
			'subtype' => (isset($_POST['subtype']) ? trim(strval($_POST['subtype'])) : ''),
		);

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}

		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		//@TODO: There is no title for posting a reply or comment but api throws an error if blank. Fix this.

		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		$time = vB5_Request::get('timeNow');
		$tagRet = false;

		if ($input['nodeid'])
		{
			$result = array();
			if ($user['userid'] < 1)
			{
				$result['error'] = 'logged_out_while_editing_post';
				$this->sendAsJson($result);
				exit;
			}

			$textData = array(
				'title'           => $input['title'],
				'parentid'        => $input['parentid'],
				'rawtext'         => ($input['subtype'] == 'comment') ? nl2br($input['text']) : $input['text'],
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'reason'          => $input['reason'], //@TODO
				'enable_comments' => $input['enable_comments'],
			);

			$options = array();

			// We need to convert WYSIWYG html here and run the img check
			if (isset($textData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($textData['rawtext'], $options));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}
			$updateRet = $api->callApi('content_text', 'update', array($input['nodeid'], $textData, $options));
			$this->handleErrorsForAjax($result, $updateRet);

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));
			$this->handleErrorsForAjax($result, $tagRet);

			// add attachments
			$this->handleAttachmentUploads($input['nodeid'], $result);

			$this->sendAsJson($result);
		}
		else
		{
			$result = array();
			$textData = array(
				'title' => $input['title'],
				'parentid' => $input['parentid'],
				'rawtext' => $input['text'],
				'userid' => $user['userid'],
				'authorname' => $user['username'],
				'created' => $time,
				'iconid' => $input['iconid'],
				'prefixid' => $input['prefixid'],
				'publishdate' => $api->callApi('content_text', 'getTimeNow', array()),
				'hvinput' => $input['hvinput'],
				'enable_comments' => $input['enable_comments'],
			);

			if (!empty($_POST['setfor']))
			{
				$textData['setfor'] = intval($_POST['setfor']);
			}

			$options = array(
				'facebook' => $this->getFacebookOptionsForAddNode(),
			);

			// We need to convert WYSIWYG html here and run the img check
			if (isset($textData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($textData['rawtext'], $options));
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$nodeId = $api->callApi('content_text', 'add', array($textData, $options));
			$this->handleErrorsForAjax($result, $nodeId);

			if (!is_int($nodeId) OR $nodeId < 1)
			{
				$this->handleErrorsForAjax($result, $nodeId);
				$this->sendAsJson($result);
				exit();
				/*
				if (!empty($nodeId['errors']) AND in_array('postfloodcheck', $nodeId['errors'][0]))
				{
					$message = vB5_Template_Phrase::instance()->getPhrase('searchfloodcheck', $nodeId['errors'][0][1], $nodeId['errors'][0][2]);

				}
				else
				{
					// @todo: catch this problem more gracefully.
					// DO NOT remove this exception unless you are adding code to
					// actually handle the problem and display a user-friendly error
					// We do not want to "hide" problems when content cannot be created
					$message ="Node cannot be created";
					$config = vB5_Config::instance();

					if ($config->debug)
					{
						$message .= "<br />\n" . var_export($nodeId, true);
					}
				}

				throw new Exception($message);
				*/
			}

			if (!empty($input['tags']))
			{
				$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
				$this->handleErrorsForAjax($result, $tagRet);
			}
			// add attachments
			$this->handleAttachmentUploads($nodeId, $result);

			$node = $api->callApi('node', 'getNode', array($nodeId));
			if ($node AND empty($node['errors']))
			{
				if (empty($node['approved']))
				{
					$result['moderateNode'] = true;
				}
			}

			$this->getReturnUrl($result, $input['channelid'], $input['parentid'], $nodeId);
			$result['nodeId'] = $nodeId;

			$this->sendAsJson($result);
		}
		exit;
	}

	function actionPoll()
	{
		$api = Api_InterfaceAbstract::instance();
		$offset = $api->callApi('user', 'fetchTimeOffset', array());

		$input = array(
			'title'           => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'            => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'polloptions'     => (array)$_POST['polloptions'],
			'parentid'        => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'nodeid'          => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'ret'             => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'timeout'         => ((isset($_POST['timeout']) AND !empty($_POST['timeout'])) ? intval(strtotime(trim(strval($_POST['timeout'])))) - $offset : 0),
			'multiple'        => (isset($_POST['multiple'])? (boolean)$_POST['multiple'] : false),
			'public'          => (isset($_POST['public'])? (boolean)$_POST['public'] : false),
			'parseurl'        => (isset($_POST['parseurl']) ? (boolean)$_POST['parseurl'] : false),
			'tags'            => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'enable_comments' => (isset($_POST['enable_comments']) ? (bool)$_POST['enable_comments'] : false), // Used only when entering blog posts
			'reason'          => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
		);
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		// Poll Options
		$polloptions = array();
		foreach ($input['polloptions'] as $k => $v)
		{
			if ($v)
			{
				if ($k == 'new')
				{
					foreach ($v as $v2)
					{
						$v2 = trim(strval($v2));
						if ($v2 !== '')
						{
							$polloptions[]['title'] = $v2;
						}
					}
				}
				else
				{
					$polloptions[] = array(
						'polloptionid' => intval($k),
						'title' => trim($v),
					);
				}
			}
		}

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		if ($input['nodeid'])
		{
			$pollData = array(
				'title'           => $input['title'],
				'rawtext'         => $input['text'],
				'parentid'        => $input['parentid'],
//				'userid'          => $user['userid'],
				'options'         => $polloptions,
				'multiple'        => $input['multiple'],
				'public'          => $input['public'],
				'parseurl'        => $input['parseurl'],
				'timeout'         => $input['timeout'],
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'enable_comments' => $input['enable_comments'],
				'reason'          => $input['reason'],
			);

			$nodeId = $api->callApi('content_poll', 'update', array($input['nodeid'], $pollData));

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));

			// add attachments
			$this->handleAttachmentUploads($nodeId, $result);
		}
		else
		{
			$result = array();
			$time = vB5_Request::get('timeNow');
			$pollData = array(
				'title'           => $input['title'],
				'rawtext'         => $input['text'],
				'parentid'        => $input['parentid'],
				'userid'          => $user['userid'],
				'authorname'      => $user['username'],
				'created'         => $time,
				'publishdate'     => $time,
				'options'         => $polloptions,
				'multiple'        => $input['multiple'],
				'public'          => $input['public'],
				'parseurl'        => $input['parseurl'],
				'timeout'         => $input['timeout'],
				'prefixid'        => $input['prefixid'],
				'hvinput'         => $input['hvinput'],
				'enable_comments' => $input['enable_comments'],
			);

			$options = array(
				'facebook' => $this->getFacebookOptionsForAddNode(),
			);

			$nodeId = $api->callApi('content_poll', 'add', array($pollData, $options));

			if (!is_int($nodeId))
			{
				$this->handleErrorsForAjax($result, $nodeId);
				$this->sendAsJson($result);
				exit;
			}

			if (!empty($input['tags']))
			{
				$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
			}

			// add attachments
			$this->handleAttachmentUploads($nodeId, $result);

			//redirect to the conversation detail page of the newly created Poll starter
			$node = $api->callApi('node', 'getNode', array($nodeId));
			$this->handleErrorsForAjax($result, $nodeId);
			if ($node AND empty($node['errors']))
			{
				if (empty($node['approved']))
				{
					$result['moderateNode'] = true;
					$node = $api->callApi('node', 'getNode', array($input['parentid']));
				}

				$returnUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $api->callApi('route', 'getUrl', array('route' => $node['routeid'], 'data' => $node, 'extra' => array()));
			}

			if (!empty($returnUrl))
			{
				$result['retUrl'] = $returnUrl;
			}

			$result['nodeId'] = $nodeId;

			$this->sendAsJson($result);
			exit;
		}
		exit;
	}

	/**
	 * Creates a gallery, used by actionAlbum and actionGallery
	 */
	private function createGallery()
	{
		if (!isset($_POST['parentid']) OR !intval($_POST['parentid']))
		{
			return '';
		}

		$time = vB5_Request::get('timeNow');
		$input = array(
			'parentid'        => intval($_POST['parentid']),
			'publishdate'     => $time,
			'created'         => $time,
			'rawtext'         => (isset($_POST['text'])) ? trim(strval($_POST['text'])) : '',
			'title'           => (isset($_POST['title'])) ? trim(strval($_POST['title'])) : 'No Title',
			'tags'            => (isset($_POST['tags'])) ? trim(strval($_POST['tags'])) : '',
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'enable_comments' => (isset($_POST['enable_comments']) ? (bool)$_POST['enable_comments'] : false), // Used only when entering blog posts
		);

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$api = Api_InterfaceAbstract::instance();

		if (!empty($_POST['filedataid']))
		{
			// We need to convert WYSIWYG html here and run the img check
			if (isset($input['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($input['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$options = array(
				'facebook' => $this->getFacebookOptionsForAddNode(),
				'filedataid' => $_POST['filedataid'],
			);

			$nodeId = $api->callApi('content_gallery', 'add', array($input, $options));

			if (!empty($nodeId['errors']))
			{
				return $nodeId;
			}

			foreach($_POST['filedataid'] AS $filedataid)
			{

				$titleKey = "title_$filedataid";
				if (isset($_POST[$titleKey]))
				{
					$caption = $_POST[$titleKey];
				}
				else
				{
					$caption = '';
				}

				$result = $api->callApi('content_photo', 'add', array(
					array(
						'publishdate' => $time,
						'parentid' => $nodeId,
						'caption' => $caption,
						'title' => $caption,
						'filedataid' => $filedataid,
						'hvinput' => $input['hvinput'],
					),
					array(
						'isnewgallery' => true
					)
				));
				if (!empty($result['errors']))
				{
					return $result;
				}
			}

			if (!empty($input['tags']))
			{
				$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
				if (!empty($tagRet['errors']))
				{
					return $tagRet;
				}
			}

			// add attachments
			$this->handleAttachmentUploads($nodeId, $result);
		}

		return $nodeId;
	}

	/**
	 * Creates a user album, which is really just a gallery in the "Albums" channel
	 */
	function actionAlbum()
	{
		$api = Api_InterfaceAbstract::instance();
		$_POST['parentid'] = $api->callApi('node', 'fetchAlbumChannel', array());
		$galleryid = $this->createGallery();
		$html = '';

		$galleries = $api->callApi('profile', 'fetchAlbums', array());
		$templater = new vB5_Template('album_photo');
		foreach ($galleries as $gallery)
		{
			$templater->register('node', $gallery);
			$html .=  $templater->render();
		}

		$this->outputPage($html);
	}

	/**
	 * Creates a gallery
	 * This is called when creating a thread or reply using the "Photos" tab
	 * And when uploading photos at Profile => Media => Share Photos
	 */
	function actionGallery()
	{
		$galleryid = $this->createGallery();

		$input = array(
			'parentid' => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid' => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'ret' => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
		);

		$result = array();

		if (!is_int($galleryid))
		{
			$this->handleErrorsForAjax($result, $galleryid);
			$this->sendAsJson($result);
			exit;
		}

		$node = Api_InterfaceAbstract::instance()->callApi('node', 'getNode', array($galleryid));
		if ($node AND empty($node['errors']))
		{
			if (empty($node['approved']))
			{
				$result['moderateNode'] = true;
			}
		}

		// Sets redirect url when creating new conversation
		$this->getReturnUrl($result, $input['channelid'], $input['parentid'], $galleryid);
		$result['nodeId'] = $galleryid;
		if (!Api_InterfaceAbstract::instance()->callApi('user', 'hasPermissions', array('albumpermissions', 'picturefollowforummoderation')))
		{
			$result['alert'] = 'post_avaiting_moderation';
		}
		$this->sendAsJson($result);
		exit;
	}

	function actionVideo()
	{
		$input = array(
			'title'           => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'            => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'parentid'        => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid'       => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'nodeid'          => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'ret'             => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'tags'            => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'url_title'       => (isset($_POST['url_title']) ? trim(strval($_POST['url_title'])) : ''),
			'url'             => (isset($_POST['url']) ? trim(strval($_POST['url'])) : ''),
			'url_meta'        => (isset($_POST['url_meta']) ? trim(strval($_POST['url_meta'])) : ''),
			'videoitems'      => (isset($_POST['videoitems']) ? $_POST['videoitems'] : array()),
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'enable_comments' => (isset($_POST['enable_comments']) ? (bool)$_POST['enable_comments'] : false), // Used only when entering blog posts
			'reason'          => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
		);

		//@TODO: There is no title for posting a reply or comment but api throws an error if blank. Fix this.

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$videoitems = array();
		foreach ($input['videoitems'] as $k => $v)
		{
			if ($k == 'new')
			{
				foreach ($v as $v2)
				{
					if ($v2)
					{
						$videoitems[]['url'] = $v2['url'];
					}
				}
			}
			else
			{
				$videoitems[] = array(
					'videoitemid' => intval($k),
					'url' => $v['url'],
				);
			}
		}

		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		if ($input['nodeid'])
		{
			$videoData = array(
				'title'           => $input['title'],
				'rawtext'         => $input['text'],
				'url_title'       => $input['url_title'],
				'url'             => $input['url'],
				'meta'            => $input['url_meta'],
				'videoitems'      => $videoitems,
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'enable_comments' => $input['enable_comments'],
				'reason'          => $input['reason'],
				'parentid'        => $input['parentid'],
			);

			// We need to convert WYSIWYG html here and run the img check
			if (isset($videoData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($videoData['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$ret = $api->callApi('content_video', 'update', array($input['nodeid'], $videoData));

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));

			// add attachments
			$this->handleAttachmentUploads($input['nodeid'], $result);
		}
		else
		{
			$result = array();
			$videoData = array(
				'title'           => $input['title'],
				'parentid'        => $input['parentid'],
				'rawtext'         => $input['text'],
				'userid'          => $user['userid'],
				'authorname'      => $user['username'],
				'created'         => vB5_Request::get('timeNow'),
				'publishdate'     => $api->callApi('content_text', 'getTimeNow', array()),
				'url_title'       => $input['url_title'],
				'url'             => $input['url'],
				'meta'            => $input['url_meta'],
				'videoitems'      => $videoitems,
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'hvinput'         => $input['hvinput'],
				'enable_comments' => $input['enable_comments'],
			);

			if (!empty($_POST['setfor']))
			{
				$videoData['setfor'] = $_POST['setfor'];
			}

			$options = array(
				'facebook' => $this->getFacebookOptionsForAddNode(),
			);

			// We need to convert WYSIWYG html here and run the img check
			if (isset($videoData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($videoData['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$nodeId = $api->callApi('content_video', 'add', array($videoData, $options));

			if (!is_int($nodeId))
			{
				$this->handleErrorsForAjax($result, $nodeId);
				$this->sendAsJson($result);
				exit();
			}

			if (!empty($input['tags']))
			{
				$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
			}

			// add attachments
			$this->handleAttachmentUploads($nodeId, $result);

			// Sets redirect url when creating new conversation
			$this->getReturnUrl($result, $input['channelid'], $input['parentid'], $nodeId);
			$result['nodeId'] = $nodeId;

			// publish to facebook
			//$this->publishToFacebook($result);
			if (!Api_InterfaceAbstract::instance()->callApi('user', 'hasPermissions', array('albumpermissions', 'picturefollowforummoderation')))
			{
				$result['alert'] = 'post_avaiting_moderation';
			}

			$this->sendAsJson($result);
		}
		exit;
	}

	function actionLink()
	{

		if (isset($_POST['videoitems']))
		{
			return $this->actionVideo();
		}

		$input = array(
			'title'           => (isset($_POST['title']) ? trim(strval($_POST['title'])) : ''),
			'text'            => (isset($_POST['text']) ? trim(strval($_POST['text'])) : ''),
			'parentid'        => (isset($_POST['parentid']) ? trim(intval($_POST['parentid'])) : 0),
			'channelid'       => (isset($_POST['channelid']) ? trim(intval($_POST['channelid'])) : 0),
			'nodeid'          => (isset($_POST['nodeid']) ? trim(intval($_POST['nodeid'])) : 0),
			'ret'             => (isset($_POST['ret']) ? trim(strval($_POST['ret'])) : ''),
			'tags'            => (isset($_POST['tags']) ? $_POST['tags'] : ''),
			'url_image'       => (isset($_POST['url_image']) ? trim(strval($_POST['url_image'])) : ''),
			'url_title'       => (isset($_POST['url_title']) ? trim(strval($_POST['url_title'])) : ''),
			'url'             => (isset($_POST['url']) ? trim(strval($_POST['url'])) : ''),
			'url_meta'        => (isset($_POST['url_meta']) ? trim(strval($_POST['url_meta'])) : ''),
			'url_nopreview'   => (isset($_POST['url_nopreview']) ? intval($_POST['url_nopreview']) : 0),
			'iconid'          => (isset($_POST['iconid']) ? intval($_POST['iconid']) : 0),
			'prefixid'        => (isset($_POST['prefixid']) ? trim(strval($_POST['prefixid'])) : ''),
			'hvinput'         => (isset($_POST['humanverify']) ? $_POST['humanverify'] : ''),
			'enable_comments' => (isset($_POST['enable_comments']) ? (bool)$_POST['enable_comments'] : false), // Used only when entering blog posts
			'reason'          => (isset($_POST['reason']) ? trim(strval($_POST['reason'])) : ''), //used in editing a post
		);

		//@TODO: There is no title for posting a reply or comment but api throws an error if blank. Fix this.

		if (!empty($_POST['setfor']))
		{
			$input['setfor'] = $_POST['setfor'];
		}
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$api = Api_InterfaceAbstract::instance();

		// get user info for the currently logged in user
		$user  = $api->callApi('user', 'fetchUserinfo', array());

		// Upload images
		$filedataid = 0;
		if (!$input['url_nopreview'] AND $input['url_image'])
		{
			$ret = $api->callApi('content_attach', 'uploadUrl', array($input['url_image']));

			if (empty($ret['error']))
			{
				$filedataid = $ret['filedataid'];
			}
		}

		if ($input['nodeid'])
		{
			if ($filedataid)
			{
//				$api->callApi('content_attach', 'deleteAttachment', array($input['nodeid']));
			}

			$linkData = array(
				'title'           => $input['title'],
				'url_title'       => $input['url_title'],
				'rawtext'         => $input['text'],
				'url'             => $input['url'],
				'meta'            => $input['url_meta'],
				'filedataid'      => $filedataid,
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'enable_comments' => $input['enable_comments'],
				'reason'          => $input['reason'],
				'parentid'        => $input['parentid'],
			);

			// We need to convert WYSIWYG html here and run the img check
			if (isset($linkData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($linkData['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$ret = $api->callApi('content_link', 'update', array($input['nodeid'], $linkData));

			//update tags
			$tags = !empty($input['tags']) ? explode(',', $input['tags']) : array();
			$tagRet = $api->callApi('tags', 'updateUserTags', array($input['nodeid'], $tags));

			// update attachments
			$this->handleAttachmentUploads($input['nodeid'], $result);
		}
		else
		{
			$result = array();
			$linkData = array(
				'title'           => $input['title'],
				'url_title'       => $input['url_title'],
				'parentid'        => $input['parentid'],
				'rawtext'         => $input['text'],
				'userid'          => $user['userid'],
				'authorname'      => $user['username'],
				'created'         => vB5_Request::get('timeNow'),
				'publishdate'     => $api->callApi('content_link', 'getTimeNow', array()),
				'url'             => $input['url'],
				'meta'            => $input['url_meta'],
				'filedataid'      => $filedataid,
				'iconid'          => $input['iconid'],
				'prefixid'        => $input['prefixid'],
				'hvinput'         => $input['hvinput'],
				'enable_comments' => $input['enable_comments'],
			);

			if (!empty($_POST['setfor']))
			{
				$linkData['setfor'] = $_POST['setfor'];
			}

			$options = array(
				'facebook' => $this->getFacebookOptionsForAddNode(),
			);

			// We need to convert WYSIWYG html here and run the img check
			if (isset($linkData['rawtext']))
			{
				$tmpText = $api->callApi('bbcode', 'convertWysiwygTextToBbcode', array($linkData['rawtext'], array()));
				// Check Images
				if (($phrase = vB5_Frontend_Controller_Bbcode::verifyImgCheck($tmpText)) !== true)
				{
					$results['error'] = $phrase;
					$this->sendAsJson($results);
					return;
				}
			}

			$nodeId = $api->callApi('content_link', 'add', array($linkData, $options));

			if (!is_int($nodeId))
			{
				$this->handleErrorsForAjax($result, $nodeId);
				$this->sendAsJson($result);
				exit();
			}

			if (!empty($input['tags']))
			{
				$tagRet = $api->callApi('tags', 'addTags', array($nodeId, $input['tags']));
				$this->handleErrorsForAjax($result, $tagRet);
			}

			// add attachments
			$this->handleAttachmentUploads($nodeId, $result);

			$node = $api->callApi('node', 'getNode', array($nodeid));
			if ($node AND empty($node['errors']))
			{
				if (empty($node['approved']))
				{
					$result['moderateNode'] = true;
				}
			}

			// Sets redirect url when creating new conversation
			$this->getReturnUrl($result, $input['channelid'], $input['parentid'], $nodeId);
			$result['nodeId'] = $nodeId;

			// publish to facebook
			//$this->publishToFacebook($result);

			$this->sendAsJson($result);
		}
		exit;
	}

	/**
	 * Creates a private message.
	 */
	public function actionPrivateMessage()
	{
		$api = Api_InterfaceAbstract::instance();

		if (!empty($_POST['autocompleteHelper']) AND empty($_POST['msgRecipients']))
		{
			$msgRecipients = $_POST['autocompleteHelper'];


			if (substr($msgRecipients, -1) == ';')
			{
				$msgRecipients = substr($msgRecipients, 0, -1);
			}
			$_POST['msgRecipients'] = $msgRecipients;
		}

		if (!empty($_POST['msgRecipients']) AND (substr($_POST['msgRecipients'], -1) == ';'))
		{
			$_POST['msgRecipients'] = substr($_POST['msgRecipients'], 0, -1);
		}

		$hvInput = isset($_POST['humanverify']) ? $_POST['humanverify'] : '';
		if (!empty($_POST['recaptcha_challenge_field']))
		{
			// reCaptcha fields
			$hvInput['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
			$hvInput['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}
		$_POST['hvinput'] =& $hvInput;

		$_POST['rawtext'] = $_POST['text'];
		unset($_POST['text']);

		$result = $api->callApi('content_privatemessage', 'add', array($_POST));
		$results = array();

		if (!empty($result['errors']))
		{
			if (is_array($result['errors'][0]))
			{
				$errorphrase = array_shift($result['errors'][0]);
				$phrases = $api->callApi('phrase', 'fetch', array(array($errorphrase)));
				$results['error'] = vsprintf($phrases[$errorphrase], $result['errors'][0]);
			}
			else
			{
				$phrases = $api->callApi('phrase', 'fetch', array(array($result['errors'][0])));
				$results['error'] =  $phrases[$result['errors'][0]];
			}

		}
		else
		{
			// add attachments
			$this->handleAttachmentUploads($result, $results);

			$phrases = $api->callApi('phrase', 'fetch', array(array('pm_sent')));
			$results['message'] = $phrases['pm_sent'];

			$results['nodeId'] = (int) $result;
		}

		return $this->sendAsJson($results);
	}

	public function actionParseWysiwyg()
	{
		return $this->sendAsJson(array('data' => vB5_Frontend_Controller_Bbcode::parseWysiwyg($_POST['data'])));
	}

	public function actionLoadeditor()
	{
		$input = array(
			'nodeid' => (isset($_POST['nodeid']) ? intval($_POST['nodeid']) : 0),
			'type' => (isset($_POST['type']) ? trim(strval($_POST['type'])) : ''),
			'view' => (isset($_POST['view']) ? trim($_POST['view']) : 'stream'),
		);

		$results = array();

		if (!$input['nodeid'])
		{
			$results['error'] = 'error_loading_editor';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();
		$user  = $api->callApi('user', 'fetchUserinfo', array());
		$node = $api->callApi('node', 'getNodeContent', array($input['nodeid'], false));
		$node = $node[$input['nodeid']];

		if (!$node)
		{
			$results['error'] = 'error_loading_editor';
			$this->sendAsJson($results);
			return;
		}

		$node['canremove'] = 0;

		//See if we should show delete
		if ($api->callApi('user', 'hasPermissions', array('moderatorpermissions', 'canremoveposts'))
		 OR $api->callApi('user', 'hasPermissions', array('moderatorpermissions', 'candeleteposts')))
		{
			$node['canremove'] = 1;
	 	}
		else if ($api->callApi('user', 'hasPermissions', array('moderatorpermissions', 'canremoveposts', $input['nodeid']))
			OR $api->callApi('user', 'hasPermissions', array('moderatorpermissions', 'candeleteposts', $input['nodeid'])))
		{
			$node['canremove'] = 1;
		}
		else if (($node['starter'] > 0) // Skip if it's a channel
			AND ($node['userid'] == $user['userid']))
		{
			if ((($node['starter'] == $node['nodeid']) AND $api->callApi('user', 'hasPermissions', array('forumpermissions', 'candeletethread', $node['nodeid'])))
			OR (($node['starter'] != $node['nodeid']) AND $api->callApi('user', 'hasPermissions', array('forumpermissions', 'candeletepost', $node['nodeid'])))
			)
			{
				$node['canremove'] = 1;
			}

		}
		else if (($node['starter'] > 0) AND ($node['setfor'] > 0) AND
			($api->callApi('user', 'hasPermissions', array('moderatorpermissions2', 'candeletevisitormessages'))
				OR
			$api->callApi('user', 'hasPermissions', array('moderatorpermissions2', 'canremovevisitormessages')))
		)
		{
			// Make the editor show Delete button
			$node['canremove'] = 1;
		}
		else if (($node['starter'] > 0) AND ($node['setfor'] > 0) AND ($user['userid'] == $node['setfor']) AND
			$api->callApi('user', 'hasPermissions', array('visitormessagepermissions', 'can_delete_own_visitor_messages'))
		)
		{
			// Make the editor show Delete button
			$node['canremove'] = 1;
		}

		if (in_array($node['contenttypeclass'], array('Text', 'Gallery', 'Poll', 'Video', 'Link')))
		{
			if ($input['type'] == 'comment' AND $node['contenttypeclass'] == 'Text')
			{
				$results = vB5_Template::staticRenderAjax('editor_contenttype_Text_comment', array(
					'conversation'	=> $node,
					'showDelete'	=> 1,
				));
			}
			else
			{
				$templateData = array(
					'nodeid' 				=> $node['nodeid'],
					'conversation' 			=> $node,
					'parentid' 				=> $node['parentid'],
					'showCancel' 			=> 1,
					'showDelete' 			=> $node['canremove'],
					'showPreview' 			=> 1,
					'showToggleEditor' 		=> 1,
					'showSmiley' 			=> 1,
					'showAttachment' 		=> 1,
					'showTags'              => ($node['nodeid'] == $node['starter'] AND $node['channeltype'] != 'vm'),
					'showTitle' 			=> ($node['nodeid'] == $node['starter'] AND $node['channeltype'] != 'vm'),
					'editPost' 				=> 1,
					'conversationType' 		=> $input['type'],
					'compactButtonSpacing'	=> 1,
					'initOnPageLoad'		=> 1,
					'focusOnPageLoad'		=> 1,
					'noJavascriptInclude'   => 1,
				);

				//for Blog posts, we need Blog Channel info to determine if we need to display Blog Options
				if ($node['channeltype'] == 'blog')
				{
					$channelInfo = $api->callApi('content_channel', 'fetchChannelById', array($node['channelid']));
					$templateData['channelInfo'] = $channelInfo;
				}

				foreach (array('Text', 'Gallery', 'Poll', 'Video', 'Link') AS $type)
				{
					$templateFlag = (($type == 'Gallery') ? 'Photo' : $type);
					$templateFlagValue = ($node['contenttypeclass'] == $type ? 1 : 0);
					$templateData['allowType' . $templateFlag] =  $templateFlagValue;
					if ($templateFlagValue == 1)
					{
						$templateData['defaultContentType'] = $node['contenttypeclass'];
					}
				}

				if ($node['contenttypeclass'] == 'Gallery')
				{
					if (!empty($node['photo']))
					{
						$templateData['maxid'] = max(array_keys($node['photo']));
					}
					else
					{
						$templateData['maxid'] = 0;
					}
				}

				//content types that has no Tags. Types used should be the same used in $input['type']
				$noTagsContentTypes = array('media', 'visitorMessage'); //add more types as needed
				if ($node['nodeid'] == $node['starter'])
				{
					if (!in_array($input['type'], $noTagsContentTypes)) //get tags of the starter (exclude types that don't use tags)
					{
						$tagList = $api->callApi('tags', 'getNodeTags', array($input['nodeid']));
						if (!empty($tagList) AND !empty($tagList['tags']))
						{
							$tags = array();
							foreach ($tagList['tags'] as $tag)
							{
								$tags[] = $tag['tagtext'];
							}

							$tagList['displaytags']	= implode(', ', $tags);
							$templateData['tagList'] = $tagList;
						}
					}
					$channelInfo = $api->callApi('content_channel', 'fetchChannelById', array($node['parentid']));

					if ($channelInfo['can_comment'] AND $node['channeltype'] == 'blog')
					{
						$templateData['can_comment_option'] = 1;
					}
				}
				if (in_array($input['type'], $noTagsContentTypes) OR $node['nodeid'] != $node['starter'])
				{
					$templateData['showTags'] = 0;
				}

				$results = vB5_Template::staticRenderAjax('contententry', $templateData);
			}
		}
		else
		{
			$results['error'] = 'error_loading_editor';
		}
		$this->sendAsJson($results);
		return;
	}

	public function actionLoadPreview()
	{
		$input = array(
			'parentid' => (isset($_POST['parentid']) ? intval($_POST['parentid']) : 0),
			'channelid' => (isset($_POST['channelid']) ? intval($_POST['channelid']) : 0),
			'pagedata' => (isset($_POST['pagedata']) ? ((array)$_POST['pagedata']) : array()),
			'conversationtype' => (isset($_POST['conversationtype']) ? trim(strval($_POST['conversationtype'])) : ''),
			'posttags' => (isset($_POST['posttags']) ? trim(strval($_POST['posttags'])) : ''),
			'rawtext' => (isset($_POST['rawtext']) ? trim(strval($_POST['rawtext'])) : ''),
			'filedataid' => (isset($_POST['filedataid']) ? ((array)$_POST['filedataid']) : array()),
			'link' => (isset($_POST['link']) ? ((array)$_POST['link']) : array()),
			'poll' => (isset($_POST['poll']) ? ((array)$_POST['poll']) : array()),
			'video' => (isset($_POST['video']) ? ((array)$_POST['video']) : array()),
		);

		$results = array();

		if ($input['parentid'] < 1)
		{
			$results['error'] = 'invalid_parentid';
			$this->sendAsJson($results);
			return;
		}

		// when creating a new content item, channelid == parentid
		$input['channelid'] = ($input['channelid'] == 0) ? $input['parentid'] : $input['channelid'];

		$templateName = 'display_contenttype_conversationreply_';
		$templateName .= ucfirst($input['conversationtype']);

		$api = Api_InterfaceAbstract::instance();
		$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', array($input['channelid']));

		// The $node['starter'] and $node['nodeid'] values are just there to differentiate starters and replies
		$node = array(
			'rawtext' => '',
			'userid' => vB5_User::get('userid'),
			'authorname' => vB5_User::get('username'),
			'tags' => $input['posttags'],
			'taglist' => $input['posttags'],
			'approved' => true,
			'created' => time(),
			'avatar' => $api->callApi('user', 'fetchAvatar', array('userid' => vB5_User::get('userid'))),
			'parentid' => $input['parentid'],
			'starter' => ($input['channelid'] == $input['parentid']) ? 0 : $input['parentid'],
			'nodeid' => ($input['channelid'] == $input['parentid']) ? 0 : 1,
		);

		if ($input['conversationtype'] == 'gallery')
		{
			$node['photopreview'] = array();
			foreach ($input['filedataid'] AS $filedataid)
			{
				$node['photopreview'][] = array(
					'nodeid' => $filedataid,
					'htmltitle' => isset($_POST['title_' . $filedataid]) ? vB_String::htmlSpecialCharsUni($_POST['title_' . $filedataid]) : '',
				);

				//photo preview is up to 3 photos only
				if (count($node['photopreview']) == 3)
				{
					break;
				}
			}
			$node['photocount'] = count($input['filedataid']);
		}

		if ($input['conversationtype'] == 'link')
		{
			//{vb:template link, title={vb:raw conversation.url_title}, url={vb:raw conversation.url}, meta={vb:raw conversation.meta}, linkid={vb:raw conversation.nodeid}, filedataid={vb:raw conversation.filedataid} }
			$node['url_title'] = !empty($input['link']['title']) ? $input['link']['title'] : '';
			$node['url'] = !empty($input['link']['url']) ? $input['link']['url'] : '';
			$node['meta'] = !empty($input['link']['meta']) ? $input['link']['meta'] : '';
			$node['previewImage'] = !empty($input['link']['url_image']) ? $input['link']['url_image'] : '';
		}

		if ($input['conversationtype'] == 'poll')
		{
			$node['multiple'] = !empty($input['poll']['mutliple']);
			$node['options'] = array();
			if (!empty($input['poll']['options']) and is_array($input['poll']['options']))
			{
				$optionIndex = 1;
				foreach ($input['poll']['options'] AS $option)
				{
					$node['options'][] = array (
						'polloptionid' => $optionIndex,
						'title' => $option,
					);
					$optionIndex++;
				}
			}
			$node['permissions']['canviewthreads'] = 1; //TODO: Fix this!!
		}

		if ($input['conversationtype'] == 'video')
		{
			//{vb:template link, title={vb:raw conversation.url_title}, url={vb:raw conversation.url}, meta={vb:raw conversation.meta}, linkid={vb:raw conversation.nodeid}, filedataid={vb:raw conversation.filedataid} }
			$node['url_title'] = !empty($input['video']['title']) ? $input['video']['title'] : '';
			$node['url'] = !empty($input['video']['url']) ? $input['video']['url'] : '';
			$node['meta'] = !empty($input['video']['meta']) ? $input['video']['meta'] : '';
			$node['items'] = !empty($input['video']['items']) ? $input['video']['items'] : '';
		}

		try
		{
			$results = vB5_Template::staticRenderAjax(
				$templateName,
				array(
					'nodeid' => $node['nodeid'],
					'conversation' => $node,
					'currentConversation' => $node,
					'bbcodeOptions' => $channelBbcodes,
					'pagingInfo' => array(),
					'postIndex' => 0,
					'reportActivity' => false,
					'showChannelInfo' => false,
					'showInlineMod' => false,
					'commentsPerPage' => 1,
					'view' => 'stream',
					'previewMode' => true,
				)
			);
		}
		catch (Exception $e)
		{
			if (vB5_Config::instance()->debug)
			{
				$results['error'] = 'error_rendering_preview_template ' . (string) $e;
			}
			else
			{
				$results['error'] = 'error_rendering_preview_template';
			}
			$this->sendAsJson($results);
			return;
		}

		$results = array_merge($results, $this->parseBbCodeForPreview(fetch_censored_text($input['rawtext'])));

		$this->sendAsJson($results);
	}

	public function actionLoadnode()
	{
		$input = array(
			'nodeid' => (isset($_REQUEST['nodeid']) ? intval($_REQUEST['nodeid']) : 0),
			'view' => (isset($_REQUEST['view']) ? trim($_REQUEST['view']) : 'stream'),
			'page' => (isset($_REQUEST['page']) ? $_REQUEST['page'] : array()),
			'index' => (isset($_REQUEST['index']) ? floatval($_REQUEST['index']) : 0),
			'type' => (isset($_REQUEST['type']) ? trim(strval($_REQUEST['type'])) : ''),
		);

		$results = array();
		$results['css_links'] = array();

		if (!$input['nodeid'])
		{
			$results['error'] = 'error_loading_post';
			$this->sendAsJson($results);
			return;
		}

		$api = Api_InterfaceAbstract::instance();

		$node = $api->callApi('node', 'getNodeFullContent', array('nodeid' => $input['nodeid'], 'contenttypeid' => false, 'options' => array('showVM' => 1, 'withParent' => 1)));
		$node = isset($node[$input['nodeid']]) ? $node[$input['nodeid']] : null;

		if (!$node)
		{
			$results['error'] = 'error_loading_post';
			$this->sendAsJson($results);
			return;
		}

		$currentNodeIsBlog = $node['channeltype'] == 'blog';

		if (!in_array($input['view'], array('stream', 'thread', 'activity-stream', 'full-activity-stream')))
		{
			$input['view'] = 'stream';
		}

		//comment in Thread view
		if (($input['view'] == 'thread' OR $currentNodeIsBlog) AND $input['type'] == 'comment' AND $node['contenttypeclass'] == 'Text')
		{
			$templater = new vB5_Template('conversation_comment_item');
			$templater->register('conversation', $node);
			$templater->register('conversationIndex', floor($input['index']));
			if ($currentNodeIsBlog)
			{
				$templater->register('commentIndex', $input['index']);
				$templater->register('parentNodeIsBlog', true);

				$enableInlineMod = (
					!empty($node['moderatorperms']['canmoderateposts']) OR
					!empty($node['moderatorperms']['candeleteposts']) OR
					!empty($node['moderatorperms']['caneditposts']) OR
					!empty($node['moderatorperms']['canremoveposts'])
				);
				$templater->register('enableInlineMod', $enableInlineMod);
			}
			else if ($input['index'] - floor($input['index']) > 0)
			{
				$commentIndex = explode('.', strval($input['index']));
				$templater->register('commentIndex', $commentIndex[1]);
			}
			else
			{
				$templater->register('commentIndex', 1);
			}
		}
		else //reply or starter node or comment in Stream view
		{
			//Media tab Video Album
			if ($input['type'] == 'media' AND $node['contenttypeclass'] == 'Video')
			{
				$templater = new vB5_Template('profile_media_videoitem');
				$templater->register('conversation', $node);
				$templater->register('reportActivity', true);
				$results['template'] = $templater->render(true, true);
				$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

				$this->sendAsJson($results);
				return;
			}
			else
			{
				//designed to duplicate some logic in the widget_conversationdisplay template that updates a flag on the nodes used
				//by the conversation_footer template.  This really needs to be pushed back on the node API, but that's a riskier fix
				$starter = $api->callApi('node', 'getNodeFullContent', array($node['starter']));
				if (!isset($starter['error']))
				{
					$node['can_use_multiquote'] = ($starter[$node['starter']]['can_comment'] AND
						($starter[$node['starter']]['channeltype'] != 'blog'));
				}
				else
				{
					//explicitly handle the error case.  This is unlikely and throwing an error here would be bad.
					//so we'll ignore it and just return false as the safest behavior.
					$node['can_use_multiquote'] = false;
				}


				$template = 'display_contenttype_';
				if ($node['nodeid'] == $node['starter'])
				{
					$template .= ($input['view'] == 'thread') ? 'conversationstarter_threadview_' : 'conversationreply_';
					$parentConversation = $node;
				}
				else
				{
					$template .= ($input['view'] == 'thread') ? 'conversationreply_threadview_' : 'conversationreply_';
				}
			}

			$conversationRoute = $api->callApi('route', 'getChannelConversationRoute', array($node['channelid']));
			$channelBbcodes = $api->callApi('content_channel', 'getBbcodeOptions', array($node['channelid']));

			if (strpos($input['view'], 'stream') !== false)
			{
				$totalCount = $node['totalcount'];
			}
			else
			{
				$totalCount = $node['textcount'];
			}

			$arguments = array(
				'nodeid'	=>	$node['nodeid'],
				'pagenum'	=>	$input['page']['pagenum'],
				'channelid'	=>	$input['page']['channelid'],
				'pageid'	=>	$input['page']['pageid']
			);

			$routeInfo = array(
				'routeId' => $conversationRoute,
				'arguments'	=> $arguments,
			);

			$pagingInfo = $api->callApi('page', 'getPagingInfo', array($input['page']['pagenum'], $totalCount, (isset($input['page']['posts-perpage']) ? $input['page']['posts-perpage'] : null), $routeInfo, vB5_Template_Options::instance()->get('options.frontendurl')));

			if (!isset($node['parsedSignature']))
			{
				$signatures = array($node['userid'] => $node['signature']);
				$parsed_signatures = Api_InterfaceAbstract::instance()->callApi('bbcode', 'parseSignatures', array(array_keys($signatures), $signatures));
				$node['parsedSignature'] = $parsed_signatures[$node['userid']];
			}

			$template .= $node['contenttypeclass'];

			$templater = new vB5_Template($template);
			$templater->register('nodeid', $node['nodeid']);
			$templater->register('currentNodeIsBlog', $currentNodeIsBlog);
			$templater->register('conversation', $node);
			$templater->register('currentConversation', $node);
			$templater->register('bbcodeOptions', $channelBbcodes);
			$templater->register('pagingInfo', $pagingInfo);
			$templater->register('postIndex', $input['index']);
			$templater->register('reportActivity', strpos($input['view'], 'activity-stream') !== false);
			$templater->register('showChannelInfo', $input['view'] == 'full-activity-stream');
			if ($input['view'] == 'thread')
			{
				$templater->register('showInlineMod', true);
				$templater->register('commentsPerPage', $input['page']['comments-perpage']);
			}
			else if ($input['view'] == 'stream' AND !$node['isVisitorMessage']) // Visitor Message doesn't allow to be quoted. See VBV-5583.
			{
				$templater->register('view', 'conversation_detail');
			}
		}

		$results['template'] = $templater->render(true, true);
		$results['css_links'] = vB5_Template_Stylesheet::instance()->getAjaxCssLinks();

		$this->sendAsJson($results);
		return;
	}

	/**
	 * This handles all saves of blog data.
	 */
	public function actionBlog()
	{
		$fields = array('title', 'description', 'nodeid', 'filedataid', 'invite_usernames', 'invite_userids', 'viewperms', 'commentperms',
			'moderate_comments', 'approve_membership', 'allow_post', 'autoparselinks', 'disablesmilies', 'sidebarInfo');

		// forum options map
		$channelOpts = array('allowsmilies' => 'disablesmilies', 'allowposting' => 'allow_post');

		$input = array();
		foreach ($fields as $field)
		{
			if (isset($_POST[$field]))
			{
				$input[$field] = $_POST[$field];
			}
		}

		// allowsmilies is general
		if (isset($_POST['next']) AND ($_POST['next'] == 'permissions'))
		{
			foreach (array('autoparselinks', 'disablesmilies') AS $field)
			{
				// channeloptions
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 0 : 1);
					}
					else
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 1 : 0);
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}


		//If this is the "permission" step, we must pass the three checkboxes
		if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
		{
			foreach (array( 'moderate_comments', 'approve_membership', 'allow_post') AS $field )
			{
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = 1;
					}
					else
					{
						$input['options'][$idx] = 0;
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}
		if (empty($input['options']))
		{
			$input['options'] = array();
		}
		// Other default options
		$input['options'] += array(
			'allowbbcode' => 1,
			'allowimages' => 1,
		);
		$input['auto_subscribe_on_join'] = 1;

		$api = Api_InterfaceAbstract::instance();

		$quickCreateBlog = (isset($_POST['wizard']) AND $_POST['wizard'] == '0') ? true : false; //check if in quick create blog mode (in overlay and non-wizard type)

		if (count($input) > 1)
		{
			$input['parentid'] = $api->callApi('blog', 'getBlogChannel');
			if (empty($input['nodeid']))
			{
				$nodeid = $api->callApi('blog', 'createBlog', array($input));
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . '/blogadmin/create/settings';
				if (is_array($nodeid) AND array_key_exists('errors', $nodeid))
				{
					if ($quickCreateBlog)
					{
						$this->sendAsJson($nodeid);
						return;
					}
					else
					{
						vB5_ApplicationAbstract::handleFormError($nodeid['errors'], $url);
					}

				}
				if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
				{
					if ($quickCreateBlog)
					{
						$this->sendAsJson($nodeid);
						return;
					}
					else
					{
						$urlparams = array('blogaction' => 'create', 'action2' => 'settings');
						$url = $api->callApi('route', 'getUrl', array('blogadmin', $urlparams, array()));
						header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
						vB5_Cookie::set('blogadmin_error', $nodeid['errors'][0][0]);
						if (isset($input['title']))
						{
							vB5_Cookie::set('blog_title', $input['title']);
						}
						if (isset($input['description']))
						{
							vB5_Cookie::set('blog_description', $input['description']);
						}
						die();
					}
				}
			}
			else if(isset($input['invite_usernames']) AND $input['nodeid'])
			{
				$inviteUnames = explode(',', $input['invite_usernames']);
				$inviteIds = (isset($input['invite_userids'])) ? $input['invite_userids'] : array();
				$nodeid = $input['nodeid'];
				$api->callApi('user', 'inviteMembers', array($inviteIds, $inviteUnames, $nodeid, 'member_to'));
			}
			else if (isset($input['sidebarInfo']) AND $input['nodeid'])
			{
				$modules = explode(',', $input['sidebarInfo']);
				$nodeid = $input['nodeid'];
				foreach ($modules AS $key => $val)
				{
					$info = explode(':', $val);
					$modules[$key] = array('widgetinstanceid' => $info[0], 'hide' => ($info[1] == 'hide'));
				}
				$api->callApi('blog', 'saveBlogSidebarModules', array($input['nodeid'], $modules));
			}
			else
			{

				foreach (array('allow_post', 'moderate_comments', 'approve_membership', 'autoparselinks', 'disablesmilies') as $bitfield)
				{
					if (!empty($_POST[$bitfield]))
					{
						$input[$bitfield] = 1;
					}
				}

				$nodeid = $input['nodeid'];
				unset($input['nodeid']);
				$api->callApi('content_channel', 'update', array($nodeid, $input));

				//if this is for the permission page we handle differently

			}
//			set_exception_handler(array('vB5_ApplicationAbstract','handleException'));
//
//			if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
//			{
//				throw new exception($nodeid['errors'][0][0]);
//			}
		}
		else if (isset($_POST['nodeid']))
		{
			$nodeid = $_POST['nodeid'];
			if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
			{
				$updates = array();
				foreach (array('allow_post', 'moderate_comments', 'approve_membership') as $bitfield)
				{

					if (empty($_POST[$bitfield]))
					{
						$updates[$bitfield] = 0;
					}
					else
					{
						$updates[$bitfield] = 1;
					}
				}
				$api->callApi('node', 'setNodeOptions', array($nodeid, $updates));
				$updates = array();

				if (isset($_POST['viewperms']))
				{
					$updates['viewperms'] = $_POST['viewperms'];
				}

				if (isset($_POST['commentperms']))
				{
					$updates['commentperms'] = $_POST['commentperms'];
				}

				if (!empty($updates))
				{
					$results = $api->callApi('node', 'setNodePerms', array($nodeid, $updates));
				}

			}
		}
		else
		{
			$nodeid = 0;
		}

		//If the user clicked Next we go to the permissions page. Otherwise we go to the node.
		if (isset($_POST['btnSubmit']))
		{
			if (isset($_POST['next']))
			{
				$action2 = $_POST['next'];
			}
			else
			{
				$action2 = 'permissions';
			}

			if (isset($_POST['blogaction']))
			{
				$blogaction = $_POST['blogaction'];
			}
			else
			{
				$blogaction = 'admin';
			}

			if (($action2 == 'permissions') AND
				!($api->callApi('user', 'hasPermissions', array( 'group' => 'forumpermissions2', 'permission' => 'canconfigchannel', 'nodeid' => $nodeid)))
				)
			{
				$action2 = 'contributors';
			}

			$urlparams = array('nodeid' => $nodeid, 'blogaction' => $blogaction, 'action2' => $action2);
			$url = $api->callApi('route', 'getUrl', array('blogadmin', $urlparams, array()));
		}
		else if ($quickCreateBlog)
		{
			$this->sendAsJson(array('nodeid' => $nodeid));
			return;
		}
		else
		{
			$node = $api->callApi('node', 'getNode', array('nodeid' => $nodeid));
			$url = $api->callApi('route', 'getUrl', array($node['routeid'], array('nodeid' => $nodeid, 'title' => $node['title'], 'urlident' => $node['urlident']), array()));
		}

		header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
	}


	/**
	 * This handles all saves of social group data.
	 */
	public function actionSocialgroup()
	{
		$fields = array('title', 'description', 'nodeid', 'filedataid', 'invite_usernames', 'parentid', 'invite_userids',
			'group_type', 'viewperms', 'commentperms', 'moderate_topics', 'autoparselinks',
			'disablesmilies', 'allow_post', 'approve_subscription', 'group_type');

		// forum options map
		$channelOpts = array('allowsmilies' => 'disablesmilies', 'allowposting' => 'allow_post');

		$input = array();
		foreach ($fields as $field)
		{
			if (isset($_POST[$field]))
			{
				$input[$field] = $_POST[$field];
			}
		}

		//If this is the "permission" step, we must pass the four checkboxes
		if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
		{
			foreach (array( 'moderate_comments', 'autoparselinks', 'disablesmilies', 'allow_post', 'approve_subscription', 'moderate_topics') AS $field)
			{
				// channeloptions
				if ($idx = array_search($field, $channelOpts))
				{
					// some options means totally the oppositve than the bf when enable, tweak then
					if (isset($_POST[$field]))
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 0 : 1);
					}
					else
					{
						$input['options'][$idx] = (in_array($field, array('disablesmilies')) ? 1 : 0);
					}
				}

				if (!isset($_POST[$field]))
				{
					$input[$field] = 0;
				}
			}
		}

		$api = Api_InterfaceAbstract::instance();
		if (count($input) > 1)
		{
			if (!isset($input['nodeid']) OR (intval($input['nodeid']) == 0))
			{
				$nodeid = $api->callApi('socialgroup', 'createSocialGroup', array($input));
				$url = vB5_Template_Options::instance()->get('options.frontendurl') . '/sgadmin/create/settings';
				if (is_array($nodeid) AND array_key_exists('errors', $nodeid))
				{
					$message = $api->callApi('phrase', 'fetch', array('phrases' => $nodeid['errors'][0][0]));
					if (empty($message))
					{
						$message = $api->callApi('phrase', 'fetch', array('phrases' => 'pm_ajax_error_desc'));
					}

					vB5_ApplicationAbstract::handleFormError(array_pop($message), $url);
				}
				if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
				{
					$urlparams = array('sgaction' => 'create', 'action2' => 'settings');
					$url = $api->callApi('route', 'getUrl', array('sgadmin', $urlparams, array()));
					header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
					vB5_Cookie::set('sgadmin_error', $nodeid['errors'][0][0]);
					if (isset($input['title']))
					{
						vB5_Cookie::set('sg_title', $input['title']);
					}
					if (isset($input['description']))
					{
						vB5_Cookie::set('sg_description', $input['description']);
					}
					die();
				}

				if ($nodeid AND !empty($nodeid['errors']))
				{
					$urlparams = array('sgaction' => 'create', 'action2' => 'settings');
					$url = $api->callApi('route', 'getUrl', array('sgadmin', $urlparams, array()));
					header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
					vB5_Cookie::set('sgadmin_error', $nodeid['errors'][0][0]);
					if (isset($input['title']))
					{
						vB5_Cookie::set('sg_title', $input['title']);
					}
					if (isset($input['description']))
					{
						vB5_Cookie::set('sg_description', $input['description']);
					}
					die();
				}

			}
			else if(isset($input['invite_usernames']) AND $input['nodeid'])
			{
				$inviteUnames = explode(',', $input['invite_usernames']);
				$inviteIds = (isset($input['invite_userids'])) ? $input['invite_userids'] : array();
				$nodeid = $input['nodeid'];
				$api->callApi('user', 'inviteMembers', array($inviteIds, $inviteUnames, $nodeid, 'sg_member_to'));
			}
			else
			{
				$nodeid = $input['nodeid'];
				unset($input['nodeid']);

				$update = $api->callApi('content_channel', 'update', array($nodeid, $input));

				// set group type nodeoptions
				if (empty($update['errors']) AND isset($input['group_type']))
				{
					$bitfields = array();
					switch ($input['group_type'])
					{
						case 2:
							$bitfields['invite_only'] = 1;
							$bitfields['approve_membership'] = 0;
							break;
						case 1:
							$bitfields['invite_only'] = 0;
							$bitfields['approve_membership'] = 0;
							break;
						default:
							$bitfields['invite_only'] = 0;
							$bitfields['approve_membership'] = 1;
							break;
					}

					$api->callApi('node', 'setNodeOptions', array($nodeid, $bitfields));
				}

				//if this is for the permission page we handle differently

			}
			//			set_exception_handler(array('vB5_ApplicationAbstract','handleException'));
			//
			//			if (!is_numeric($nodeid) AND !empty($nodeid['errors']))
			//			{
			//				throw new exception($nodeid['errors'][0][0]);
			//			}
		}
		else if (isset($_POST['nodeid']))
		{
			$nodeid = $_POST['nodeid'];
			if (isset($_POST['next']) AND ($_POST['next'] == 'contributors'))
			{
				$updates = array();
				foreach (array('allow_post', 'moderate_comments', 'autoparselinks', 'disablesmilies', 'approve_subscription') as $bitfield)
				{
					if (empty($_POST[$bitfield]))
					{
						$updates[$bitfield] = 0;
					}
					else
					{
						$updates[$bitfield] = 1;
					}
				}
				$api->callApi('node', 'setNodeOptions', array($nodeid, $updates));
				$updates = array();

				if (isset($_POST['viewperms']))
				{
					$updates['viewperms'] = $_POST['viewperms'];
				}

				if (isset($_POST['commentperms']))
				{
					$updates['commentperms'] = $_POST['commentperms'];
				}

				if (!empty($updates))
				{
					$results = $api->callApi('node', 'setNodePerms', array($nodeid, $updates));
				}

			}
		}
		else
		{
			$nodeid = 0;
		}

		//If the user clicked Next we go to the permissions page. Otherwise we go to the node.
		if (isset($_POST['btnSubmit']))
		{
			if (isset($_POST['next']))
			{
				$action2 = $_POST['next'];
			}
			else
			{
				$action2 = 'permissions';
			}

			if (isset($_POST['sgaction']))
			{
				$sgaction = $_POST['sgaction'];
			}
			else
			{
				$sgaction = 'admin';
			}

			$urlparams = array('nodeid' => $nodeid, 'sgaction' => $sgaction, 'action2' => $action2);
			$url = $api->callApi('route', 'getUrl', array('sgadmin', $urlparams, array()));
		}
		else
		{
			$node = $api->callApi('node', 'getNode', array('nodeid' => $nodeid));
			$url = $api->callApi('route', 'getUrl', array($node['routeid'], array('nodeid' => $nodeid, 'title' => $node['title'], 'urlident' => $node['urlident']), array()));
		}

		header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl') . $url);
	}

	/**
	 * This sets a return url when creating new content and sets if the created content
	 * is a visitor message
	 *
	 */
	protected function getReturnUrl(&$result, $channelid, $parentid, $nodeid)
	{
		$api = Api_InterfaceAbstract::instance();
		$returnUrl = '';

		// ensure we have a channelid for the redirect
		if (!$channelid && $parentid)
		{
			try
			{
				$channel = $api->callApi('content_channel', 'fetchChannelById', array($parentid));
				if ($channel && isset($channel['nodeid']) && $channel['nodeid'])
				{
					$channelid = $channel['nodeid'];
				}
			}
			catch (Exception $e){}
		}

		//Get the conversation detail page of the newly created post if we are creating a starter
		if ($channelid == $parentid)
		{
			if(isset($result['moderateNode']))
			{
				$nodeid = $parentid;
			}
			$node = $api->callApi('node', 'getNode', array($nodeid));
			if ($node AND empty($node['errors']))
			{
				$returnUrl = vB5_Template_Options::instance()->get('options.frontendurl') . $api->callApi('route', 'getUrl', array('route' => $node['routeid'], 'data' => $node, 'extra' => array()));
			}
		}

		if (!empty($returnUrl))
		{
			$result['retUrl'] = $returnUrl;
		}
	}

	/**
	 * Get facebook related options to pass to the add node apis
	 *
	 * @return	array
	 *
	 */
	protected function getFacebookOptionsForAddNode()
	{
		return array(
			'fbpublish' => (isset($_POST['fbpublish']) && intval($_POST['fbpublish']) === 1),
			'baseurl' => vB5_Template_Options::instance()->get('options.frontendurl'),
		);
	}

	/**
	 * Handles attaching uploaded files to the node being created or updated. This also
	 * removes any attachments that were removed when editing.
	 *
	 * @param	int	NodeID for the node being edited or that was just created
	 * @param	array	Result array (passed by reference, may be modified)
	 */
	protected function handleAttachmentUploads($nodeId, &$result)
	{
		$api = Api_InterfaceAbstract::instance();

		// Remove attachements
		if (!empty($_POST['removeattachnodeids']))
		{
			$data = array('attachnodeid' => array());
			foreach ($_POST['removeattachnodeids'] AS $removeattachnodeid)
			{
				$removeattachnodeid = (int) $removeattachnodeid;
				if ($removeattachnodeid > 0)
				{
					$data['attachnodeid'][] = $removeattachnodeid;
				}
			}
			if (!empty($data['attachnodeid']))
			{
				$attRes = $api->callApi('node', 'removeAttachment', array($nodeId, $data));
				$this->handleErrorsForAjax($result, $attRes);
			}
		}

		// Add new attachments -- When editing, this array does not contain existing
		// attachments, even though the existing attachments are displayed because
		// the hidden inputs aren't added to the markup.
		if (isset($_POST['filedataids']))
		{
			foreach ($_POST['filedataids'] AS $k => $filedataid)
			{
				$filedataid = (int) $filedataid;

				if ($filedataid < 1)
				{
					continue;
				}

				$data = array(
					'filedataid' => $filedataid,
					'filename' => isset($_POST['filenames'][$k]) ? strval($_POST['filenames'][$k]) : '',
				);

				$attRes = $api->callApi('node', 'addAttachment', array($nodeId, $data));
				$this->handleErrorsForAjax($result, $attRes);
			}
		}

		$api->callApi('content_text', 'fixAttachBBCode', array($nodeId));

	}
}
