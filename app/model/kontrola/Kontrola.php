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
use Nextras\Orm\Entity\Entity;

/**
 * Stanoviště včelstev.
 *
 * @property-read int $id {primary}
 * @property Vcelstvo $vcelstvo {m:1 Vcelstvo::$kontroly}
 * @property DateTime $datumKontroly
 * @property int|NULL $pocetNastavku
 * @property bool|NULL $matkaKlade
 * @property int|NULL $obsedajiUlicek
 * @property string|NULL $plod
 * @property string|NULL $zasoby
 * @property string|NULL $pyl
 * @property int|NULL $mirnost
 * @property int|NULL $sezeni
 * @property int|NULL $rojivost
 * @property int|NULL $rozvoj
 * @property int|NULL $hygiena
 * @property string|NULL $mednyVynos
 * @property string|NULL $priste
 * @property string|NULL $poznamka
 * @property bool $smazana {default FALSE}
 * @property DateTime|NULL $smazanaDatum
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Kontrola extends Entity implements IResource
{

	public function getResourceId()
	{
		return ResourceNames::KONTROLA;
	}

}
