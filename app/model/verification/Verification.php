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

namespace App\Model;

use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nextras\Orm\Entity\Entity;

/**
 * Odeslaný ověřovací kód pro potvrzení akce.
 *
 * @property-read int $id {primary}
 * @property User $user {m:1 User, oneSided=true}
 * @property string $action {enum self::AKCE_*}
 * @property string $code
 * @property string|NULL $data
 * @property DateTime $createdAt
 * @property DateTime $validUntil
 * @property DateTime|NULL $invalidatedAt
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Verification extends Entity
{

	const AKCE_REGISTRACE = 'registrace';
	const AKCE_ZMENA_EMAILU = 'zmena_emailu';
	const AKCE_OBNOVENI_HESLA = 'obnoveni_hesla';

	/**
	 * @var array|NULL
	 */
	private $_data = NULL;

	/**
	 * Zkontroluje platnost přijatého ověřovacího kódu.
	 *
	 * @param string $action
	 * @param string $code
	 * @return bool
	 */
	public function verifies($action, $code)
	{
		return $this->action === $action && $this->code === $code && !$this->invalidatedAt && DateTime::from('now') < $this->validUntil;
	}

	/**
	 * Vrátí položku z atributu $data.
	 * Pokud atribut $data obsahuje objekt zakódovaný pomocí JSON, metoda vrátí
	 * položku s příslušným klíčem, jinak vrátí NULL.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getData($key)
	{
		$this->decodeData();
		return isset($this->_data) && isset($this->_data[$key]) ? $this->_data[$key] : NULL;
	}

	/**
	 * Voláno při nastavování atributu $data.
	 * Vymaže cache dat používaných při čtení jednotlivých položek.
	 *
	 * @param string|NULL $data
	 * @return string|NULL
	 */
	protected function setterData($data)
	{
		$this->_data = NULL;
		return $data;
	}

	/**
	 * Dekóduje data uložená v atributu $data ve formátu JSON.
	 */
	private function decodeData()
	{
		if (isset($this->_data)) {
			return;
		}

		if ($this->data === NULL) {
			$this->_data = NULL;
			return;
		}

		try {
			$this->_data = Json::decode($this->data, Json::FORCE_ARRAY);
		} catch (JsonException $ex) {
			$this->_data = NULL;
		}
	}

}
