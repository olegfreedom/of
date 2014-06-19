<?php

class vB5_Template_Stylesheet
{

	protected static $instance;
	protected $pending = array();
	protected $cssBundles = null;
	protected $ajaxCssLinks = array();

	/**
	 * List of CSS templates that have already been included on this page load and removed from $this->pending.
	 * @var array
	 */
	protected $previouslyIncluded = array();

	public static function instance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public function register($args)
	{
		$this->pending = array_unique(array_merge($this->pending, $args));
	}

	public function resetPending()
	{
		$this->previouslyIncluded = array_unique(array_merge($this->previouslyIncluded, $this->pending));
		$this->pending = array();
	}

	/**
	 * Inserts <link>s for CSS in the content
	 *
	 * @param	string	The content
	 * @param	boolean	true if we are rendering for a call to /ajax/render/ and we want CSS <link>s separate
	 */
	public function insertCss(&$content, $isAjaxTemplateRender)
	{
		// register block css templates for blocks that are used in the markup
		$this->registerBlockCssTemplates($content, $isAjaxTemplateRender);

		if (empty($this->pending))
		{
			return;
		}

		$options = vB5_Template_Options::instance();
		$storecssasfile = $options->get('options.storecssasfile');
		$cssdate = intval($options->get('miscoptions.cssdate'));
		
		// Used when css is stored as files
		$cssfiledate = intval($options->get('miscoptions.cssfiledate'));

		if (!$cssdate)
		{
			$cssdate = time(); // fallback so we get the latest css
		}

		$user = vB5_User::instance();
		$textdirection = ($user['lang_options']['direction'] ? 'ltr' : 'rtl');
		// we cannot query user directly for styleid, we need to consider other parameters
		$styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId();
		$vbcsspath = $this->getCssPath($storecssasfile, $textdirection, $styleid);

		// some css files need to be loaded always from DB
		$vbcssdbpath = $this->getCssPath(false, $textdirection, $styleid);

		$replace = '';
		$replaceAjax = array();

		//if user style customization is enabled we need to hand css_profile specially. It can never come from disk
		//regardless of the option setting.
		$userprofilecss = '';
		$userprofilecssAjax = '';

		// Search for css_additional.css and css_profile.css files, send them to the last in that order (VBV-8781)
		$additionalToQueue = '';
		$profileToQueue = '';

		foreach ($this->pending as $key => $css)
		{

			if ($css === 'css_additional.css')
			{
				$additionalToQueue = $css;
				unset ($this->pending[$key]);
				continue;
			}

			if (substr($css, 0, 15) === 'css_profile.css')
			{

				if ($options->get('options.enable_profile_styling'))
				{
					$joinChar = (strpos($vbcssdbpath . $css, '?') === false) ? '?' : '&amp;';
					$userprofilecss = '<link rel="stylesheet" type="text/css" href="' .
						htmlspecialchars($vbcssdbpath . $css) . "{$joinChar}ts=$cssdate \" />\n";
					$userprofilecssAjax = htmlspecialchars($vbcssdbpath . $css) . "{$joinChar}ts=$cssdate";
					unset ($this->pending[$key]);
				}
				else
				{
					$profileToQueue = $css;
					unset ($this->pending[$key]);
				}
			}
		}

		if (!empty($additionalToQueue))
		{
			$this->pending[] = $additionalToQueue;
		}

		if (!empty($profileToQueue))
		{
			$this->pending[] = $profileToQueue;
		}

		if ($storecssasfile)
		{
			foreach($this->pending as $css)
			{
				$cssfile = $vbcsspath . $cssfiledate . '-' . $css;
				$replace .= '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($cssfile) . "\" />\n";
				$replaceAjax[] = htmlspecialchars($cssfile);
			}
		}
		else
		{
			// Deconstruct bundle logic
			if ($this->cssBundles == null)
			{
				$this->loadCSSBundles();
			}

			$joinChar = (strpos($vbcsspath, '?') === false) ? '?' : '&amp;';
			$templates = array(); //for dupe checking
			$ieLinks = '';
			$nonIeLinks = '';
			$ieLinksAjax = array();
			$nonIeLinksAjax = array();

			// We're using css bunldes instead of combining everything into a single css.php call
			// to take advantage of client side caching. We're also incoporating the rollup system into
			// css files stored on the db by linking css.php with all the templates of that bundle in one call.
			// And we're also avoiding single templates having their own <link> tag if they're already used
			// in a bundle or elsewhere.
			foreach ($this->pending as $bundle)
			{
				if (isset($this->cssBundles[$bundle]))
				{
					$templates = array_merge($templates, $this->cssBundles[$bundle]);

					// Output the stylesheets twice-- once for IE, once for the rest.
					// For IE, we split into groups of 5 so we don't exceed IE's limit
					// on the number of CSS rules in a file. See VBV-7077
					$pendingChunks = array_chunk($this->cssBundles[$bundle], 5);
					foreach ($pendingChunks AS $pendingSheets)
					{
						$ieLinks .= '<link rel="stylesheet" type="text/css" href="' .
							htmlspecialchars($vbcsspath . implode(',', $pendingSheets)) . "{$joinChar}ts=$cssdate \" />\n";
						$ieLinksAjax[] = htmlspecialchars($vbcsspath . implode(',', $pendingSheets)) . "{$joinChar}ts=$cssdate";
					}
					$nonIeLinks .= '<link rel="stylesheet" type="text/css" href="' .
						htmlspecialchars($vbcsspath . implode(',', $this->cssBundles[$bundle])) . "{$joinChar}ts=$cssdate \" />\n";
					$nonIeLinksAjax[] = htmlspecialchars($vbcsspath . implode(',', $this->cssBundles[$bundle])) . "{$joinChar}ts=$cssdate";
				}
				else if (!in_array($bundle, $templates))
				{
					// we have a single template. that wasn't caught before. link it.
					$templates[] = $bundle;
					$ieLinks .= '<link rel="stylesheet" type="text/css" href="' .
						htmlspecialchars($vbcsspath . $bundle) . "{$joinChar}ts=$cssdate \" />\n";
					$nonIeLinks .= '<link rel="stylesheet" type="text/css" href="' .
						htmlspecialchars($vbcsspath . $bundle) . "{$joinChar}ts=$cssdate \" />\n";

					$ieLinksAjax[] = htmlspecialchars($vbcsspath . $bundle) . "{$joinChar}ts=$cssdate";
					$nonIeLinksAjax[] = htmlspecialchars($vbcsspath . $bundle) . "{$joinChar}ts=$cssdate";
				}
			}
			unset ($templates);

			$replace .= "\n<!--[if IE]>\n";
			$replace .= $ieLinks;
			$replace .= "<![endif]-->\n<!--[if !IE]><!-->\n";
			$replace .= $nonIeLinks;
			$replace .= "<!--<![endif]-->\n";

			if (vB5_Template_Runtime::isBrowser('ie'))
			{
				$replaceAjax = array_merge($replaceAjax, $ieLinksAjax);
			}
			else
			{
				$replaceAjax = array_merge($replaceAjax, $nonIeLinksAjax);
			}
		}

		// Note: This places user profile customized css after css_additional.css.
		$replace .= $userprofilecss . "\n";

		if ($userprofilecssAjax)
		{
			$replaceAjax[] = $userprofilecssAjax;
		}

		// insert the css before the first <script> tag in head element
		// if there is no script tag in <head>, then insert it at the
		// end of <head>
		$scriptPos = stripos($content, '<script');
		$headPos = stripos($content, '</head>');
		if ($scriptPos !== false && $scriptPos < (($headPos === false) ? PHP_INT_MAX : $headPos))
		{
			$top = substr($content, 0, $scriptPos);
			$bottom = substr($content, $scriptPos);
			$content = $top . $replace . $bottom;
		}
		else if ($headPos !== false)
		{
			$replace .= '</head>';
			$content = str_replace('</head>', $replace, $content);
		}
		//else
		//{	// specifically in here to accomidate fetching style sheets <link>s in ajax calls
		//	$content .= $replace;
		//}

		if ($isAjaxTemplateRender)
		{
			// We can't use the CDN URL for these, since they will be fetched via AJAX;
			// fall back to the local URL. VBV-8960
			$cdnurl = $options->get('options.cdnurl');
			if ($cdnurl)
			{
				$baseurl = vB5_Template_Options::instance()->get('options.frontendurl');
				$cdnurllen = strlen($cdnurl);
				foreach ($replaceAjax AS $key => $url)
				{
					$replaceAjax[$key] = $baseurl . substr($url, $cdnurllen);
				}
			}

			$this->ajaxCssLinks = array_unique(array_merge($this->ajaxCssLinks, $replaceAjax));
		}
	}

