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
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;

/**
 * Rozšíření třídy Nette\Forms\Container o továrničky na vlastní ovládací prvky.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class CustomControlsExtension extends CompilerExtension
{

	/**
	 * Přidá do inicializace aplikace volání metody registerControls().
	 *
	 * @param ClassType $class
	 */
	public function afterCompile(ClassType $class)
	{
		$init = $class->getMethods()['initialize'];
		$init->addBody(__CLASS__ . '::registerControls($this->getService(\'orm.model\'));');
	}

	/**
	 * Rozšíří třídu Nette\Forms\Container o továrničky na vlastní ovládací prvky.
	 */
	public static function registerControls(Model $model)
	{
		(new BaseControls())->register();
		(new UserControls($model))->register();
		(new VcelarControls($model))->register();
		(new VcelstvaControls())->register();
		(new ZemedelecControls($model))->register();
                (new StanovisteControls($model))->register();
                (new PostrikControls($model))->register();
	}

}
