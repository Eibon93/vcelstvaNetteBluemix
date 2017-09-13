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

use App\Managers\KontrolaManager;
use App\Model\Model;
use App\Model\ModelException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na registrační formulář.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class KontrolaFormFactory
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
	 * @var KontrolaManager
	 */
	private $kontrolaManager;

	public function __construct(User $user, Model $model, KontrolaManager $kontrolaManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->kontrolaManager = $kontrolaManager;
	}

	/**
	 * @return Form
	 */
	public function createAddForm()
	{
		$form = new Form();
		$form->addProtection();
		$form->addHidden('vcelstvoId');

		$this->addCommonControls($form);

		$form->addSubmit('send', 'Uložit záznam o kontrole');

		$form->onSuccess[] = [$this, 'onAddFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * @return Form
	 */
	public function createEditForm()
	{
		$form = new Form();
		$form->addProtection();
		$form->addHidden('id');
		$form->addHidden('vcelstvoId');

		$this->addCommonControls($form);

		$form->addSubmit('send', 'Uložit záznam o kontrole');

		$form->onSuccess[] = [$this, 'onEditFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	private function addCommonControls(Form $form)
	{
		$form->addGroup('Údaje o včelstvu a jeho úlu');
		$form->addDateTimePicker('datumKontroly', "Datum kontroly")
				->setRequired("Datum kontroly musí být zadáno");
		$form->addInteger('pocetNastavku', 'Počet nástavků');
		$form->addCheckbox('matkaKlade', ' Matka klade?');
		$form->addInteger('obsedajiUlicek', 'Obsedají uliček');
		$form->addText('plod', 'Plod');
		$form->addText('zasoby', 'Zásoby');
		$form->addText('pyl', 'Pyl');
		$form->addText('mednyVynos', 'Medný výnos');

		$form->addGroup('Oznámkujte včely jako ve škole');
		$form->addRadioZnamkovani('mirnost', 'Mírnost');
		$form->addRadioZnamkovani('sezeni', 'Sezení');
		$form->addRadioZnamkovani('rojivost', 'Rojivost');
		$form->addRadioZnamkovani('rozvoj', 'Rozvoj');
		$form->addRadioZnamkovani('hygiena', 'Hygiena');

		$form->addGroup('Další doplňující údaje');
		$form->addTextArea('priste', 'Příště je třeba');
		$form->addTextArea('poznamka', 'Poznámka');

		$form->addGroup();
	}

	public function onAddFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$vcelstvo = $this->getVcelstvo($values->vcelstvoId);
			$data = [
				'datumKontroly' => $values->datumKontroly,
				'pocetNastavku' => $values->pocetNastavku,
				'matkaKlade' => $values->matkaKlade,
				'obsedajiUlicek' => $values->obsedajiUlicek,
				'plod' => $values->plod,
				'zasoby' => $values->zasoby,
				'pyl' => $values->pyl,
				'mirnost' => $values->mirnost,
				'sezeni' => $values->sezeni,
				'rojivost' => $values->rojivost,
				'rozvoj' => $values->rozvoj,
				'hygiena' => $values->hygiena,
				'mednyVynos' => $values->mednyVynos,
				'priste' => $values->priste,
				'poznamka' => $values->poznamka,
			];
			$this->kontrolaManager->add($vcelstvo, $data);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	public function onEditFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$kontrola = $this->getKontrola($values->id);
			$data = [
				'datumKontroly' => $values->datumKontroly,
				'pocetNastavku' => $values->pocetNastavku,
				'matkaKlade' => $values->matkaKlade,
				'obsedajiUlicek' => $values->obsedajiUlicek,
				'plod' => $values->plod,
				'zasoby' => $values->zasoby,
				'pyl' => $values->pyl,
				'mirnost' => $values->mirnost,
				'sezeni' => $values->sezeni,
				'rojivost' => $values->rojivost,
				'rozvoj' => $values->rozvoj,
				'hygiena' => $values->hygiena,
				'mednyVynos' => $values->mednyVynos,
				'priste' => $values->priste,
				'poznamka' => $values->poznamka,
			];
			$this->kontrolaManager->update($kontrola, $data);
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

	private function getKontrola($id)
	{
		$kontrola = $this->model->kontroly->getById($id);
		if (!$kontrola) {
			throw new BadRequestException('Kontrola nebyla nalezena.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed($kontrola->vcelstvo, 'edit')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $kontrola;
	}

}
