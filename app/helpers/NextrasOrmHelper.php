<?php

namespace App\Helpers;

use Nette\Forms\Form;
use Nextras\Orm\Entity\Entity;

/**
 * Description of NextrasOrmHelper
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek,   
 */
class NextrasOrmHelper
{

	public static function bindFormToModel($formData, $entity)
	{
		$properties = $entity->toArray();
		foreach ($properties as $key => $value) {
			if (isset($formData[$key])) {
				$entity->$key = $formData[$key];
			}
		}
	}


}
