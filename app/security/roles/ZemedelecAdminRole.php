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

namespace App\Security\Roles;

use App\Security\RoleNames;
use Nette\Security\IRole;
use Nette\SmartObject;

/**
 * Description of ZemedelecAdminRole
 *
 * @author Pavel Junek
 */
class ZemedelecAdminRole implements IRole
{

	use SmartObject;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @param int $id
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Vrátí identifikátor zemědělského podniku.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Vrátí identifikátor role.
	 *
	 * @return string
	 */
	public function getRoleId()
	{
		return RoleNames::ZEMEDELEC_ADMIN;
	}

}
