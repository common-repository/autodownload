<?php
/*
Plugin Name: Autodownload
Plugin URI: http://www.fabiobernasconi.com/code/autodownload/
Description: Link to files in your Wordpress blog posts and pages.
Version: 1.4
Author: Fabio Bernasconi
Author URI: http://www.fabiobernasconi.com


    ----------------------------- LICENCE -----------------------------

    Copyright 2008  Fabio Bernasconi  (email : fabio@fabiobernasconi.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

CHANGE LOG:
- 1.4 new features added (include/exclude), new function call syntax! Old syntax is still 
      working for backwards compatibility
- 1.3 bug fixes. if no description 'array' is outputed behind every download. 
      trailing slash problem fixed.
- 1.2.6 support for individual description files (one per file in directory)
- 1.2.5 sizeof in for loop causes high cpu load in some php versions (4.0.2 and others?)
      corrected!
- 1.2.4 minor changes in code
- 1.2.3 Updated comments
- 1.2.2 Added support for link descriptions. Added new function: ad__listFilesTable
- 1.2 Update typos in readme and autodownload.php
- 1.1 Reademe update
- 1.0 Bug fix: In Autodownload options the first char of the path was always cut
      of if the path contained no slashes => added strpos bool return check
      Renamed the files of autodownload: removed autodownload prefix on admin.php
      and commons.php
- 0.9 Transforms occurences of file name in the text of post/page matching
      file names of files in the indicated directories into links
- 0.2 Sorting now supported
- 0.1 Initial version

TODO's
- improve syntax error detection in "template" strings (page, post ... etc)
- add more functions: ordered list, plain text
- recurse into directories?

*/

require_once(ABSPATH . "wp-content/plugins/autodownload/commons.php");

// filters
add_filter('the_content', 'ad_parseContent');

// actions
add_action('admin_menu', 'ad_adAdmin');

/* FILTERS */

/**
 * Parses the content for comments defining calls to ad_* functions.
 * 
 * @param $content The document content
 */
function ad_parseContent($content)
{
	// find all calls to ad_* functions
	$funPattern = "ad__.+\(.*\)";
	$funPatternCommented = "<!--\s*" . $funPattern . "\s*-->";
	if(preg_match_all("/" . $funPatternCommented . "/", $content, $funDefinitionsEscaped) > 0)
	{
		// we are interested in only the first class
		$funDefinitionsEscaped = $funDefinitionsEscaped[0];
		for($i = 0; $i < count($funDefinitionsEscaped); $i++)
		{
			$replacePatterns[$i] = "/(<p>)?\s*". $funPatternCommented . "\s*(<\/p>)?/";
			
			// now get the function without html comment pre-/postfix...
			preg_match("/$funPattern/", $funDefinitionsEscaped[$i], $funDefinition);

			// see ad_parseFunDefinition for available array indices
			$fun = ad_parseFunDefinition($funDefinition[0]);
			
			// if return value is boolean, it means that it is not a 
			// callable function (invalid prefix, not ad__)
			if(is_bool($fun)) 
			{
				continue;
			}
			
			// list the files 
			$files = ad_listFiles($fun['functionArgs']);
			
			// link all occurences of filenames in the post/page
			$content = ad_linkFilesInContent($content, $files);
			
			// finally replace the html comment with the results
			$replaceStrings[$i] = call_user_func($fun['functionName'], $files, $fun['functionArgs']);
		}
		
		// get rid of the paragraph wrapping tags, which Wordpress does by default...
		// Regex is taken from lazy-k-gallery.php: http://plugins.atterberry.net/lazy-k-gallery
		// Thank you Korey Atterberry!
		return preg_replace($replacePatterns, $replaceStrings, $content, 1);
	}

	return $content;
}

/* ACTIONS */

/**
 * Called to add a new option.
 */
function ad_adAdmin() 
{
	add_options_page('Autodownload', 'Autodownload', 8, 'autodownload/admin.php');
}

/* FUNCTIONS - callable from the template */

/**
 *
 *
 */
function ad__filesList($files, $args)
{
	if(preg_match("/ul|ol/", $args['wrap']))
	{
		return ad_makeList($files, $args);
	} else if(preg_match("/table/", $args['wrap']))
	{
		return ad_makeTable($files, $args);
	}
}

/**
 * Link the files and wraps them into a <li> list items.
 *
 * @param $files Files that should be linked
 * @param $args The arguments with which the function was called
 *        see ad_parseFunDefinition
 * @return String containing all the linked files
 */
function ad__filesListLI($files, $args)
{
	$args = $args['old'];
	if(empty($args[2]))
	{
		$args[2] = "ul";
	}
	
	$outStr = '<' . $args[2] . '>';
	$length = sizeof($files);
	for($i = 0; $i < $length; $i++)
	{
		$outStr .= sprintf('<li style="%s"> %s <font style="%s">%s</font></li>', $args[3], linkToFile($files[$i], "_blank"), $args[4], $files[$i]['description']);
	}
	$outStr .= "</" . $args[2] . ">"; 

	return $outStr;
}


/**
 * Links the files and wraps them into a <li> list items.
 *
 * @param $files An array of files to link and wrap
 * @param $descs Array containing descriptions of each file
 * @param $args The arguments with which the function was called
 *        see ad_parseFunDefinition
 * @return String containing all the linked files
 */
function ad__filesListTable($files, $args)
{
	$args = $args['old'];
    $outStr = '<table style="' . $args[2] . '">';
    $length = sizeof($files);
	for($i = 0; $i < $length; $i++)
	{
        $outStr .= sprintf('<tr><td style="%s">%s</td><td style="%s">%s</td></tr>', $args[3], linkToFile($files[$i]), $args[4], $files[$i]['description']); 
    }
    $outStr .= "</table>";
    
    return $outStr;
}
?>
