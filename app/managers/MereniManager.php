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

namespace App\Managers;

use App\Model\Mereni;
use App\Model\Model;
use App\Model\ModelException;
use App\Model\Zarizeni;
use DateTime;
use Nette\SmartObject;

/**
 * Description of MereniManager
 *
 * @author Pavel Junek
 */
class MereniManager
{

	use SmartObject;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param Model $model
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	/**
	 * Uloží všechny naměřené hodnoty.
	 *
	 * Pole $hodnoty musí být ve tvaru: [(int) senzorId => (float) hodnota, ...].
	 *
	 * @param array $hodnoty pole naměřených hodnot.
	 * @throws ModelException pokud některý z identifikátorů senzorů nepatří k zadanému zařízení.
	 */
	public function insertAll(Zarizeni $zarizeni, DateTime $cas, array $hodnoty)
	{
		$senzory = $this->getSenzory($zarizeni);
		$pripojeni = $this->getPripojeni($zarizeni, $cas);

		foreach ($hodnoty as $senzorId => $hodnota) {
			if (!isset($senzory[$senzorId])) {
				throw new ModelException(sprintf('Invalid sensor "%d" for device type "%d"', $senzorId, $zarizeni->typZarizeni->id));
			}
			$mereni = new Mereni();
			$mereni->cas = $cas;
			$mereni->hodnota = $hodnota;
			$mereni->zarizeni = $zarizeni;
			$mereni->senzor = $senzory[$senzorId];
			$mereni->stanoviste = isset($pripojeni[$senzorId]) ? $pripojeni[$senzorId]->stanoviste : NULL;
			$mereni->vcelstvo = isset($pripojeni[$senzorId]) ? $pripojeni[$senzorId]->vcelstvo : NULL;

			$this->model->persist($mereni);
		}

		$this->model->flush();
	}

	/**
	 * Vrátí pole senzorů připojených k zadanému zařízení.
	 *
	 * @param Zarizeni $zarizeni
	 * @return pole ve formátu [(int) senzorId => (Senzor) senzor, ...]
	 */
	private function getSenzory(Zarizeni $zarizeni)
	{
		$senzory = [];
		foreach ($zarizeni->typZarizeni->senzory as $s) {
			$senzory[$s->id] = $s;
		}
		return $senzory;
	}

	/**
	 * Vrátí pole připojení senzorů zadaného zařízení v zadaném čase.
	 *
	 * @param Zarizeni $zarizeni
	 * @param DateTime $cas
	 * @return pole ve formátu [(int) senzorId => (PripojeniSenzoru) pripojeni, ...]
	 */
	private function getPripojeni(Zarizeni $zarizeni, DateTime $cas)
	{
		$pripojeni = [];
		foreach ($zarizeni->findPripojeni($cas) as $p) {
			$pripojeni[$p->senzor->id] = $p;
		}
		return $pripojeni;
	}

}
