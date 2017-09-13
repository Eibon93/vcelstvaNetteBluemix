<?php

/*
 * Copyright (C) 2017 Eibon
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

use App\Model\Model;
use App\Model\ModelException;
use App\Model\Stanoviste;
use App\Model\Vcelar;
use Nette\SmartObject;

/**
 * Description of StanovisteManager
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,   , Pavel Junek
 */
class StanovisteManager
{

	use SmartObject;

	private $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function addStanoviste(Vcelar $vcelar, array $data)
	{
		$katastralniUzemi = $this->getKatastralniUzemi($data['katastralniUzemiId']);
		$parcela = $this->getParcela($data['parcelaId']);

		$stanoviste = new Stanoviste();

		$stanoviste->vcelar = $vcelar;
		$stanoviste->katastralniUzemi = $katastralniUzemi;
		$stanoviste->parcela = $parcela;
		$stanoviste->lat = $data['lat'];
		$stanoviste->lng = $data['lng'];

		$stanoviste->nazev = $data['nazev'];
		$stanoviste->registracniCislo = $data['registracniCislo'];
		$stanoviste->pocatek = $data['pocatek'];
		$stanoviste->predpokladanyKonec = $data['predpokladanyKonec'];

		$this->model->persistAndFlush($stanoviste);

		return $stanoviste;
	}

	public function changeStanoviste(Stanoviste $stanoviste, array $data)
	{
		$katastralniUzemi = $this->getKatastralniUzemi($data['katastralniUzemiId']);
		$parcela = $this->getParcela($data['parcelaId']);

		$stanoviste->katastralniUzemi = $katastralniUzemi;
		$stanoviste->parcela = $parcela;
		$stanoviste->lat = $data['lat'];
		$stanoviste->lng = $data['lng'];

		$stanoviste->nazev = $data['nazev'];
		$stanoviste->registracniCislo = $data['registracniCislo'];
		$stanoviste->pocatek = $data['pocatek'];
		$stanoviste->predpokladanyKonec = $data['predpokladanyKonec'];

		$this->model->persistAndFlush($stanoviste);
	}

	private function getKatastralniUzemi($id)
	{
		$katastralniUzemi = $this->model->katastralniUzemi->getById($id);
		if (!$katastralniUzemi) {
			throw new ModelException('Katastrální území nebylo nalezeno.');
		}
		return $katastralniUzemi;
	}

	private function getParcela($id)
	{
		$parcela = $this->model->parcely->getById($id);
		if (!$parcela) {
			throw new ModelException('Parcela nebyla nalezena.');
		}
		return $parcela;
	}

}
