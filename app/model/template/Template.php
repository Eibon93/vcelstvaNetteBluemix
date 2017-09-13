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
 * Šablona e-mailové zprávy.
 *
 * @property-read int $id {primary}
 * @property string $subject
 * @property string $body
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Template extends Entity implements IResource
{

	const ID_REGISTRACE = 1;
	const ID_ZMENA_EMAILU = 2;
	const ID_OBNOVENI_HESLA = 3;
	const ID_OZNAMENI_O_POSTRIKU = 4;

	/**
	 * Vrátí identifikátor prostředku aplikace.
	 *
	 * @return string
	 */
	public function getResourceId()
	{
		return ResourceNames::TEMPLATE;
	}

}
