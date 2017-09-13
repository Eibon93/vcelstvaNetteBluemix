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

namespace App\Presenters;

use App\Api\GenericAdapter;
use App\Api\InvalidInputException;
use App\Api\InvalidTokenException;
use App\Api\SigfoxAdapter;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Tracy\Debugger;

/**
 * Presenter, který přijímá data z měřících zařízení.
 *
 * Základem je metoda doPostGeneric($json), která přijímá data metodou POST ve
 * formátu JSON. Data musejí být v obecném formátu popsaném ve veřejném API.
 *
 * Dále je zde metoda doPostSigfox($json), která přijímá data ze sítě SigFox
 * zakódovaná v našem veřejném formátu.
 *
 * Kromě toho jsou zde i metody pro příjem dat z různých typů zařízení výrobců,
 * kteří nejsou (či nechtějí být) kompatibilní s naším API.
 *
 * @author Pavel Junek
 */
class CallbackPresenter extends ApiPresenter
{

	/**
	 * @var GenericAdapter
	 * @inject
	 */
	public $genericAdapter;

	/**
	 * @var SigfoxAdapter
	 * @inject
	 */
	public $sigfoxAdapter;

	/**
	 * Přijme data z měřícího zařízení ve formátu JSON a uloží je do databáze.
	 *
	 * Podporovaný formát:
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
	 * @param array $json
	 *
	 * @see GenericAdapter
	 */
	public function doPostGeneric(array $json)
	{
		Debugger::log(sprintf('Received generic data: %s', Json::encode($json)));
		try {
			$token = $this->getToken();
			$this->genericAdapter->insert($json, $token);
			$this->sendJson(['result' => TRUE]);
		} catch (InvalidInputException $ex) {
			Debugger::log($ex->getMessage());
			$this->error($ex->getMessage(), IResponse::S400_BAD_REQUEST);
		} catch (InvalidTokenException $ex) {
			Debugger::log($ex->getMessage());
			$this->error($ex->getMessage(), IResponse::S403_FORBIDDEN);
		}
	}

	/**
	 * Přijme data ze sítě Sigfox ve formátu JSON a uloží je do databáze.
	 *
	 * Podporovaný formát:
	 * {
	 * 	"device": string, (identifikátor zařízení - 8 znaků hexadecimálně = 4 bajty)
	 * 	"time": int, (UNIX timestamp)
	 * 	"data": string, (binární zakódovaná data - 24 znaků hexadecimálně = 12 bajtů)
	 * 	"seqNumber": int, (pořadové číslo zprávy)
	 * 	"snr": float, (Signal to noise ratio)
	 * 	"avgSnr": float, (průměrný snr za posledních 25 zpráv)
	 * 	"station": string, (identifikátor základnové stanice, která data přijala - 4 znaky hexadecimálně = 2 bajty)
	 * }
	 *
	 * @param array $json
	 *
	 * @see SigfoxAdapter
	 */
	public function doPostSigfox(array $json)
	{
		Debugger::log(sprintf('Received sigfox data: %s', Json::encode($json)));
		try {
			$token = $this->getToken();
			$this->sigfoxAdapter->insert($json, $token);
			$this->sendJson(['result' => TRUE]);
		} catch (InvalidInputException $ex) {
			Debugger::log($ex->getMessage());
			$this->error($ex->getMessage(), IResponse::S400_BAD_REQUEST);
		} catch (InvalidTokenException $ex) {
			Debugger::log($ex->getMessage());
			$this->error($ex->getMessage(), IResponse::S403_FORBIDDEN);
		}
	}

	/**
	 * Vrátí bezpečnostní token zaslaný v hlavičce požadavku.
	 *
	 * @return string|NULL
	 * @throws InvalidTokenException
	 */
	private function getToken()
	{
		$header = $this->getHttpRequest()->getHeader('Authorization');
		if (!$header) {
			return NULL;
		}

		$match = Strings::match($header, '#^Basic\\s+(.+)$#');
		if (!$match) {
			throw new InvalidTokenException('Invalid token header.');
		}

		$token = base64_decode($match[1], TRUE);
		if ($token === FALSE) {
			throw new InvalidTokenException('Invalid token encoding.');
		}

		return $token;
	}

}
