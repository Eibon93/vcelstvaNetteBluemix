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

use App\Model\KatastralniUzemi;
use App\Model\Model;
use App\Model\Obec;
use App\Model\Parcela;
use App\Model\PudniBlok;
use Exception;
use Nette\Http\Url;
use Nette\SmartObject;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Description of GeoClient
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class GeoClient
{

	use SmartObject;

	/**
	 * Výchozí URL serveru.
	 */
	const DEFAULT_URL = 'http://10.128.31.21/query/wgs84';

	/**
	 * Příznak, že server vrátil platnou odpověď.
	 */
	const STATUS_SUCCESS = 'success';

	/**
	 * Druh parcely - stavební pozemek.
	 */
	const DRUH_STAVEBNI = 'stavebni';

	/**
	 * Druh parcely - zemědělský pozemek.
	 */
	const DRUH_POZEMKOVA = 'pozemkova';

	/**
	 * @var Url
	 */
	private $url;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param Model $model
	 * @param string|NULL $url
	 */
	public function __construct(Model $model, $url = NULL)
	{
		$this->model = $model;
		if ($url === NULL) {
			$this->url = new Url(self::DEFAULT_URL);
		} else {
			$this->url = new Url($url);
		}
	}

	/**
	 * Získá informace o parcele na zadaných souřadnicích. Data současně uloží do databáze pro pozdější použití.
	 *
	 * @param float $lat
	 * @param float $lng
	 * @return Parcela|NULL nalezená parcela, nebo NULL, pokud na zadané pozici neleží žádná známá parcela.
	 * @throws ClientException pokud došlo k závažné chybě a data nebylo možné získat.
	 */
	public function query($lat, $lng)
	{
		$url = clone $this->url;
		$url->setQueryParameter('lat', $lat);
		$url->setQueryParameter('lng', $lng);

		$text = @file_get_contents((string) $url);
		if ($text === FALSE) {
			throw new ClientException('Connection failed', ClientException::NETWORK_ERROR);
		}

		try {
			$data = Json::decode($text);

			if ($data->status === self::STATUS_SUCCESS) {
				return $this->saveData($data);
			} else {
				return NULL;
			}
		} catch (JsonException $ex) {
			throw new ClientException('Query failed', ClientException::SERVER_ERROR);
		}
	}

	/**
	 * Uloží přijatá data do databáze a vrátí uloženou parcelu.
	 *
	 * @param mixed $data
	 * @return Parcela
	 * @throws ClientException pokud nebylo možné data uložit.
	 */
	private function saveData($data)
	{
		$obec = $this->saveObec($data->obec);
		$katastralniUzemi = $this->saveKatastralniUzemi($data->katastralniUzemi, $obec);
		$parcela = $this->saveParcela($data->parcela, $katastralniUzemi);
		if ($data->pudniBlok) {
			$pudniBlok = $this->savePudniBlok($data->pudniBlok, $katastralniUzemi);
		} else {
			$pudniBlok = NULl;
		}
		$this->model->flush();

		return ['obec' => $obec, 'katastralniUzemi' => $katastralniUzemi, 'parcela' => $parcela, 'pudniBlok' => $pudniBlok];
	}

	/**
	 * Ověří, že obec je v databázi.
	 *
	 * @param mixed $data
	 * @return Obec
	 * @throws ClientException pokud obec v databázi není.
	 */
	private function saveObec($data)
	{
		$obec = $this->model->obce->getById($data->id);
		if (!$obec) {
			throw new ClientException('Invalid data', ClientException::DATA_ERROR);
		}
		return $obec;
	}

	/**
	 * Ověří, že katastrální území je v databázi, a pokud není, doplní ho.
	 *
	 * @param mixed $data
	 * @param Obec $obec
	 * @return KatastralniUzemi
	 */
	private function saveKatastralniUzemi($data, Obec $obec)
	{
		$katastralniUzemi = $this->model->katastralniUzemi->getById($data->id);
		if (!$katastralniUzemi) {
			$katastralniUzemi = new KatastralniUzemi();
			$katastralniUzemi->id = $data->id;
		}
		$katastralniUzemi->obec = $obec;
		$katastralniUzemi->nazev = $data->nazev;
		$this->model->persist($katastralniUzemi);

		return $katastralniUzemi;
	}

	/**
	 * Ověří, že parcela je v databázi, a pokud není, doplní ji.
	 *
	 * @param mixed $data
	 * @param KatastralniUzemi $katastralniUzemi
	 * @return Parcela
	 */
	private function saveParcela($data, KatastralniUzemi $katastralniUzemi)
	{
		$parcela = $this->model->parcely->getById($data->id);
		if (!$parcela) {
			$parcela = new Parcela();
			$parcela->id = $data->id;
		}
		$parcela->katastralniUzemi = $katastralniUzemi;
		$parcela->cislo = $data->kmenoveCislo;
		$parcela->podcislo = $data->pododdeleniCisla;
		$parcela->druh = $this->getDruh($data->druh);
		$this->model->persist($parcela);

		return $parcela;
	}

	private function savePudniBlok($data, KatastralniUzemi $katastralniUzemi)
	{
		$pudniBlok = $this->model->pudniBloky->getById($data->id);
		if (!$pudniBlok) {
			$pudniBlok = new PudniBlok();
			$pudniBlok->id = $data->id;
		}
		$pudniBlok->katastralniUzemi = $katastralniUzemi;
		$this->model->persist($pudniBlok);

		return $pudniBlok;
	}

	/**
	 * Vrátí druh parcely odpovídající přijaté hodnotě.
	 *
	 * @param string $druh
	 * @return string
	 * @throws ClientException
	 */
	private function getDruh($druh)
	{
		switch ($druh) {
			case self::DRUH_STAVEBNI:
				return Parcela::DRUH_STAVEBNI;
			case self::DRUH_POZEMKOVA:
				return Parcela::DRUH_POZEMKOVA;
		}
		throw new ClientException('Invalid data', ClientException::DATA_ERROR);
	}

}

class ClientException extends Exception
{

	const NETWORK_ERROR = 1;
	const SERVER_ERROR = 2;
	const DATA_ERROR = 3;

}
