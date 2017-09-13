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

use App\Forms\OdpojeniZarizeniFormFactory;
use App\Forms\PripojeniSenzoruFormFactory;
use App\Forms\PripojeniZarizeniFormFactory;
use App\Forms\VytvoreniZarizeniFormFactory;
use App\Model\Stanoviste;
use App\Model\TypZarizeni;
use App\Model\Zarizeni;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Utils\Strings;

/**
 * Description of ZarizeniPresenter
 *
 * @author Pavel Junek
 */
class ZarizeniPresenter extends AuthenticatedPresenter
{

	/**
	 * @var VytvoreniZarizeniFormFactory
	 * @inject
	 */
	public $vytvoreniZarizeniFormFactory;

	/**
	 * @var PripojeniZarizeniFormFactory
	 * @inject
	 */
	public $pripojeniZarizeniFormFactory;

	/**
	 * @var PripojeniSenzoruFormFactory
	 * @inject
	 */
	public $pripojeniSenzoruFormFactory;

	/**
	 * @var OdpojeniZarizeniFormFactory
	 * @inject
	 */
	public $odpojeniZarizeniFormFactory;

	/**
	 * @var TypZarizeni
	 */
	private $typZarizeni;

	/**
	 * @var Zarizeni
	 */
	private $zarizeni;

	/**
	 * @var Stanoviste
	 */
	private $stanoviste;

	public function renderDefault()
	{
		$vcelar = $this->getVcelar();

		$this->template->zarizeni = $vcelar->zarizeni;
	}

	public function actionVytvoritZarizeni($typZarizeniId)
	{
		$this->typZarizeni = $this->getTypZarizeni($typZarizeniId);
	}

	public function renderVytvoritZarizeni()
	{
		$defaults = [];
		$defaults['nazev'] = $this->createNazev($this->typZarizeni);

		$form = $this->getComponent('vytvoreniZarizeniForm');
		$form->setDefaults($defaults);

		$this->template->typZarizeni = $this->typZarizeni;
	}

	protected function createComponentVytvoreniZarizeniForm()
	{
		$presenter = $this;

		$form = $this->vytvoreniZarizeniFormFactory->create($this->typZarizeni);
		$form->onSuccess[] = function(Form $form, array $values) use ($presenter) {
			$presenter->redirect('pripojitZarizeni', [
				'id' => $values['zarizeniId'],
			]);
		};

		return $form;
	}

	public function actionPripojitZarizeni($id)
	{
		$this->zarizeni = $this->getZarizeni((int) $id);
	}

	public function renderPripojitZarizeni()
	{
		$defaults = [];
		if ($this->zarizeni->aktualniStanoviste) {
			$defaults['stanovisteId'] = $this->zarizeni->aktualniStanoviste->id;
		}

		$form = $this->getComponent('pripojeniZarizeniForm');
		$form->setDefaults($defaults);

		$this->template->zarizeni = $this->zarizeni;
	}

	protected function createComponentPripojeniZarizeniForm()
	{
		$presenter = $this;

		$form = $this->pripojeniZarizeniFormFactory->create($this->zarizeni);
		$form->onSuccess[] = function(Form $form, array $values) use ($presenter) {
			$presenter->redirect('pripojitSenzory', [
				'id' => $values['zarizeniId'],
				'stanovisteId' => $values['stanovisteId'],
			]);
		};

		return $form;
	}

	public function actionPripojitSenzory($id, $stanovisteId)
	{
		$this->zarizeni = $this->getZarizeni((int) $id);
		$this->stanoviste = $this->getStanoviste((int) $stanovisteId);
	}

	public function renderPripojitSenzory()
	{
		$defaults = [];
		foreach ($this->zarizeni->aktualniPripojeni->findBy(['stanoviste' => $this->stanoviste]) as $p) {
			$defaults[$p->senzor->umisteni]['vcelstvoId'] = $p->vcelstvo ? $p->vcelstvo->id : NULL;
			$defaults[$p->senzor->umisteni][$p->senzor->id] = TRUE;
		}
		if (!$defaults) {
			foreach ($this->zarizeni->typZarizeni->senzory->get()->findBy(['umisteni' => 'stanoviste']) as $s) {
				$defaults['stanoviste'][$s->id] = TRUE;
			}
		}

		$form = $this->getComponent('pripojeniSenzoruForm');
		$form->setDefaults($defaults);

		$this->template->zarizeni = $this->zarizeni;
		$this->template->stanoviste = $this->stanoviste;
	}

	protected function createComponentPripojeniSenzoruForm()
	{
		$presenter = $this;

		$form = $this->pripojeniSenzoruFormFactory->create($this->zarizeni, $this->stanoviste);
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->redirect('default');
		};

		return $form;
	}

	public function actionOdpojitZarizeni($id)
	{
		$this->zarizeni = $this->getZarizeni((int) $id);
	}

	public function renderOdpojitZarizeni()
	{
		$this->template->zarizeni = $this->zarizeni;
	}

	protected function createComponentOdpojeniZarizeniForm()
	{
		$presenter = $this;

		$form = $this->odpojeniZarizeniFormFactory->create($this->zarizeni);
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->redirect('default');
		};

		return $form;
	}

	private function getVcelar()
	{
		$user = $this->model->users->getById($this->getUser()->getId());
		if (!$user) {
			$this->error('User not found.', Response::S500_INTERNAL_SERVER_ERROR);
		}
		$vcelar = $user->vcelar;
		if (!$vcelar) {
			$this->error('User not found.', Response::S500_INTERNAL_SERVER_ERROR);
		}
		return $vcelar;
	}

	private function getTypZarizeni($id)
	{
		$typZarizeni = $this->model->typyZarizeni->getById($id);
		if (!$typZarizeni) {
			$this->error('Invalid parameter', Response::S400_BAD_REQUEST);
		}
		return $typZarizeni;
	}

	private function getZarizeni($id)
	{
		$vcelar = $this->getVcelar();
		$zarizeni = $vcelar->zarizeni->get()->getBy(['id' => $id]);
		if (!$zarizeni) {
			$this->error('Invalid parameter', Response::S400_BAD_REQUEST);
		}
//		if (!$this->getUser()->isAllowed($zarizeni, 'connect')) {
//			$this->error('Forbidden', Response::S403_FORBIDDEN);
//		}
		return $zarizeni;
	}

	private function getStanoviste($id)
	{
		$vcelar = $this->getVcelar();
		$stanoviste = $vcelar->aktualniStanoviste->getBy(['id' => $id]);
		if (!$stanoviste) {
			$this->error('Invalid parameter', Response::S400_BAD_REQUEST);
		}
//		if (!$this->getUser()->isAllowed($stanoviste, 'connect')) {
//			$this->error('Forbidden', Response::S403_FORBIDDEN);
//		}
		return $stanoviste;
	}

	private function createNazev(TypZarizeni $typZarizeni)
	{
		$cislo = 0;
		foreach ($this->getVcelar()->zarizeni as $z) {
			if (Strings::startsWith($z->nazev, $typZarizeni->nazev)) {
				$suffix = Strings::substring($z->nazev, Strings::length($typZarizeni->nazev));
				$x = (int) Strings::after($suffix, '#');
				if ($x > $cislo) {
					$cislo = $x;
				}
			}
		}
		return sprintf('%s #%d', $typZarizeni->nazev, $cislo + 1);
	}

}
