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
use App\Model\Senzor;
use App\Model\TypZarizeni;
use App\Model\Zarizeni;
use DateTime;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;
use Nextras\Orm\Collection\ICollection;

/**
 * Adaptér pro příjem dat přes síť Sigfox.
 *
 * Podporovaný formát dat:
 * {
 * 	"device": string, (identifikátor zařízení - 8 znaků hexadecimálně = 4 bajty)
 * 	"time": int, (UNIX timestamp)
 * 	"data": string, (binární zakódovaná data - 24 znaků hexadecimálně = 12 bajtů)
 * 	"seqNumber": int, (pořadové číslo zprávy)
 * 	"snr": float, (signal to noise ratio)
 * 	"avgSnr": float, (průměrný snr za posledních 25 zpráv)
 * }
 *
 * @author Pavel Junek
 */
class SigfoxAdapter extends AbstractAdapter
{

	//
	// Atributy JSON objektu.
	//
	const ATTR_DEVICE = 'device';
	const ATTR_TIME = 'time';
	const ATTR_BYTES = 'data';
	const ATTR_SEQ_NUMBER = 'seqNumber';
	const ATTR_SNR = 'snr';
	const ATTR_AVG_SNR = 'avgSnr';
	//
	// Identifikátory veličin společných pro všechna zařízení sítě Sigfox. Tyto
	// veličiny MUSÍ existovat.
	//
	const VAR_AVG_SNR = 5;
	const VAR_SEQUENCE_NUMBER = 6;
	const VAR_LOST_MESSAGE_COUNT = 7;

	/**
	 * @var DecoderFactory
	 */
	protected $decoderFactory;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param Model $model
	 * @param MereniManager $mereniManager
	 * @param DecoderFactory $decoderFactory
	 */
	public function __construct(Model $model, MereniManager $mereniManager, DecoderFactory $decoderFactory)
	{
		parent::__construct($model, $mereniManager);

		$this->decoderFactory = $decoderFactory;
	}

