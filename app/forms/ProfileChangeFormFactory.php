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

namespace App\Forms;

use App\Model\Model;
use App\Model\ModelException;
use App\Managers\UserManager;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na formulář pro změnu profilu aktuálního uživatele.
 * Tento formulář lze použít pouze pokud je uživatel přihlášen. Je povinností
 * presenteru toto ověřit.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class ProfileChangeFormFactory
{

	use SmartObject;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var UserManager
	 */
	private $userManager;

	/**
	 * @param User $user
	 * @param Model $model
	 * @param UserManager $userManager
	 */
	public function __construct(User $user, Model $model, UserManager $userManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->userManager = $userManager;
	}

	/**
	 * Vytvoří formulář pro změnu profilu.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addProtection();
		$form->addHidden('id')->setValue($this->user->id);
		$form->addUserGivenName('jmeno');
		$form->addUserFamilyName('prijmeni');
		$form->addUserPhone('telefon');
		$form->addUserWwwStranky('wwwStranky');
		
		$form->addSubmit('send', 'Uložit změnu');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a změní profil aktuálního uživatele.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$user = $this->model->users->getById($values->id);
			if (!$user) {
				throw new BadRequestException('Profil uživatele nebyl nalezen.');
			}
			if (!$this->user->isAllowed($user, 'edit')) {
				throw new ForbiddenRequestException('Nemáte oprávenění měnit profil uživatele.');
			}
			$this->userManager->changeProfile($user, [
				'jmeno' => $values->jmeno,
				'prijmeni' => $values->prijmeni,
				'telefon' => $values->telefon,
				'wwwStranky' => $values->wwwStranky
			]);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
