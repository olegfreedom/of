<?php

class vB5_Frontend_Controller_Permission extends vB5_Frontend_Controller
{
	/** Compare two arrays, and merge any non-zero values of the second into the first. Must be string => integer members

		@param		mixed	array of $string => integer values
	*	@param		mixed	array of $string => integer values
	*
	*	@return		mixed	array of $string => integer values
	 */
	public function actionMergePerms($currPerms = array(), $addPerms = array())
	{
		if (is_array($currPerms) AND is_array($addPerms))
		{
			foreach($addPerms AS $permName => $permValue)
			{
				if (is_string($permName) AND (is_numeric($permValue) OR is_bool($permValue)) AND empty($currPerms[$permName]) AND ($permValue > 0))
				{
					$currPerms[$permName] = $permValue;
				}
			}
		}
		return $currPerms;
	}

	/** Decide if the inlinemod menu should be shown
	*
	*	@param		array
	*	@param		array
	*	@param		array
	*
	*	@return		bool
	 */
	public function showInlinemodMenu($conversation = array(), $modPerms = array(), $options = array())
	{
		// It was already decided not to show the inlinemod menu
		if (isset($options['showInlineMod']) AND !$options['showInlineMod'])
		{
			return false;
		}

		if (is_array($conversation) AND !empty($conversation))
		{
			if (
				!empty($conversation['permissions']) AND 
				(
					!empty($conversation['permissions']['canmoderate']) OR
					!empty($conversation['moderatorperms']['canmoderateposts']) OR 
					!empty($conversation['moderatorperms']['candeleteposts']) OR 
					!empty($conversation['moderatorperms']['caneditposts']) OR 
					!empty($conversation['moderatorperms']['canopenclose']) OR 
					!empty($conversation['moderatorperms']['canmassmove']) OR 
					!empty($conversation['moderatorperms']['canmassprune']) OR 
					!empty($conversation['moderatorperms']['canremoveposts']) OR 
					!empty($conversation['moderatorperms']['cansetfeatured']) OR 
					!empty($conversation['moderatorperms']['canharddeleteposts'])
				)
			)
			{
				return true;
			}
		}

		// This is from the inlinemod_nemu
		$view = (isset($options['view']) ? $options['view'] : '');

		if (is_array($modPerms) AND !empty($modPerms))
		{
			if (
				($modPerms['canmove'] > 0) OR 
				($modPerms['canopenclose'] > 0) OR
				($modPerms['candeleteposts'] > 0 AND $view == 'thread') OR 
				($modPerms['canmoderateposts'] > 0) OR 
				($modPerms['caneditposts'] > 0) OR 
				($modPerms['candeletethread'] > 0) OR 
				($modPerms['cansetfeatured'] > 0) OR 
				($modPerms['canmoderateattachments'] > 0) OR 
				($modPerms['canmassmove'] > 0) OR 
				($modPerms['canmassprune'] > 0) OR 
				($modPerms['canannounce'] > 0) OR 
				($modPerms['canremoveposts'] > 0) OR 
				($modPerms['canundeleteposts'] > 0) OR 
				($modPerms['canharddeleteposts'] > 0)
			)
			{
				return true;
			}
		}

		return false;
	}
}
