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

use App\Model\UmisteniVcelstva;
use DateTime;
use Nette\Http\Response;

/**
 * Poskytuje přístup k naměřeným datům.
 *
 * @author Pavel Junek
 */
class DataPresenter extends BasePresenter
{

	/**
	 * Odešle všechna data naměřená na zadaném včelstvu v zadaném intervalu.
	 *
	 * @param int $id
	 * @param string|NULL $pocatek
	 * @param string|NULL $konec
	 */
	public function renderMereni($id, $pocatek = NULL, $konec = NULL)
	{
		$vcelstvo = $this->model->vcelstva->getById($id);
		if (!$vcelstvo) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}
		if (!$this->getUser()->isAllowed($vcelstvo, 'view')) {
			$this->error('Nemáte oprávnění k požadované operaci.', Response::S403_FORBIDDEN);
		}

		$umisteni = $vcelstvo->aktualniUmisteni;
		if (!$umisteni) {
			$this->error('Včelstvo nebylo nalezeno.', Response::S404_NOT_FOUND);
		}

		$datumPocatek = $this->getDate($pocatek, FALSE);
		$datumKonec = $this->getDate($konec, TRUE);

		$data = [];
		foreach ($umisteni->findMereni($datumPocatek, $datumKonec) as $m) {
			if (!$m->senzor->velicina->zobrazovat) {
				continue;
			}

			// Velicina
			$velicina = & $data[$m->senzor->velicina->id];
			if (!isset($velicina)) {
				$velicina = [
					'velicina' => $m->senzor->velicina->nazev,
					'jednotka' => $m->senzor->velicina->jednotka,
					'senzory' => [],
				];
			}

			// Senzor
			$senzor = & $velicina['senzory'][$m->senzor->id][$m->stanoviste->id];
			if (!isset($senzor)) {
				$senzor = [
					'zarizeni' => $m->zarizeni->nazev,
					'senzor' => $m->senzor->nazev,
					'barva' => $m->senzor->barva,
					'stanoviste' => $m->stanoviste->nazev,
					'historicke' => $m->stanoviste->id !== $umisteni->stanoviste->id,
				];
			}

			// Hodnota
			$hodnoty = & $senzor['hodnoty'];
			$hodnoty[] = [
				$m->cas->format(DATE_W3C),
				$m->hodnota,
			];
		}

		$this->sendJson($data);
	}

	/**
	 * Převede zadaný text na objekt typu datum a čas. Pokud žádný text není
	 * zadán, vrátí NULL. Pokud text není ve správném formátu, zobrazí chybovou
	 * stránku.
	 *
	 * @param string|NULL $string
	 * @param bool $end
	 * @return DateTime|NULL
	 */
	private function getDate($string, $end)
	{
		$date = $string !== NULL && $string !== '' ? DateTime::createFromFormat('j.n.Y', $string) : NULL;
		if ($date === FALSE) {
			$this->error('Invalid date format', Response::S400_BAD_REQUEST);
		}
		if ($date !== NULL) {
			if ($end) {
				$date->setTime(23, 59, 59);
			} else {
				$date->setTime(0, 0, 0);
			}
		}
		return $date;
	}

}
