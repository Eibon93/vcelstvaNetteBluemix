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
use Nextras\Orm\Entity\Entity;

/**
 * Připojení senzoru, namontovaného na konkrétním zařízení, ke stanovišti a
 * včelstvu.
 *
 * Senzory mohou měřit buď veličiny, které se týkají konkrétního včelstva (např.
 * vnitřní teplota úlu), nebo veličiny, které se vztahují k celému stanovišti
 * (např. venkovní teplota). V tom případě bude atribut <code>$vcelstvo</code>
 * obsahovat hodnotu <code>NULL</code>.
 *
 * Je potřeba dodržovat parametry senzorů, takže senzory, které mají atribut
 * {@link Senzor::$umisteni} nastavený na {@link Senzor::UMISTENI_VCELSTVO},
 * musí být připojeny ke včelstvu, zatímco ty, které používají
 * {@link Senzor::UMISTENI_STANOVISTE}, ke včelstvu být připojené nesmí.
 *
 * @property-read int $id {primary}
 * @property DateTime $pocatek
 * @property DateTime|NULL $konec
 * @property Stanoviste $stanoviste {m:1 Stanoviste::$pripojeneSenzory}
 * @property Vcelstvo|NULL $vcelstvo {m:1 Vcelstvo::$pripojeneSenzory}
 * @property Senzor $senzor {m:1 Senzor::$pripojeni}
 * @property Zarizeni $zarizeni {m:1 Zarizeni::$pripojeni}
 *
 * @author Pavel Junek
 */
class PripojeniSenzoru extends Entity
{
	//put your code here
}
