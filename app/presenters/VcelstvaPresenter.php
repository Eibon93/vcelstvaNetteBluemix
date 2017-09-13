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

use App\Forms\PresunVcelstvaFormFactory;
use App\Forms\VcelstvoFormFactory;
use App\Managers\VcelstvaManager;
use Nette\Http\Response;
use Nette\Utils\DateTime;

/**
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class VcelstvaPresenter extends BasePresenter
{

	/**
	 * @var VcelstvoFormFactory
	 * @inject
	 */
	public $vcelstvoFormFactory;

	/**
	 * @var PresunVcelstvaFormFactory
	 * @inject
	 */
	public $presunVcelstvaFormFactory;

	/**
	 * @inject
	 * @var VcelstvaManager
	 */
	public $vcelstvaManager;

	/**
	 * @param int $id
	 */
	public function renderDetail($id)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'view')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$this->template->vcelstvo = $vcelstvo;
		$this->template->kontroly = $vcelstvo->kontroly->get()
				->findBy(['smazana' => FALSE])
				->orderBy('datumKontroly', 'DESC');
	}

	/**
	 * @param int $id
	 */
	public function renderMereni($id, $pocatek = NULL, $konec = NULL)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'view')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		if ($pocatek !== NULL) {
			$pocatek = DateTime::createFromFormat('j.n.Y', $pocatek);
		}
		if (!$pocatek) {
			$pocatek = DateTime::from('-7 day')->setTime(0, 0, 0);
		}

		if ($konec !== NULL) {
			$konec = DateTime::createFromFormat('j.n.Y', $konec);
		}
		if (!$konec) {
			$konec = DateTime::from('now')->setTime(23, 59, 59);
		}

		$this->template->vcelstvo = $vcelstvo;
		$this->template->pocatek = $pocatek;
		$this->template->konec = $konec;
	}

	/**
	 * @param int $stanovisteId
	 */
	public function renderAdd($stanovisteId)
	{
		$stanoviste = $this->model->stanoviste->getById($stanovisteId);
		if (!$stanoviste) {
			$this->error('Stanoviště nenalezeno.', Response::S400_BAD_REQUEST);
		}
		if (!$this->getUser()->isAllowed($stanoviste, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('addForm');
		$form->setDefaults([
			'stanovisteId' => $stanoviste->id,
			'datumUmisteni' => DateTime::from('now')->setTime(0, 0, 0),
		]);
	}

	/**
	 * @param int $id
	 */
	public function renderEdit($id)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('editForm');
		$form->setDefaults([
			'id' => $vcelstvo->id,
			'poradoveCislo' => $vcelstvo->poradoveCislo,
			'cisloMatky' => $vcelstvo->cisloMatky,
			'puvodMatky' => $vcelstvo->puvodMatky,
			'barvaMatky' => $vcelstvo->barvaMatky,
			'typUlu' => $vcelstvo->typUlu,
			'ramkovaMira' => $vcelstvo->ramkovaMira,
		]);
	}

	/**
	 * @param int $id
	 */
	public function renderMove($id)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			$this->error('Včelstvo nenalezeno.', Response::S400_BAD_REQUEST);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$uzivatel = $this->model->users->getById($this->getUser()->getId());
		$stanoviste = $uzivatel->vcelar->stanoviste->get()->orderBy('nazev');

		$maZarizeni = $vcelstvo->maZarizeni();
		if ($maZarizeni) {
			$maSdilenaZarizeni = $vcelstvo->maSdilenaZarizeni();
		}

		$form = $this->getComponent('moveForm');
		$form->setDefaults([
			'id' => $vcelstvo->id,
			'datumPresunu' => DateTime::from('now')->setTime(0, 0, 0),
			'vcetneZarizeni' => $maZarizeni && !$maSdilenaZarizeni,
		]);

		$this->template->vcelstvo = $vcelstvo;
		$this->template->stanoviste = $stanoviste;
		$this->template->maZarizeni = $maZarizeni;
		$this->template->maSdilenaZarizeni = $maZarizeni ? $maSdilenaZarizeni : FALSE;
	}

	/**
	 * @param int $id
	 * @param int $stanovisteId
	 */
	public function actionDelete($id, $stanovisteId)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'delete')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$this->vcelstvaManager->delete($vcelstvo);

		$this->flashMessage('Vcelstvo odstraněno ze stanovistě. Nyní se objeví v historii včelstev u stanoviště.');
		$this->redirect('Stanoviste:detail', $stanovisteId);
	}

	protected function createComponentMoveForm()
	{
		$form = $this->presunVcelstvaFormFactory->create();

		$presenter = $this;
		$form->onSuccess[] = function($_, $values) use ($presenter) {
			$presenter->flashMessage('Včelstvo úspěšně přemístěno');
			$presenter->redirect('Vcelstva:detail', [$values->id]);
		};

		return $form;
	}

	protected function createComponentAddForm()
	{
		$presenter = $this;

		$form = $this->vcelstvoFormFactory->createAddForm();
		$form->onSuccess[] = function($_, $values) use ($presenter) {
			$presenter->flashMessage('Data byla uložena.');
			$presenter->redirect('Stanoviste:detail', $values->stanovisteId);
		};

		return $form;
	}

	protected function createComponentEditForm()
	{
		$presenter = $this;

		$form = $this->vcelstvoFormFactory->createEditForm();
		$form->onSuccess[] = function($_, $values) use ($presenter) {
			$presenter->flashMessage('Data byla uložena.');
			$presenter->redirect('detail', $values->id);
		};

		return $form;
	}

}
