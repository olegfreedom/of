<?php

class vB5_Template_NodeText
{
	const PLACEHOLDER_PREFIX = '<!-- ##nodetext_';
	const PLACEHOLDER_SUFIX = '## -->';

	protected static $instance;
	protected $cache = array();
	protected $pending = array();
	protected $bbCodeOptions = array();
	protected $placeHolders = array();
	protected $previewLength = false;

	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function fetchOneNodeText($nodeId, $api)
	{
		$nodeid = intval($nodeId);
		$cangetattachments =  $api->callApi(
			'user', 'hasPermissions',
			array(
				'group' => 'forumpermissions',
				'permission' => 'cangetattachment',
				'nodeid' => $nodeId,
			)
		);
		$bbCodeOptions = array('allowimages' => $cangetattachments > 0); // Use += array() to prevent from it overwrites "allowimages" which is set in $bbCodeOptions parameter
		// - VBV-3236

		$cache = $api->cacheInstance(0);
		// since we're replacing phrases, we need the cachekey to be languageid sensitive
		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions, false);
		
		$found = $cache->read($cacheKey);
		
		if ($found !== false)
		{
			return $found;
		}
		$textDataArray =  $api->callApi('content_text', 'getDataForParse', array(array($nodeId)));

		if (empty($textDataArray))
		{
			return '';
		}
		list($previewText, $parsed) = $this->parseNode($textDataArray, $nodeId, $bbCodeOptions);
		
		// we need to replace the place holders before we can write to cache.
		$templateCache = vB5_Template_Cache::instance();
		$templateCache->setRenderTemplatesInReverseOrder(true);
		$templateCache->replacePlaceholders($parsed);
		$templateCache->setRenderTemplatesInReverseOrder(false);
		// also replace phrases & urls
		vB5_Template_Phrase::instance()->replacePlaceholders($parsed);
		vB5_Template_Url::instance()->replacePlaceholders($parsed);
		
		// writing to cache has been copied from parseNode() to here so that
		// the cached text has the placeholders replaced. VBV-9507
		// any changes to the node requires update
		$events = array('nodeChg_' . $nodeId);
		// need to update cache if channel changes options
		$events[] = 'nodeChg_' .  $textDataArray[$nodeId]['channelid'];
		// also need to update if phrases have been modified
		$events[] = 'vB_Language_languageCache';
		
		// write the parsed text to cache. cache for a week.
		$cache->write($cacheKey, $parsed, 10080, $events);
		
