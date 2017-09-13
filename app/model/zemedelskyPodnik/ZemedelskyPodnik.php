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
use Nextras\Orm\Relationships\OneHasMany;

/**
 * Zemědělský podnik.
 *
 * @property-read int $id {primary}
 * @property string $ico
 * @property string $nazev
 * @property OneHasMany|Postrik[] $postriky {1:m Postrik::$zemedelskyPodnik}
 * @property OneHasMany|User[] $uzivatele {1:m User::$zemedelskyPodnik}
 * @property Adresa $adresa {1:1 Adresa,isMain=true, oneSided=true}
 *
 * @property-read User $admin {virtual}
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class ZemedelskyPodnik extends Entity implements IResource
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
		return $this->uzivatele->get()->getBy(['active' => TRUE, 'zemedelskyPodnikAdmin' => TRUE]);
	}

	public function getResourceId()
	{
		return ResourceNames::ZEMEDELSKY_PODNIK;
	}

}
