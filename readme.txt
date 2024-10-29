=== Autodownload ===
Contributors: fbern
Donate link: http://www.fabiobernasconi.com/donatingdonating
Tags: download, post, page, common, plugin
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 1.4

Autodownload enables you to link to files in a directory in your posts and pages without
interacting with WordPress. 

== Description ==

Autodownload enables your WordPress blog to provide links to files on your server.
Emphasize has been put on ease of use and thus it does most of the things automagically. 
Installation of Autodownload is easy and should not take more than 5 minutes (if not less!). 
Once Autodownload was configured you can used in your posts or pages. A simple function call 
automatically generates links to files in a given directory. Adding new links is then a 
matter of uploading files into this directory and does not need further interaction with 
WordPress. Optionally you can also add descriptions to the links and configure the html.

Features

 * Works with posts and pages
 * Nearly no configuration (one configuration variable!!!)
 * Automatically generates links to files and wraps them in list items or a html table
 * Multiple download sources within one post or page
 * Files can be sorted either by name or by modification time
 * Generates links for every occurrence of “file name” in a post or page text
 * Supports link descriptions
 * Include/Exclude patterns support

== Installation ==

This section describes how to install the plugin.
<br><br>For **complete instructions** on how to perform the installation, please see
[Autodownload](http://www.fabiobernasconi.com/code/autodownload) homepage.

**API change in version 1.4 and above!** Autodownload now provides only one callable 
function `ad_filesList` and a new function argument syntax. For backward compatibility 
though, the old functions can still apply and need not to be updated.

1. Download autodownload from the wordpress plugin central
1. Unpack the archive and upload it to your WordPress plugins directory 
   (usually `wp-content/plugins`)
1. [optional] If you want to change the default download root directory 
   (it defaults to `wp-content/`) then go to the Autodownload options page and change it
1. In any post or page you want to add links to files you must call `ad__filesList`.
   The results of this call can be configured with parameters. A function call is always 
   enclosed in a HTML comment. Please refer to the 
   [Autodownload](http://www.fabiobernasconi.com/code/autodownload#table) homepage 
   for a list of available parameters and their meaning. 
1. [optional] If you want to add a description to the links either add a file named 
   `ad_descritions.desc` (the first entry refers to the first file, the second entry to the 
   second file, etc.) in the directory where your files reside or, for each file in the 
   directory, create a text file with the equal name but with extension `.desc` 
   (i.e. `myFile.tar.gz` -> `myFile.tar.gz.desc`). A comment in, i.e. `myFile.tar.gz.desc`, 
   has precedence over the global comments file `ad_descriptions.desc`.
   
== Frequently Asked Questions ==

None at the moment :-(

== Screenshots ==

1. Screenshot showing the (only) configuration variable for the autodownload plugin
2. Backend. That's how a post/page source looks like
3. Result of what was defined in the post/page source.
