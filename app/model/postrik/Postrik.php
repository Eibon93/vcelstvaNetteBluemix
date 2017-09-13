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
 * Postřik.
 *
 * @property-read int $id {primary}
 * @property ZemedelskyPodnik $zemedelskyPodnik {m:1 ZemedelskyPodnik::$postriky}
 * @property KatastralniUzemi $katastralniUzemi {m:1 KatastralniUzemi::$postriky}
 * @property Parcela|NULL $parcela {m:1 Parcela::$postriky}
 * @property PudniBlok|NULL $pudniBlok {m:1 PudniBlok::$postriky}
 * @property double $lat
 * @property double $lng
 * @property DateTime $datum
 * @property string $plodina
 * @property bool $kvetouci
 * @property int $nebezpecny
 * @property bool $mimoLetovouAktivitu
 * @property bool $uverejnitTelefon
 * @property bool $smazan {default FALSE}
 * @property DateTime|NULL $smazanDatum
 * @property-read string $souradnice {virtual}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Postrik extends Entity implements IResource
{

	public function getterSouradnice()
	{
		return GeoUtils::format($this->lat, $this->lng);
	}

	public function getResourceId()
	{
		return ResourceNames::POSTRIK;
	}

}
