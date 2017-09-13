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

namespace App\Managers;

use App\Client\DistanceClient;
use App\Model\MessageFactory;
use App\Model\MessageSender;
use App\Model\Model;
use App\Model\Template;
use App\Model\ZemedelskyPodnik;
use Nette\SmartObject;
use Nette\Utils\DateTime;

/**
 * Description of OhrozeniManager
 *
 * @author Pavel Junek
 */
class OhrozeniManager
{

	use SmartObject;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var DistanceClient
	 */
	private $distanceClient;

	/**
	 * @var MessageFactory
	 */
	private $messageFactory;

	/**
	 * @var MessageSender
	 */
	private $messageSender;

	public function __construct(Model $model, DistanceClient $distanceClient, MessageFactory $messageFactory, MessageSender $messageSender)
	{
		$this->model = $model;
		$this->distanceClient = $distanceClient;
		$this->messageFactory = $messageFactory;
		$this->messageSender = $messageSender;
	}

	/**
	 * Najde všechna stanoviště ohrožená postřiky zadaného zemědělského podniku.
	 *
	 * @param ZemedelskyPodnik $zemedelskyPodnik
	 * @return array
	 */
	public function findOhrozenaStanoviste(ZemedelskyPodnik $zemedelskyPodnik)
	{
		$seznamPostriku = $zemedelskyPodnik->postriky->get()->findBy([
			'smazan' => FALSE,
			'nebezpecny>' => 1,
			'datum>=' => DateTime::from('now'),
		]);
		$seznamStanovist = $this->model->stanoviste->findAll();

		$result = [];
		foreach ($seznamPostriku as $postrik) {
			foreach ($seznamStanovist as $stanoviste) {
				$vzdalenost = $this->distanceClient->measure($postrik, $stanoviste);
				if ($vzdalenost <= 5000) {
					$result[$postrik->id][] = [
						'stanoviste' => $stanoviste,
						'vzdalenost' => $vzdalenost,
					];
				}
			}
		}
		return $result;
	}

	public function odesliEmailOhrozenymVcelarum(ZemedelskyPodnik $zemedelskyPodnik, $text)
	{
		$vysledky = $this->findOhrozenaStanoviste($zemedelskyPodnik);

		$ids = [];
		foreach ($vysledky as $seznamStanovist) {
			foreach ($seznamStanovist as $hodnota) {
				foreach ($hodnota['stanoviste']->vcelar->uzivatele as $uzivatel) {
					$ids[$uzivatel->id] = TRUE;
				}
			}
		}

		$uzivatele = $this->model->users->findById(array_keys($ids));

		$template = $this->model->templates->getById(Template::ID_OZNAMENI_O_POSTRIKU);

		foreach ($uzivatele as $uzivatel) {
			$message = $this->messageFactory->createMessage($uzivatel, $template, ['text' => $text]);
			$this->messageSender->sendMessage($message);
		}
	}

}
