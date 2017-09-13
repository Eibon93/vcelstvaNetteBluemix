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

namespace App\Security;

/**
 * Specifikuje názvy rolí uživatelů.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
interface RoleNames
{

	const GUEST = 'guest';
	const AUTHENTICATED = 'authenticated';
	const ADMIN = 'admin';
	const USER = 'user';
	const USER_ID = 'user_id';
	const VCELAR = 'vcelar';
	const VCELAR_ID = 'vcelar_id';
	const VCELAR_ADMIN = 'vcelar_admin';
	const ZEMEDELEC = 'zemedelec';
	const ZEMEDELEC_ID = 'zemedelec_id';
	const ZEMEDELEC_ADMIN = 'zemedelec_admin';

}
