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

namespace App\Presenters;

use App\Forms\StanovisteFormFactory;
use App\Security\ResourceNames;
use Nette\Http\Response;
use PdfResponse\PdfResponse;

/**
 * Description of StanovistePresenter
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class StanovistePresenter extends AuthenticatedPresenter
{

	/**
	 * @var StanovisteFormFactory
	 * @inject
	 */
	public $stanovisteFormFactory;

	public function renderDefault()
	{
		$uzivatel = $this->model->users->getById($this->getUser()->getId());
		if (!$uzivatel->vcelar) {
			$this->error('Nemáte oprávnění zobrazit tuto stránku.', Response::S403_FORBIDDEN);
		}

		$stanoviste = $uzivatel->vcelar->stanoviste
				->get()
				->orderBy('katastralniUzemi');

		$this->template->stanoviste = $stanoviste;

		$pocetVcelstev = 0;
		foreach ($stanoviste as $s) {
			$pocetVcelstev = $pocetVcelstev + $s->aktualniVcelstva->countStored();
		}

		$this->template->maVcelstva = $pocetVcelstev > 0;
	}

	public function renderDetail($id)
	{
		$stanoviste = $this->model->stanoviste->getById($id);
		if (!$stanoviste) {
			$this->error('Stanoviště nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($stanoviste, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$this->template->stanoviste = $stanoviste;
	}

	public function renderEdit($id)
	{
		$stanoviste = $this->model->stanoviste->getById($id);
		if (!$stanoviste) {
			$this->error('Stanoviště nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($stanoviste, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('stanovisteForm');
		$form->setDefaults([
			'id' => $stanoviste->id,
			'parcelaId' => $stanoviste->parcela ? $stanoviste->parcela->id : NULL,
			'katastralniUzemiId' => $stanoviste->katastralniUzemi->id,
			'lat' => $stanoviste->lat,
			'lng' => $stanoviste->lng,
			'nazev' => $stanoviste->nazev,
			'registracniCislo' => $stanoviste->registracniCislo,
			'zacatek' => $stanoviste->pocatek,
			'predpokladanyKonec' => $stanoviste->predpokladanyKonec,
			'katastralniUzemi' => $stanoviste->katastralniUzemi->nazev,
			'parcela' => $stanoviste->parcela ? $stanoviste->parcela->celyNazev : NULL,
			'souradnice' => $stanoviste->souradnice,
		]);

		$this->template->stanoviste = $stanoviste;
	}

	public function renderAdd()
	{
		if (!$this->getUser()->isAllowed(ResourceNames::STANOVISTE, 'add')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}
	}

	public function renderHtmlFormularHlaseniCMSCH()
	{
		// Předáme data do view
		$uzivatel = $this->model->users->getById($this->user->id);
		$this->template->uzivatel = $uzivatel;
		$this->template->vsechnyStanoviste = $uzivatel->vcelar->stanoviste;

		// Stranka pro tisk certifikatu nebude obsahovat hlavicku a paticku
		$this->setLayout('pdfFormularHlaseniCMSCH');
		$this->template->setFile(__DIR__ . '/templates/Stanoviste/htmlFormularHlaseniCMSCH.latte');
		$this->sendResponse(new PDFResponse($this->template));
	}

	public function renderHtmlFormularHlaseniObec()
	{
		// Předáme data do view
		$uzivatel = $this->model->users->getById($this->user->id);
		$this->template->uzivatel = $uzivatel;

		$vsechnyStanovistePodleObci = [];
		foreach ($uzivatel->vcelar->stanoviste as $stanoviste) {
			$vsechnyStanovistePodleObci[$stanoviste->katastralniUzemi->id][] = $stanoviste;
		}
		$this->template->vsechnyStanovistePodleObci = $vsechnyStanovistePodleObci;

		// Stranka pro tisk certifikatu nebude obsahovat hlavicku a paticku
		$this->setLayout('pdfFormularHlaseniObec');
		$this->template->setFile(__DIR__ . '/templates/Stanoviste/htmlFormularHlaseniObec.latte');
		$this->sendResponse(new PdfResponse($this->template));
	}

	protected function createComponentStanovisteForm()
	{
		$presenter = $this;

		$form = $this->stanovisteFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Stanoviště bylo uloženo.');
			$presenter->redirect('default');
		};

		return $form;
	}

}
