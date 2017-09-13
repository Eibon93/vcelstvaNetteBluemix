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
use Nette\Forms\Form;

/**
 * Description of PostrikControls
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class PostrikControls extends CustomControls
{

	public function addDatumPostriku(Container $form, $name)
	{
		return $form->addDateTimePicker($name, "Datum postřiku", 16, 16)
						->setRequired("Postřik musí být zadán s datem a hodinou zahájení.");
	}

	public function addNebezpecny(Container $form, $name)
	{
		return $form->addRadioList($name, 'Vliv na včely:')
						->setItems([
							1 => 'Bez vlivu',
							2 => 'Nebezpečný',
							3 => 'Zvlášť nebezpečný',
						])
						->setRequired("Vliv na včely musí být zadaný.");
	}

	public function addDalsiMoznosti(Container $form, $name)
	{
		return $form->addCheckboxList($name, 'Další informace o postřiku:', [
					'kvetouci' => 'Kvetoucí plodina (včely ji mohou vyhledávat)',
					'mimoLetovouAktivitu' => 'Postřik bude probíhat mimo letovou aktivitu včel',
		]);
	}

	public function addPlodina(Container $form, $name)
	{
		return $form->addText($name, 'Plodina')
						->setRequired("Plodina musí být zadána.")
						->addRule(Form::MAX_LENGTH, NULL, 255);
	}

}
