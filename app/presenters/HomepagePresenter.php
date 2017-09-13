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

use Nette\Utils\DateTime;

/**
 * - Zobrazení úvodní stránky
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{
		if ($this->getUser()->isLoggedIn()) {
			$uzivatel = $this->model->users->getById($this->getUser()->getId());
			$vcelar = $uzivatel->vcelar;
		}

		$this->template->prodejnyMedu = $this->model->prodejnyMedu->findBy([
			'smazana' => FALSE,
		]);

		$this->template->postriky = $this->model->postriky->findBy([
			'smazan' => FALSE,
			'datum>=' => DateTime::from('now'),
		]);

		$referencniStanoviste = $this->model->stanoviste->findWithReferencniVcelstva();

		if (isset($vcelar)) {
			$vlastniStanoviste = $vcelar->stanoviste;
		}

		$stanoviste = [];
		foreach ($referencniStanoviste as $s) {
			$stanoviste[$s->id] = $s;
		}
		if (isset($vlastniStanoviste)) {
			foreach ($vlastniStanoviste as $s) {
				$stanoviste[$s->id] = $s;
			}
		}

		$this->template->stanoviste = $stanoviste;
	}

}
