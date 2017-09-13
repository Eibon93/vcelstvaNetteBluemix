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

namespace App\Managers;

use App\Model\Adresa;
use App\Model\Model;
use App\Model\ProdejnaMedu;
use App\Model\Vcelar;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Správa prodejních míst medu.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class ProdejnaMeduManager
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

	public function create(Vcelar $vcelar, array $data)
	{
		$prodejna = new ProdejnaMedu();
		$prodejna->vcelar = $vcelar;
		$prodejna->datumVytvoreni = DateTime::from('now');
		$prodejna->lat = $data['lat'];
		$prodejna->lng = $data['lng'];
		$prodejna->nazev = $data['nazev'];
		$prodejna->informace = $data['informace'];
		$prodejna->uverejnitTelefon = $data['uverejnitTelefon'];

		$adresa = new Adresa();
		$adresa->ulice = $data['ulice'];
		$adresa->castObce = $data['castObce'];
		$adresa->psc = $data['psc'];
		$adresa->obec = $this->model->obce->getBy([
			'nazev' => trim($data['castObce'], ' 0123456789')
		]);

		$prodejna->adresa = $adresa;

		$this->model->persist($prodejna->adresa);
		$this->model->persist($prodejna);
		$this->model->flush();
	}

	public function update(ProdejnaMedu $prodejna, array $data)
	{
		$prodejna->lat = $data['lat'];
		$prodejna->lng = $data['lng'];
		$prodejna->nazev = $data['nazev'];
		$prodejna->informace = $data['informace'];
		$prodejna->uverejnitTelefon = $data['uverejnitTelefon'];

		$prodejna->adresa->ulice = $data['ulice'];
		$prodejna->adresa->castObce = $data['castObce'];
		$prodejna->adresa->psc = $data['psc'];
		$prodejna->adresa->obec = $this->model->obce->getBy([
			'nazev' => trim($data['castObce'], ' 0123456789')
		]);

		$this->model->persist($prodejna->adresa);
		$this->model->persist($prodejna);
		$this->model->flush();
	}

	public function delete(ProdejnaMedu $prodejna)
	{
		$prodejna->smazana = TRUE;
		$prodejna->smazanaDatum = DateTime::from('now');

		$this->model->persistAndFlush($prodejna);
	}

}
