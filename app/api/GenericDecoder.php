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
 * Dekodér dat z univerzální váhy.
 *
 * +----+----+----+----+----+----+----+----+----+----+----+----+
 * | 1.B| 2.B| 3.B| 4.B| 5.B| 6.B| 7.B| 8.B| 9.B|10.B|11.B|12.B|
 * +----+----+----+----+----+----+----+----+----+----+----+----+
 * |    M1   | T11| T12| H1 |    M2   | T21| T22| H2 | T0 |  R |
 * +---------+----+----+----+---------+----+----+----+----+----+
 *
 * M1        hmotnost 1. úlu
 * T11, T12  dvě vnitřní teploty 1. úlu
 * H1        vnitřní vlhkost 1. úlu
 * M2        hmotnost 2. úlu
 * T21, T22  dvě vnitřní teploty 2. úlu
 * H2        vnitřní vlhkost 2. úlu
 * T0        venkovní teplota (společná pro stanoviště)
 * R         rezerva pro doplňující parametry daného výrobce
 *
 * Popis kódování:
 * Hmotnost:
 *   celé číslo reprezentující desítky gramů
 *   rozsah 0 až 655.34 kg
 *   ROUND((hmotnost v kg) * 100) nebo ROUND((hmotnost v g) / 10),
 *   Číslo 65535 znamená chybějící (neplatnou) hodnotu
 * Teplota:
 *   celé číslo reprezentující poloviny stupňů Celsia,
 *   rozsah -50 až +77
 *   tj. ROUND((teplota ve st. C) * 2) + 100)
 *   Číslo 255 znamená chybějící (neplatnou) hodnotu
 * Vlhkost:
 *   celé číslo reprezentující vlhkost v %
 *   ROUND(vlhkost v %)
 *   Číslo 255 znamená chybějící (neplatnou) hodnotu, ale ignorujeme jakékoliv číslo vyšší než 100.
 *
 * @author Pavel Junek
 */
class GenericDecoder implements IDecoder
{

	use SmartObject;

	//
	// Identifikátory senzorů. Tyto senzory MUSÍ existovat a MUSÍ být přiřazeny
	// k univerzálnímu zařízení.
	//
	const SENSOR_WEIGHT_1 = 13;
	const SENSOR_INNER_TEMP_11 = 14;
	const SENSOR_INNER_TEMP_12 = 15;
	const SENSOR_HUMIDITY_1 = 16;
	const SENSOR_WEIGHT_2 = 20;
	const SENSOR_INNER_TEMP_21 = 21;
	const SENSOR_INNER_TEMP_22 = 22;
	const SENSOR_HUMIDITY_2 = 23;
	const SENSOR_OUTER_TEMP = 27;

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
		$result = [];
		$result[self::SENSOR_WEIGHT_1] = $this->decodeWeight(substr($bytes, 0, 4));
		$result[self::SENSOR_INNER_TEMP_11] = $this->decodeTemperature(substr($bytes, 4, 2));
		$result[self::SENSOR_INNER_TEMP_12] = $this->decodeTemperature(substr($bytes, 6, 2));
		$result[self::SENSOR_HUMIDITY_1] = $this->decodeHumidity(substr($bytes, 8, 2));
		$result[self::SENSOR_WEIGHT_2] = $this->decodeWeight(substr($bytes, 10, 4));
		$result[self::SENSOR_INNER_TEMP_21] = $this->decodeTemperature(substr($bytes, 14, 2));
		$result[self::SENSOR_INNER_TEMP_22] = $this->decodeTemperature(substr($bytes, 16, 2));
		$result[self::SENSOR_HUMIDITY_2] = $this->decodeHumidity(substr($bytes, 18, 2));
		$result[self::SENSOR_OUTER_TEMP] = $this->decodeTemperature(substr($bytes, 20, 2));
		return array_filter($result);
	}

	/**
	 * Dekóduje hmotnost ze zadané dvojice bajtů.
	 *
	 * @param string $bytes binární data jako hexadecimální řetězec.
	 * @return float vypočtená hmotnost, nebo FALSE, pokud hodnota není platná.
	 */
	private function decodeWeight($bytes)
	{
		$x = hexdec($bytes);
		return $x < 65535 ? $x / 100 : FALSE;
	}

	/**
	 * Dekóduje teplotu ze zadaného bajtu.
	 *
	 * @param string $bytes binární data jako hexadecimální řetězec.
	 * @return float vypočtená teplota, nebo FALSE, pokud hodnota není platná.
	 */
	private function decodeTemperature($bytes)
	{
		$x = hexdec($bytes);
		return $x < 255 ? ($x - 100) / 2 : FALSE;
	}

	/**
	 * Dekóduje vlhkost ze zadaného bajtu.
	 *
	 * @param string $bytes binární data jako hexadecimální řetězec.
	 * @return float vypočtená vlhkost, nebo FALSE, pokud hodnota není platná.
	 */
	private function decodeHumidity($bytes)
	{
		$x = hexdec($bytes);
		return $x <= 100 ? $x : FALSE;
	}

}
