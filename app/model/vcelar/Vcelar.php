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

use App\Security\ResourceNames;
use DateTime;
use Nette\Security\IResource;
use Nextras\Orm\Collection\ArrayCollection;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Včelař.
 *
 * @property-read int $id {primary}
 * @property string|NULL $registracniCislo
 * @property DateTime $datumRegistrace
 * @property string|NULL $rodneCislo
 * @property string|NULL $ico
 * @property string|NULL $nazev
 * @property string|NULL $vcelariOd
 * @property bool $jeReferencni
 * @property Adresa $adresa {1:1 Adresa,isMain=true, oneSided=true}
 * @property OneHasMany|ProdejnaMedu[] $prodejnyMedu {1:m ProdejnaMedu::$vcelar}
 * @property OneHasMany|Stanoviste[] $stanoviste {1:m Stanoviste::$vcelar}
 * @property OneHasMany|Vcelstvo[] $vcelstva {1:m Vcelstvo::$vcelar}
 * @property OneHasMany|Zarizeni[] $zarizeni {1:m Zarizeni::$vcelar}
 * @property OneHasMany|User[] $uzivatele {1:m User::$vcelar}
 *
 * @property-read ICollection|Stanoviste[] $aktualniStanoviste {virtual}
 * @property-read User|NULL $admin {virtual}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Vcelar extends Entity implements IResource
{

	public function getWwwStranky()
	{
		$wwwStranky = '';
		foreach ($this->uzivatele as $uzivatel) {
			if ($uzivatel->active) {
				if (strlen($uzivatel->wwwStranky) > 4) {
					$wwwStranky = $uzivatel->wwwStranky;
				}
			}
		}
		return $wwwStranky;
	}

	protected function getterAdmin()
	{
		return $this->uzivatele->get()->getBy([
					'active' => TRUE,
					'vcelarAdmin' => TRUE,
		]);
	}

	protected function getterAktualniStanoviste()
	{
		$data = [];
		foreach ($this->stanoviste as $s) {
			if ($s->aktualniVcelstva->countStored() > 0) {
				$data[] = $s;
			}
		}
		return new ArrayCollection($data, $this->getModel()->getRepository(StanovisteRepository::class));
	}

	public function getResourceId()
	{
		return $this->jeReferencni ? ResourceNames::VCELAR_REFERENCNI : ResourceNames::VCELAR;
	}

}
