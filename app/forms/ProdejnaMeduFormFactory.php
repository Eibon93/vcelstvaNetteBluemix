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

use App\Managers\ProdejnaMeduManager;
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
 * Továrna na formulář pro editaci místa prodeje medu.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class ProdejnaMeduFormFactory
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
	 * @var ProdejnaMeduManager
	 */
	private $prodejnaMeduManager;

	public function __construct(User $user, Model $model, ProdejnaMeduManager $prodejnaMeduManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->prodejnaMeduManager = $prodejnaMeduManager;
	}

	/**
	 * Vytvoří formulář.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->setRenderer(new Bs3FormRenderer());
		$form->addProtection();

		$form->addHidden('id')
				->setHtmlId('id');
		$form->addHidden('lat')
				->setRequired()
				->setHtmlId('lat');
		$form->addHidden('lng')
				->setRequired()
				->setHtmlId('lng');

		$form->addTextArea('nazev', 'Název prodejního místa')
				->setHtmlId('nazev');
		$form->addTextArea('informace', 'Otevírací doba a druh medu')
				->setRequired('Prosím zadejte základní informace o vaší prodejně')
				->setHtmlId('informace');
		$form->addStreet('ulice')
				->setHtmlId('ulice');
		$form->addZip('psc')
				->setHtmlId('psc');
		$form->addObec('castObce')
				->setHtmlId('castObce');
		$form->addCheckbox('uverejnitTelefon', 'Uveřejnit můj telefon');

		$form->addText('souradnice', 'Souřadnice')
				->setHtmlId('souradnice')
				->setDisabled();

		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = [$this, 'onSuccess'];

		return $form;
	}

	public function onSuccess(Form $form, ArrayHash $values)
	{
		try {
			$data = [
				'lat' => $values->lat,
				'lng' => $values->lng,
				'nazev' => $values->nazev,
				'informace' => $values->informace,
				'ulice' => $values->ulice,
				'psc' => $values->psc,
				'castObce' => $values->castObce,
				'uverejnitTelefon' => $values->uverejnitTelefon,
			];

			if ($values->id) {
				$this->prodejnaMeduManager->update($this->getProdejnaMedu($values->id), $data);
			} else {
				$this->prodejnaMeduManager->create($this->getVcelar(), $data);
			}
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	private function getProdejnaMedu($id)
	{
		$prodejnaMedu = $this->model->prodejnyMedu->getById($id);
		if (!$prodejnaMedu) {
			throw new BadRequestException('Prodejna medu nebyla nalezena.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed($prodejnaMedu, 'edit')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $prodejnaMedu;
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
		if (!$this->user->isAllowed(ResourceNames::PRODEJNA_MEDU, 'add')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $vcelar;
	}

}
