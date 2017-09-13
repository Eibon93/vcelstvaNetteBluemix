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

use DateTime;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Mapper\Mapper;

/**
 * Ukládá naměřené hodnoty do databáze.
 *
 * @author Pavel Junek
 */
class MereniMapper extends Mapper
{

	/**
	 * Načte všechna měření ze zadaného umístění a v zadaném časovém rozsahu.
	 *
	 * @param UmisteniVcelstva $umisteni
	 * @param DateTime|NULL $pocatek
	 * @param DateTime|NULL $konec
	 * @return ICollection
	 */
	public function findByUmisteniVcelstva(UmisteniVcelstva $umisteni, DateTime $pocatek = NULL, DateTime $konec = NULL)
	{
		$builder = $this->builder();
		$builder->where('(vcelstvo_id = %i) OR ((vcelstvo_id IS NULL) AND (stanoviste_id = %i))', $umisteni->vcelstvo->id, $umisteni->stanoviste->id);
		if ($pocatek) {
			$builder->andWhere('cas >= %dt', $pocatek);
		}
		if ($konec) {
			$builder->andWhere('cas <= %dt', $konec);
		}
		return $this->toCollection($builder);
	}

}
