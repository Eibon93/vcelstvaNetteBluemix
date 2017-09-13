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
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;
use App\Managers\RegistrationManager;


/**
 * Továrna na registrační formulář.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class ZemedelecRegistrationFormFactory
{

	use SmartObject;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var RegistrationManager
	 */
	private $registrationManager;


	/**
	 * @param Model $model
	 * @param UserManager $userManager
	 */

	public function __construct(Model $model, RegistrationManager $registrationManager)
	{
		$this->model = $model;
		$this->registrationManager = $registrationManager;

	}

	/**
	 * Vytvoří registrační formulář.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addProtection();
		$form->addGroup('Registrační údaje');
		$form->addUserEmail('email');
		$form->addUserPassword('heslo');
		$form->addUserTestPassword('hesloTest', $form['heslo']);

		$form->addGroup('Údaje o zemědělci');
		$form->addUserGivenName('jmeno');
		$form->addUserFamilyName('prijmeni');
		
		$form->addUserPhone('telefon');
		$form->addUserWwwStranky('wwwStranky');		

		$form->addGroup('Zemědělský podnik');
		$form->addICO('ico')->setRequired('IČO je povinný údaj');
		$form->addNazevPodniku('nazev')->setRequired('Název podniku je povinný údaj');
		$form->addStreet('ulice');
		$form->addObec('castObce');
		$form->addZip('psc');
		$form->addCheckbox('souhlas', ' Souhlasím s výše uvedenými podmínkami')
				->setRequired('Pro dokončení registrace je potřeba odsouhlasit podmínky použití a zásady ochrany osobních údajů.');

		$form->addSubmit('send', 'Zaregistrovat se');
		$form->onSuccess[] = [$this, 'onFormSubmitted'];
		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z registračního formuláře a zaregistruje uživatele.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {

			$this->registrationManager->registerZemedelec($values);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
