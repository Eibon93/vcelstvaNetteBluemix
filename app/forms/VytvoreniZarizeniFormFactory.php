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
use App\Model\TypZarizeni;
use App\Model\Vcelar;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\InvalidStateException;
use Nette\Security\User;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Description of VytvoreniZarizeniFormFactory
 *
 * @author Pavel Junek
 */
class VytvoreniZarizeniFormFactory
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
	 * Vytvoří nový formulář pro umístění zadaného zařízení.
	 *
	 * @param TypZarizeni $typZarizeni
	 * @return Form
	 */
	public function create(TypZarizeni $typZarizeni)
	{
		switch ($typZarizeni->technologie) {
			case TypZarizeni::TECH_SIGFOX:
				$pattern = '[0-9A-Fa-f]{4,8}';
				break;
			default:
				$pattern = '(?:[0-9A-Za-z]+_)?[0-9A-Za-z_]+';
				break;
		}

		$form = new Form();
		$form->addHidden('typZarizeniId', $typZarizeni->id)
				->setRequired()
				->addRule(Form::EQUAL, NULL, $typZarizeni->id);
		$form->addHidden('zarizeniId');

		$form->addText('nazev', 'Název úlové váhy')
				->setRequired();
		$form->addText('identifikator', 'Identifikátor úlové váhy')
				->setRequired()
				->addRule(Form::PATTERN, 'Zadejte prosím platný identifikátor.', $pattern);
		$form->addText('token', 'Bezpečnostní token')
				->setRequired()
				->addRule(Form::MIN_LENGTH, 'Zadejte alespoň %d znaků.', 8)
				->addRule(Form::MAX_LENGTH, 'Zadejte nejvýše %d znaků.', 128);

		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a vytvoří nové zařízení.
	 *
	 * @param Form $form
	 * @param array $values
	 */
	public function onFormSubmitted(Form $form, array $values)
	{
		$typZarizeni = $this->getTypZarizeni($values['typZarizeniId']);

//		if (!$this->user->isAllowed('zarizeni', 'create')) {
//			throw new ForbiddenRequestException();
//		}

		$vcelar = $this->getVcelar();

		try {
			$zarizeni = $this->zarizeniManager->vytvorZarizeni($typZarizeni, $vcelar, $values['identifikator'], $values['token'], $values['nazev']);
			$form['zarizeniId']->setValue($zarizeni->id);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	/**
	 * Vrátí zadaný typ zařízení.
	 *
	 * @param int $id
	 * @return TypZarizeni
	 * @throws BadRequestException
	 */
	private function getTypZarizeni($id)
	{
		$typZarizeni = $this->model->typyZarizeni->getById($id);
		if (!$typZarizeni) {
			throw new BadRequestException('', Response::S400_BAD_REQUEST);
		}
		return $typZarizeni;
	}

	/**
	 * Vrátí aktuálně přihlášeného včelaře.
	 *
	 * @return Vcelar
	 * @throws InvalidStateException
	 */
	private function getVcelar()
	{
		$uzivatel = $this->model->users->getById($this->user->getId());
		if (!$uzivatel) {
			throw new InvalidStateException();
		}
		$vcelar = $uzivatel->vcelar;
		if (!$vcelar) {
			throw new InvalidStateException();
		}
		return $vcelar;
	}

}
