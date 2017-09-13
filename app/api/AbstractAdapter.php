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
use App\Model\PripojeniSenzoru;
use App\Model\Zarizeni;
use DateTime;
use Nette\SmartObject;
use Nextras\Orm\Collection\ICollection;

/**
 * Abstraktní adaptér, který sice umí ukládat naměřené hodnoty, ale neumí je
 * dekódovat z přijatých dat. K tomu je potřeba dodefinovat připravené
 * abstraktní mdetody.
 *
 * @author Pavel Junek
 */
abstract class AbstractAdapter implements IAdapter
{

	use SmartObject;

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var MereniManager
	 */
	protected $mereniManager;

	/**
	 * @var DateTime
	 */
	protected $cas = NULL;

	/**
	 * @var Zarizeni
	 */
	protected $zarizeni = NULL;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param Model $model
	 * @param MereniManager $mereniManager
	 */
	public function __construct(Model $model, MereniManager $mereniManager)
	{
		$this->model = $model;
		$this->mereniManager = $mereniManager;
	}

	/**
	 * Uloží všechny veličiny přijaté ze zařízení.
	 *
	 * @param array $data přijatá data.
	 * @param string|NULL $token bezpečnostní token.
	 * @throws InvalidInputException pokud data nelze uložit kvůli chybě v datech.
	 * @throws InvalidStateException pokud data nelze uložit kvůli špatně nastavené databázi.
	 */
	public function insert(array $data, $token = NULL)
	{
		$this->cas = $this->getCas($data);
		$this->zarizeni = $this->getZarizeni($data, $token);

		$hodnoty = $this->getHodnoty($data);

		$this->mereniManager->insertAll($this->zarizeni, $this->cas, $hodnoty);
	}

	/**
	 * Vrátí připojení všech senzorů právě zpracovávaného zařízení v aktuálním
	 * čase.
	 *
	 * @return ICollection|PripojeniSenzoru[] aktuální připojení senzorů.
	 */
	protected function getAktualniPripojeni()
	{
		return $this->zarizeni->findPripojeni($this->cas);
	}

	/**
	 * Vrátí datum a čas měření.
	 *
	 * @param array $data přijatá data.
	 * @return DateTime datum a čas měření.
	 * @throws InvalidInputException pokud datum a čas není ve správném formátu.
	 */
	protected abstract function getCas(array $data);

	/**
	 * Vrátí zařízení, ze kterého byla přijata data, a zkontroluje, zda se jedná
	 * o zařízení správného typu pro tento adaptér.
	 *
	 * @param array $data přijatá data.
	 * @param string|NULL $token bezpečnostní token.
	 * @return Zarizeni nalezené zařízení.
	 * @throws InvalidInputException pokud zařízení neexistuje nebo je jiného typu.
	 * @throws InvalidTokenException pokud bezpečnostní token není platný.
	 */
	protected abstract function getZarizeni(array $data, $token = NULL);

	/**
	 * Vrátí pole naměřených hodnot.
	 *
	 * Výsledné pole musí být ve formátu [(int) senzorId => (float) hodnota, ...].
	 *
	 * @param array $data přijatá data.
	 * @return array pole naměřených hodnot.
	 */
	protected abstract function getHodnoty(array $data);
}
