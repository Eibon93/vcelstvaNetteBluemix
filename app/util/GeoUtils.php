<?php

/*
 * Copyright (C) 2017 Pavel Junek
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

namespace App\Util;

use Nette\StaticClass;

/**
 * Description of GeoUtils
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class GeoUtils
{

	use StaticClass;

	public static function format($lat, $lng)
	{
		return self::_format($lat) . ($lat < 0 ? 'S' : 'N') . ', ' . self::_format($lng) . ($lng < 0 ? 'W' : 'E');
	}

	private static function _format($val)
	{
		$deg = (int) $val;
		$tmp = 3600 * ($val - $deg);

		$min = (int) ($tmp / 60);
		$sec = $tmp - (60 * $min);

		return sprintf("%d°%d'%.3f\"", $deg, $min, $sec);
	}

}
