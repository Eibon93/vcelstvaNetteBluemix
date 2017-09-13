<?php

/*
 * Copyright (C) 2017
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

use App\Managers\PostrikManager;
use App\Model\Model;
use App\Model\ModelException;
use App\Security\ResourceNames;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Http\Response;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nextras\Forms\Rendering\Bs3FormRenderer;

/**
 * Description of AddPostrikFormFactory
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class PostrikFormFactory
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
	 * @var PostrikManager
	 */
	private $postrikManager;

	public function __construct(User $user, Model $model, PostrikManager $postrikManager)
	{
		$this->user = $user;
		$this->model = $model;
		$this->postrikManager = $postrikManager;
	}

	public function create()
	{
		$form = new Form();
		$form->addProtection();

		$form->addHidden('id');
		$form->addHidden('lat')
				->setRequired()
				->setHtmlId('lat');
		$form->addHidden('lng')
				->setRequired()
				->setHtmlId('lng');
		$form->addHidden('katastralniUzemiId')
				->setRequired()
				->setHtmlId('katastralniUzemiId');
		$form->addHidden('parcelaId')
				->setRequired()
				->setHtmlId('parcelaId');
		$form->addHidden('pudniBlokId')
				->setHtmlId('pudniBlokId');

		$form->addDatumPostriku('datum');
		$form->addPlodina('plodina');
		$form->addNebezpecny('nebezpecny');
		$form->addDalsiMoznosti('moznosti');
		$form->addCheckbox('uverejnitTelefon', 'Uveřejnit můj telefon');

		$form->addText('souradnice', 'Souřadnice')
				->setHtmlId('souradnice')
				->setDisabled();
		$form->addText('katastralniUzemi', 'Katastrální území')
				->setHtmlId('katastralniUzemi')
				->setDisabled();
		$form->addText('parcela', 'Parcela')
				->setHtmlId('parcela')
				->setDisabled();
		$form->addText('pudniBlok', 'Půdní blok')
				->setHtmlId('pudniBlok')
				->setDisabled();

		$form->addSubmit('send', 'Uložit a uveřejnit postřik v mapě');

		$form->onSuccess[] = [$this, 'onFormSubmitted'];

		$form->setRenderer(new Bs3FormRenderer());

		return $form;
	}

	public function onFormSubmitted(Form $form, ArrayHash $values)
	{
		try {
			$data = [
				'lat' => $values->lat,
				'lng' => $values->lng,
				'katastralniUzemiId' => $values->katastralniUzemiId,
				'parcelaId' => $values->parcelaId,
				'pudniBlokId' => $values->pudniBlokId,
				'datum' => $values->datum,
				'plodina' => $values->plodina,
				'nebezpecny' => $values->nebezpecny,
				'kvetouci' => in_array('kvetouci', $values->moznosti),
				'mimoLetovouAktivitu' => in_array('mimoLetovouAktivitu', $values->moznosti),
				'uverejnitTelefon' => $values->uverejnitTelefon,
			];

			if ($values->id) {
				$this->postrikManager->changePostrik($this->getPostrik($values->id), $data);
			} else {
				$this->postrikManager->createPostrik($this->getZemedelskyPodnik(), $data);
			}
		} catch (ModelException $ex) {
			$form->addError($ex->getMessage());
		}
	}

	private function getPostrik($id)
	{
		$postrik = $this->model->postriky->getById($id);
		if (!$postrik) {
			throw new BadRequestException('Postřik nebyl nalezen.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed($postrik, 'edit')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $postrik;
	}

	private function getZemedelskyPodnik()
	{
		$user = $this->model->users->getById($this->user->getId());
		if (!$user) {
			throw new BadRequestException('Uživatel nebyl nalezen.', Response::S400_BAD_REQUEST);
		}
		$zemedelskyPodnik = $user->zemedelskyPodnik;
		if (!$zemedelskyPodnik) {
			throw new BadRequestException('Zemědělský podnik nebyl nalezen.', Response::S400_BAD_REQUEST);
		}
		if (!$this->user->isAllowed(ResourceNames::POSTRIK, 'add')) {
			throw new ForbiddenRequestException('Nemáte oprávnění k požadované operaci.');
		}
		return $zemedelskyPodnik;
	}

}
