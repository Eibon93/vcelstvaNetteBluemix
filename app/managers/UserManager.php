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

namespace App\Managers;

use App\Model\MessageFactory;
use App\Model\MessageSender;
use App\Model\Model;
use App\Model\ModelException;
use App\Model\Template;
use App\Model\User;
use App\Model\Verification;
use DateInterval;
use Nette\Mail\Message;
use Nette\Object;
use Nette\Security\Passwords;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\Random;
use Nextras\Dbal\UniqueConstraintViolationException;
/**
 * Provádí operace s uživateli.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class UserManager extends Object
{

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var MessageFactory
	 */
	private $messageFactory;

	/**
	 * @var MessageSender
	 */
	private $messageSender;

	/**
	 * Přiřazení identifikátorů šablon k jednotlivým akcím, které je potřeba potvrzovat e-mailem.
	 *
	 * @var array
	 */
	private static $actionTemplates = [
		Verification::AKCE_REGISTRACE => Template::ID_REGISTRACE,
		Verification::AKCE_ZMENA_EMAILU => Template::ID_ZMENA_EMAILU,
		Verification::AKCE_OBNOVENI_HESLA => Template::ID_OBNOVENI_HESLA,
	];

	/**
	 * @param Model $model
	 * @param MessageFactory $messageFactory
	 * @param MessageSender $messageSender
	 */
	public function __construct(Model $model, MessageFactory $messageFactory, MessageSender $messageSender)
	{
		$this->model = $model;
		$this->messageFactory = $messageFactory;
		$this->messageSender = $messageSender;
	}

	/**
	 * Zaregistruje nového uživatele a odešle mu odkaz na potvrzení registrace.
	 *
	 * Údaje o uživateli musí být zadány v poli $data v následující struktuře:
	 * 'jmeno' => string,
	 * 'prijmeni' => string,
	 * 'telefon' => string,
	 * 'wwwStranky' => string,
	 * 'email' => string,
	 * 'password' => string
	 *
	 * @param array $data
	 * @return User
	 * @throws ModelException
	 */
	public function registerUser(array $data)
	{
		$user = new User();
		$user->admin = FALSE;
		$user->active = FALSE;
		$user->jmeno = $data['jmeno'];
		$user->prijmeni = $data['prijmeni'];
		$user->telefon = $data['telefon'];
		$user->wwwStranky = $data['wwwStranky'];
		$user->email = $data['email'];
		$user->passwordHash = Passwords::hash($data['password']);
		$this->model->persist($user);

		$message = $this->sendVerification($user, Verification::AKCE_REGISTRACE);
		$this->model->flush();
		$this->messageSender->sendMessage($message);

		return $user;
	}

	/**
	 * Zkontroluje platnost ověřovacího kódu a aktivuje uživatele.
	 *
	 * @param Verification $verification
	 * @param string $kod
	 * @throws ModelException
	 */
	public function activateUser(Verification $verification, $kod)
	{
		if (!$verification->verifies(Verification::AKCE_REGISTRACE, $kod)) {
			throw new ModelException('Neplatný ověřovací kód.');
		}

		$verification->invalidatedAt = DateTime::from('now');
		$verification->user->active = TRUE;

		$this->model->persist($verification);
		$this->model->persist($verification->user);
		$this->model->flush();
	}

	/**
	 * Odešle uživateli odkaz pro obnovu zapomenutého hesla.
	 *
	 * @param User $user
	 * @throws ModelException
	 */
	public function verifyPasswordReset(User $user)
	{
		$message = $this->sendVerification($user, Verification::AKCE_OBNOVENI_HESLA);
		$this->model->flush();

		$this->messageSender->sendMessage($message);
	}

	/**
	 * Zkontroluje platnost ověřovacího kódu a nastaví nové heslo uživatele.
	 *
	 * @param Verification $verification
	 * @param string $code
	 * @param string $newPassword
	 * @throws ModelException
	 */
	public function resetPassword(Verification $verification, $code, $newPassword)
	{
		if (!$verification->verifies(Verification::AKCE_OBNOVENI_HESLA, $code)) {
			throw new ModelException('Neplatný ověřovací kód.');
		}

		$verification->invalidatedAt = DateTime::from('now');
		$verification->user->passwordHash = Passwords::hash($newPassword);

		$this->model->persist($verification);
		$this->model->persist($verification->user);
		$this->model->flush();
	}

	/**
	 * Odešle uživateli odkaz pro potvrzení nové e-mailové adresy.
	 *
	 * @param User $user
	 * @param string $newEmail
	 * @throws ModelException
	 */
	public function verifyEmailReset(User $user, $newEmail)
	{
		if ($user->email === $newEmail) {
			throw new ModelException('E-mailová adresa se nezměnila.');
		}

		$message = $this->sendVerification($user, Verification::AKCE_ZMENA_EMAILU, ['email' => $newEmail], $newEmail);
		$this->model->flush();

		$this->messageSender->sendMessage($message);
	}

	/**
	 * Zkontroluje platnost ověřovacího kódu a nastaví nový e-mail uživatele.
	 *
	 * @param Verification $verification
	 * @param string $code
	 * @throws ModelException
	 */
	public function resetEmail(Verification $verification, $code)
	{
		if (!$verification->verifies(Verification::AKCE_ZMENA_EMAILU, $code)) {
			throw new ModelException('Neplatný ověřovací kód.');
		}

		try {
			$verification->invalidatedAt = DateTime::from('now');
			$verification->user->email = $verification->getData('email');

			$this->model->persist($verification);
			$this->model->persist($verification->user);
			$this->model->flush();
		} catch (UniqueConstraintViolationException $ex) {
			throw new ModelException('Tato e-mailová adresa je již zaregistrovaná.');
		}
	}

	/**
	 * Změní e-mailovou adresu uživatele (bez ověření, že je adresa platná).
	 *
	 * @param User $user
	 * @param string $newEmail
	 * @throws ModelException
	 */
	public function changeEmail(User $user, $newEmail)
	{
		if ($user->email === $newEmail) {
			return;
		}

		try {
			$user->email = $newEmail;
			$this->model->persistAndFlush($user);
		} catch (UniqueConstraintViolationException $ex) {
			throw new ModelException('Tato e-mailová adresa je již zaregistrovaná.');
		}
	}

	/**
	 * Změní heslo uživatele.
	 *
	 * @param User $user
	 * @param string $oldPassword
	 * @param string $newPassword
	 * @throws ModelException
	 */
	public function changePassword(User $user, $oldPassword, $newPassword)
	{
		if (!Passwords::verify($oldPassword, $user->passwordHash)) {
			throw new ModelException('Špatně zadané staré heslo.');
		}

		$user->passwordHash = Passwords::hash($newPassword);
		$this->model->persistAndFlush($user);
	}

	/**
	 * Změní profil uživatele (např. jméno).
	 *
	 * Údaje o profilu musí být zadány v poli $data v následující struktuře:
	 * 'givenName' => string,
	 * 'familyName' => string,
	 * Jednotlivá pole jsou nepovinná, změní se jen zadané hodnoty.
	 *
	 * @param User $user
	 * @param array $data
	 * @throws ModelException
	 */
	public function changeProfile(User $user, array $data)
	{
		if (isset($data['jmeno'])) {
			$user->jmeno = $data['jmeno'];
		}
		if (isset($data['prijmeni'])) {
			$user->prijmeni = $data['prijmeni'];
		}
		if (isset($data['telefon'])) {
			$user->telefon = $data['telefon'];
		}
		if (isset($data['wwwStranky'])) {
			$user->wwwStranky = $data['wwwStranky'];
		}		
		$this->model->persistAndFlush($user);
	}

	/**
	 * Vygeneruje e-mailovou zprávu pro potvrzení zadané operace.
	 *
	 * E-mailovou zprávu ještě neodesílá. Kdyby při ukládání do databáze došlo
	 * k chybě, mohla by nastat situace, že už je odeslaná zpráva s kódem, ale
	 * kód není uložen v databázi, takže není platný.
	 *
	 * @param User $user
	 * @param string $action
	 * @param array|NULL $data
	 * @param string|NULL $alternateAddress
	 * @return Message
	 * @throws ModelException
	 */
	private function sendVerification(User $user, $action, array $data = NULL, $alternateAddress = NULL)
	{
		if (!isset(self::$actionTemplates[$action])) {
			throw new ModelException('Neplatná akce.');
		}

		$verification = new Verification();
		$verification->user = $user;
		$verification->action = $action;
		$verification->code = Random::generate(10);
		$verification->data = Json::encode($data);
		$verification->createdAt = DateTime::from('now');
		$verification->validUntil = DateTime::from('now')->add(new DateInterval('P2W'));
		$this->model->persist($verification);

		$template = $this->model->templates->getById(self::$actionTemplates[$action]);
		if (!$template) {
			throw new ModelException('Chybí šablona e-mailu.');
		}

		$values = [
			'user' => $user,
			'verification' => $verification,
			'data' => $data,
		];
		$message = $this->messageFactory->createMessage($user, $template, $values, $alternateAddress);

		return $message;
	}

}