		return $parsed;
	}

	public function register($nodeId, $bbCodeOptions = array())
	{
		//  + VBV-3236 Add usergroup based permissions here for images
		$cangetattachments =  Api_InterfaceAbstract::instance()->callApi(
			'user', 'hasPermissions',
			array(
				'group' => 'forumpermissions',
				'permission' => 'cangetattachment',
				'nodeid' => $nodeId,
			)
		);
		$bbCodeOptions += array('allowimages' => $cangetattachments > 0); // Use += array() to prevent from it overwrites "allowimages" which is set in $bbCodeOptions parameter
		// - VBV-3236

		$placeHolder = $this->getPlaceholder($nodeId, $bbCodeOptions);

		$this->pending[$placeHolder] = $nodeId;
		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions);
		$this->placeHolders[$cacheKey] = $placeHolder;
		$this->bbCodeOptions[$placeHolder] = $bbCodeOptions;
		return $placeHolder;
	}


	public function registerPreview($nodeId, $bbCodeOptions = array())
	{
		//  + VBV-3236 Add usergroup based permissions here for images
		$cangetattachments =  Api_InterfaceAbstract::instance()->callApi(
			'user', 'hasPermissions',
			array(
				'group' => 'forumpermissions',
				'permission' => 'cangetattachment',
				'nodeid' => $nodeId,
			)
		);
		$bbCodeOptions += array('allowimages' => $cangetattachments > 0); // Use += array() to prevent from it overwrites "allowimages" which is set in $bbCodeOptions parameter
		// - VBV-3236

		$placeHolder = $this->getPlaceholderPre($nodeId, $bbCodeOptions);

		$this->pending[$placeHolder] = $nodeId;
		$cacheKey = $this->getCacheKey($nodeId, $bbCodeOptions, true);
		$this->placeHolders[$cacheKey] = $placeHolder;
		$this->bbCodeOptions[$placeHolder] = $bbCodeOptions;
		return $placeHolder;
	}

	public function resetPending()
	{
		$this->pending = array();
	}

	public function replacePlaceholders(&$content)
	{
		$this->fetchNodeText();

		foreach($this->cache AS $placeHolder => $replace)
		{

			$content = str_replace($placeHolder, $replace, $content);
		}

	}

	protected function getPlaceholder($nodeId, $bbCodeOptions)
	{
		if (empty($bbCodeOptions))
		{
			return self::PLACEHOLDER_PREFIX . $nodeId . self::PLACEHOLDER_SUFIX;
		}
		ksort($bbCodeOptions);
		return self::PLACEHOLDER_PREFIX . $nodeId. ':'  . serialize($bbCodeOptions) . self::PLACEHOLDER_SUFIX;
	}


	protected function getPlaceholderPre($nodeId, $bbCodeOptions)
	{
		if (empty($bbCodeOptions))
		{
			return self::PLACEHOLDER_PREFIX . '_pre_' . $nodeId . self::PLACEHOLDER_SUFIX;
		}
		ksort($bbCodeOptions);
		return self::PLACEHOLDER_PREFIX  . '_pre_'. $nodeId. ':'  . serialize($bbCodeOptions) . self::PLACEHOLDER_SUFIX;
	}


	/**
	 * Returns the cache key to be used by vB_Cache
	 * @param type $nodeId
	 * @return string
	 */
	protected function getCacheKey($nodeId, $bbCodeOptions, $preview = false)
	{
		$styleId = vB5_Template_Stylevar::instance()->getPreferredStyleId();
		$languageId = vB5_User::getLanguageId();
		$cacheKey = "vbNodeText". ($preview ? "_pre_" : '') . "{$nodeId}_{$styleId}_{$languageId}";
		if (!empty($bbCodeOptions))
		{
			ksort($bbCodeOptions);
			$cacheKey .= ':' . md5(serialize($bbCodeOptions));
		}

		return strtolower($cacheKey);
	}

	protected function extractNodeIdFromKey($cacheKey)
	{
		//If we passed in bbcode options we need to trim the end.
		$end = strpos($cacheKey, ':');

		if ($end)
		{
			$cacheKey = substr($cacheKey, 0, $end);
		}

		return filter_var($cacheKey, FILTER_SANITIZE_NUMBER_INT);
	}

	protected function fetchNodeText()
	{
		if (!empty($this->placeHolders))
		{
			// first try with cache
			$api = Api_InterfaceAbstract::instance();
			$cache = $api->cacheInstance(0);
			$found = $cache->read(array_keys($this->placeHolders));

			if (!empty($found))
			{
				$foundValues = array();
				foreach($found AS $cacheKey => $parsedText)
				{

					if ($parsedText !== false)
					{
						$nodeId = $this->extractNodeIdFromKey($cacheKey);
						$placeHolder = $this->placeHolders[$cacheKey];
						$this->cache[$placeHolder] = $parsedText;
						unset($this->placeHolders[$cacheKey]);
					}
				}
			}

			if (!empty($this->placeHolders))
			{
				$missing = array();
				foreach ($this->placeHolders AS $placeHolder)
				{
					if (isset($this->pending[$placeHolder]))
					{
						$missing[] = $this->pending[$placeHolder];
					}
				}
				// we still have to parse some nodes, fetch data for them
				$textDataArray =  Api_InterfaceAbstract::instance()->callApi('content_text', 'getDataForParse', array($missing));
				$templateCache = vB5_Template_Cache::instance();
				$phraseCache = vB5_Template_Phrase::instance();
				$urlCache = vB5_Template_Url::instance();

				// In BBCode parser, the templates of inner BBCode are registered first,
				// so they should be replaced after the outer BBCode templates. See VBV-4834.

				//Also- if we have a preview we're likely to need the full text, and vice versa. So if either is requested
				// let's parse both.
				$templateCache->setRenderTemplatesInReverseOrder(true);

				if (empty($this->previewLength))
				{
					$options =  Api_InterfaceAbstract::instance()->callApiStatic('options', 'fetchStatic', array('previewLength'));
					$this->previewLength = $options['previewLength'];
				}

				foreach($this->placeHolders AS $cacheKey => $placeHolder)
				{
					$nodeId = isset($this->pending[$placeHolder]) ? $this->pending[$placeHolder] : 0;

					if ($nodeId AND !empty($textDataArray[$nodeId]))
					{
						list($previewText, $parsed) = $this->parseNode($textDataArray, $nodeId, $this->bbCodeOptions[$placeHolder]);
						
						// It's safe to do it here cause we already are in delayed rendering.
						$templateCache->replacePlaceholders($parsed);
						$phraseCache->replacePlaceholders($parsed);
						$urlCache->replacePlaceholders($parsed);
						
						// also need to replace phrase & url placeholders for preview text
						$phraseCache->replacePlaceholders($previewText);
						$urlCache->replacePlaceholders($previewText);

						// writing to cache has been moved from parseNode() to here so that
						// the cached text has the placeholders replaced. (VBV-9507)
						// any changes to the node requires update
						$events = array('nodeChg_' . $nodeId);
						// need to update cache if channel changes options
						$events[] = 'nodeChg_' .  $textDataArray[$nodeId]['channelid'];
						// also need to update if phrases have been modified
						$events[] = 'vB_Language_languageCache';
						
						// write the parsed text values to cache. cache for a week.
						$cache->write($this->getCacheKey($nodeId, $this->bbCodeOptions[$placeHolder], false), $parsed, 10080, $events);
						$cache->write($this->getCacheKey($nodeId, $this->bbCodeOptions[$placeHolder], true), $previewText, 10080, $events);
						
						if ($parsed !== false)
						{
							if (stripos($placeHolder, '_pre_') === false)
							{
								$this->cache[$placeHolder] = $parsed;
							}
							else
							{
								$this->cache[$placeHolder] = $previewText;
							}
						}
					}
				}

				$templateCache->setRenderTemplatesInReverseOrder(false);
			}
		}
	}

	/**
	 * @param $textDataArray
	 * @param $nodeId
	 * @param $bbcodeOptions
	 * @return array
	 */
	protected function parseNode($textDataArray, $nodeId, $bbcodeOptions)
	{
		$textData = $textDataArray[$nodeId];
		$parser = new vB5_Template_BbCode();
		$parser->setRenderImmediate(true);

		$parser->setAttachments($textData['attachments']);
		//make sure we have values for all the necessary options
		foreach (array('allowimages', 'allowimagebbcode', 'allowbbcode', 'allowhtml', 'allowsmilies') as $option)
		{
			if (!empty($bbcodeOptions) AND isset($bbcodeOptions[$option]))
			{
				$textData['bbcodeoptions'][$option] = $bbcodeOptions[$option];
			}
			else if (!isset($textData['bbcodeoptions'][$option]))
			{
				$textData['bbcodeoptions'][$option] = false;
			}
		}
		$allowimages = false;

		if (!empty($bbcodeOptions) AND !empty($bbcodeOptions['allowimages']))
		{
			$allowimages = $bbcodeOptions['allowimages'];
		}
		else if (!empty($textData['bbcodeoptions']['allowimages']))
		{
			$allowimages = $textData['bbcodeoptions']['allowimages'];
		}
		else if (!empty($textData['bbcodeoptions']['allowimagecode']))
		{
			$allowimages = $textData['bbcodeoptions']['allowimagecode'];
		}

		// Get full text
		$parsed = $parser->doParse(
			$textData['rawtext'],
			$textData['bbcodeoptions']['allowhtml'],
			$textData['bbcodeoptions']['allowsmilies'],
			$textData['bbcodeoptions']['allowbbcode'],
			$allowimages,
			true, // do_nl2br
			false, // cachable
			$textData['htmlstate']
		);
		
		// Get preview text
		if (empty($this->previewLength))
		{
			$options =  Api_InterfaceAbstract::instance()->callApiStatic('options', 'fetchStatic', array('previewLength'));
			$this->previewLength = $options['previewLength'];
		}

		$previewText = $parser->get_preview(
			$textData['rawtext'],
			$this->previewLength,
			$textData['bbcodeoptions']['allowhtml'],
			true, 
			$textData['htmlstate'],
			array('do_smilies' => $textData['bbcodeoptions']['allowsmilies'])
		);
		if (strlen($previewText) < strlen($parsed))
		{
			$previewText .= '...';
		}


		return array($previewText, $parsed);
	}
}
