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

namespace App\Presenters;

use App\Forms\KontrolaFormFactory;
use App\Managers\KontrolaManager;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Utils\DateTime;

/**
 * Správa kontrol včelstev.
 *
 * @author Pavel Junek
 */
class KontrolyPresenter extends AuthenticatedPresenter
{

	/**
	 * @var KontrolaFormFactory
	 * @inject
	 */
	public $kontrolaFormFactory;

	/**
	 * @var KontrolaManager
	 * @inject
	 */
	public $kontrolaManager;

	/**
	 * @param int $vcelstvoId
	 */
	public function renderAdd($vcelstvoId)
	{
		$vcelstvo = $this->model->vcelstva->getById($vcelstvoId);
		if (!$vcelstvo) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('addForm');
		$form->setDefaults([
			'vcelstvoId' => $vcelstvo->id,
			'datumKontroly' => DateTime::from('now')->format('j.n.Y H:i:s'),
		]);
	}

	/**
	 * @param int $id
	 */
	public function renderEdit($id)
	{
		$kontrola = $this->model->kontroly->getById($id);
		if (!$kontrola) {
			$this->error('Kontrola nebyla nalezena.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($kontrola->vcelstvo, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('editForm');
		$form->setDefaults([
			'id' => $kontrola->id,
			'vcelstvoId' => $kontrola->vcelstvo->id,
			'datumKontroly' => $kontrola->datumKontroly->format('j.n.Y H:i:s'),
			'pocetNastavku' => $kontrola->pocetNastavku,
			'matkaKlade' => $kontrola->matkaKlade,
			'obsedajiUlicek' => $kontrola->obsedajiUlicek,
			'plod' => $kontrola->plod,
			'zasoby' => $kontrola->zasoby,
			'pyl' => $kontrola->pyl,
			'mirnost' => $kontrola->mirnost,
			'sezeni' => $kontrola->sezeni,
			'rojivost' => $kontrola->rojivost,
			'rozvoj' => $kontrola->rozvoj,
			'hygiena' => $kontrola->hygiena,
			'mednyVynos' => $kontrola->mednyVynos,
			'priste' => $kontrola->priste,
			'poznamka' => $kontrola->poznamka,
		]);
	}

	/**
	 * @param int $id
	 */
	public function actionDelete($id)
	{
		$kontrola = $this->model->kontroly->getById($id);
		if (!$kontrola) {
			$this->error('Kontrola nebyla nalezena.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($kontrola->vcelstvo, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$this->kontrolaManager->delete($kontrola);
		$this->flashMessage('Kontrola smazána');
		$this->redirect('Vcelstva:detail', $kontrola->vcelstvo->id);
	}

	/**
	 * @return Form
	 */
	protected function createComponentAddForm()
	{
		$presenter = $this;

		$form = $this->kontrolaFormFactory->createAddForm();
		$form->onSuccess[] = function($_, $values) use ($presenter) {
			$presenter->flashMessage('Záznam o kontrole úspěšně uložen.');
			$presenter->redirect('Vcelstva:detail', $values->vcelstvoId);
		};

		return $form;
	}

	/**
	 * @return Form
	 */
	protected function createComponentEditForm()
	{
		$presenter = $this;

		$form = $this->kontrolaFormFactory->createEditForm();
		$form->onSuccess[] = function($_, $values) use ($presenter) {
			$presenter->flashMessage('Záznam o kontrole úspěšně uložen.');
			$presenter->redirect('Vcelstva:detail', $values->vcelstvoId);
		};

		return $form;
	}

}
