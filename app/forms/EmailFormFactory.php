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

use App\Managers\OhrozeniManager;
use App\Model\Model;
use App\Model\ModelException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Description of EmailFormFactory
 *
 * @author Pavel Junek
 */
class EmailFormFactory
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
	 * @var OhrozeniManager
	 */
	private $ohrozeniManager;

	public function __construct(User $user, Model $model, OhrozeniManager $ohrozeniManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->ohrozeniManager = $ohrozeniManager;
	}

	public function create()
	{
		$form = new Form();

		$form->addProtection();

		$form->addTextArea('text', 'Text e-mailu')
				->setRequired('Text e-mailu musí být před odesláním vyplněn.');
		$form->addSubmit('send', 'Odeslat e-mail včelařům');

		$form->onSuccess[] = [$this, 'onSubmit'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	public function onSubmit(Form $form, ArrayHash $values)
	{
		try {
			$uzivatel = $this->model->users->getById($this->user->getId());
			$this->ohrozeniManager->odesliEmailOhrozenymVcelarum($uzivatel->zemedelskyPodnik, $values->text);
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

}
