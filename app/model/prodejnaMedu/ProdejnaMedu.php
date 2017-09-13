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
use Nextras\Orm\Entity\Entity;

/**
 * Prodejna medu.
 *
 * @property-read int $id {primary}
 * @property Vcelar $vcelar {m:1 Vcelar::$prodejnyMedu}
 * @property DateTime $datumVytvoreni
 * @property double $lat
 * @property double $lng
 * @property Adresa $adresa {1:1 Adresa,isMain=true, oneSided=true}
 * @property string $informace
 * @property string|NULL $nazev
 * @property bool $uverejnitTelefon
 * @property bool $smazana {default FALSE}
 * @property DateTime|NULL $smazanaDatum
 * @property-read string $souradnice {virtual}
 */
class ProdejnaMedu extends Entity implements IResource
{

	public function getterSouradnice()
	{
		return GeoUtils::format($this->lat, $this->lng);
	}

	public function getResourceId()
	{
		return ResourceNames::PRODEJNA_MEDU;
	}

}
