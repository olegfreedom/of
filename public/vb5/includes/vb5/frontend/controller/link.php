<?php

class vB5_Frontend_Controller_Link extends vB5_Frontend_Controller
{

	function __construct()
	{
		parent::__construct();
	}

	function actionGetlinkdata()
	{
		$input = array(
			'url' => trim($_REQUEST['url']),
		);

		$api = Api_InterfaceAbstract::instance();

		$video = $api->callApi('content_video', 'getVideoFromUrl', array($input['url']));
		$data = $api->callApi('content_link', 'parsePage', array($input['url']));

		if ($video AND empty($video['errors']))
		{
			$result = vB5_Template::staticRenderAjax('video_edit', array(
				'video' => $video,
				'existing' => 0,
				'editMode' => 1,
				'title' => $data['title'],
				'url' => $input['url'],
				'meta' => $data['meta'],
			));
		}
		else
		{
			if ($data AND empty($data['errors']))
			{
				$result = vB5_Template::staticRenderAjax('link_edit', array(
					'images' => $data['images'],
					'title' => $data['title'],
					'url' => $input['url'],
					'meta' => $data['meta'],
				));
			}
			else
			{
				$result = array(
					'template' => array('error' => 'invalid_url'),
					'css_links' => array(),
				);
			}
		}

		$this->sendAsJson($result);
		return;
	}
}