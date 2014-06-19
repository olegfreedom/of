<?php

class vB5_Frontend_Controller_Auth extends vB5_Frontend_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function actionLoginForm(array $errors = array(), array $formData = array())
	{
		$requestUrl = vB5_Request::instance()->get('vBUrlWebroot') . vB5_Request::instance()->get('scriptPath');
		$loginUrl = vB5_Template_Options::instance()->get('options.frontendurl_login') . '/auth/login-form';
		list($requestUrl) = explode('?', $requestUrl, 2);	// strip the query string to make strpos($loginUrl, $requestUrl) work VBV-9529
		
		//@TODO: Validate URL to check against whitelisted URLs
		// VBV-8394 Remove URLPATH querystring from Login form URL
		// use referer URL instead of querystring
		//  however, if the query string is provided, use that instead to handle older URLs
		if (empty($_REQUEST['url']))
		{
			// use referrer
			$url = filter_var(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : vB5_Template_Options::instance()->get('options.frontendurl'), FILTER_SANITIZE_STRING);
		}
		else
		{
			// it's an old url. Use the query string
			$url = filter_var(isset($_REQUEST['url']) ? $_REQUEST['url'] : vB5_Template_Options::instance()->get('options.frontendurl'), FILTER_SANITIZE_STRING);
		}

		// if it's encoded, we need to decode it to check if it's gonna try to redirect to the login or registration form.
		$url_decoded = base64_decode($url, true);
		$url_decoded = $url_decoded ? $url_decoded : $url;
		if (!empty($url_decoded) AND (strpos($url_decoded, '/auth/') !== false OR strpos($url_decoded, '/register') !== false))
		{
			$url = '';
		}

		// Try to resolve some XSS attack. See VBV-1124
		// Make sure the URL hasn't been base64 encoded already
		if (!base64_decode($url, true))
		{
			$url = base64_encode($url);
		}

		// VBV-7835 Stop search engine index this page
		header("X-Robots-Tag: noindex, nofollow");
		
		
		// apparently, the reason strpos($loginUrl, $requestUrl) is in that order is because the request can 
		// come from http://localhost/sprint71m9/auth/login, when the user hits the login-strikes system.
		// So we need to leave that haystack & needle order
		if (!empty($requestUrl) AND strpos($loginUrl, $requestUrl) !== 0)
		{
			// no infinite loops here
			if (isset($_REQUEST['redirected']) AND $_REQUEST['redirected'] == 1)
			{
				throw new Exception('Unable to redirect to the correct login URL');
			}
			else
			{
				header('Location: ' . $loginUrl . '?redirected=1&url=' . urlencode($url));
				exit;
			}
		}

		$user = vB5_User::instance();

		$templater = new vB5_Template('login_form');
		$templater->register('charset', $user['lang_charset']);
		$templater->register('errors', $errors);
		$templater->register('formData', $formData);
		$templater->register('url', $url);
		$templater->register('urlpath', $url);
		$this->outputPage($templater->render());
	}

	public function actionLogin()
	{
		$api = Api_InterfaceAbstract::instance();

		// @todo password is currently sent as plain text
		if (!isset($_POST['username']) OR !isset($_POST['password']))
		{
			$this->actionLoginForm();
		}
		else
		{
			$loginInfo = $api->callApi('user', 'login', array($_POST['username'], $_POST['password'], $_POST['vb_login_md5password'], $_POST['vb_login_md5password_utf'], ''));

			if (isset($loginInfo['errors']) AND !empty($loginInfo['errors']))
			{
				$errorIds = array();
				foreach ($loginInfo['errors'] AS $k => $error)
				{
					$errorIds[] = $errorId = array_shift($error);
					// this enables the template code to parse phrases with unknown number of variables
					$loginInfo['errors'][$k] = array($errorId, $error);
				}

				$loginErrors = array(
					'errors' => $loginInfo['errors'],
					'errorIds' => implode(' ', $errorIds)
				);

				$this->actionLoginForm($loginErrors, array(
					'username' => $_POST['username'],
					'remembermeCheckedAttr' => ((isset($_POST['rememberme']) AND $_POST['rememberme']) ? ' checked="checked"' : ''),
				));
			}
			else
			{
				vB5_Auth::setLoginCookies($loginInfo);
				vB5_Auth::doLoginRedirect();
			}
		}
	}

	public function actionInlinemodLogin()
	{
		$api = Api_InterfaceAbstract::instance();

		$currentuser = vB5_User::instance();

		if (!$currentuser['userid'])
		{
			$this->sendAsJson(array('error' => 'inlinemod_auth_login_first'));
			return false;
		}

		if (empty($_POST['password']))
		{
			$this->sendAsJson(array('error' => 'inlinemod_auth_password_empty'));
			return false;
		}

		$loginInfo = $api->callApi('user', 'login', array($currentuser['username'], $_POST['password'], '', '', 'cplogin'));

		if (isset($loginInfo['errors']) AND !empty($loginInfo['errors']))
		{
			$this->sendAsJson(array('error' => 'inlinemod_auth_login_failed'));
			return false;
		}
		else
		{
			vB5_Auth::setLoginCookies($loginInfo, 'cplogin');

			$this->sendAsJson(true);
			return true;
		}
	}

	public function actionLogout()
	{
		$api = Api_InterfaceAbstract::instance();
		$api->callApi('user', 'logout', array($_REQUEST['logouthash']));

		//delete all cookies with cookiePrefix
		vB5_Cookie::deleteAll();

		// @todo: this should redirect the user back to where they were
		header('Location: ' . vB5_Template_Options::instance()->get('options.frontendurl'));
		exit;
	}

	/**
	 * Forgot password form action
	 * Reset url = /auth/lostpw/?action=pwreset&userid=<n>&activationid=<xxxxx>
	 */
	public function actionLostpw()
	{
		$input = array(
			// Send request
			'email' => (isset($_POST['email']) ? trim(strval($_POST['email'])) : ''),
			'hvinput' => isset($_POST['humanverify']) ? (array)$_POST['humanverify'] : array(),

			// Reset Request
			'action' => (isset($_REQUEST['action']) ? trim($_REQUEST['action']) : ''),
			'userid' => (isset($_REQUEST['userid']) ? trim(strval($_REQUEST['userid'])) : ''),
			'activationid' => (isset($_REQUEST['activationid']) ? trim($_REQUEST['activationid']) : ''),
		);

		if (isset($_POST['recaptcha_challenge_field']) AND $_POST['recaptcha_challenge_field'])
		{
			$input['hvinput']['recaptcha_challenge_field'] = $_POST['recaptcha_challenge_field'];
		}
		if (isset($_POST['recaptcha_response_field']) AND $_POST['recaptcha_response_field'])
		{
			$input['hvinput']['recaptcha_response_field'] = $_POST['recaptcha_response_field'];
		}

		$api = Api_InterfaceAbstract::instance();

		if ($input['action'] == 'pwreset')
		{
			$response = $api->callApi('user', 'resetPassword', array('userid' => $input['userid'], 'activationid' => $input['activationid']));
			vB5_ApplicationAbstract::showMsgPage($response['password_reset'], $response['resetpw_message']);
		}
		else
		{
			$response = $api->callApi('user', 'emailPassword', array('userid' => 0, 'email' => $input['email'], 'hvinput' => $input['hvinput']));
			$this->sendAsJson(array('response' => $response));
		}
	}
}

