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

use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;

/**
 * Vyhledává e-mailové zprávy.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class MessageRepository extends Repository
{

	/**
	 * Najde všechny neodeslané zprávy.
	 *
	 * @return ICollection|Message[]
	 */
	public function findUnsent()
	{
		return $this->findBy(['sentAt' => NULL]);
	}

	/**
	 * Vrátí třídy entit uložených v tomto úložišti.
	 *
	 * @return array
	 */
	public static function getEntityClassNames()
	{
		return [Message::class];
	}

}
