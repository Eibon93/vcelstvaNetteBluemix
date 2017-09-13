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

namespace App\Api;

use Nette\SmartObject;

/**
 * Dekodér binárních dat z úlové váhy AceLogic přijatých přes síť Sigfox.
 *
 * Formát přijatých dat: WWWTTTHHHTTT
 *
 * @author Pavel Junek
 */
class AceLogicDecoder implements IDecoder
{

	use SmartObject;

	//
	// Identifikátory senzorů. Tyto senzory MUSÍ existovat a MUSÍ být přiřazeny
	// k zařízení typu AceLogic.
	//
	const SENSOR_WEIGHT = 31;
	const SENSOR_INNER_TEMP_1 = 32;
	const SENSOR_INNER_TEMP_2 = 33;
	const SENSOR_HUMIDITY = 34;
	const SENSOR_OUTER_TEMP = 35;

	/**
	 * Dekóduje zadaná binární data z váhy AceLogic a vrátí pole hodnot měřených
	 * veličin.
	 *
	 * Výsledné pole bude ve formátu [(int) senzor_id => (float) hodnota, ...].
	 *
	 * @param string $bytes binární data jako hexadecimální řetězec (12 bajtů = 24 znaků)
	 * @return array pole hodnot naměřených veličin
	 */
	public function decode($bytes)
	{
		$usedBytes = substr($bytes, -12);

		$w = substr($usedBytes, 0, 2);
		$wpoint = substr($usedBytes, 2, 1);
		$t1 = substr($usedBytes, 3, 2);
		$t1point = substr($usedBytes, 5, 1);
		$h = substr($usedBytes, 6, 2);
		$hpoint = substr($usedBytes, 8, 1);
		$t2 = substr($usedBytes, 9, 2);
		$t2point = substr($usedBytes, 11, 1);

		$weight = $this->hex2float($w, $wpoint);
		$temperature1 = $this->hex2float($t1, $t1point) - 127;
		$humidity = $this->hex2float($h, $hpoint);
		$temperature2 = $this->hex2float($t2, $t2point) - 127;

		$result = [
			self::SENSOR_WEIGHT => $weight,
			self::SENSOR_INNER_TEMP_1 => $temperature1,
			self::SENSOR_INNER_TEMP_2 => $temperature2,
			self::SENSOR_HUMIDITY => $humidity,
		];
		return $result;
	}

	/**
	 * Převede 3 hexadecimální číslice na desetinné číslo.
	 *
	 * @param string $number
	 * @param string $point
	 * @return float
	 */
	private function hex2float($number, $point)
	{
		$float = hexdec($number);
		$float += round(hexdec($point) / 15, 1);
		return $float;
	}

}
