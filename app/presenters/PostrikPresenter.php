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

use App\Forms\EmailFormFactory;
use App\Forms\PostrikFormFactory;
use App\Managers\OhrozeniManager;
use App\Managers\PostrikManager;
use App\Model\ModelException;
use App\Security\ResourceNames;
use Nette\Http\Response;
use Nette\Utils\DateTime;
use PdfResponse\PdfResponse;

/**
 * Správa postřiků.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class PostrikPresenter extends AuthenticatedPresenter
{

	/**
	 * @var PostrikFormFactory
	 * @inject
	 */
	public $postrikFormFactory;

	/**
	 * @var EmailFormFactory
	 * @inject
	 */
	public $emailFormFactory;

	/**
	 * @var PostrikManager
	 * @inject
	 */
	public $postrikManager;

	/**
	 * @var OhrozeniManager
	 * @inject
	 */
	public $ohrozeniManager;

	public function renderDefault()
	{
		$uzivatel = $this->model->users->getById($this->getUser()->getId());
		if (!$uzivatel->zemedelskyPodnik) {
			$this->error('Nemáte oprávnění zobrazit tuto stránku.', Response::S403_FORBIDDEN);
		}

		$this->template->postrikyPlanovane = $uzivatel->zemedelskyPodnik->postriky->get()
				->findBy([
					'datum>=' => DateTime::from('now'),
					'smazan' => FALSE,
				])
				->orderBy('datum');

		$this->template->postrikyHistoricke = $uzivatel->zemedelskyPodnik->postriky->get()
				->findBy([
					'datum<' => DateTime::from('now'),
					'smazan' => FALSE,
				])
				->orderBy('datum', 'DESC');
	}

	public function renderAdd()
	{
		if (!$this->getUser()->isAllowed(ResourceNames::POSTRIK, 'add')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}
	}

	public function renderEdit($id)
	{
		$postrik = $this->model->postriky->getById($id);
		if (!$postrik) {
			$this->error('Postřik nebyl nalezen.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($postrik, 'edit')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('postrikForm');
		$form->setDefaults([
			'id' => $postrik->id,
			'lat' => $postrik->lat,
			'lng' => $postrik->lng,
			'katastralniUzemiId' => $postrik->katastralniUzemi->id,
			'parcelaId' => $postrik->parcela->id,
			'pudniBlokId' => $postrik->pudniBlok ? $postrik->pudniBlok->id : NULL,
			'datum' => $postrik->datum->format('j.n.Y H:i'),
			'plodina' => $postrik->plodina,
			'nebezpecny' => $postrik->nebezpecny,
			'moznosti' => array_filter([
				$postrik->kvetouci ? 'kvetouci' : FALSE,
				$postrik->mimoLetovouAktivitu ? 'mimoLetovouAktivitu' : FALSE,
			]),
			'souradnice' => $postrik->souradnice,
			'katastralniUzemi' => $postrik->katastralniUzemi->celyNazev,
			'parcela' => $postrik->parcela->celyNazev,
			'pudniBlok' => $postrik->pudniBlok ? $postrik->pudniBlok->celyNazev : '',
		]);
	}

	public function renderOhrozenaStanoviste()
	{
		$uzivatel = $this->model->users->getById($this->getUser()->getId());

		$vysledky = $this->ohrozeniManager->findOhrozenaStanoviste($uzivatel->zemedelskyPodnik);
		$postriky = $this->model->postriky->findById(array_keys($vysledky));

		$this->template->postriky = $postriky;
		$this->template->vysledky = $vysledky;
	}

	public function renderHtmlFormularPostrik()
	{
		$uzivatel = $this->model->users->getById($this->getUser()->getId());
		$postriky = $uzivatel->zemedelskyPodnik->postriky->get()
				->findBy(['smazan' => FALSE])
				->findBy(['datum>' => DateTime::from('now')])
				->orderBy('datum', 'DESC');

		$vsechnyPostrikyPodleObci = [];
		foreach ($postriky as $postrik) {
			$vsechnyPostrikyPodleObci[$postrik->katastralniUzemi->obec->id][] = $postrik;
		}

		$this->template->uzivatel = $uzivatel;
		$this->template->vsechnyPostrikyPodleObci = $vsechnyPostrikyPodleObci;

		// Stranka pro tisk certifikatu nebude obsahovat hlavicku a paticku
		$this->setLayout('pdfFormularPostrik');
		$this->template->setFile(__DIR__ . '/templates/Postrik/htmlFormularPostrik.latte');
		$this->sendResponse(new PdfResponse($this->template));
	}

	public function actionDelete($id)
	{
		$postrik = $this->model->postriky->getById($id);
		if (!$postrik) {
			$this->error('Postřik nebyl nalezen.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($postrik, 'delete')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		try {
			$this->postrikManager->deletePostrik($postrik);
			$this->flashMessage('Záznam o postřiku byl smazán.');
		} catch (ModelException $ex) {
			$this->flashMessage($ex->getMessage());
		}
		$this->redirect('default');
	}

	protected function createComponentPostrikForm()
	{
		$presenter = $this;

		$form = $this->postrikFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Data byla uložena.');
			$presenter->redirect('default');
		};

		return $form;
	}

	protected function createComponentEmailForm()
	{
		$presenter = $this;

		$form = $this->emailFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('E-mail byl odeslán včelařům.');
			$presenter->redirect('default');
		};

		return $form;
	}

}
