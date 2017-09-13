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

namespace App\Presenters;

use App\Forms\ProdejnaMeduFormFactory;
use App\Managers\ProdejnaMeduManager;
use App\Security\ResourceNames;
use Nette\Http\Response;

/**
 * Správa prodejních míst medu.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class ProdejnyMeduPresenter extends AuthenticatedPresenter
{

	/**
	 * @var ProdejnaMeduFormFactory
	 * @inject
	 */
	public $prodejnaMeduFormFactory;

	/**
	 * @var ProdejnaMeduManager
	 * @inject
	 */
	public $prodejnaMeduManager;

	public function renderDefault()
	{
		$uzivatel = $this->model->users->getById($this->getUser()->getId());
		if (!$uzivatel->vcelar) {
			$this->error('Nemáte oprávnění zobrazit tuto stránku.', Response::S403_FORBIDDEN);
		}

		$this->template->prodejnyMedu = $uzivatel->vcelar->prodejnyMedu
				->get()
				->findBy(['smazana' => FALSE,])
				->orderBy('adresa');
	}

	public function renderAdd()
	{
		if (!$this->getUser()->isAllowed(ResourceNames::PRODEJNA_MEDU, 'add')) {
			$this->error('Nemáte oprávnění zobrazit tuto stránku.', Response::S403_FORBIDDEN);
		}
	}

	public function renderEdit($id)
	{
		$prodejna = $this->model->prodejnyMedu->getById($id);
		if (!$prodejna) {
			$this->error('Prodejna nebyla nalezena.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($prodejna, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('prodejnaMeduForm');
		$form->setDefaults([
			'id' => $prodejna->id,
			'lat' => $prodejna->lat,
			'lng' => $prodejna->lng,
			'nazev' => $prodejna->nazev,
			'informace' => $prodejna->informace,
			'ulice' => $prodejna->adresa->ulice,
			'castObce' => $prodejna->adresa->castObce,
			'psc' => $prodejna->adresa->psc,
			'uverejnitTelefon' => $prodejna->uverejnitTelefon,
			'souradnice' => $prodejna->souradnice,
		]);
	}

	public function actionDelete($id)
	{
		$prodejna = $this->model->prodejnyMedu->getById($id);
		if (!$prodejna) {
			$this->error('Prodejna nebyla nalezena.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($prodejna, 'delete')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$this->prodejnaMeduManager->delete($prodejna);

		$this->flashMessage('Prodejní místo medu bylo odstraněno.');
		$this->redirect('default');
	}

	protected function createComponentProdejnaMeduForm()
	{
		$presenter = $this;

		$form = $this->prodejnaMeduFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Prodejní místo medu bylo uloženo a zveřejněno.');
			$presenter->redirect('default');
		};

		return $form;
	}

}
