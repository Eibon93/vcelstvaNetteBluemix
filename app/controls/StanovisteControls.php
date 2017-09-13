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

namespace App\Controls;

use Nette\Forms\Container;

/**
 * Rozšíří formuláře o ovládací prvky pro zadávání stanovist.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,
 */
class StanovisteControls extends CustomControls
{

	public function addNameStanoviste(Container $form, $name)
	{
		return $form->addText($name, 'Název stanoviště')
						->setRequired('Název stanovitě je povinný údaj.');
	}

	public function addRegNumberStanoviste(Container $form, $name)
	{
		return $form->addText($name, 'Registrační číslo')
						->setRequired("Registrační číslo musí být zadáno.")
						->setType("number");
	}

	public function addBegin(Container $form, $name)
	{
		return $form->addDatePicker($name, "Datum vzniku stanoviště")
						->setRequired('Datum zahájení stanoviště je povinný údaj (může být přibližné).');
	}

	public function addPlanningEnd(Container $form, $name)
	{
		return $form->addDatePicker($name, "Předpokládané ukončení");
	}

}
