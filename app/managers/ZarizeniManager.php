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

use App\Model\Model;
use App\Model\ModelException;
use App\Model\PripojeniSenzoru;
use App\Model\Senzor;
use App\Model\Stanoviste;
use App\Model\TypZarizeni;
use App\Model\Vcelar;
use App\Model\Vcelstvo;
use App\Model\Zarizeni;
use DateTime;
use Nette\SmartObject;

/**
 * Description of ZarizeniManager
 *
 * @author Pavel Junek
 */
class ZarizeniManager
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
	 * Vytvoří nové zařízení se zadanými parametry.
	 *
	 * @param TypZarizeni $typZarizeni
	 * @param Vcelar $vcelar
	 * @param string $identifikator
	 * @param string $nazev
	 * @return Zarizeni
	 */
	public function vytvorZarizeni(TypZarizeni $typZarizeni, Vcelar $vcelar, $identifikator, $token, $nazev)
	{
		$zarizeni = new Zarizeni();
		$zarizeni->typZarizeni = $typZarizeni;
		$zarizeni->vcelar = $vcelar;
		$zarizeni->identifikator = $identifikator;
		$zarizeni->token = $token;
		$zarizeni->nazev = $nazev;

		$this->model->persist($zarizeni);
		$this->model->flush();

		return $zarizeni;
	}

	/**
	 * Propojí zadané zařízení se stanovištěm a vybranými včelstvy.
	 *
	 * @param Zarizeni $zarizeni
	 * @param Stanoviste $stanoviste
	 * @param array $arguments
	 */
	public function pripojZarizeniSeStanovistem(Zarizeni $zarizeni, Stanoviste $stanoviste, array $arguments)
	{
		$this->odpojVse($zarizeni);

		foreach ($arguments as $vcelstvoId => $senzory) {
			if ($vcelstvoId) {
				$vcelstvo = $this->najdiVcelstvo($stanoviste, $vcelstvoId);
			} else {
				$vcelstvo = NULL;
			}
			foreach ($senzory as $senzorId) {
				$senzor = $this->najdiSenzor($zarizeni, $senzorId);
				$this->pripoj($zarizeni, $senzor, $stanoviste, $vcelstvo);
			}
		}

		$this->model->flush();
	}

	/**
	 * Kompletně odpojí zadané zařízení.
	 *
	 * @param Zarizeni $zarizeni
	 */
	public function odpojZarizeni(Zarizeni $zarizeni)
	{
		$this->odpojVse($zarizeni);

		$this->model->flush();
	}

	/**
	 * Odpojí všechny senzory zadaného zařízení.
	 *
	 * @param Zarizeni $zarizeni
	 */
	private function odpojVse(Zarizeni $zarizeni)
	{
		foreach ($zarizeni->aktualniPripojeni as $pripojeni) {
			$pripojeni->konec = new DateTime();
			$this->model->persist($pripojeni);
		}
	}

	/**
	 * Najde včelstvo na zadaném stanovišti.
	 *
	 * @param Stanoviste $stanoviste
	 * @param int $vcelstvoId
	 * @return Vcelstvo
	 * @throws ModelException
	 */
	private function najdiVcelstvo(Stanoviste $stanoviste, $vcelstvoId)
	{
		$umisteni = $stanoviste->aktualniVcelstva->getBy(['this->vcelstvo->id' => $vcelstvoId]);
		if (!$umisteni) {
			throw new ModelException(sprintf('Včelstvo %d nenalezeno.', $vcelstvoId));
		}
		return $umisteni->vcelstvo;
	}

	/**
	 * Najde senzor zadaného zařízení.
	 *
	 * @param Zarizeni $zarizeni
	 * @param int $senzorId
	 * @return Senzor
	 * @throws ModelException
	 */
	private function najdiSenzor(Zarizeni $zarizeni, $senzorId)
	{
		$senzor = $zarizeni->typZarizeni->senzory->get()->getBy(['id' => $senzorId]);
		if (!$senzor) {
			throw new ModelException(sprintf('Senzor %d nenalezen.', $senzorId));
		}
		return $senzor;
	}

	/**
	 * Připojí senzor na zadaném zařízení ke stanovišti a včelstvu.
	 *
	 * @param Zarizeni $zarizeni
	 * @param Senzor $senzor
	 * @param Stanoviste $stanoviste
	 * @param Vcelstvo|NULL $vcelstvo
	 */
	private function pripoj(Zarizeni $zarizeni, Senzor $senzor, Stanoviste $stanoviste, Vcelstvo $vcelstvo = NULL)
	{
		$pripojeni = new PripojeniSenzoru();
		$pripojeni->pocatek = new DateTime();
		$pripojeni->stanoviste = $stanoviste;
		$pripojeni->vcelstvo = $vcelstvo;
		$pripojeni->zarizeni = $zarizeni;
		$pripojeni->senzor = $senzor;
		$this->model->persist($pripojeni);
	}

}
