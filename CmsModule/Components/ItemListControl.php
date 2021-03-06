<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace CmsModule\Components;

use CmsModule\Content\Control;
use CmsModule\Content\Entities\RouteEntity;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class ItemListControl extends Control
{

	public function renderDefault($route = NULL)
	{
		$this->template->routes = (isset($route[0]) && (is_array($route[0]) || $route[0] instanceof \Traversable)) ? $route[0] : $route;
	}

}