	public function getAjaxCssLinks()
	{
		return $this->ajaxCssLinks;
	}

	public function getCssFile($filename)
	{
		$options = vB5_Template_Options::instance();
		$storecssasfile = $options->get('options.storecssasfile');

		$user = vB5_User::instance();
		$textdirection = ($user['lang_options']['direction'] ? 'ltr' : 'rtl');
		// we cannot query user directly for styleid, we need to consider other parameters
		$styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId();
		$vbcsspath = $this->getCssPath($storecssasfile, $textdirection, $styleid);

		if ($storecssasfile)
		{
			$cssfiledate = intval($options->get('miscoptions.cssfiledate'));
			$file = htmlspecialchars($vbcsspath . $cssfiledate . '-' . $filename);
		}
		else
		{
			if (!($cssdate = intval($options->get('miscoptions.cssdate'))))
			{
				$cssdate = time(); // fallback so we get the latest css
			}

			$joinChar = (strpos($vbcsspath, '?') === false) ? '?' : '&';
			$file = htmlspecialchars($vbcsspath . $filename . "{$joinChar}ts=$cssdate");
		}

		return $file;
	}

	private function getCssPath($storecssasfile, $textdirection, $styleid)
	{
		$csspath = "";
		if ($storecssasfile)
		{
			$csspath = 'core/clientscript/vbulletin_css/style' . str_pad($styleid, 5, '0', STR_PAD_LEFT) . $textdirection[0] . '/';
		}
		else
		{
			$csspath = 'css.php?styleid=' . $styleid . '&td=' . $textdirection . '&sheet=';
		}

		$vboptions = vB5_Template_Options::instance()->getOptions();
		$vboptions = $vboptions['options'];

		$baseurl = $vboptions['cdnurl'];
		if(!$baseurl)
		{
			$baseurl = vB5_Template_Options::instance()->get('options.frontendurl');
		}

		return $baseurl . '/' . $csspath;
	}

