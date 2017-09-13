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
use Nette\Security\IResource;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Včelstvo (nebo také úl).
 *
 * @property-read int $id {primary}
 * @property string $poradoveCislo
 * @property int|NULL $cisloMatky
 * @property string|NULL $puvodMatky
 * @property string|NULL $barvaMatky
 * @property string|NULL $typUlu
 * @property string|NULL $ramkovaMira
 * @property bool $jeReferencni
 * @property Vcelar $vcelar {m:1 Vcelar::$vcelstva}
 * @property OneHasMany|UmisteniVcelstva[] $umisteni {1:m UmisteniVcelstva::$vcelstvo}
 * @property OneHasMany|Kontrola[] $kontroly {1:m Kontrola::$vcelstvo}
 * @property OneHasMany|PripojeniSenzoru[] $pripojeneSenzory {1:m PripojeniSenzoru::$vcelstvo}
 * @property OneHasMany|Mereni[] $mereni {1:m Mereni::$vcelstvo}
 *
 * @property-read UmisteniVcelstva|NULL $aktualniUmisteni {virtual}
 * @property-read ICollection|UmisteniVcelstva[] $historickaUmisteni {virtual}
 * @property-read ICollection|PripojeniSenzoru[] $aktualniPripojeni {virtual}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Vcelstvo extends Entity implements IResource
{

	protected function getterAktualniUmisteni()
	{
		return $this->umisteni->get()
						->findBy(['aktualni' => TRUE])
						->fetch();
	}

	protected function getterHistorickaUmisteni()
	{
		return $this->umisteni->get()
						->findBy(['aktualni' => FALSE])
						->orderBy('datumUmisteni', 'DESC');
	}

	protected function getterAktualniPripojeni()
	{
		return $this->pripojeneSenzory->get()
						->findBy(['konec' => NULL]);
	}

	public function maZarizeni()
	{
		return $this->aktualniPripojeni->countStored() > 0;
	}

	public function maSdilenaZarizeni()
	{
		// Vytvoříme seznam unikátních zařízení
		$zarizeni = [];
		foreach ($this->aktualniPripojeni as $p) {
			$zarizeni[$p->zarizeni->id] = $p->zarizeni;
		}

		// Projdeme všechna zařízení a zjistíme, zda je některé připojeno i k jinému včelstvu
		foreach ($zarizeni as $z) {
			foreach ($z->aktualniPripojeni as $p) {
				if ($p->vcelstvo !== NULL && $p->vcelstvo !== $this) {
					return true;
				}
			}
		}

		return false;
	}

	public function getResourceId()
	{
		return $this->jeReferencni ? ResourceNames::VCELSTVO_REFERENCNI : ResourceNames::VCELSTVO;
	}

}
