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
use App\Model\Vcelar;
use App\Model\Zarizeni;
use Nette\Application\UI\Form;
use Nette\InvalidStateException;
use Nette\Security\User;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na formulář, pomocí kterého může uživatel umísťovat svá měřící
 * zařízení na stanoviště.
 *
 * Uživatel si vybere stanoviště, na které chce měřící zařízení umístit. Dalším
 * krokem pak je připojení jednotlivých senzorů ke včelstvům.
 *
 * Metoda create() potřebuje znát zařízení, pro které má formulář vytvořit.
 *
 * @author Pavel Junek
 */
class PripojeniZarizeniFormFactory
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
	 * @param Zarizeni $zarizeni
	 * @return Form
	 */
	public function create(Zarizeni $zarizeni)
	{
		$stanoviste = $this->fetchStanoviste();

		$form = new Form();
		$form->addHidden('zarizeniId', $zarizeni->id)
				->setRequired()
				->addRule(Form::EQUAL, NULL, $zarizeni->id);

		$form->addSelect('stanovisteId', 'Stanoviště')
				->setItems($stanoviste)
				->setPrompt('')
				->setRequired();

		$form->addSubmit('send', 'Pokračovat');

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Načte názvy používaných stanovišť aktuálně přihlášeného včelaře.
	 *
	 * @return array
	 */
	private function fetchStanoviste()
	{
		return $this->getVcelar()->aktualniStanoviste->fetchPairs('id', 'nazev');
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
