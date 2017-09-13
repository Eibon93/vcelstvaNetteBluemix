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

use App\Managers\VcelstvaManager;
use App\Model\Model;
use App\Model\ModelException;
use DateInterval;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Description of PresunVcelstvaFormFactory
 *
 * @author Pavel Junek
 */
class PresunVcelstvaFormFactory
{

	use SmartObject;

	const DAYS_BEFORE = 7;
	const DAYS_AFTER = 0;

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

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param User $user
	 * @param Model $model
	 * @param VcelstvaManager $vcelstvaManager
	 */
	public function __construct(User $user, Model $model, VcelstvaManager $vcelstvaManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->vcelstvaManager = $vcelstvaManager;
	}

	/**
	 * Vytvoří formulář pro přesun včelstva.
	 *
	 * @return Form
	 */
	public function create()
	{
		$minDate = $this->getMinDate();
		$maxDate = $this->getMaxDate();

		$form = new Form();
		$form->setRenderer(new Bs3FormRenderer());
		$form->addProtection();

		$form->addHidden('id');
		$form->addDatePicker('datumPresunu', 'Datum přemístění včelstva')
				->setRequired()
				->addRule(function($control) use ($minDate, $maxDate) {
					return $minDate <= $control->getValue() && $control->getValue() <= $maxDate;
				}, 'Datum přemístění včelstva nesmí být starší než týden.');
		$form->addCheckbox('vcetneZarizeni', 'Přesunout včelstvo i s úlovou váhou');
		$form->addRadioList('stanovisteId', 'Nové stanoviště včelstva')
				->setRequired()
				->setItems($this->getStanoviste());
		$form->addSubmit('send', 'Přesunout včelstvo');

		$form->onSuccess[] = [$this, 'onSuccess'];

		return $form;
	}

	/**
	 * Po odeslání formuláře přesune včelstvo na zadané stanoviště.
	 *
	 * @param Form $form
	 * @param ArrayHash $values
	 */
	public function onSuccess(Form $form, ArrayHash $values)
	{
		// Získáme včelstvo, které se bude přesouvat
		$vcelstvo = $this->model->vcelstva->getById($values->id);
		if (!$vcelstvo) {
			throw new BadRequestException('Včelstvo nenalezeno', Response::S400_BAD_REQUEST);
		}

		// Získáme stanoviště, na které se má včelstvo přesunout
		$stanoviste = $this->model->stanoviste->getById($values->stanovisteId);
		if (!$stanoviste) {
			throw new BadRequestException('Stanoviste nenalezeno', Response::S400_BAD_REQUEST);
		}

		// Zkontrolujeme, že uživatel má k akci oprávnění
		if (!$this->user->isAllowed($vcelstvo, 'edit') || !$this->user->isAllowed($stanoviste, 'edit')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k provedení požadované operace');
		}

		// Přesuneme včelstvo do nového umístění
		try {
			$this->vcelstvaManager->move($vcelstvo, $stanoviste, $values->datumPresunu, $values->vcetneZarizeni);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	/**
	 * Vrátí seznam stanovišť aktuálně přihlášeného uživatele.
	 *
	 * @return array
	 */
	private function getStanoviste()
	{
		$uzivatel = $this->model->users->getById($this->user->getId());
		return $uzivatel->vcelar->stanoviste->get()->fetchPairs('id', 'nazev');
	}

	/**
	 * Vrátí počáteční datum, od kdy je možné zadat přesun včelstva.
	 *
	 * @return DateTime
	 */
	private function getMinDate()
	{
		return DateTime::from('now')
						->setTime(0, 0, 0)
						->sub(new DateInterval(sprintf('P%dD', self::DAYS_BEFORE)));
	}

	/**
	 * Vrátí koncové datum, do kdy je možné zadat přesun včelstva.
	 *
	 * @return DateTime
	 */
	private function getMaxDate()
	{
		return DateTime::from('now')
						->setTime(0, 0, 0)
						->add(new DateInterval(sprintf('P%dD', self::DAYS_AFTER)));
	}

}
