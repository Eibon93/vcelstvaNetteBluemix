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

namespace App\Client;

use App\Model\Model;
use App\Model\Postrik;
use App\Model\PudniBlok;
use App\Model\Stanoviste;
use Nette\Http\Url;
use Nette\SmartObject;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Description of DistanceClient
 *
 * @author Pavel Junek
 */
class DistanceClient
{

	use SmartObject;

	/**
	 * Výchozí URL serveru.
	 */
	const MAIN_URL = 'http://10.128.31.21/distance/pudni-blok-wgs84';
	const ALTERNATE_URL = 'http://10.128.31.21/distance/between-wgs84';

	/**
	 * Příznak, že server vrátil platnou odpověď.
	 */
	const STATUS_SUCCESS = 'success';

	/**
	 * Tolerance 500 metrů pro případy, kdy neznáme hranice pozemku a počítáme pouze vzdálenost bod-bod.
	 */
	const TOLERANCE = 500;

	/**
	 * @var Url
	 */
	private $mainUrl;

	/**
	 * @var Url
	 */
	private $alternateUrl;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param Model $model
	 * @param string|NULL $mainUrl
	 * @param string|NULL $alternateUrl
	 */
	public function __construct(Model $model, $mainUrl = NULL, $alternateUrl = NULL)
	{
		$this->model = $model;
		if ($mainUrl === NULL) {
			$this->mainUrl = new Url(self::MAIN_URL);
		} else {
			$this->mainUrl = new Url($mainUrl);
		}
		if ($alternateUrl === NULL) {
			$this->alternateUrl = new Url(self::ALTERNATE_URL);
		} else {
			$this->alternateUrl = new Url($alternateUrl);
		}
	}

	/**
	 * Změří vzdálenost mezi místem postřiku a stanovištěm včelstva.
	 *
	 * Pokud je znám půdní blok postřiku, změří přesnou vzdálenost mezi okrajem
	 * půdního bloku a umístěním včelstva pomocí webové služby.
	 *
	 * Pokud půdní blok není znám, nebo ud změří vzdálenost mezi místem postřiku a
	 * umístěním včelstva a zvětší ji o 500 m (jako tolerance kvůli neznalosti
	 * hranice pozemku).
	 *
	 * @param Postrik $postrik
	 * @param Stanoviste $stanoviste
	 * @return float
	 * @throws ClientException
	 */
	public function measure(Postrik $postrik, Stanoviste $stanoviste)
	{
		$pudniBlok = $postrik->pudniBlok;
		if ($pudniBlok) {
			try {
				$distance = $this->fetchMainDistance($pudniBlok, $stanoviste);
			} catch (ClientException $ex) {
				if ($ex->getCode() !== ClientException::DATA_ERROR) {
					throw $ex;
				}
				$distance = $this->fetchAlternateDistance($postrik, $stanoviste);
			}
			return $distance;
		} else {
			return $this->fetchAlternateDistance($postrik, $stanoviste);
		}
	}

	private function fetchMainDistance(PudniBlok $pudniBlok, Stanoviste $stanoviste)
	{
		$url = clone $this->mainUrl;
		$url->setPath($url->getPath() . '/' . $pudniBlok->id);
		$url->setQueryParameter('lat', $stanoviste->lat);
		$url->setQueryParameter('lng', $stanoviste->lng);

		return $this->fetchDistance($url);
	}

	private function fetchAlternateDistance(Postrik $postrik, Stanoviste $stanoviste)
	{
		$url = clone $this->alternateUrl;
		$url->setQueryParameter('lat1', $postrik->lat);
		$url->setQueryParameter('lng1', $postrik->lng);
		$url->setQueryParameter('lat2', $stanoviste->lat);
		$url->setQueryParameter('lng2', $stanoviste->lng);

		return $this->fetchDistance($url) - self::TOLERANCE; // Tolerance 500 metrů, když neznáme přesné hranice pozemku
	}

	private function fetchDistance(Url $url)
	{
		$text = @file_get_contents((string) $url);
		if ($text === FALSE) {
			throw new ClientException('Connection failed', ClientException::NETWORK_ERROR);
		}

		try {
			$data = Json::decode($text);

			if ($data->status !== self::STATUS_SUCCESS) {
				throw new ClientException('Invalid data', ClientException::DATA_ERROR);
			}

			return $data->distance;
		} catch (JsonException $ex) {
			throw new ClientException('Query failed', ClientException::SERVER_ERROR);
		}
	}

}
