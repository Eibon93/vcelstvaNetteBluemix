<?php

/*
 * Copyright (C) 2017 junek
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

namespace App\Controls;

use App\Model\Model;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Utils\Strings;

/**
 * Rozšíří formuláře o ovládací prvky pro zadávání atributů uživatele.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class UserControls extends CustomControls {

    public static $PRAVNICKA_OSOBA = 'p';
    public static $FYZICKA_OSOBA = 'f';

    /**
     * @var Model
     */
    private $model;

    /**
     * @param Model $model
     */
    public function __construct(Model $model) {
	$this->model = $model;
    }

    /**
     * Přidá do formuláře prvek pro zadání křestního jména.
     *
     * @param Container $form
     * @param string $name
     * @return IControl
     */
    public function addUserGivenName(Container $form, $name) {
	return $form->addText($name, 'Jméno')
			->setRequired('Jméno je povinný údaj.')
			->addRule(Form::MAX_LENGTH, NULL, 255);
    }

    /**
     * Přidá do formuláře prvek pro zadání příjmení.
     *
     * @param Container $form
     * @param string $name
     * @return IControl
     */
    public function addUserFamilyName(Container $form, $name) {
	return $form->addText($name, 'Příjmení')
			->setRequired('Příjmení je povinný údaj.')
			->addRule(Form::MAX_LENGTH, NULL, 255);
    }

    /**
     * Přidá do formuláře prvek pro zadání e-mailové adresy.
     *
     * @param Container $form
     * @param string $name
     * @param int|IControl|NULL $userId identifikátor uživatele, jehož e-mailová adresa se bude měnit. Při registraci nového uživatele musí být NULL.
     * @return IControl
     */
    public function addUserEmail(Container $form, $name, $userId = NULL) {
	$control = $form->addBaseEmail($name, 'E-mail')
		->setRequired('Emailová adresa je povinný údaj.');
	if ($userId) {
	    $control->addRule([$this, 'validateCanChangeEmail'], 'Tato e-mailová adresa je již zaregistrovaná.', $userId);
	} else {
	    $control->addRule([$this, 'validateCanRegisterEmail'], 'Tato e-mailová adresa je již zaregistrovaná.');
	}
	return $control;
    }

    /**
     * Přidá do formuláře prvek pro zadání hesla.
     *
     * @param Container $form
     * @param string $name
     * @return IControl
     */
    public function addUserPassword(Container $form, $name) {
	return $form->addPassword($name, 'Heslo')
			->setRequired('Heslo je povinný údaj.')
			->addRule(Form::MIN_LENGTH, NULL, 6);
    }

    /**
     * Přidá do formuláře prvek pro zadání hesla.
     *
     * @param Container $form
     * @param string $name
     * @return IControl
     */
    public function addUserNewPassword(Container $form, $name) {
	return $form->addPassword($name, 'Nové heslo')
			->setRequired('Nové heslo je povinný údaj.')
			->addRule(Form::MIN_LENGTH, NULL, 6);
    }

    /**
     * Přidá do formuláře prvek pro znovuzadání hesla pro kontrolu.
     *
     * @param Container $form
     * @param string $name
     * @param IControl $control ovládací prvek s heslem.
     * @return IControl
     */
    public function addUserTestPassword(Container $form, $name, IControl $control) {
	return $form->addPassword($name, 'Nové heslo (kontrola)')
			->setRequired('Nové heslo pro kontrolu je povinný údaj.')
			->setOmitted()
			->addRule(Form::EQUAL, 'Obě nová hesla musí být stejná.', $control);
    }

    /**
     * Přidá do formuláře prvek pro zadani telefonniho cisla.
     *
     * @param Container $form
     * @param string $name
     * @return IControl
     */
    public function addUserPhone(Container $form, $name) {
	return $form->addText($name, 'Telefonní číslo')
			->setType('tel')
			->setEmptyValue('+420')
			->setRequired('Telefonní číslo je povinný údaj.')
			->addFilter(__CLASS__ . '::filterPhone')
			->addRule(Form::PATTERN, 'Zadejte platné telefonní číslo.', '\\+420[0-9]{9}');
    }

    /**
     * Přidá do formuláře prvek pro zadani telefonniho cisla.
     *
     * @param Container $form
     * @param string $name
     * @return IControl
     */
    public function addUserWwwStranky(Container $form, $name) {
	return $form->addText($name, 'WWW stránky')
			->setRequired(FALSE)
			->addRule(Form::URL)
			->addRule(Form::MAX_LENGTH, NULL, 255);
    }

    /**
     * Validátor ovládacího prvku pro registraci nového e-mailu.
     *
     * @param IControl $control
     * @return bool
     */
    public function validateCanRegisterEmail(IControl $control) {
	$conflictingUser = $this->model->users->getBy([
	    'email' => $control->getValue(),
	]);
	return $conflictingUser === NULL;
    }

    /**
     * Validátor ovládacícho prvku pro změnu e-mailu.
     *
     * @param IControl $control
     * @param IControl|int $userId
     * @return bool
     */
    public function validateCanChangeEmail(IControl $control, $userId) {
	$conflictingUser = $this->model->users->getBy([
	    'email' => $control->getValue(),
	    'id!=' => $userId instanceof IControl ? $userId->getValue() : $userId,
	]);
	return $conflictingUser === NULL;
    }

    /**
     * Upraví telefonní číslo do platného tvaru +420xxxyyyzzz. Pokud vstupní
     * řetězec neobsahuje telefonní číslo, ponechá ho beze změny.
     *
     * @param string $s
     * @return string
     */
    public static function filterPhone($s) {
	if (Strings::match($s, '#^\\s*\\+?(?:\\s*\\d)+\\s*$#')) {
	    $s = Strings::replace($s, '#\\s#', '');

	    if (!Strings::match($s, '#^\\+#')) {
		$s = (Strings::length($s) < 9 ? '+420' : '+') . $s;
	    }
	}
	return $s;
    }

    public function addPravnickaNeboFyzickaOsobaRadioList(Container $form, $name) {
	return $form->addRadioList($name, $label = NULL, [
		    self::$FYZICKA_OSOBA => 'Fyzická osoba',
		    self::$PRAVNICKA_OSOBA => 'Právnická osoba nebo osoba s IČO'
		])->setDefaultValue(self::$FYZICKA_OSOBA);
    }

    public function addStreet(Container $form, $name) {
	return $form->addText($name, 'Ulice a číslo')
			->setRequired('Ulice je povinný údaj.');
    }

    public function addZip(Container $form, $name) {
	return $form->addText($name, 'Směrovací číslo')
			->addRule(Form::PATTERN, 'Směrovací číslo musí obsahovat přesně 5 čísel.', '([0-9]\s*){5}')
			->addFilter(function ($value) {
			    return preg_replace('/\s+/', '', $value);
			})
			->setRequired('Směrovací číslo je povinný údaj');
    }

    public function addObec(Container $form, $name) {

	return $form->addText($name, 'Obec')
			->setRequired('Obec je povinný údaj.');    
    }

    public function addCastObce(Container $form, $name) {

	return $form->addText($name, 'Část obce');    
    }
}
