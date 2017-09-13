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

namespace App\Presenters;

use App\Forms\LoginFormFactory;
use App\Forms\PasswordResetFormFactory;
use App\Forms\PasswordSendFormFactory;
use App\Model\ModelException;
use App\Managers\UserManager;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Utils\ArrayHash;

/**
 * - Přihlášení uživatele
 * - Odhlášení uživatele
 * - Obnovení zapomenutého hesla
 * - Nastavení nového hesla
 * - Potvrzení změny e-mailu
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class UserPresenter extends BasePresenter
{

	/**
	 * @var UserManager
	 * @inject
	 */
	public $userManager;

	/**
	 * @var LoginFormFactory
	 * @inject
	 */
	public $loginFormFactory;

	/**
	 * @var PasswordResetFormFactory
	 * @inject
	 */
	public $passwordResetFormFactory;

	/**
	 * @var PasswordSendFormFactory
	 * @inject
	 */
	public $passwordSendFormFactory;

	/*
	 * - Přihlášení uživatele
	 */

	/**
	 * Vytvoří přihlašovací formulář.
	 *
	 * @return Form
	 */
	protected function createComponentLoginForm()
	{
		$form = $this->loginFormFactory->create();
		$form->onSuccess[] = [$this, 'onUserLoggedIn'];
		return $form;
	}

	/**
	 * Voláno po přihlášení uživatele.
	 * Přesměruje na úvodní stránku.
	 */
	public function onUserLoggedIn()
	{
		$this->redirect('Homepage:');
	}

	/*
	 * - Odhlášení uživatele
	 */

	/**
	 * Odhlásí uživatele a následně přesměruje na úvodní stránku.
	 */
	public function actionLogout()
	{
		$this->getUser()->logout(TRUE);

		$this->flashMessage('Byl(a) jste úspěšně odhlášen(a).');
		$this->redirect('Homepage:');
	}

	/*
	 * - Obnovení zapomenutého hesla
	 */

	/**
	 * Vytvoří formulář pro odeslání pokynů pro obnovení hesla.
	 *
	 * @return Form
	 */
	protected function createComponentPasswordSendForm()
	{
		$form = $this->passwordSendFormFactory->create();
		$form->onSuccess[] = [$this, 'onPasswordSent'];
		return $form;
	}

	/**
	 * Voláno po odeslání pokynů pro obnovení hesla.
	 * Přesměruje na úvodní stránku.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onPasswordSent()
	{
		$this->flashMessage('Na zadanou adresu byl odeslán e-mail s pokyny pro obnovení hesla. Prosím pokračujte podle těchto pokynů.');
		$this->redirect('Homepage:');
	}

	/*
	 * - Nastavení nového hesla
	 */

	/**
	 * Zobrazí formulář pto nastavení nového hesla.
	 *
	 * @param int $id
	 * @param string $code
	 */
	public function renderResetPassword($id, $code)
	{
		$verification = $this->model->verifications->getById($id);
		if (!$verification) {
			$this->error('Potvrzovací kód nebyl nalezen.', Response::S404_NOT_FOUND);
		}

		$form = $this->getComponent('passwordResetForm');
		$form->setDefaults([
			'id' => $id,
			'code' => $code,
		]);

		$this->template->profile = $verification->user;
	}

	/**
	 * Vytvoří formulář pro nastavení nového hesla.
	 *
	 * @return Form
	 */
	protected function createComponentPasswordResetForm()
	{
		$form = $this->passwordResetFormFactory->create();
		$form->onSuccess[] = [$this, 'onPasswordReset'];
		return $form;
	}

	/**
	 * Voláno po nastavení nového hesla.
	 * Přesměruje na přihlašovací stránku.
	 */
	public function onPasswordReset()
	{
		$this->getUser()->logout(TRUE);

		$this->flashMessage('Nové heslo bylo nastaveno, nyní se můžete přihlásit.');
		$this->redirect('login');
	}

	/*
	 * - Potvrzení změny e-mailu
	 */

	/**
	 * Nastaví nový e-mail uživatele.
	 *
	 * @param int $id
	 * @param string $code
	 */
	public function actionResetEmail($id, $code)
	{
		$verification = $this->model->verifications->getById($id);
		if (!$verification) {
			$this->error('Neplatný ověřovací kód.', Response::S404_NOT_FOUND);
		}

		try {
			$this->userManager->resetEmail($verification, $code);
		} catch (ModelException $ex) {
			$this->error($ex->getMessage(), Response::S400_BAD_REQUEST);
		}

		$this->getUser()->logout(TRUE);

		$this->flashMessage('Vaše e-mailová adresa byla změněna. Nyní se prosím znovu přihlaste.');
		$this->redirect('login');
	}

}
