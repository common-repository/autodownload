<?php
/*
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
*/

define("OPTION_ROOT_DIR", "ad_rootDir");

// file that usually contains the descriptions of each file (one per line)
define("IGNORE_FILES_PATTERN", "/^\.+.*|.+\.desc/"); 
define("FILE_DESCRIPTION", "ad_descriptions.desc");

/**
 * Parses a function definition string and calls it. A call to ad_parseFunDefinition
 * will, by default, returns an associative array with indices 'functionName' 
 * (name of the callable function) and 'functionArgs' (the arguments for that
 * function).
 *
 * @param $functionDefinition String denoting a function: ad_fn(.*)
 * @return An associative array with indices 'functionName' and 'functionArgs'
 *         or false if the function prefix is not ad__
 */
function ad_parseFunDefinition($functionDefinition)
{
	// simple security check. Functions starting with ad__ are callable only!
	if(strpos($functionDefinition, "ad__") != 0)
	{
		return false;
	}

	// find position of parenthesis
	$parIndex = strpos(trim($functionDefinition), "("); 

	// get the function name
	$fn = substr($functionDefinition, 0, $parIndex);

	// get the argument string
	$argsString = preg_replace("/\s*/", "", substr($functionDefinition, $parIndex + 1, -1));
	
	$args = ad_defaultArgs();
	if(preg_match("/{.*}/", $argsString, $query))
	{
		$args = ad_parseArgDef($args, $query[0]);
		
		// replace match so that we can use split for old argument
		// format (backwards compatibility)
		$argsString = preg_replace("/{.*}/", "", $argsString);
	}
	
	$args = ad_parseArgDefOld($args, $argsString);
	return array('functionName' => $fn, 'functionArgs' => $args);
}

/**
 * List the files in a directory (files only). The path is a combination of
 * the path set in the Autodownload defaults and the $dir argument.
 *
 * @param $dir  The directory to list from (relative to the download path 
 *              set in plugin options page)
 * @param $pattern Pattern of file to exclude 
 * @param $sort How to sort the resulting array.
 * @return A list of files where each entry is an associative array 
 *             with keys: 'path', 'name', 'date', and 'description' (if any)
 */
function ad_listFiles($args)
{
	$dir = $args['dir'];
	$sort = $args['sort'];
	$include = $args['include'];
	$exclude = $args['exclude'];
	
	$url = ad_getDownloadURL($dir);
	$absDir = ad_getDownloadDir($dir) . "/";

	if(!ad_isDirectory($absDir))
	{
		return array();
	}
	
	$descriptions = ad_getDescriptionArray($dir);

	// get each entry
	$files = array();
	$handle = opendir($absDir);

	$i = -1;
	while (false !== ($file = readdir($handle))) 
	{
		if(preg_match(IGNORE_FILES_PATTERN, $file))
		{
			continue;
		}
		
		$i++;

		$matchInclude = false;
		$absFile = $absDir . $file;
		if(is_file($absFile))
		{
			if(empty($include) && !empty($exclude))
			{
				if(preg_match("/".$exclude."/", $file))
				{
					continue;
				}
			} else if(!empty($include))
			{
				if(!preg_match("/" . $include . "/", $file))
				{
					continue;
				}
			}
			
			$tmp['path'] = ad_getDownloadUrl($dir . "/" . $file);
			$tmp['name'] = $file;
			$tmp['date'] = filemtime($absFile);
			
			// try to fetch the description from the specific description file
			// if false, then try to get it from the global description file
			$description = ad_getDescription($absFile. ".desc");
			if(!$description)
			{
				$description = $descriptions[$i];
			}			
			$tmp['description'] = $description;
			
			$files[] = $tmp;
		}
	}
	
	// close directory
	closedir($handle);
	
	// sort them
	usort($files, "ad_sortFilesBy_" . $sort);
	
	return $files;
}

/** 
 * Get file description from a specific file.
 */
function ad_getDescription($file)
{
	if(!file_exists($file))
	{
		return false;
	}
	
	$handle = fopen($file, "rb");
	if(!feof($handle)) 
	{
		$line = fgets($handle);
	}
	fclose($handle);
		
	return $line;
}

/** 
 * Retrieves the file comments from the 'globl' description file.
 */
