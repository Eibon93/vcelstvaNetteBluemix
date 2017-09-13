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

use App\Forms\VcelarRegistrationFormFactory;
use App\Forms\ZemedelecRegistrationFormFactory;
use App\Forms\VcelstvoFormFactory;
use App\Model\ModelException;
use App\Managers\RegistrationManager;
use Nette\Application\UI\Form;
use Nette\Http\Response;

/**
 * - Registrace uživatele
 * - Potvrzení registrace uživatele
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class RegistrationPresenter extends BasePresenter
{

	/**
	 * @var RegistrationManager
	 * @inject
	 */
	public $registrationManager;

	/**
	 * @var VcelarRegistrationFormFactory
	 * @inject
	 */
	public $vcelarRegistrationFormFactory;

	/**
	 * @var VcelstvoFormFactory
	 * @inject
	 */
	public $vcelstvoFormFactory;

	/**
	 * @var ZemedelecRegistrationFormFactory
	 * @inject
	 */
	public $zemedelecRegistrationFormFactory;

	/*
	 * - Registrace
	 */

	public function renderObce()
	{
		$okresy = $this->model->obce->findAll();

		if ($this->ajax) {
			$this->payload->okresy = array_map(function($o) {
				return $o->toArray();
			}, array_values($okresy->fetchAll()));
			$this->sendPayload();
		}
	}

	/**
	 * Vytvoří registrační formulář.
	 *
	 * @return Form
	 */
	protected function createComponentVcelarRegistrationForm()
	{
		$form = $this->vcelarRegistrationFormFactory->create();
		$form->onSuccess[] = [$this, 'onUserRegistered'];
		return $form;
	}

	/**
	 * Vytvoří registrační formulář.
	 *
	 * @return Form
	 */
	protected function createComponentZemedelecRegistrationForm()
	{

		$form = $this->zemedelecRegistrationFormFactory->create();
		$form->onSuccess[] = [$this, 'onUserRegistered'];
		return $form;
	}

	/**
	 * Vytvoří formular na vcelstva
	 *
	 * @return Form
	 */
//	protected function createComponentVcelstvaForm()
//	{
//		$form = $this->addVcelstvoFormFactory->create();
//		$form->onSuccess[] = [$this, 'onVcelarRegistered'];
//		return $form;
//	}

	/**
	 * Voláno po zaregistrování uživatele.
	 * Přesměruje na úvodní stránku.
	 */
	public function onUserRegistered()
	{
		$this->flashMessage('Děkujeme za váš zájem. Na zadanou adresu byl odeslán e-mail s pokyny pro dokončení registrace. Prosím pokračujte podle těchto pokynů.');
		$this->redirect('Homepage:');
	}

	/*
	 * - Potvrzení registrace uživatele
	 */

	/**
	 * Ověří platnost zadaného kódu a aktivuje uživatele. Poté přesměruje na přihlašovací stránku.
	 *
	 * @param int $id
	 * @param string $code
	 */
	public function actionActivate($id, $code)
	{
		$verification = $this->model->verifications->getById($id);
		if (!$verification) {
			$this->error('Neplatný ověřovací kód.', Response::S404_NOT_FOUND);
		}

		try {
			$this->registrationManager->activateUser($verification, $code);
		} catch (ModelException $ex) {
			$this->error($ex->getMessage(), Response::S400_BAD_REQUEST);
		}

		$this->getUser()->logout(TRUE);

		$this->flashMessage('Vaše registrace je dokončena, nyní se můžete přihlásit.');
		$this->redirect('User:login');
	}

}
