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
use Nextras\Orm\Entity\Entity;

/**
 * Uživatel aplikace.
 *
 * @property-read int $id {primary}
 * @property string $email
 * @property string $passwordHash
 * @property string $jmeno
 * @property string $prijmeni
 * @property string $telefon
 * @property string|NULL $wwwStranky
 * @property Vcelar|NULL $vcelar {m:1 Vcelar::$uzivatele}
 * @property bool $vcelarAdmin {default FALSE}
 * @property bool $vcelarSchvalen {default FALSE}
 * @property ZemedelskyPodnik|NULL $zemedelskyPodnik {m:1 ZemedelskyPodnik::$uzivatele}
 * @property bool $zemedelskyPodnikAdmin {default FALSE}
 * @property bool $zemedelskyPodnikSchvalen {default FALSE}
 * @property bool $active {default FALSE}
 * @property bool $admin {default FALSE}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class User extends Entity implements IResource
{

	/**
	 * Vrátí identifikátor prostředku aplikace.
	 *
	 * @return string
	 */
	public function getResourceId()
	{
		return ResourceNames::USER;
	}

}
