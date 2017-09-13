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
use App\Model\ZemedelskyPodnik;

/**
 * Description of StanovisteManager
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class ZemedelecManager
{

	private $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function updateZemedelec(ZemedelskyPodnik $zemedelec, array $data)
	{
		$zemedelec->nazev = $data['nazev'];
		$zemedelec->ico = $data['ico'];

		$zemedelec->adresa->ulice = $data['ulice'];
		$zemedelec->adresa->castObce = $data['castObce'];
		$zemedelec->adresa->psc = $data['psc'];
		$zemedelec->adresa->obec = $this->model->obce->getBy(['nazev' => trim($data['castObce'], ' 0123456789')]);

		$this->model->persist($zemedelec->adresa);
		$this->model->persist($zemedelec);
		$this->model->flush();
	}

}
