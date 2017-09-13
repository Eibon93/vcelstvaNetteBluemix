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

/**
 * Dekodér binárních dat přijatých přes síť Sigfox.
 *
 * @author Pavel Junek
 */
interface IDecoder
{

	/**
	 * Dekóduje zadaná binární data a vrátí pole hodnot jednotlivých veličin.
	 *
	 * Výsledné pole musí být ve formátu [(int) senzor_id => (float) hodnota, ...].
	 *
	 * @param string $bytes binární data jako hexadecimální řetězec (12 bajtů = 24 znaků)
	 * @return array pole hodnot naměřených veličin
	 */
	function decode($bytes);
}
