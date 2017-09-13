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
use App\Model\Postrik;
use App\Model\ZemedelskyPodnik;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Správce postřiků.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class PostrikManager
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

	public function createPostrik(ZemedelskyPodnik $zemedelskyPodnik, array $data)
	{
		$katastralniUzemi = $this->getKatastralniUzemi($data['katastralniUzemiId']);
		$parcela = $this->getParcela($data['parcelaId']);
		$pudniBlok = $data['pudniBlokId'] ? $this->getPudniBlok($data['pudniBlokId']) : NULL;

		$postrik = new Postrik();

		$postrik->zemedelskyPodnik = $zemedelskyPodnik;
		$postrik->katastralniUzemi = $katastralniUzemi;
		$postrik->parcela = $parcela;
		$postrik->pudniBlok = $pudniBlok;

		$postrik->lat = $data['lat'];
		$postrik->lng = $data['lng'];
		$postrik->datum = $data['datum'];
		$postrik->plodina = $data['plodina'];
		$postrik->kvetouci = $data['kvetouci'];
		$postrik->nebezpecny = $data['nebezpecny'];
		$postrik->mimoLetovouAktivitu = $data['mimoLetovouAktivitu'];
		$postrik->uverejnitTelefon = $data['uverejnitTelefon'];

		$this->model->persistAndFlush($postrik);

		return $postrik;
	}

	public function changePostrik(Postrik $postrik, array $data)
	{
		$katastralniUzemi = $this->getKatastralniUzemi($data['katastralniUzemiId']);
		$parcela = $this->getParcela($data['parcelaId']);
		$pudniBlok = $data['pudniBlokId'] ? $this->getPudniBlok($data['pudniBlokId']) : NULL;

		$postrik->katastralniUzemi = $katastralniUzemi;
		$postrik->parcela = $parcela;
		$postrik->pudniBlok = $pudniBlok;

		$postrik->lat = $data['lat'];
		$postrik->lng = $data['lng'];
		$postrik->datum = $data['datum'];
		$postrik->plodina = $data['plodina'];
		$postrik->kvetouci = $data['kvetouci'];
		$postrik->nebezpecny = $data['nebezpecny'];
		$postrik->mimoLetovouAktivitu = $data['mimoLetovouAktivitu'];
		$postrik->uverejnitTelefon = $data['uverejnitTelefon'];

		$this->model->persistAndFlush($postrik);
	}

	public function deletePostrik(Postrik $postrik)
	{
		$postrik->smazan = 1;
		$postrik->smazanDatum = DateTime::from('now');

		$this->model->postriky->persistAndFlush($postrik);
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

	private function getPudniBlok($id)
	{
		$pudniBlok = $this->model->pudniBloky->getById($id);
		if (!$pudniBlok) {
			throw new ModelException('Půdní blok nebyl nalezen.');
		}
		return $pudniBlok;
	}

}