	/**
	 * Vrátí datum a čas měření.
	 *
	 * @param array $data přijatá data.
	 * @return DateTime datum a čas měření.
	 * @throws InvalidInputException pokud datum a čas není ve správném formátu.
	 */
	protected function getCas(array $data)
	{
		try {
			Validators::assertField($data, self::ATTR_TIME, 'numericint', 'item "%" in input data');
			$received = $data[self::ATTR_TIME];

			return new DateTime('@' . $received);
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

	/**
	 * Vrátí zařízení, ze kterého byla přijata data, a zkontroluje, zda se jedná
	 * o zařízení sítě Sigfox.
	 *
	 * @param array $data přijatá data.
	 * @param string|NULL $token bezpečnostní token.
	 * @return Zarizeni nalezené zařízení.
	 * @throws InvalidInputException pokud zařízení neexistuje nebo se nejedná o zařízení sítě Sigfox.
	 * @throws InvalidTokenException pokud bezpečnostní token není platný.
	 */
	protected function getZarizeni(array $data, $token = NULL)
	{
		try {
			Validators::assertField($data, self::ATTR_DEVICE, 'string', 'item "%" in input data');
			$received = $data[self::ATTR_DEVICE];

			$zarizeni = $this->model->zarizeni->getBy([
				'identifikator' => $received,
				'this->typZarizeni->technologie' => TypZarizeni::TECH_SIGFOX,
			]);
			if (!$zarizeni) {
				throw new InvalidInputException(sprintf('Unknown Sigfox device "%s"', $received));
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
	 */
	protected function getHodnoty(array $data)
	{
		$dekoder = $this->getDekoder();
		$bytes = $this->getBytes($data);

		$senzory = $this->getObecneSenzory();
		$senzorAvgSnr = isset($senzory[self::VAR_AVG_SNR]) ? $senzory[self::VAR_AVG_SNR] : NULL;
		$senzorSequenceNumber = isset($senzory[self::VAR_SEQUENCE_NUMBER]) ? $senzory[self::VAR_SEQUENCE_NUMBER] : NULL;
		$senzorLostMessageCount = isset($senzory[self::VAR_LOST_MESSAGE_COUNT]) ? $senzory[self::VAR_LOST_MESSAGE_COUNT] : NULL;

		$hodnoty = [];

		$avgSnr = ($senzorAvgSnr !== NULL) ? $this->getAvgSnr($data) : NULL;
		if ($avgSnr !== NULL) {
			$hodnoty[$senzorAvgSnr->id] = $avgSnr;
		}

		$sequenceNumber = ($senzorSequenceNumber !== NULL) ? $this->getSequenceNumber($data) : NULL;
		if ($sequenceNumber !== NULL) {
			$hodnoty[$senzorSequenceNumber->id] = $sequenceNumber;
		}

		$lostMessageCount = ($senzorLostMessageCount !== NULL) && ($senzorSequenceNumber !== NULL) && ($sequenceNumber !== NULL) ? $this->getLostMessageCount($sequenceNumber, $senzorSequenceNumber) : NULL;
		if ($lostMessageCount !== NULL) {
			$hodnoty[$senzorLostMessageCount->id] = $lostMessageCount;
		}

		return $hodnoty + $dekoder->decode($bytes);
	}

	/**
	 * Vrátí dekodér binárních dat pro právě zpracovávané zařízení.
	 *
	 * @return IDecoder dekodér binárních dat pro aktuální zařízení.
	 * @throws InvalidStateException pokud pro zadané zařízení není k dispozici dekodér.
	 */
	private function getDekoder()
	{
		return $this->decoderFactory->create($this->zarizeni);
	}

	/**
	 * Vrátí přijatá binární data jako hexadecimální řetězec.
	 *
	 * @param array $data přijatá data
	 * @return string binární data jako hexadecimální řetězec
	 * @throws InvalidInputException pokud zpráva neobsahuje binární data
	 */
	private function getBytes($data)
	{
		try {
			Validators::assertField($data, self::ATTR_BYTES, 'pattern:[0-9A-Fa-f]{0,24}', 'item "%" in input data');
			$received = $data[self::ATTR_BYTES];

			return str_pad((string) $received, 24, '0', STR_PAD_LEFT);
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

	/**
	 * Vrátí senzory pro měření těch veličin, které jsou společné pro všechna
	 * zařízení sítě Sigfox.
	 *
	 * @return array|Senzor[] pole senzorů aktuálního zařízení, které měří společné veličiny.
	 */
	private function getObecneSenzory()
	{
		$senzory = [];

		$veliciny = $this->model->veliciny->findById([
			self::VAR_AVG_SNR,
			self::VAR_SEQUENCE_NUMBER,
			self::VAR_LOST_MESSAGE_COUNT,
		]);

		foreach ($veliciny as $v) {
			$senzory[$v->id] = $v->senzory->get()->getBy(['typZarizeni' => $this->zarizeni->typZarizeni]);
		}

		return array_filter($senzory);
	}

	/**
	 * Vrátí průměrné signal-to-noise ratio.
	 *
	 * @param array $data přijatá data.
	 * @return float|NULL průměrné signal-to-noise ratio.
	 * @throws InvalidInputException pokud hodnota není číselného typu.
	 */
	private function getAvgSnr(array $data)
	{
		if (!isset($data[self::ATTR_AVG_SNR])) {
			return NULL;
		}

		try {
			Validators::assertField($data, self::ATTR_AVG_SNR, 'numeric', 'item "%" in input data');
			return (float) $data[self::ATTR_AVG_SNR];
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

	/**
	 * Vrátí pořadové číslo zprávy ze zadaného zařízení.
	 *
	 * @param array $data přijatá data.
	 * @return int|NULL pořadové číslo zprávy z tohoto zařízení.
	 * @throws InvalidInputException pokud hodnota není celočíselného typu.
	 */
	private function getSequenceNumber(array $data)
	{
		if (!isset($data[self::ATTR_SEQ_NUMBER])) {
			return NULL;
		}

		try {
			Validators::assertField($data, self::ATTR_SEQ_NUMBER, 'numericint', 'item "%" in input data');
			return (int) $data[self::ATTR_SEQ_NUMBER];
		} catch (AssertionException $ex) {
			throw new InvalidInputException($ex->getMessage());
		}
	}

	/**
	 * Vrátí počet ztracených práv ze zadaného zařízení a včelstva.
	 *
	 * @param int $sequenceNumber pořadové číslo přijaté zprávy.
	 * @param Senzor $senzorSequenceNumber senzor aktuálního zařízení snímající pořadové číslo zprávy.
	 * @return int|NULL počet ztracených zpráv z tohoto zařízení.
	 */
	private function getLostMessageCount($sequenceNumber, Senzor $senzorSequenceNumber)
	{
		$lastSequenceNumber = $this->getPosledniHodnota($senzorSequenceNumber);
		if ($lastSequenceNumber === NULL) {
			return NULL;
		}

		$interval = $sequenceNumber - $lastSequenceNumber;
		if ($interval <= 0) {
			$interval += 4096;
		}

		return $interval - 1;
	}

	/**
	 * Vrátí naposledy naměřenou hodnotu ze zadaného senzoru.
	 *
	 * @param Senzor $senzor vybraný senzor aktuálního zařízení.
	 * @return int|NULL naposledy naměřená hodnota, nebo NULL, pokud číslo nelze zjistit.
	 */
	private function getPosledniHodnota(Senzor $senzor)
	{
		$aktualniPripojeni = $this->getAktualniPripojeni()->getBy(['senzor' => $senzor]);
		if ($aktualniPripojeni === NULL) {
			return NULL;
		}

		$posledniMereni = $this->zarizeni->mereni->get()
				->findBy([
					'stanoviste' => $aktualniPripojeni->stanoviste,
					'vcelstvo' => $aktualniPripojeni->vcelstvo,
					'senzor' => $aktualniPripojeni->senzor,
				])
				->orderBy('cas', ICollection::DESC)
				->limitBy(1)
				->fetch();
		if ($posledniMereni === NULL) {
			return NULL;
		}

		return $posledniMereni->hodnota;
	}

}
