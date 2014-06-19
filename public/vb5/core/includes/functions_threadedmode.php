<?php if(!defined('VB_ENTRY')) die('Access denied.');
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

// ###################### Start findparents #######################
function fetch_post_parentlist($postid)
{
	global $postparent;

	$retlist = '';
	$postid = $postparent["$postid"];

	while ($postid != 0)
	{
		$retlist .= ",$postid";
		$postid = $postparent["$postid"];
	}

	return $retlist;
}

// ###################### Start fetch statusicon from child posts #######################
function fetch_statusicon_from_child_posts($postid)
{
	// looks through children to see if there are new posts or not
	global $postarray, $ipostarray, $vbulletin;
	global $threadinfo; // ugly!

	if ($postarray["$postid"]['dateline'] > $threadinfo['threadview'])
	{
		return 1;
	}
	else
	{
		if (is_array($ipostarray["$postid"]))
		{ //if it has children look in there
			foreach($ipostarray["$postid"] AS $postid)
			{
				if (fetch_statusicon_from_child_posts($postid))
				{
					return 1;
				}
			}
		}
		return 0;
	}
}

// ###################### Start getImageString #######################
function fetch_threaded_post_image_string($post, $depth)
{
	global $ipostarray, $vbulletin;
	static $depthbits;

	$imgstring = array();
	$blanks = 0;

	for ($i = 1; $i < $depth; $i ++) // get initial images
	{
		if ($depthbits["$i"] == '-')
		{
			$blanks++;
		}
		else if ($blanks != 0)
		{
			$imgstring[] = $blanks;
			$imgstring[] = $depthbits["$i"];
			$blanks = 0;
		}
		else
		{
			$imgstring[] = $depthbits["$i"];
		}
	}

	if ($blanks != 0) // return blanks if there are any left over
	{
		$imgstring[] = $blanks;
	}

	// find out if current post is last at this level of the tree
	$lastElm = sizeof($ipostarray["$post[parentid]"]) - 1;
	if ($ipostarray["$post[parentid]"]["$lastElm"] == $post['postid'])
	{
		$islast = 1;
	}
	else
	{
		$islast = 0;
	}

	if ($islast == 1) // if post is not last in tree, use L graphic...
	{
		$depthbits["$depth"] = '-';
		$imgstring[] = 'L';

	}
	else // ... otherwise use T graphic
	{
		$depthbits["$depth"] = 'I';
		$imgstring[] = 'T';
	}

	return implode(',', $imgstring);

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 75544 $
|| ####################################################################
\*======================================================================*/
?>