function ad_getDescriptionArray($dir)
{
	$absFile = ad_getDownloadDir($dir) . "/" . FILE_DESCRIPTION;

	if(!file_exists($absFile))
	{
		return false;
	}
	
	$arr = array();
	$handle = fopen($absFile, "rb");
	while (!feof($handle)) 
	{
		$line = fgets($handle);
		$arr[] = $line;
	}
	fclose($handle);
		
	return $arr;
}

/**
 * Links all occurrences of file names in the post/page content.
 * 
 * @param $content The document content
 * @param $files An array of file names to be replaced by links
 * @return The content with all occurrences of file names replaced
 */
function ad_linkFilesInContent($content, $files)
{
	// FIXME if linkFilesInContent is called more than once with the same 
	// arguments it will replace the same string over and over!!! must be fixed
	foreach($files as $file)
	{
		$linked = sprintf('<a href="%s" target="_blank">%s</a>', $file['path'], $file['name']);
		$content = ereg_replace($file['name'], $linked, $content);
	}
	
	return $content;
}

/**
 * Default arguments for the $args array.
 */
function ad_defaultArgs()
{
	$defaultArgs = array();
	$defaultArgs['dir'] = '';
	$defaultArgs['sort'] = 'date';
	$defaultArgs['exclude'] = '';
	$defaultArgs['include'] = '';

	$defaultArgs['wrap'] = 'table';
	$defaultArgs['wrap_attributes'] = '';
	$defaultArgs['title'] = '';
	$defaultArgs['title_wrap'] = 'strong';
	$defaultArgs['title_wrap_attributes'] = '';
	$defaultArgs['old'] = array();
	
	return $defaultArgs;
}

/**
 * Parses the arguments of a given function call.
 * NOTE that this function assumes $str to be
 * the full arguments string wrapped in curly braces -> {.*}
 *
 * @param $args Default args array as returned by ad_defaultArgs()
 * @str The argument string wrapped in curly braces
 * @return Updated $args array
 */
function ad_parseArgDef($args, $str)
{
	// get rid of { and }
	$str = substr($str, 1, -1);
	$argsDef = split(",", $str);
	foreach($argsDef as $argDef)
	{
		$arg = split("=", $argDef, 2);
		$args[$arg[0]] = $arg[1];
	}
	
	return $args;
}

/**
 * Parses the arguments of a given function call (old function
 * call protocol).
 * NOTE that this function assumes $str to be a comma separated
 * string only!!
 *
 * @param $args Default args array as returned by ad_defaultArgs()
 * @str The argument string
 * @return Updated $args array
 */
function ad_parseArgDefOld($args, $str)
{
	$str = substr($str, 0, -1);
	
	$argss = split(",", $str);
	if(sizeof($argss) > 0)
	{
		if(!empty($argss[0]))
		{
			$args['dir'] = $argss[0];
		}

		if(!empty($argss[1]))
		{
			$args['sort'] = $argss[1];
		} 
		
		$args['old'] = $argss;
	}
	
	return $args;
}

/* HELPER FUNCTIONS */

/**
 * Get the absolute path to a directory.
 *
 * @param $dir A relative path that will be combined with the root path defined
 *             in the autodownload settings
 * @return The newly generated absolute path
 */
function ad_getDownloadDir($dir)
{
	$absPath = ABSPATH . get_option(OPTION_ROOT_DIR);
	return $absPath . "/" . ad_stripTrailingSlash($dir);
}

/**
 * Checks whether a path is a directory or not.
 *
 * @param $path The path that should denote a directory
 * @return True if $path points to a valid directory, false otherwise
 */
function ad_isDirectory($path)
{
	return is_dir($path);
}

/**
 * Returns the download url (link) for a given file.
 *
 * @param $fiel Relative path to the file
 * @return The complete url to the given file
 */
function ad_getDownloadURL($file)
{
	$siteUrl = get_option('siteurl');
	$relPath = get_option(OPTION_ROOT_DIR);
	return $siteUrl . "/" . $relPath . "/" . $file;
}

/**
 * Strips a trailing slash from a path string.
 * 
 * @param $path The path to strip the slash from
 * @return The path with stripped trailing slash
 */
function ad_stripTrailingSlash($path)
{
	while(substr($path, -1) == "/")
	{
		$path = substr($path, 0, -1);
	}

	return $path;
}

