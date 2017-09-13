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

use App\Client\ClientException;
use App\Client\GeoClient;
use App\Presenters\BasePresenter;

/**
 * Description of ApiPresenter
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class GeoPresenter extends BasePresenter
{

	/**
	 * @var GeoClient
	 * @inject
	 */
	public $geoClient;

	/**
	 * Vyhledá informace o parcele na zadané pozici a odešle je jako JSON.
	 *
	 * @param float $lat
	 * @param float $lng
	 */
	public function renderQuery($lat, $lng)
	{
		try {
			$result = $this->geoClient->query($lat, $lng);
			if ($result) {
				$this->payload->status = 'success';

				$parcela = $result['parcela'];
				$this->payload->parcela = [
					'id' => $parcela->id,
					'cislo' => $parcela->cislo,
					'podcislo' => $parcela->podcislo,
					'druh' => $parcela->druh,
				];

				$katastralniUzemi = $result['katastralniUzemi'];
				$this->payload->katastralniUzemi = [
					'id' => $katastralniUzemi->id,
					'nazev' => $katastralniUzemi->nazev,
				];

				$obec = $result['obec'];
				$this->payload->obec = [
					'id' => $obec->id,
					'nazev' => $obec->nazev,
				];

				$pudniBlok = $result['pudniBlok'];
				if ($pudniBlok) {
					$this->payload->pudniBlok = [
						'id' => $pudniBlok->id,
					];
				} else {
					$this->payload->pudniBlok = NULL;
				}
			} else {
				$this->payload->status = 'empty';
			}
		} catch (ClientException $ex) {
			$this->payload->status = 'error';
		}
		$this->sendPayload();
	}

}
