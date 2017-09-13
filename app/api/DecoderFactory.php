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

use App\Model\Zarizeni;
use Nette\SmartObject;

/**
 * Továrna na dekodéry dat ze sítě Sigfox.
 *
 * @author Pavel Junek
 */
class DecoderFactory
{

	use SmartObject;

	const TYPE_GENERIC = 2;
	const TYPE_ACE_LOGIC = 3;

	/**
	 * Vytvoří dekodér pro příjem dat ze zadaného zařízení.
	 *
	 * @param Zarizeni $zarizeni
	 * @return IDecoder
	 * @throws InvalidStateException pokud pro zadané zařízení není k dispozici dekodér.
	 */
	public function create(Zarizeni $zarizeni)
	{
		switch ($zarizeni->typZarizeni->id) {
			case self::TYPE_GENERIC:
				return new GenericDecoder();
			case self::TYPE_ACE_LOGIC:
				return new AceLogicDecoder();
			default:
				throw new InvalidStateException(sprintf('Missing decoder for device type: %d', $zarizeni->typZarizeni->id));
		}
	}

}
