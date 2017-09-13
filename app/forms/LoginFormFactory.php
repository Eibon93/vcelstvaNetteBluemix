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

use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na přihlašovací formulář.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class LoginFormFactory
{

	use SmartObject;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @param User $realUser
	 */
	public function __construct(User $realUser)
	{
		$this->user = $realUser;
	}

	/**
	 * Vytvoří přihlašovací formulář.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addProtection();
		$form->addBaseEmail('email', 'E-mail')
				->setRequired('E-mail je povinný údaj.');
		$form->addPassword('password', 'Heslo')
				->setRequired('Heslo je povinný údaj.');
		
                $form->addSubmit('send', 'Přihlásit se');
                

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a přihlásí uživatele.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$this->user->login($values->email, $values->password);
		} catch (AuthenticationException $ex) {
			switch ($ex->getCode()) {
				case IAuthenticator::IDENTITY_NOT_FOUND:
				case IAuthenticator::INVALID_CREDENTIAL:
					$form->addError('Neplatná e-mailová adresa nebo heslo.');
					break;
				case IAuthenticator::NOT_APPROVED:
					$form->addError('Registrace nebyla ještě dokončena. Prosím dokončete registraci podle pokynů v zaslaném e-mailu.');
					break;
				default:
					$form->addError('Přihlášení se nezdařilo.');
					break;
			}
		}
	}

}
