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

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Senzor měřené veličiny, který je namontován na zařízení nějakého typu.
 *
 * @property-read int $id {primary}
 * @property string|NULL $attr
 * @property string $umisteni {enum self::UMISTENI_*}
 * @property string $nazev
 * @property string $popis
 * @property string $barva
 * @property Velicina $velicina {m:1 Velicina::$senzory}
 * @property TypZarizeni $typZarizeni {m:1 TypZarizeni::$senzory}
 * @property OneHasMany|PripojeniSenzoru[] $pripojeni {1:m PripojeniSenzoru::$senzor}
 * @property OneHasMany|Mereni[] $mereni {1:m Mereni::$senzor}
 *
 * @property-read int|NULL $cisloUlu {virtual}
 * @author Pavel Junek
 */
class Senzor extends Entity
{

	const UMISTENI_STANOVISTE = 'stanoviste';
	const UMISTENI_VCELSTVO_1 = 'vcelstvo_1';
	const UMISTENI_VCELSTVO_2 = 'vcelstvo_2';
	const UMISTENI_VCELSTVO_3 = 'vcelstvo_3';
	const UMISTENI_VCELSTVO_4 = 'vcelstvo_4';
	const UMISTENI_VCELSTVO_5 = 'vcelstvo_5';
	const UMISTENI_VCELSTVO_6 = 'vcelstvo_6';
	const UMISTENI_VCELSTVO_7 = 'vcelstvo_7';
	const UMISTENI_VCELSTVO_8 = 'vcelstvo_8';
	const UMISTENI_VCELSTVO_9 = 'vcelstvo_9';
	const UMISTENI_VCELSTVO_10 = 'vcelstvo_10';

}
