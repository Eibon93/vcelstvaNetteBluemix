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

use App\Model\Model;
use App\Model\User;
use App\Security\Roles\UserRole;
use App\Security\Roles\VcelarAdminRole;
use App\Security\Roles\VcelarRole;
use App\Security\Roles\ZemedelecAdminRole;
use App\Security\Roles\ZemedelecRole;
use Nette\Object;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;

/**
 * Autentifikátor uživatelů.
 * Autentifikátor ověřuje e-maily a hesla a vytváří identity uživatelů.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Authenticator extends Object implements IAuthenticator
{

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @param Model $model
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	/**
	 * Ověří e-mail a heslo uživatele a vrátí jeho identitu.
	 *
	 * @param array $credentials
	 * @return IIdentity
	 */
	public function authenticate(array $credentials)
	{
		list($email, $password) = $credentials;

		$user = $this->model->users->getByEmail($email);
		if (!$user) {
			throw new AuthenticationException('User not found.', IAuthenticator::IDENTITY_NOT_FOUND);
		}
		if (!$user->active) {
			throw new AuthenticationException('User not allowed.', IAuthenticator::NOT_APPROVED);
		}
		if (!Passwords::verify($password, $user->passwordHash)) {
			throw new AuthenticationException('Invalid password.', IAuthenticator::INVALID_CREDENTIAL);
		}

		return new Identity($user->id, $this->fetchRoles($user), $this->fetchData($user));
	}

	/**
	 * Vrátí pole rolí uživatele.
	 *
	 * @param User $user
	 * @return array
	 */
	private function fetchRoles(User $user)
	{
		$roles = [];
		$roles[] = RoleNames::AUTHENTICATED;
		$roles[] = RoleNames::USER;
		$roles[] = new UserRole($user->id);
		if ($user->admin) {
			$roles[] = RoleNames::ADMIN;
		}
		if (isset($user->vcelar)) {
			if ($user->vcelarSchvalen) {
				$roles[] = RoleNames::VCELAR;
				$roles[] = new VcelarRole($user->vcelar->id);
			}
			if ($user->vcelarAdmin) {
				$roles[] = new VcelarAdminRole($user->vcelar->id);
			}
		}
		if (isset($user->zemedelskyPodnik)) {
			if ($user->zemedelskyPodnikSchvalen) {
				$roles[] = RoleNames::ZEMEDELEC;
				$roles[] = new ZemedelecRole($user->zemedelskyPodnik->id);
			}
			if ($user->zemedelskyPodnikAdmin) {
				$roles[] = new ZemedelecAdminRole($user->zemedelskyPodnik->id);
			}
		}
		return $roles;
	}

	/**
	 * Vrátí pole popisných údajů uživatele.
	 *
	 * @param User $uzivatel
	 * @return array
	 */
	private function fetchData(User $uzivatel)
	{
		return [
			'email' => $uzivatel->email,
			'jmeno' => $uzivatel->jmeno,
			'prijmeni' => $uzivatel->prijmeni,
			'vcelarId' => $uzivatel->vcelar ? $uzivatel->vcelar->id : NULL,
			'zemedelskyPodnikId' => $uzivatel->zemedelskyPodnik ? $uzivatel->zemedelskyPodnik->id : NULL,
		];
	}

}
