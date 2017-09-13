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

use DateTime;
use Nextras\Orm\Entity\Entity;

/**
 * Umístění včelstva.
 *
 * Definuje vazbu mezi včelstvem a stanovištěm. Včelstvo lze mezi stanovišti
 * přesouvat, proto obsahuje atributy určující, od kdy do kdy se včelstvo na
 * stanovišti nacházelo.
 *
 * @property int $id {primary}
 * @property DateTime $datumUmisteni
 * @property DateTime|NULL $datumPresunu
 * @property DateTime|NULL $datumZruseni
 * @property bool $aktualni {default FALSE}
 * @property Stanoviste $stanoviste {m:1 Stanoviste::$umistenaVcelstva}
 * @property Vcelstvo $vcelstvo {m:1 Vcelstvo::$umisteni}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class UmisteniVcelstva extends Entity
{

	/**
	 * Najde všechny naměřené hodnoty na tomto včelstvu v zadaném časovém rozmezí.
	 *
	 * @param DateTime|NULL $pocatek
	 * @param DateTime|NULL $konec
	 */
	public function findMereni(DateTime $pocatek = NULL, DateTime $konec = NULL)
	{
		return $this->getModel()->mereni->findByUmisteniVcelstva($this, $pocatek, $konec);
	}

}
