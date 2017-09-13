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
use Nette\Application\UI\Form;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na formulář pro odeslání pokynů pro obnovení hesla.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class PasswordSendFormFactory extends Object
{

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
	 * Vytvoří formulář pro odeslání pokynů pro obnovení hesla.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addProtection();
		$form->addBaseEmail('email', 'E-mailová adresa')
				->setRequired();
		$form->addSubmit('send', 'Odeslat pokyny pro obnovu hesla');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a odešle uživateli pokyny pro obnovení hesla.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$user = $this->model->users->getByEmail($values->email);
			if (!$user) {
				throw new ModelException('E-mailová adresa nebyla nalezena.');
			}
			$this->userManager->verifyPasswordReset($user);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
