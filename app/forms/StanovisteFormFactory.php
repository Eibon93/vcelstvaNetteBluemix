<?php

/*
 * Copyright (C) 2017
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

use App\Managers\StanovisteManager;
use App\Model\Model;
use App\Model\ModelException;
use App\Security\ResourceNames;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Description of AddStanovisteFormFactory
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class StanovisteFormFactory
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
	 * @var StanovisteManager
	 */
	private $stanovisteManager;

	public function __construct(User $user, Model $model, StanovisteManager $stanovisteManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->stanovisteManager = $stanovisteManager;
	}

	public function create()
	{
		$form = new Form();

		$form->addProtection();

		$form->addHidden('id');
		$form->addHidden('parcelaId')
				->setRequired('Prosím vyberte umístění stanoviště v mapě.')
				->setHtmlId('parcelaId');
		$form->addHidden('katastralniUzemiId')
				->setRequired('Prosím vyberte umístění stanoviště v mapě.')
				->setHtmlId('katastralniUzemiId');
		$form->addHidden('lat')
				->setRequired('Prosím vyberte umístění stanoviště v mapě.')
				->setHtmlId('lat');
		$form->addHidden('lng')
				->setRequired('Prosím vyberte umístění stanoviště v mapě.')
				->setHtmlId('lng');

		$form->addNameStanoviste('nazev');
		$form->addRegNumberStanoviste('registracniCislo');
		$form->addBegin('zacatek');
		$form->addPlanningEnd('predpokladanyKonec');

		$form->addText('katastralniUzemi', 'Katastrální území')
				->setDisabled()
				->setHtmlId('katastralniUzemi');
		$form->addText('parcela', 'Parcela')
				->setDisabled()
				->setHtmlId('parcela');
		$form->addText('souradnice', 'Souřadnice')
				->setDisabled()
				->setHtmlId('souradnice');

		$form->addSubmit('send', 'Uložit stanoviště');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$data = [
				'parcelaId' => $values->parcelaId,
				'katastralniUzemiId' => $values->katastralniUzemiId,
				'lat' => $values->lat,
				'lng' => $values->lng,
				'nazev' => $values->nazev,
				'registracniCislo' => $values->registracniCislo,
				'pocatek' => $values->zacatek,
				'predpokladanyKonec' => $values->predpokladanyKonec,
			];
			if ($values->id) {
				$this->stanovisteManager->changeStanoviste($this->getStanoviste($values->id), $data);
			} else {
				$this->stanovisteManager->addStanoviste($this->getVcelar(), $data);
			}
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
		if (!$this->user->isAllowed($stanoviste, 'edit')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $stanoviste;
	}

	private function getVcelar()
	{
		$user = $this->model->users->getById($this->user->getId());
		if (!$user) {
			throw new BadRequestException('Uživatel nebyl nalezen.', Response::S400_BAD_REQUEST);
		}
		$vcelar = $user->vcelar;
		if (!$vcelar) {
			throw new BadRequestException('Včelař nebyl nalezen.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed(ResourceNames::STANOVISTE, 'add')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $vcelar;
	}

}
