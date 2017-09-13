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

namespace App\Api;

use Exception;
use InvalidArgumentException;

/**
 * Výjimka vyhozená, pokud přijatá data nejsou platná.
 */
class InvalidInputException extends InvalidArgumentException
{

}

/**
 * Výjimka vyhozená, pokud databáze neobsahuje platná data (např. naměřená
 * veličina neodpovídá příslušnému typu zařízení apod.)
 */
class InvalidStateException extends Exception
{

}

/**
 * Výjimka vyhozená, pokud zařízení odeslalo neplatný token.
 */
class InvalidTokenException extends Exception
{

}
