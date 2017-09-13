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

namespace App\Model;

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Typ měřícího zařízení.
 *
 * @property-read int $id {primary}
 * @property string $nazev
 * @property string $technologie {enum self::TECH_*}
 * @property string|NULL $popis
 * @property string|NULL $vyrobce
 * @property string|NULL $dokumentace
 * @property bool $zobrazit {default false}
 * @property OneHasMany|Senzor[] $senzory {1:m Senzor::$typZarizeni}
 * @property OneHasMany|Zarizeni[] $zarizeni {1:m Zarizeni::$typZarizeni}
 *
 * @author Pavel Junek
 */
class TypZarizeni extends Entity
{

	//
	// Technologie přenosu dat
	//
	const TECH_SIGFOX = 'sigfox';
	const TECH_PUSH = 'push';
	const TECH_PULL = 'pull';

}
