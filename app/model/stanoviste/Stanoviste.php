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
use App\Util\GeoUtils;
use DateTime;
use Nette\Security\IResource;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Stanoviště včelstev.
 *
 * @property-read int $id {primary}
 * @property Vcelar $vcelar {m:1 Vcelar::$stanoviste}
 * @property KatastralniUzemi $katastralniUzemi {m:1 KatastralniUzemi::$stanoviste}
 * @property Parcela|NULL $parcela {m:1 Parcela::$stanoviste}
 * @property PudniBlok|NULL $pudniBlok {m:1 PudniBlok::$stanoviste}
 * @property string $registracniCislo
 * @property string $nazev
 * @property double $lat
 * @property double $lng
 * @property DateTime $pocatek
 * @property DateTime|NULL $predpokladanyKonec
 * @property OneHasMany|UmisteniVcelstva[] $umistenaVcelstva {1:m UmisteniVcelstva::$stanoviste}
 * @property OneHasMany|PripojeniSenzoru[] $pripojeneSenzory {1:m PripojeniSenzoru::$stanoviste}
 * @property OneHasMany|Mereni[] $mereni {1:m Mereni::$stanoviste}
 *
 * @property-read string $souradnice {virtual}
 * @property-read ICollection|UmisteniVcelstva[] $aktualniVcelstva {virtual}
 * @property-read ICollection|UmisteniVcelstva[] $historickaVcelstva {virtual}
 * @property-read ICollection|PripojeniSenzoru[] $aktualniPripojeni {virtual}
 * @property-read ICollection|UmisteniVcelstva[] $referencniVcelstva {virtual}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Stanoviste extends Entity implements IResource
{

	protected function getterSouradnice()
	{
		return GeoUtils::format($this->lat, $this->lng);
	}

	protected function getterAktualniVcelstva()
	{
		return $this->umistenaVcelstva
						->get()
						->findBy(['aktualni' => TRUE]);
	}

	protected function getterHistorickaVcelstva()
	{
		return $this->umistenaVcelstva
						->get()
						->findBy(['aktualni' => FALSE]);
	}

	protected function getterReferencniVcelstva()
	{
		return $this->aktualniVcelstva->findBy([
					'this->vcelstvo->jeReferencni' => TRUE,
		]);
	}

	protected function getterAktualniPripojeni()
	{
		return $this->pripojeneSenzory->get()
						->findBy(['konec' => NULL]);
	}

	public function getResourceId()
	{
		return ResourceNames::STANOVISTE;
	}

}
