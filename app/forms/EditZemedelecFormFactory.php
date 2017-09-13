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

namespace App\Forms;

use App\Managers\ZemedelecManager;
use App\Model\Model;
use App\Model\ModelException;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Továrna na registrační formulář.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class EditZemedelecFormFactory
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
	 * @var ZemedelecManager
	 */
	private $zemedelecManager;

	public function __construct(User $user, Model $model, ZemedelecManager $zemedelecManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->zemedelecManager = $zemedelecManager;
	}

	/**
	 * Vytvoří formulář.
	 *
	 * @return Form
	 */
	public function create()
	{
		$form = new Form();
		$form->addProtection();
		$form->addHidden('id');

		$form->addGroup('Organizace provádějící postřiky');
		$form->addICO('ico')
				->setRequired('IČO je povinný údaj');
		$form->addNazevPodniku('nazev')
				->setRequired('Název podniku je povinný údaj');

		$form->addGroup('Sídlo organizace');
		$form->addStreet('ulice');
		$form->addObec('castObce');
		$form->addZip('psc');

		$form->addGroup();
		$form->addSubmit('send', 'Uložit Profil');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	/**
	 * Přijme data z formuláře a změní profil zemědělského podniku.
	 *
	 * @param Form $form
	 * @param array $values
	 */
	public function onFormSubmitted(Form $form, array $values)
	{
		try {
			$zemedelskyPodnik = $this->model->zemedelskePodniky->getById($values['id']);
			if (!$zemedelskyPodnik) {
				throw new BadRequestException('Zemědělský podnik nebyl nalezen.', Response::S400_BAD_REQUEST);
			}
			if (!$this->user->isAllowed($zemedelskyPodnik, 'edit')) {
				throw new ForbiddenRequestException('Nemáte oprávnění měnit profil zemědělského podniku.');
			}
			$this->zemedelecManager->updateZemedelec($zemedelskyPodnik, $values);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
