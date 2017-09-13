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
use App\Model\PripojeniSenzoru;
use App\Model\Senzor;
use App\Model\Stanoviste;
use App\Model\UmisteniVcelstva;
use App\Model\Vcelstvo;
use App\Model\Zarizeni;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Description of StanovisteManager
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class VcelstvaManager
{

	use SmartObject;

	private $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function add(Stanoviste $stanoviste, array $data)
	{
		$vcelstvo = new Vcelstvo();
		$vcelstvo->vcelar = $stanoviste->vcelar;
		$vcelstvo->poradoveCislo = $data['poradoveCislo'];
		$vcelstvo->cisloMatky = $data['cisloMatky'];
		$vcelstvo->puvodMatky = $data['puvodMatky'];
		$vcelstvo->barvaMatky = $data['barvaMatky'];
		$vcelstvo->typUlu = $data['typUlu'];
		$vcelstvo->ramkovaMira = $data['ramkovaMira'];
		$vcelstvo->jeReferencni = false;
		$this->model->persist($vcelstvo);

		$umisteni = new UmisteniVcelstva();
		$umisteni->stanoviste = $stanoviste;
		$umisteni->vcelstvo = $vcelstvo;
		$umisteni->datumUmisteni = $data['datumUmisteni'];
		$umisteni->aktualni = TRUE;
		$this->model->persist($umisteni);

		$this->model->flush();
	}

	public function update(Vcelstvo $vcelstvo, array $data)
	{
		$vcelstvo->poradoveCislo = $data['poradoveCislo'];
		$vcelstvo->cisloMatky = $data['cisloMatky'];
		$vcelstvo->puvodMatky = $data['puvodMatky'];
		$vcelstvo->barvaMatky = $data['barvaMatky'];
		$vcelstvo->typUlu = $data['typUlu'];
		$vcelstvo->ramkovaMira = $data['ramkovaMira'];
		$this->model->persistAndFlush($vcelstvo);
	}

	public function delete(Vcelstvo $vcelstvo)
	{
		$umisteni = $vcelstvo->aktualniUmisteni;
		if ($umisteni) {
			$umisteni->datumZruseni = DateTime::from('now');
			$umisteni->aktualni = FALSE;
			$this->model->persistAndFlush($umisteni);
		}
	}

	public function move(Vcelstvo $vcelstvo, Stanoviste $noveStanoviste, DateTime $datumPresunu, $vcetneZarizeni)
	{
		// Odpojíme měřící zařízení:
		if ($vcetneZarizeni) {
			// Pokud se včelstvo přemísťuje včetně zařízení,
			// zapamatuje si, která zařízení jsou k němu připojena
			// a odpojí zařízení od všech včelstev na současném stanovišti.
			$pripojenaZarizeni = $this->zjistiZarizeniPripojenaKeVcelstvu($vcelstvo);
			$this->odpojZarizeniOdOstatnichVcelstev($vcelstvo, $datumPresunu);
		} else {
			// Pokud se včelstvo přemísťuje bez zařízení,
			// odpojí zařízení od včelstva.
			$this->odpojZarizeniOdVcelstva($vcelstvo, $datumPresunu);
		}

		// Odebere včelstvo z aktuálního umístění
		$puvodniUmisteni = $vcelstvo->aktualniUmisteni;
		$puvodniUmisteni->datumPresunu = $datumPresunu;
		$puvodniUmisteni->aktualni = FALSE;
		$this->model->persist($puvodniUmisteni);

		// Přidá včelstvo na nové umístění
		$noveUmisteni = new UmisteniVcelstva();
		$noveUmisteni->stanoviste = $noveStanoviste;
		$noveUmisteni->vcelstvo = $vcelstvo;
		$noveUmisteni->datumUmisteni = $datumPresunu;
		$noveUmisteni->aktualni = TRUE;
		$this->model->persist($noveUmisteni);

		// Znovu připojí měřící zařízení
		if ($vcetneZarizeni) {
			// Pokud se včelstvo přemísťuje včetně zařízení,
			// připojí k němu zařízení, která byla odpojena.
			$this->pripojZarizeniKeVcelstvu($vcelstvo, $noveStanoviste, $pripojenaZarizeni, $datumPresunu);
		}

		// Zapíše všechny změny
		$this->model->flush();
	}

	/**
	 * Zjistí všechna zařízení a jejich senzory připojené k zadanému včelstvu.
	 *
	 * @param Vcelstvo $vcelstvo
	 * @return array
	 */
	private function zjistiZarizeniPripojenaKeVcelstvu(Vcelstvo $vcelstvo)
	{
		$zarizeni = [];
		foreach ($vcelstvo->aktualniPripojeni as $p) {
			$zarizeni[] = [$p->zarizeni, $p->senzor];
		}
		return $zarizeni;
	}

	/**
	 * Odpojí všechna zařízení od zadaného včelstva.
	 *
	 * @param Vcelstvo $vcelstvo
	 * @param DateTime $datumOdpojeni
	 */
	private function odpojZarizeniOdVcelstva(Vcelstvo $vcelstvo, DateTime $datumOdpojeni)
	{
		foreach ($vcelstvo->aktualniPripojeni as $p) {
			$this->odpoj($p, $datumOdpojeni);
		}
	}

	/**
	 * Odpojí zařízení, která jsou připojena k zadanému včelstvu, od tohoto
	 * včelstva i od všech ostatních včelstev na stejném sanovišti.
	 *
	 * @param Vcelstvo $vcelstvo
	 * @param DateTime $datumOdpojeni
	 */
	private function odpojZarizeniOdOstatnichVcelstev(Vcelstvo $vcelstvo, DateTime $datumOdpojeni)
	{
		// Zjistí všechna zařízení připojená ke včelstvu
		$zarizeni = [];
		foreach ($vcelstvo->aktualniPripojeni as $p) {
			$zarizeni[$p->zarizeni->id] = $p->zarizeni;
		}

		// Odpojí zjištěná zařízení od všech včelstev
		foreach ($zarizeni as $z) {
			foreach ($z->aktualniPripojeni as $p) {
				$this->odpoj($p, $datumOdpojeni);
			}
		}
	}

	/**
	 * Odpojí senzor zařízení od včelstva na stanovišti.
	 *
	 * @param PripojeniSenzoru $pripojeni
	 * @param DateTime $datumOdpojeni
	 */
	private function odpoj(PripojeniSenzoru $pripojeni, DateTime $datumOdpojeni)
	{
		$pripojeni->konec = $datumOdpojeni;
		$this->model->persist($pripojeni);

		$this->vyradNamereneHodnoty($pripojeni);
	}

	/**
	 * Hromadně připojí všechny zadané senzory zařízení ke včelstvu na novém
	 * stanovišti.
	 *
	 * @param Vcelstvo $vcelstvo
	 * @param array $seznamZarizeni
	 * @param DateTime $datumPripojeni
	 */
	private function pripojZarizeniKeVcelstvu(Vcelstvo $vcelstvo, Stanoviste $stanoviste, array $seznamZarizeni, DateTime $datumPripojeni)
	{
		foreach ($seznamZarizeni as list($zarizeni, $senzor)) {
			$this->pripoj($vcelstvo, $stanoviste, $zarizeni, $senzor, $datumPripojeni);
		}
	}

	/**
	 * Připojí senzor zařízení ke včelstvu na zadaném stanovišti.
	 *
	 * @param Vcelstvo $vcelstvo
	 * @param Stanoviste $stanoviste
	 * @param Zarizeni $zarizeni
	 * @param Senzor $senzor
	 * @param DateTime $datumPripojeni
	 */
	private function pripoj(Vcelstvo $vcelstvo, Stanoviste $stanoviste, Zarizeni $zarizeni, Senzor $senzor, DateTime $datumPripojeni)
	{
		$pripojeni = new PripojeniSenzoru();
		$pripojeni->zarizeni = $zarizeni;
		$pripojeni->senzor = $senzor;
		$pripojeni->stanoviste = $stanoviste;
		$pripojeni->vcelstvo = $vcelstvo;
		$pripojeni->pocatek = $datumPripojeni;
		$this->model->persist($pripojeni);

		$this->priradNamereneHodnoty($pripojeni);
	}

	/**
	 * Odstraní hodnoty naměřené po odpojení senzoru od včelstva a stanoviště,
	 * které byly ke včelstvu a stanovišti nesprávně přiřazeny (z důvodu
	 * prodlevy mezi skutečným odpojením a zapsáním do databáze).
	 *
	 * @param PripojeniSenzoru $pripojeni
	 */
	private function vyradNamereneHodnoty(PripojeniSenzoru $pripojeni)
	{
		assert($pripojeni->konec !== NULL);

		$prirazenaMereni = $this->model->mereni->findBy([
			'zarizeni' => $pripojeni->zarizeni,
			'senzor' => $pripojeni->senzor,
			'stanoviste' => $pripojeni->stanoviste,
			'vcelstvo' => $pripojeni->vcelstvo,
			'cas>=' => $pripojeni->konec,
		]);

		foreach ($prirazenaMereni as $m) {
			$m->stanoviste = NULL;
			$m->vcelstvo = NULL;
			$this->model->persist($m);
		}
	}

	/**
	 * Přiřadí hodnoty naměřené po připojení senzoru ke včelstvu a stanovišti,
	 * které nebyly ke včelstvu a stanovišti přiřazeny (z důvodu prodlevy mezi
	 * skutečným připojením a zapsáním do databáze).
	 *
	 * @param PripojeniSenzoru $pripojeni
	 */
	private function priradNamereneHodnoty(PripojeniSenzoru $pripojeni)
	{
		assert($pripojeni->konec === NULL);

		$neprirazenaMereni = $this->model->mereni->findBy([
			'zarizeni' => $pripojeni->zarizeni,
			'senzor' => $pripojeni->senzor,
			'stanoviste' => NULL,
			'vcelstvo' => NULL,
			'cas>=' => $pripojeni->zacatek,
		]);

		foreach ($neprirazenaMereni as $m) {
			$m->stanoviste = $pripojeni->stanoviste;
			$m->vcelstvo = $pripojeni->vcelstvo;
			$this->model->persist($m);
		}
	}

}
