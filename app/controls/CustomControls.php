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

use InvalidArgumentException;
use Nette\Forms\Container;
use Nette\Reflection\ClassType;
use Nette\SmartObject;
use Nette\Utils\Strings;

/**
 * Společný předek tříd, které rozšiřují formuláře o vlastní ovládací prvky.
 *
 * Obsahuje jedinou metodu <code>register()</code>, která rozšíří třídu
 * <code>Nette\Forms\Container</code> o vlastní metody definované potomkem
 * této třídy.
 *
 * Automaticky registruje všechny metody, které jsou veřejné, nejsou statické
 * a jejichž název začíná na <code>add</code>. Ostatní metody ignoruje. Rozšiřující
 * metody musí mít první parametr typu <code>Nette\Forms\Container</code>.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
abstract class CustomControls
{

	use SmartObject;

	/**
	 * Zaregistruje metody pro vytváření ovládacích prvků.
	 *
	 * @throws InvalidArgumentException pokud první parametr některé rozšiřující metody není typu <code>Nette\Forms\Container</code>.
	 */
	public final function register()
	{
		$containerClassName = ClassType::from('Nette\\Forms\\Container')->getName();

		$class = ClassType::from($this);
		foreach ($class->getMethods() as $method) {
			if (!$method->isPublic() || $method->isAbstract() || $method->isStatic()) {
				continue;
			}
			$name = $method->getName();
			if (!Strings::match($name, '#^add#')) {
				continue;
			}
			$parameters = $method->getParameters();
			$thisClassName = $parameters[0]->getClassName();
			if ($thisClassName !== $containerClassName) {
				throw new InvalidArgumentException(sprintf('First argument of %s.%s() must be of type %s, is %s', $class->getName(), $method->getName(), $containerClassName, $thisClassName));
			}
			Container::extensionMethod($name, [$this, $name]);
		}
	}

}
