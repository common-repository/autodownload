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

require_once(ABSPATH . "wp-content/plugins/autodownload/commons.php");

?>
<div class="wrap">
	<h2>Autodownload</h2>
	<form method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<table class="form-table">

			<tr valign="top">
				<th scope="row">Download directory</th>
				<td>
					<input type="text" name="ad_rootDir" value="<?php echo get_option(OPTION_ROOT_DIR); ?>" /><br>
					The directory in which you upload your files. The path must be relative to your Wordpress install root. Default is wp-content/.
					<br>Current path is <b><?php echo ad_getDownloadRootDir(); ?></b>
					<?php 
						if(!ad_isDownloadRootDirValid())
						{
							echo '<br><i><b>WARNING! Directory does not exist!</b></i>';
						}
					?>
				</td>
			</tr>
		</table>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="<?php echo OPTION_ROOT_DIR ?>" />

		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
