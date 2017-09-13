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
use Nextras\Orm\Collection\ArrayCollection;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Konkrétní kus měřícího zařízení.
 *
 * @property-read int $id {primary}
 * @property string $identifikator
 * @property string|NULL $token
 * @property string $nazev
 * @property TypZarizeni $typZarizeni {m:1 TypZarizeni::$zarizeni}
 * @property Vcelar $vcelar {m:1 Vcelar::$zarizeni}
 * @property OneHasMany|PripojeniSenzoru[] $pripojeni {1:m PripojeniSenzoru::$zarizeni}
 * @property OneHasMany|Mereni[] $mereni {1:m Mereni::$zarizeni}
 *
 * @property-read ICollection|PripojeniSenzoru[] $aktualniPripojeni {virtual}
 *
 * @property-read Stanoviste|NULL $aktualniStanoviste {virtual}
 * @property-read ICollection|Vcelstvo[] $aktualniVcelstva {virtual}
 *
 * @author Pavel Junek
 */
class Zarizeni extends Entity
{

	/**
	 * Vrátí připojení senzorů, patřících k tomuto zařízení, v zadaný ukamžik.
	 *
	 * @param DateTime $cas
	 * @return ICollection|PripojeniSenzoru[]
	 */
	public function findPripojeni(DateTime $cas)
	{
		$data = [];
		foreach ($this->pripojeni as $pripojeni) {
			if (($pripojeni->pocatek <= $cas) && ((NULL === $pripojeni->konec) || ($cas <= $pripojeni->konec))) {
				$data[] = $pripojeni;
			}
		}
		return new ArrayCollection($data, $this->getModel()->getRepository(PripojeniSenzoruRepository::class));
	}

	protected function getterAktualniPripojeni()
	{
		return $this->pripojeni->get()
						->findBy(['konec' => NULL]);
	}

	protected function getterAktualniStanoviste()
	{
		$pripojeni = $this->aktualniPripojeni->fetch();
		return $pripojeni ? $pripojeni->stanoviste : NULL;
	}

	protected function getterAktualniVcelstva()
	{
		$data = [];
		foreach ($this->aktualniPripojeni as $p) {
			if ($p->vcelstvo) {
				$data[$p->vcelstvo->id] = $p->vcelstvo;
			}
		}
		return new ArrayCollection(array_values($data), $this->getModel()->getRepository(VcelstvoRepository::class));
	}

}
