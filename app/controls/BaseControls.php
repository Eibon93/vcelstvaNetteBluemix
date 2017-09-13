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

namespace App\Controls;

use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Utils\Strings;
use App\Forms\CustomFormRules;

/**
 * Rozšíří formuláře o ovládací prvky pro základní datové typy.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class BaseControls extends CustomControls
{

	/**
	 * Přidá do formuláře prvek pro zadání kódu katastrálního území.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addKatastralniUzemiKod(Container $form, $name)
	{
		return $form->addText($name, 'Kód kat. území')
						->setRequired("Kód katastrálního území je nutné zadat.");
	}

	/**
	 * Přidá do formuláře prvek pro zadání čísla parcely.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addParcelniCislo(Container $form, $name)
	{
		return $form->addText($name, 'Parcelní číslo')
						->setRequired("Parcelní číslo je nutné zadat.");
		;
	}

	/**
	 * Přidá do formuláře prvek pro zadání půdního bloku.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addPudniBlok(Container $form, $name)
	{
		return $form->addText($name, 'Půdní Blok')
						->setRequired("Půdní blok musí být zadán.");
	}

	/**
	 * Přidá do formuláře prvek pro zadání koordinátu polohy v mapě.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addLatitude(Container $form, $name)
	{
		return $form->addText($name, 'Latitude')
						->setRequired("V mapě není vybrána lokalita.");
	}

	/**
	 * Přidá do formuláře prvek pro zadání koordinátu polohy v mapě.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addLongitude(Container $form, $name)
	{
		return $form->addText($name, 'Longitude')
						->setRequired("V mapě není vybrána lokalita.");
	}

	/**
	 * Přidá do formuláře prvek pro zadání e-mailu.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addBaseEmail(Container $form, $name, $label)
	{
		return $form->addText($name, $label)
						->setType('email')
						->setEmptyValue('@')
						->setRequired(FALSE)
						->addRule(Form::EMAIL)
						->addRule(Form::MAX_LENGTH, NULL, 255);
	}

	/**
	 * Přidá do formuláře prvek pro zadání telefonního čísla.
	 *
	 * @param Container $form
	 * @param string $name
	 * @param string $label
	 * @return IControl
	 */
	public function addBasePhone(Container $form, $name, $label)
	{
		return $form->addText($name, $label)
						->setType('tel')
						->setEmptyValue('+420')
						->setRequired(FALSE)
						->addFilter(__CLASS__ . '::filterPhone')
						->addRule(Form::PATTERN, 'Zadejte platné telefonní číslo.', '\\+420[0-9]{9}');
	}

	/**
	 * Upraví telefonní číslo do platného tvaru +420xxxyyyzzz. Pokud vstupní
	 * řetězec neobsahuje telefonní číslo, ponechá ho beze změny.
	 *
	 * @param string $s
	 * @return string
	 */
	public static function filterPhone($s)
	{
		if (Strings::match($s, '#^\\s*\\+?(?:\\s*\\d)+\\s*$')) {
			$s = Strings::replace($s, '\\s', '');
			if (!Strings::match($s, '#^\\+#')) {
				$s = (Strings::length($s) < 9 ? '+420' : '+') . $s;
			}
		}
		return $s;
	}

	public function addStreet(Container $form, $name)
	{
		return $form->addText($name, 'Ulice')
						->setRequired();
	}

	public function addHouseNumber(Container $form, $name)
	{
		return $form->addText($name, 'Číslo popisné')
						->setRequired();
	}

	public function addCity(Container $form, $name)
	{
		return $form->addText($name, 'Město')
						->setRequired();
	}

	/**
	 *
	 * @param Container $form
	 * @param type $name
	 * @return IControl
	 */
	public function addNazevPodniku(Container $form, $name)
	{
		return $form->addText($name, 'Název podniku')
						->setRequired(FALSE)
						->addRule(Form::MAX_LENGTH, NULL, 255);
	}

	public function addICO(Container $form, $name)
	{
		return $form->addText($name, 'IČO')
						->setRequired(FALSE)
						->addRule(CustomFormRules::ICO, 'Zadejte prosím validní IČO.');
	}

}
