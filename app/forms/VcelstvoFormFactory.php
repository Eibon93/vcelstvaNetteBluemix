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

use App\Managers\VcelstvaManager;
use App\Model\Model;
use App\Model\ModelException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na registrační formulář.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class VcelstvoFormFactory
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
	 * @var VcelstvaManager
	 */
	private $vcelstvaManager;

	public function __construct(User $user, Model $model, VcelstvaManager $vcelstvaManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->vcelstvaManager = $vcelstvaManager;
	}

	/**
	 * @return Form
	 */
	public function createAddForm()
	{
		$form = new Form();
		$form->setRenderer(new Bs3FormRenderer());
		$form->addProtection();

		$form->addHidden('stanovisteId');
		$form->addDatePicker('datumUmisteni', 'Datum umístění')
				->setRequired('Datum umístění musí být zadáno.');

		$this->addCommonControls($form);

		$form->addSubmit('send', 'Uložit včelstvo');

		$form->onSuccess[] = [$this, 'onAddFormSubmitted'];

		return $form;
	}

	/**
	 * @return Form
	 */
	public function createEditForm()
	{
		$form = new Form();
		$form->setRenderer(new Bs3FormRenderer());
		$form->addProtection();

		$form->addHidden('id');

		$this->addCommonControls($form);

		$form->addSubmit('send', 'Uložit včelstvo');

		$form->onSuccess[] = [$this, 'onEditFormSubmitted'];

		return $form;
	}

	private function addCommonControls(Form $form)
	{
		$form->addText('poradoveCislo', 'Pořadové číslo úlu')
				->setRequired('Pořadové číslo úlu je povinný údaj.');
		$form->addInteger('cisloMatky', 'Číslo Matky');
		$form->addText('puvodMatky', 'Původ matky');
		$form->addRadioList('barvaMatky', 'Barva Matky', [
			'Bílá' => 'Bílá',
			'Žlutá' => 'Žlutá',
			'Červená' => 'Červená',
			'Zelená' => 'Zelená',
			'Modrá' => 'Modrá',
		]);
		$form->addText('typUlu', 'Typ úlu')
				->setRequired('Typ úlu je povinný údaj.');
		$form->addText('ramkovaMira', 'Rámková míra')
				->setRequired('Rámková míra je povinný údaj.');
	}

	public function onAddFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$data = [
				'datumUmisteni' => $values->datumUmisteni,
				'poradoveCislo' => $values->poradoveCislo,
				'cisloMatky' => $values->cisloMatky,
				'puvodMatky' => $values->puvodMatky,
				'barvaMatky' => $values->barvaMatky,
				'typUlu' => $values->typUlu,
				'ramkovaMira' => $values->ramkovaMira,
			];
			$this->vcelstvaManager->add($this->getStanoviste($values->stanovisteId), $data);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	private function getStanoviste($id)
	{
		$stanoviste = $this->model->stanoviste->getById($id);
		if (!$stanoviste) {
			throw new BadRequestException('Stanoviště nebylo nalezeno.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed($stanoviste, 'add_vcelstvo')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $stanoviste;
	}

	public function onEditFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$data = [
				'poradoveCislo' => $values->poradoveCislo,
				'cisloMatky' => $values->cisloMatky,
				'puvodMatky' => $values->puvodMatky,
				'barvaMatky' => $values->barvaMatky,
				'typUlu' => $values->typUlu,
				'ramkovaMira' => $values->ramkovaMira,
			];
			$this->vcelstvaManager->update($this->getVcelstvo($values->id), $data);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	private function getVcelstvo($id)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			throw new BadRequestException('Včelstvo nebylo nalezeno.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed($vcelstvo, 'edit')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $vcelstvo;
	}

}