	private function loadCSSBundles()
	{
		$cssFileList = Api_InterfaceAbstract::instance()->callApi('product', 'loadProductXmlListParsed', array('type' => 'cssrollup', 'typekey' => true));
		$vBDefaultCss = array();

		if (empty($cssFileList['vbulletin']))
		{
			return false;
		}
		else
		{
			$data = $cssFileList['vbulletin'];
		}

		if (!is_array($data['rollup'][0]))
		{
			$data['rollup'] = array($data['rollup']);
		}

		foreach ($data['rollup'] AS $file)
		{
			if (!is_array($file['template']))
			{
				$file['template'] = array($file['template']);
			}
			foreach ($file['template'] AS $name)
			{
				$vBDefaultCss["$file[name]"] = $file['template'];
			}
		}

		$this->cssBundles = $vBDefaultCss;

		// TODO: Add product xml handling here if we need it.

		return true;
	}

	/**
	 * Registers block CSS templates for the block classes used in the markup
	 * This provides "autoload" functionality for block classes.
	 *
	 * @param	string	Page HTML
	 */
	protected function registerBlockCssTemplates($content)
	{
		// suppress autoloading for AJAX requests for single templates
		//if (strpos($content, '</head>') === false)
		//{
		//	return;
		//}

		// find the blocks that were used
		if (!preg_match_all("#class=(\"|')([a-z0-9_ \t-]+)\\1#i", $content, $matches))
		{
			return;
		}

		if (empty($matches[2]))
		{
			return;
		}

		$blockTemplates = array();

		foreach ($matches[2] AS $match)
		{
			$match = trim($match);
			if ($match != '')
			{
				$match = preg_replace("#[ \t]+#", ' ', $match);
				$match = explode(' ', $match);
				foreach ($match AS $matchedClass)
				{
					if (substr($matchedClass, 0, 2) == 'b-')
					{
						// remove trailing element & modifier names
						list($matchedClass) = explode('__', $matchedClass, 2);
						list($matchedClass) = explode('--', $matchedClass, 2);

						$blockTemplates['css_' . str_replace('-', '_', $matchedClass) . '.css'] = true;
					}
				}
			}
		}

		if (empty($blockTemplates))
		{
			return;
		}

		// get bundle mappings
		if ($this->cssBundles == null)
		{
			$this->loadCSSBundles();
		}

		$bundleLookup = array();
		foreach ($this->cssBundles AS $bundle => $bundleTemplates)
		{
			foreach ($bundleTemplates AS $bundleTemplate)
			{
				$bundleLookup[$bundleTemplate] = $bundle;
			}
		}

		// make list of templates and bundles that need to be included
		$addTemplates = array();
		foreach (array_keys($blockTemplates) AS $blockTemplate)
		{
			if (isset($bundleLookup[$blockTemplate]))
			{
				// use the bundle that the block template is in
				$addTemplates[$bundleLookup[$blockTemplate]] = true;
			}
			else
			{
				// use the block template
				$addTemplates[$blockTemplate] = true;
			}
		}
		$addTemplates = array_keys($addTemplates);

		// remove any templates or bundles that have already been included on this page
		$addTemplates = array_diff($addTemplates, $this->previouslyIncluded);

		if (!empty($addTemplates))
		{
			$this->pending = array_unique(array_merge($this->pending, $addTemplates));
		}
	}
}
