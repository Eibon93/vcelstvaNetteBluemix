<?php

/*
 * Copyright (C) 2015 Pavel Junek
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

header('Content-Type: text/plain');

function eraseDir($dir, $rmdir = TRUE)
{
	$files = array();
	$dirs = array();

	$dh = opendir($dir);
	if ($dh) {
		while ($file = readdir($dh)) {
			if ($file == '.' || $file == '..')
				continue;

			$abspath = $dir . '/' . $file;
			if (is_file($abspath)) {
				$files[] = $abspath;
			} elseif (is_dir($abspath)) {
				$dirs[] = $abspath;
			}
		}
		closedir($dh);
	}

	foreach ($files as $f) {
		echo "Deleting file $f\n";
		unlink($f);
	}

	foreach ($dirs as $d) {
		echo "Deleting dir $d\n";
		eraseDir($d, TRUE);
	}

	if ($rmdir) {
		rmdir($dir);
	}
}

eraseDir(realpath('../temp'), FALSE);
