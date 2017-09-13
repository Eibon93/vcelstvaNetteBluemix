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

namespace App\Forms;

use App\Managers\ZarizeniManager;
use App\Model\Model;
use App\Model\ModelException;
use App\Model\Zarizeni;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na formulář, pomocí kterého může uživatel odpojovat svá měřící
 * zařízení od stanovišť i včelstev.
 *
 * Uživatel pouze potvrdí, že chce zařízení kompletně odpojit.
 *
 * Metoda create() potřebuje znát zařízení, pro které má formulář vytvořit.
 *
 * @author Pavel Junek
 */
class OdpojeniZarizeniFormFactory
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
	 * @var ZarizeniManager
	 */
	private $zarizeniManager;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param User $user
	 * @param Model $model
	 * @param ZarizeniManager $zarizeniManager
	 */
	public function __construct(User $user, Model $model, ZarizeniManager $zarizeniManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->zarizeniManager = $zarizeniManager;
	}

	/**
	 * Vytvoří nový formulář pro odpojení zadaného zařízení.
	 *
	 * @param Zarizeni $zarizeni
	 * @return Form
	 */
	public function create(Zarizeni $zarizeni)
	{

		$form = new Form();
		$form->addHidden('zarizeniId', $zarizeni->id)
				->setRequired()
				->addRule(Form::EQUAL, NULL, $zarizeni->id);

		$form->addSubmit('send', 'Odpojit');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a odpojí všechny senzory zařízení.
	 *
	 * @param Form $form
	 * @param array $values
	 */
	public function onFormSubmitted(Form $form, array $values)
	{
		$zarizeni = $this->getZarizeni($values['zarizeniId']);

//		if (!$this->user->isAllowed($zarizeni, 'connect')) {
//			throw new ForbiddenRequestException();
//		}

		try {
			$this->zarizeniManager->odpojZarizeni($zarizeni);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	/**
	 * Vrátí zadané zařízení.
	 *
	 * @param int $id
	 * @return Zarizeni
	 * @throws BadRequestException
	 */
	private function getZarizeni($id)
	{
		$zarizeni = $this->model->zarizeni->getById($id);
		if (!$zarizeni) {
			throw new BadRequestException('', Response::S400_BAD_REQUEST);
		}
		return $zarizeni;
	}

}
