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
use Nette\Application\UI\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na formulář pro nastavení nového hesla.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class PasswordResetFormFactory
{

	use SmartObject;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var UserManager
	 */
	private $userManager;

	/**
	 * @param Model $model
	 * @param UserManager $userManager
	 */
	public function __construct(Model $model, UserManager $userManager)
	{
		$this->model = $model;
		$this->userManager = $userManager;
	}

	/**
	 * Vytvoří formulář pro nastavení nového hesla.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addHidden('id');
		$form->addHidden('code');
		$form->addUserPassword('newPassword');
		$form->addUserTestPassword('newPasswordTest', $form['newPassword']);
		$form->addSubmit('send', 'Nastavit heslo');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a nastaví nové heslo uživatele.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$verification = $this->model->verifications->getById($values->id);
			if (!$verification) {
				throw new BadRequestException('Ověřovací kód nebyl nalezen.');
			}
			$this->userManager->resetPassword($verification, $values->code, $values->newPassword);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