/**
 * Generates a list given the input files.
 *
 * @param $files List of file arrays as returned by ad_listFiles
 * @param $args Associative array with the arguments -> ad_parseArgDef
 * @return HTML
 */
function ad_makeList($files, $args)
{
	$outStr = "";
	if(!empty($args['title']))
	{
		$outStr .= sprintf('<%s %s>%s</%s>', $args['title_wrap'], $args['title_wrap_attributes'], $args['title'], $args['title_wrap']);
	}
	
	$length = sizeof($files);
	for($i = 0; $i < $length; $i++)
	{
		$str .= sprintf('<li> %s <font style="font-style:italic;">%s</font></li>', linkToFile($files[$i], "_blank"), $files[$i]['description']);
	}
	
	$outStr .= sprintf("<%s %s>%s</%s>", $args['wrap'], $args['wrap_attributes'], $str, $args['wrap']);

	return $outStr;
}

/**
 * Generates a html table given the input files.
 *
 * @param $files List of file arrays as returned by ad_listFiles
 * @param $args Associative array with the arguments -> ad_parseArgDef
 * @return HTML
 */
function ad_makeTable($files, $args)
{
	$length = sizeof($files);
	for($i = 0; $i < $length; $i++)
	{
		$str .= sprintf('<tr><td class="ad_td_left" style="padding-left:5px;">%s</td><td class="ad_td_right" style="font-style:italic;">%s</td></tr>', linkToFile($files[$i]), $files[$i]['description']);
	}
	
	$openTag = 'table';
	if(!empty($args['title']))
	{
		$str = sprintf('<tr><th colspan="2" %s>%s</td></tr>', $args['title_wrap_attributes'], $args['title']) . $str;
	}
	
	$outStr .= sprintf("<%s %s>%s</%s>", $args['wrap'], $args['wrap_attributes'], $str, $args['wrap']);

	return $outStr;
}

/* HELPER */

/**
 * Generates a html link for a file specification.
 */
function linkToFile($file, $target = "_blank")
{
	return sprintf('<a href="%s" target="%s">%s</a>', $file['path'], $target, $file['name']);
}

/**
 * Returns the absolute path to the donwload root dir (path indicated in
 * the Autodownload options page. Also formats the input field (option)
 * 'ad_rootDir' (makes it relative if absolute and strips the trailing slash).
 *
 * @return The absolute path to the download root directory
 */
function ad_getDownloadRootDir()
{
	$absPath = ABSPATH;
	$ad_rootDir = get_option(OPTION_ROOT_DIR);

	if(strlen($ad_rootDir) == 0)
	{
		update_option('ad_rootDir', "wp-content");	
	 	$absPath .= "wp-content";
	} else
	{	
		$rel_ad_rootDir = $ad_rootDir;

		// if path is not relative remove slash at pos = 0
		$pos = strpos($rel_ad_rootDir, "/"); 
		if(!is_bool($pos) && $pos == 0) // this was a bug in version 0.2
		{
			$rel_ad_rootDir = substr($rel_ad_rootDir, 1);
			update_option(OPTION_ROOT_DIR, $rel_ad_rootDir);	
		}
		
		// also check that we have no trailing slash
		$rel_ad_rootDir = ad_stripTrailingSlash($rel_ad_rootDir);
		update_option(OPTION_ROOT_DIR, $rel_ad_rootDir);

		$absPath .= $rel_ad_rootDir;
	}

	return $absPath;
}

/**
 * Determines if the path indicated in the Autodownload options
 * is valid or not.
 * 
 * @return True is path exits
 */
function ad_isDownloadRootDirValid()
{
	$path = ad_getDownloadRootDir();
	return ad_isDirectory($path);
}

/**
 * Function used by usort.
 */
function ad_sortFilesBy_name($a, $b)
{
	if(array_key_exists('name', $a))
	{
	    return ($a['name'] < $b['name']) ? -1 : 1;
	}
    return 0;
}

/**
 * Function used by usort.
 */
function ad_sortFilesBy_date($a, $b)
{
	if(array_key_exists('date', $a))
	{
	    return ($a['date'] > $b['date']) ? -1 : 1;
	}

        return 0;
}

/**
 * Function used by array_walk. Trims the value of every array element.
 */
function ad_myTrim(&$value, $key)
{
	$value = trim($value);
}

?>
