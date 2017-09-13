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

namespace App\Api;

use App\Managers\MereniManager;
use App\Model\Model;
use App\Model\Zarizeni;
use DateTime;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;

/**
 * Adaptér pro příjem dat z generického zařízení.
 *
 * Podporovaný formát dat:
 * {
 * 	"device": string,
 * 	"time": string, (datum a čas měření, ISO8601 "YYYY-MM-DD'T'HH:MM:SS")
 * 	"weight": float, (hmotnost, kg)
 * 	"inner_temp_1": float, (vnitřní teplota, °C)
 * 	"inner_temp_2": float, (vnitřní teplota, °C)
 * 	"humidity": float, (vlhkost vzduchu, %)
 * 	"outer_temp": float, (vnější teplota, °C)
 * }
 *
 * @author Pavel Junek
 */
class GenericAdapter extends AbstractAdapter
{

	//
	// Sdílené atributy JSON objektu.
	//
	const ATTR_DEVICE = 'device';
	const ATTR_TIME = 'time';

	/**
	 * Identifikátory typů zařízení.
	 *
	 * @var array
	 */
	private $typyZarizeni = [1, 4, 5];

	/**
	 * Přiřazení senzorů k atributům struktury přijatých hodnot.
	 *
	 * @var array
	 */
	private $senzory;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param Model $model
	 * @param MereniManager $mereniManager
	 */
	public function __construct(Model $model, MereniManager $mereniManager)
	{
		parent::__construct($model, $mereniManager);
	}

	/**
	 * Vrátí datum a čas měření.
	 *
	 * @param array $data přijatá data
	 * @return DateTime datum a čas měření
	 * @throws InvalidInputException pokud datum a čas není ve správném formátu.
	 */
	protected function getCas(array $data)
	{
		try {
			Validators::assertField($data, self::ATTR_TIME, 'string', 'item "%" in input data');
			$received = $data[self::ATTR_TIME];

			$cas = DateTime::createFromFormat(DateTime::W3C, $received);
			if (!$cas) {
				throw new InvalidInputException(sprintf('Invalid datetime format "%s"', $received));
			}
			return $cas;
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

	/**
	 * Vrátí zařízení, ze kterého byla přijata data, a zkontroluje, zda se jedná
	 * o generické zařízení.
	 *
	 * @param array $data přijatá data
	 * @param string|NULL $token bezpečnostní token.
	 * @return Zarizeni nalezené zařízení
	 * @throws InvalidInputException pokud zařízení neexistuje nebo se nejedná o generické zařízení.
	 * @throws InvalidTokenException pokud bezpečnostní token není platný.
	 */
	protected function getZarizeni(array $data, $token = NULL)
	{
		try {
			Validators::assertField($data, self::ATTR_DEVICE, 'string', 'item "%" in input data');
			$received = $data[self::ATTR_DEVICE];

			$zarizeni = $this->model->zarizeni->getBy([
				'identifikator' => $received,
				'this->typZarizeni->id' => $this->typyZarizeni,
			]);
			if (!$zarizeni) {
				throw new InvalidInputException(sprintf('Unknown generic device "%s"', $received));
			}
//			if ($zarizeni->token && $zarizeni->token !== $token) {
//				throw new InvalidTokenException(sprintf('Invalid token "%s", expected "%s"', $token, $zarizeni->token));
//			}
			return $zarizeni;
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

	/**
	 * Vrátí pole naměřených hodnot.
	 *
	 * Výsledné pole bude ve formátu [(int) senzorId => (float) hodnota, ...].
	 *
	 * @param array $data přijatá data.
	 * @return array pole naměřených hodnot.
	 * @throws InvalidInputException pokud některá z přijatých hodnot není číselného typu.
	 */
	protected function getHodnoty(array $data)
	{
		$hodnoty = [];
		foreach ($this->zarizeni->typZarizeni->senzory as $senzor) {
			$hodnota = $this->getHodnota($data, $senzor->attr);
			if ($hodnota !== NULL) {
				$hodnoty[$senzor->id] = $hodnota;
			}
		}
		return $hodnoty;
	}

	/**
	 * Vrátí požadovanou naměřenou hodnotu.
	 *
	 * @param array $data přijatá data.
	 * @param string|NULL $attr klíč požadované hodnoty.
	 * @return float|NULL naměřená hodnota, nebo NULL, pokud klíč v poli přijatých hodnot není.
	 * @throws InvalidInputException pokud přijatá hodnota není číselného typu.
	 */
	private function getHodnota(array $data, $attr)
	{
		if (!$attr) {
			return NULL;
		}
		if (!isset($data[$attr])) {
			return NULL;
		}

		try {
			Validators::assertField($data, $attr, 'numeric', 'item "%" in input data');
			return (float) $data[$attr];
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

}
