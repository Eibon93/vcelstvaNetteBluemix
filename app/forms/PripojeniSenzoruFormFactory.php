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

namespace App\Forms;

use App\Managers\ZarizeniManager;
use App\Model\Model;
use App\Model\ModelException;
use App\Model\Senzor;
use App\Model\Stanoviste;
use App\Model\TypZarizeni;
use App\Model\Zarizeni;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na formulář, pomocí kterého může uživatel propojovat svá měřící
 * zařízení se stanovišti a včelstvy.
 *
 * Uživatel si pro každou skupinu senzorů vybere včelstvo, kde jsou tyto senzory
 * umístěny.
 *
 * Metoda create() potřebuje znát zařízení a stanoviště, pro které má formulář
 * vytvořit. Pro každou kombinaci totiž může formulář vypadat jinak. Podle
 * typu zařízení se vytvářejí ovládací prvky pro jednotlivé senzory, podle
 * stanoviště se pak načítají seznamy dostupných včelstev.
 *
 * @author Pavel Junek
 */
class PripojeniSenzoruFormFactory
{

	use SmartObject;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var ZarizeniManager
	 */
	private $zarizeniManager;

	/**
	 * Inicializuje novou instanci.
	 *
	 * @param User $user
	 * @param Model $model
	 * @param ZarizeniManager $zarizeniManager
	 */
	public function __construct(User $user, Model $model, ZarizeniManager $zarizeniManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->zarizeniManager = $zarizeniManager;
	}

	/**
	 * Vytvoří nový formulář pro připojení senzorů zadaného zařízení ke
	 * včelstvům na zadaném stanovišti.
	 *
	 * @param Zarizeni $zarizeni
	 * @param Stanoviste $stanoviste
	 * @return Form
	 */
	public function create(Zarizeni $zarizeni, Stanoviste $stanoviste)
	{
		$senzory = $this->fetchSenzory($zarizeni->typZarizeni);
		$vcelstva = $this->fetchVcelstva($stanoviste);

		$form = new Form();
		$form->addHidden('zarizeniId', $zarizeni->id)
				->setRequired()
				->addRule(Form::EQUAL, NULL, $zarizeni->id);
		$form->addHidden('stanovisteId', $stanoviste->id)
				->setRequired()
				->addRule(Form::EQUAL, NULL, $stanoviste->id);

		foreach ($senzory as $umisteni => $skupina) {
			$form->addGroup($this->getNazevUmisteni($umisteni));

			$container = $form->addContainer($umisteni);

			if ($umisteni !== Senzor::UMISTENI_STANOVISTE) {
				$container->addSelect('vcelstvoId', 'Včelstvo')
						->setItems($vcelstva)
						->setPrompt('');
			}

			foreach ($skupina as $s) {
				$container->addCheckbox($s->id, $s->nazev);
			}
		}

		$form->addGroup();
		$form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a propojí senzory se stanovištěm a včelstvy.
	 *
	 * @param Form $form
	 * @param array $values
	 */
	public function onFormSubmitted(Form $form, array $values)
	{
		$zarizeni = $this->getZarizeni($values['zarizeniId']);
		$stanoviste = $this->getStanoviste($values['stanovisteId']);

//		if (!$this->user->isAllowed($zarizeni, 'connect') || !$this->user->isAllowed($stanoviste, 'connect')) {
//			throw new ForbiddenRequestException();
//		}

		try {
			$arguments = [];
			foreach (array_filter($values, 'is_array') as $arg) {
				if (array_key_exists('vcelstvoId', $arg) && !$arg['vcelstvoId']) {
					continue;
				}
				$vcelstvoId = array_key_exists('vcelstvoId', $arg) ? $arg['vcelstvoId'] : NULL;
				$arguments[$vcelstvoId] = array_keys(array_filter(array_filter($arg, 'is_int', ARRAY_FILTER_USE_KEY)));
			}
			$this->zarizeniManager->pripojZarizeniSeStanovistem($zarizeni, $stanoviste, $arguments);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	/**
	 * Vrátí název zadané skupiny senzorů.
	 *
	 * @param string $umisteni
	 * @return string
	 */
	private function getNazevUmisteni($umisteni)
	{
		$m = NULL;
		if ($umisteni === 'stanoviste') {
			return 'Senzory pro celé stanoviště';
		} elseif (preg_match('#^vcelstvo_([0-9]+)\z#', $umisteni, $m)) {
			return sprintf('Senzory pro %s. úl', $m[1]);
		} else {
			return 'Jiné senzory';
		}
	}

	/**
	 * Načte všechny senzory zadaného typu zařízení a rozdělí je do skupin podle
	 * umístění na stanovišti.
	 *
	 * @param TypZarizeni $typZarizeni
	 * @return array
	 */
	private function fetchSenzory(TypZarizeni $typZarizeni)
	{
		$senzory = [];
		foreach ($typZarizeni->senzory as $s) {
			$senzory[$s->umisteni][] = $s;
		}
		return $senzory;
	}

	/**
	 * Načte názvy všech včelstev na zadaném stanovišti.
	 *
	 * @return array
	 */
	private function fetchVcelstva(Stanoviste $stanoviste)
	{
		$vcelstva = [];
		foreach ($stanoviste->aktualniVcelstva as $u) {
			$vcelstva[$u->vcelstvo->id] = 'Včelstvo č. ' . $u->vcelstvo->poradoveCislo;
		}
		return $vcelstva;
	}

	/**
	 * Vrátí zadané zařízení.
	 *
	 * @param int $id
	 * @return Zarizeni
	 * @throws BadRequestException
	 */
	private function getZarizeni($id)
	{
		$zarizeni = $this->model->zarizeni->getById($id);
		if (!$zarizeni) {
			throw new BadRequestException('', Response::S400_BAD_REQUEST);
		}
		return $zarizeni;
	}

	/**
	 * Vrátí zadané stanoviště.
	 *
	 * @param int $id
	 * @return Stanoviste
	 * @throws BadRequestException
	 */
	private function getStanoviste($id)
	{
		$zarizeni = $this->model->stanoviste->getById($id);
		if (!$zarizeni) {
			throw new BadRequestException('', Response::S400_BAD_REQUEST);
		}
		return $zarizeni;
	}

}
