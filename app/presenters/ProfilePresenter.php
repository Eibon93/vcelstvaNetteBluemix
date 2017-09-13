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

namespace App\Presenters;

use App\Forms\EditVcelarFormFactory;
use App\Forms\EditZemedelecFormFactory;
use App\Forms\EmailChangeFormFactory;
use App\Forms\PasswordChangeFormFactory;
use App\Forms\ProfileChangeFormFactory;
use App\Model\User;
use App\Model\Vcelar;
use App\Model\ZemedelskyPodnik;
use Nette\Application\UI\Form;
use Nette\Http\Response;

/**
 * Správa profilu uživatele.
 *
 * - Zobrazení profilu aktuálního uživatele
 * - Změna profilu aktuálního uživatele
 * - Změna hesla aktuálního uživatele
 * - Změna e-mailu aktuálního uživatele
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class ProfilePresenter extends AuthenticatedPresenter
{

	/**
	 * @var ProfileChangeFormFactory
	 * @inject
	 */
	public $profileFormFactory;

	/**
	 * @var PasswordChangeFormFactory
	 * @inject
	 */
	public $passwordFormFactory;

	/**
	 * @var EmailChangeFormFactory
	 * @inject
	 */
	public $emailFormFactory;

	/**
	 * @var EditVcelarFormFactory
	 * @inject
	 */
	public $vcelarFormFactory;

	/**
	 * @var EditZemedelecFormFactory
	 * @inject
	 */
	public $zemedelecFormFactory;

	/**
	 * Zobrazí profil uživatele.
	 */
	public function renderDefault()
	{
		$profile = $this->fetchProfile();
		if (!$this->getUser()->isAllowed($profile, 'view')) {
			$this->error('Nemáte oprávnění prohlížet profil uživatele.', Response::S403_FORBIDDEN);
		}

		$this->template->profile = $profile;
	}

	/**
	 * Zobrazí formulář pro změnu profilu.
	 */
	public function renderEditProfile()
	{
		$profile = $this->fetchProfile();
		if (!$this->getUser()->isAllowed($profile, 'edit')) {
			$this->error('Nemáte oprávnění prohlížet profil uživatele.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('profileForm');
		$form->setDefaults([
			'jmeno' => $profile->jmeno,
			'prijmeni' => $profile->prijmeni,
			'telefon' => $profile->telefon,
			'wwwStranky' => $profile->wwwStranky
		]);
	}

	/**
	 * Vytvoří formulář pro změnu profilu.
	 *
	 * @return Form
	 */
	protected function createComponentProfileForm()
	{
		$presenter = $this;

		$form = $this->profileFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Změna jména a příjmení byla uložena. Pokud jste změnili jméno nebo přijmení, změna se projeví až po novém přihlášení.');
			$presenter->redirect('default');
		};

		return $form;
	}

	/**
	 * Zobrazí formulář pro změnu hesla.
	 */
	public function renderEditPassword()
	{
		$profile = $this->fetchProfile();
		if (!$this->getUser()->isAllowed($profile, 'edit')) {
			$this->error('Nemáte oprávnění měnit profil uživatele.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('passwordForm');
		$form->setDefaults([
			'id' => $profile->id,
		]);
	}

	/**
	 * Vytvoří formulář pro změnu hesla.
	 *
	 * @return Form
	 */
	protected function createComponentPasswordForm()
	{
		$presenter = $this;

		$form = $this->passwordFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Nové heslo bylo nastaveno.');
			$presenter->redirect('default');
		};

		return $form;
	}

	/**
	 * Zobrazí formulář pro změnu e-mailové adresy.
	 */
	public function renderEditEmail()
	{
		$profile = $this->fetchProfile();
		if (!$this->getUser()->isAllowed($profile, 'edit')) {
			$this->error('Nemáte oprávnění měnit profil uživatele.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('emailForm');
		$form->setDefaults([
			'id' => $profile->id,
			'newEmail' => $profile->email,
		]);
	}

	/**
	 * Vytvoří formulář pro změnu e-mailové adresy.
	 *
	 * @return Form
	 */
	public function createComponentEmailForm()
	{
		$presenter = $this;

		$form = $this->emailFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Na nově zadanou adresu byl odeslán e-mail s pokyny pro dokončení změny adresy. Prosím pokračujte podle těchto pokynů.');
			$presenter->redirect('default');
		};

		return $form;
	}

	/*
	 * - Změna včelaře
	 */

	public function renderEditVcelar()
	{
		$vcelar = $this->fetchVcelar();
		if (!$this->getUser()->isAllowed($vcelar, 'edit')) {
			$this->error('Nemáte oprávnění měnit profil včelaře.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('vcelarForm');
		$form->setDefaults([
			'id' => $vcelar->id,
			'pravnickaFyzicka' => $vcelar->ico ? 'p' : 'f',
			'registracniCislo' => $vcelar->registracniCislo,
			'rodneCislo' => $vcelar->rodneCislo,
			'ico' => $vcelar->ico,
			'nazev' => $vcelar->nazev,
			'ulice' => $vcelar->adresa->ulice,
			'castObce' => $vcelar->adresa->castObce,
			'psc' => $vcelar->adresa->psc,
			'vcelariOd' => $vcelar->vcelariOd,
		]);
	}

	protected function createComponentVcelarForm()
	{
		$presenter = $this;

		$form = $this->vcelarFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Záznam o včelaři úspěšně upraven.');
			$presenter->redirect('default');
		};

		return $form;
	}

	/*
	 * - Změna zemědělského podniku
	 */

	public function renderEditZemedelskyPodnik()
	{
		$zemedelskyPodnik = $this->fetchZemedelskyPodnik();
		if (!$this->getUser()->isAllowed($zemedelskyPodnik, 'edit')) {
			$this->error('Nemáte oprávnění měnit profil zemědělského podniku.', Response::S403_FORBIDDEN);
		}

		$form = $this->getComponent('zemedelskyPodnikForm');
		$form->setDefaults([
			'id' => $zemedelskyPodnik->id,
			'ico' => $zemedelskyPodnik->ico,
			'nazev' => $zemedelskyPodnik->nazev,
			'ulice' => $zemedelskyPodnik->adresa->ulice,
			'castObce' => $zemedelskyPodnik->adresa->castObce,
			'psc' => $zemedelskyPodnik->adresa->psc
		]);
	}

	protected function createComponentZemedelskyPodnikForm()
	{
		$presenter = $this;

		$form = $this->zemedelecFormFactory->create();
		$form->onSuccess[] = function() use ($presenter) {
			$presenter->flashMessage('Záznam o zemědělci úspěšně upraven.');
			$presenter->redirect('default');
		};

		return $form;
	}

	/**
	 * Načte aktuálně přihlášeného uživatele.
	 *
	 * @return User
	 */
	private function fetchProfile()
	{
		$profile = $this->model->users->getById($this->getUser()->getId());
		if (!$profile) {
			$this->error('Uživatel nebyl nalezen.', Response::S404_NOT_FOUND);
		}
		return $profile;
	}

	/**
	 * Načte včelaře příslušejícího k aktuálně přihlášenému uživateli.
	 *
	 * @return Vcelar
	 */
	private function fetchVcelar()
	{
		$profile = $this->fetchProfile();
		$vcelar = $profile->vcelar;
		if (!$vcelar) {
			$this->error('Neplatný požadavek.', Response::S400_BAD_REQUEST);
		}
		return $vcelar;
	}

	/**
	 * Načte zemědělský podnik příslušející k aktuálně přihlášenému uživateli.
	 *
	 * @return ZemedelskyPodnik
	 */
	private function fetchZemedelskyPodnik()
	{
		$profile = $this->fetchProfile();
		$zemedelskyPodnik = $profile->zemedelskyPodnik;
		if (!$zemedelskyPodnik) {
			$this->error('Neplatný požadavek.', Response::S400_BAD_REQUEST);
		}
		return $zemedelskyPodnik;
	}

}
