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

use App\Model\Kontrola;
use App\Model\Model;
use App\Model\Vcelstvo;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Description of KontrolaManager
 *
 * @author Pavel Junek
 */
class KontrolaManager
{

	use SmartObject;

	/**
	 * @var Model
	 */
	private $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function add(Vcelstvo $vcelstvo, array $data)
	{
		$kontrola = new Kontrola();
		$kontrola->vcelstvo = $vcelstvo;
		$kontrola->datumKontroly = $data['datumKontroly'];
		$kontrola->pocetNastavku = $data['pocetNastavku'];
		$kontrola->matkaKlade = $data['matkaKlade'];
		$kontrola->obsedajiUlicek = $data['obsedajiUlicek'];
		$kontrola->plod = $data['plod'];
		$kontrola->zasoby = $data['zasoby'];
		$kontrola->pyl = $data['pyl'];
		$kontrola->mirnost = $data['mirnost'];
		$kontrola->sezeni = $data['sezeni'];
		$kontrola->rojivost = $data['rojivost'];
		$kontrola->rozvoj = $data['rozvoj'];
		$kontrola->hygiena = $data['hygiena'];
		$kontrola->mednyVynos = $data['mednyVynos'];
		$kontrola->priste = $data['priste'];
		$kontrola->poznamka = $data['poznamka'];
		$this->model->persistAndFlush($kontrola);

		return $kontrola;
	}

	public function update(Kontrola $kontrola, array $data)
	{
		$kontrola->datumKontroly = $data['datumKontroly'];
		$kontrola->pocetNastavku = $data['pocetNastavku'];
		$kontrola->matkaKlade = $data['matkaKlade'];
		$kontrola->obsedajiUlicek = $data['obsedajiUlicek'];
		$kontrola->plod = $data['plod'];
		$kontrola->zasoby = $data['zasoby'];
		$kontrola->pyl = $data['pyl'];
		$kontrola->mirnost = $data['mirnost'];
		$kontrola->sezeni = $data['sezeni'];
		$kontrola->rojivost = $data['rojivost'];
		$kontrola->rozvoj = $data['rozvoj'];
		$kontrola->hygiena = $data['hygiena'];
		$kontrola->mednyVynos = $data['mednyVynos'];
		$kontrola->priste = $data['priste'];
		$kontrola->poznamka = $data['poznamka'];
		$this->model->persistAndFlush($kontrola);
	}

	public function delete(Kontrola $kontrola)
	{
		$kontrola->smazana = 1;
		$kontrola->smazanaDatum = DateTime::from('now');
		$this->model->persistAndFlush($kontrola);
	}

}
