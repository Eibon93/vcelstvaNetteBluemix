<?php

/*
 * Copyright (C) 2016 Pavel Junek
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
 * Parcela.
 *
 * @property string $id {primary}
 * @property KatastralniUzemi $katastralniUzemi {m:1 KatastralniUzemi::$parcely}
 * @property string $cislo
 * @property string|NULL $podcislo
 * @property string $druh {enum self::DRUH_*}
 * @property OneHasMany|Postrik[] $postriky {1:m Postrik::$parcela}
 * @property OneHasMany|Stanoviste[] $stanoviste {1:m Stanoviste::$parcela}
 *
 * @property-read string $celyNazev {virtual}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Parcela extends Entity
{

	const DRUH_STAVEBNI = 's';
	const DRUH_POZEMKOVA = 'p';

	/**
	 * Vrátí celý název parcely.
	 *
	 * @return string
	 */
	public function getterCelyNazev()
	{
		return $this->cislo . ($this->podcislo ? '/' . $this->podcislo : '') . ($this->druh === self::DRUH_STAVEBNI ? ' (stavební)' : ' (pozemková)');
	}

}
