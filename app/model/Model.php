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

namespace App\Model;

use Nextras\Orm\Model\Model as BaseModel;

/**
 * Datový model aplikace.
 *
 * @property-read AdresaRepository $adresy
 * @property-read DruhMeduRepository $druhyMedu
 * @property-read KatastralniUzemiRepository $katastralniUzemi
 * @property-read KontrolaRepository $kontroly
 * @property-read KrajRepository $kraje
 * @property-read MedRepository $medy
 * @property-read MereniRepository $mereni
 * @property-read MessageRepository $messages
 * @property-read ObecRepository $obce
 * @property-read OkresRepository $okresy
 * @property-read ParcelaRepository $parcely
 * @property-read PostrikRepository $postriky
 * @property-read PripojeniSenzoruRepository $pripojeniSenzoru
 * @property-read ProdejnaMeduRepository $prodejnyMedu
 * @property-read PudniBlokRepository $pudniBloky
 * @property-read SenzorRepository $senzory
 * @property-read StanovisteRepository $stanoviste
 * @property-read TemplateRepository $templates
 * @property-read TypZarizeniRepository $typyZarizeni
 * @property-read UmisteniVcelstvaRepository $umisteni
 * @property-read UserRepository $users
 * @property-read VcelarRepository $vcelari
 * @property-read VcelstvoRepository $vcelstva
 * @property-read VelicinaRepository $veliciny
 * @property-read VerificationRepository $verifications
 * @property-read ZarizeniRepository $zarizeni
 * @property-read ZemedelskyPodnikRepository $zemedelskePodniky
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class Model extends BaseModel
{

}
