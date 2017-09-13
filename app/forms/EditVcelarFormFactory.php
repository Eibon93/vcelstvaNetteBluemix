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

use App\Managers\VcelarManager;
use App\Model\Model;
use App\Model\ModelException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na registrační formulář.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class EditVcelarFormFactory
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
	 * @var VcelarManager
	 */
	private $vcelarManager;

	public function __construct(User $user, Model $model, VcelarManager $vcelarManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->vcelarManager = $vcelarManager;
	}

	/**
	 * Vytvoří formulář.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addProtection();
		$form->addHidden('id');

		$form->addGroup('Druh registrace');
		$form->addPravnickaNeboFyzickaOsobaRadioList('pravnickaFyzicka');
		$form->addRegistrationNumber('registracniCislo');

		$form->addGroup('Údaje o chovateli')
				->setOption('id', 'group-fyzicka');
		$form->addRodneCislo('rodneCislo')
				->addConditionOn($form['pravnickaFyzicka'], Form::EQUAL, 'f')
				->setRequired('Prosím vyplňte rodné číslo.');

		$form->addGroup('Údaje o včelařském podniku')
				->setOption('id', 'group-pravnicka');
		$form->addNazevPodniku('nazev')
				->addConditionOn($form['pravnickaFyzicka'], Form::EQUAL, 'p')
				->setRequired('Prosím vyplňte název podniku.');
		$form->addICO('ico')
				->addConditionOn($form['pravnickaFyzicka'], Form::EQUAL, 'p')
				->setRequired('Prosím vyplňte IČO podniku.');

		$form->addGroup('Trvalé bydliště nebo sídlo');
		$form->addObec('castObce');
		$form->addStreet('ulice');
		$form->addZip('psc');

		$form->addGroup('Doplňující informace');
		$form->addOdKdyVcelari('vcelariOd');

		$form->addGroup();
		$form->addSubmit('send', 'Uložit profil');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a změní profil včelaře.
	 *
	 * @param Form $form
	 * @param array $values
	 */
	public function onFormSubmitted(Form $form, array $values)
	{
		try {
			$vcelar = $this->model->vcelari->getById($values['id']);
			if (!$vcelar) {
				throw new BadRequestException('Profil včelaře nebyl nalezen.', Response::S400_BAD_REQUEST);
			}
			if (!$this->user->isAllowed($vcelar, 'edit')) {
				throw new ForbiddenRequestException('Nemáte oprávenění měnit profil včelaře.');
			}

			$this->vcelarManager->updateVcelar($vcelar, $values);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
