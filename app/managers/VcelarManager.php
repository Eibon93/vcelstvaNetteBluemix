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
use App\Model\Vcelar;

/**
 * Description of StanovisteManager
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class VcelarManager
{

	private $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function updateVcelar(Vcelar $vcelar, array $data)
	{
		$vcelar->registracniCislo = $data['registracniCislo'] ?: NULL;

		$vcelar->nazev = $data['pravnickaFyzicka'] === 'p' ? $data['nazev'] : NULL;
		$vcelar->ico = $data['pravnickaFyzicka'] === 'p' ? $data['ico'] : NULL;
		$vcelar->rodneCislo = $data['pravnickaFyzicka'] === 'f' && $data['rodneCislo'] ? $data['rodneCislo'] : NULL;

		$vcelar->adresa->ulice = $data['ulice'];
		$vcelar->adresa->castObce = $data['castObce'];
		$vcelar->adresa->psc = $data['psc'];
		$vcelar->adresa->obec = $this->model->obce->getBy(['nazev' => trim($data['castObce'], ' 0123456789')]);

		$vcelar->vcelariOd = $data['vcelariOd'];

		$this->model->persist($vcelar->adresa);
		$this->model->persist($vcelar);
		$this->model->flush();
	}

}
