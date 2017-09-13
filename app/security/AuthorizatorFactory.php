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

namespace App\Security;

use Nette\Object;
use Nette\Security\IAuthorizator;
use Nette\Security\Permission;

/**
 * Továrna na autorizátor.
 * Autorizátor kontroluje oprávnění uživatelů k provádění určitých akcí.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class AuthorizatorFactory extends Object
{

	/**
	 * Vytvoří nový autorizátor.
	 *
	 * @return IAuthorizator
	 */
	public static function create()
	{
		$perm = new Permission();

		$perm->addRole(RoleNames::GUEST);
		$perm->addRole(RoleNames::AUTHENTICATED);
		$perm->addRole(RoleNames::USER, [RoleNames::AUTHENTICATED]);
		$perm->addRole(RoleNames::USER_ID, [RoleNames::USER]);
		$perm->addRole(RoleNames::ZEMEDELEC, [RoleNames::USER]);
		$perm->addRole(RoleNames::ZEMEDELEC_ID, [RoleNames::ZEMEDELEC]);
		$perm->addRole(RoleNames::ZEMEDELEC_ADMIN, [RoleNames::ZEMEDELEC]);
		$perm->addRole(RoleNames::VCELAR, [RoleNames::USER]);
		$perm->addRole(RoleNames::VCELAR_ID, [RoleNames::VCELAR]);
		$perm->addRole(RoleNames::VCELAR_ADMIN, [RoleNames::VCELAR]);
		$perm->addRole(RoleNames::ADMIN);

		$perm->addResource(ResourceNames::TEMPLATE);
		$perm->addResource(ResourceNames::USER);
		$perm->addResource(ResourceNames::STANOVISTE);
		$perm->addResource(ResourceNames::VCELSTVO);
		$perm->addResource(ResourceNames::VCELSTVO_REFERENCNI, ResourceNames::VCELSTVO);
		$perm->addResource(ResourceNames::KONTROLA);
		$perm->addResource(ResourceNames::PRODEJNA_MEDU);
		$perm->addResource(ResourceNames::POSTRIK);
		$perm->addResource(ResourceNames::VCELAR);
		$perm->addResource(ResourceNames::VCELAR_REFERENCNI, ResourceNames::VCELAR);
		$perm->addResource(ResourceNames::ZEMEDELSKY_PODNIK);

		// Administrátor může vše
		$perm->allow(RoleNames::ADMIN);

		// Uživatel může prohlížet a měnit svůj vlastní profil
		// Pozn.: Tato kontrola se v aplikaci reálně nepoužívá, protože presenter
		// pro změnu profilu automaticky načítá jen profil přihlášeného uživatele.
		$isProfileOwner = function(Permission $acl) {
			$role = $acl->getQueriedRole();
			$user = $acl->getQueriedResource();

			return $role->getId() === $user->id;
		};

		$perm->allow(RoleNames::USER_ID, ResourceNames::USER, 'view', $isProfileOwner);
		$perm->allow(RoleNames::USER_ID, ResourceNames::USER, 'edit', $isProfileOwner);

		// Včelař může zadávat nová stanoviště

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::STANOVISTE, 'add');

		// Včelař může spravovat svá vlastní stanoviště

		$isStanovisteOwner = function(Permission $acl) {
			$role = $acl->getQueriedRole();
			$stanoviste = $acl->getQueriedResource();

			return $role->getId() === $stanoviste->vcelar->id;
		};

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::STANOVISTE, 'view', $isStanovisteOwner);
		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::STANOVISTE, 'edit', $isStanovisteOwner);
		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::STANOVISTE, 'delete', $isStanovisteOwner);

		// Včelař může zadávat včelstva na svých stanovištích

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::STANOVISTE, 'add_vcelstvo', $isStanovisteOwner);

		// Včelař může spravovat svá vlastní včelstva

		$isVcelstvoOwner = function(Permission $acl) {
			$role = $acl->getQueriedRole();
			$vcelstvo = $acl->getQueriedResource();

			return $role->getId() === $vcelstvo->vcelar->id;
		};

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::VCELSTVO, 'view', $isVcelstvoOwner);
		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::VCELSTVO, 'edit', $isVcelstvoOwner);
		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::VCELSTVO, 'delete', $isVcelstvoOwner);

		// Všichni uživatelé mohou vidět referenční včelstva

		$perm->allow(RoleNames::GUEST, ResourceNames::VCELSTVO_REFERENCNI, 'view');
		$perm->allow(RoleNames::AUTHENTICATED, ResourceNames::VCELSTVO_REFERENCNI, 'view');

		// Včelař může zadávat kontroly svých vlastních včelstev

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::VCELSTVO, 'add_kontrola', $isVcelstvoOwner);

		// Včelař může spravovat své vlastní kontoly

		$isKontrolaOwner = function (Permission $acl) {
			$role = $acl->getQueriedRole();
			$kontrola = $acl->getQueriedResource();

			return $role->getId() === $kontrola->vcelstvo->aktualniUmisteni->stanoviste->vcelar->id;
		};

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::KONTROLA, 'edit', $isKontrolaOwner);
		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::KONTROLA, 'delete', $isKontrolaOwner);

		// Včelař může zadávat prodejny medu

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::PRODEJNA_MEDU, 'add');

		// Včelař může spravovat své prodejny medu

		$isProdejnaOwner = function (Permission $acl) {
			$role = $acl->getQueriedRole();
			$prodejna = $acl->getQueriedResource();

			return $role->getId() === $prodejna->vcelar->id;
		};

		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::PRODEJNA_MEDU, 'edit', $isProdejnaOwner);
		$perm->allow(RoleNames::VCELAR_ID, ResourceNames::PRODEJNA_MEDU, 'delete', $isProdejnaOwner);

		// Včelař - administrátor může spravovat svůj podnik

		$isVcelarOwner = function(Permission $acl) {
			$role = $acl->getQueriedRole();
			$vcelar = $acl->getQueriedResource();

			return $role->getId() === $vcelar->id;
		};

		$perm->allow(RoleNames::VCELAR_ADMIN, ResourceNames::VCELAR, 'edit', $isVcelarOwner);

		// Zemědělec může zadávat postřiky

		$perm->allow(RoleNames::ZEMEDELEC_ID, ResourceNames::POSTRIK, 'add');

		// Zemědělec může spravovat své vlastní postřiky

		$isPostrikOwner = function (Permission $acl) {
			$role = $acl->getQueriedRole();
			$postrik = $acl->getQueriedResource();

			return $role->getId() === $postrik->zemedelskyPodnik->id;
		};

		$perm->allow(RoleNames::ZEMEDELEC_ID, ResourceNames::POSTRIK, 'edit', $isPostrikOwner);
		$perm->allow(RoleNames::ZEMEDELEC_ID, ResourceNames::POSTRIK, 'delete', $isPostrikOwner);

		// Zemědělec - administrátor může spravovat svůj podnik

		$isZemedelskyPodnikOwner = function(Permission $acl) {
			$role = $acl->getQueriedRole();
			$zemedelskyPodnik = $acl->getQueriedResource();

			return $role->getId() === $zemedelskyPodnik->id;
		};

		$perm->allow(RoleNames::ZEMEDELEC_ADMIN, ResourceNames::ZEMEDELSKY_PODNIK, 'edit', $isZemedelskyPodnikOwner);

		return $perm;
	}

}
