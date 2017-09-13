<?php

/*
 * Copyright (C) 2015 Pavel Junek
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

use Latte\Engine;
use Latte\Loaders\StringLoader;
use Nette;
use Nette\Application\Application;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Http\IRequest;
use Nette\Utils\DateTime;

/**
 * Vytváří e-mailové zprávy pomocí šablon.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class MessageFactory extends Nette\Object
{

	/**
	 * @var Model
	 */
	private $model;

	/**
	 *  @var ILatteFactory
	 */
	private $latteFactory;

	/**
	 * @var IRequest
	 */
	private $httpRequest;

	/**
	 * @var Application
	 */
	private $application;

	/**
	 * @var Engine
	 */
	private $latte;

	/**
	 * @param Model $model
	 * @param ILatteFactory $latteFactory
	 * @param IRequest $httpRequest
	 * @param Application $application
	 */
	public function __construct(Model $model, ILatteFactory $latteFactory, IRequest $httpRequest, Application $application)
	{
		$this->model = $model;
		$this->latteFactory = $latteFactory;
		$this->httpRequest = $httpRequest;
		$this->application = $application;
	}

	/**
	 * Vytvoří zprávu ze zadané šablony a parametrů.
	 *
	 * @param User $recipient
	 * @param Template $template
	 * @param array $values
	 * @param string|NULL $alternateAddress
	 * @return Message
	 */
	public function createMessage(User $recipient, Template $template, array $values, $alternateAddress = NULL)
	{
		$message = new Message();
		$message->recipient = $recipient;
		$message->alternateAddress = $alternateAddress;
		$message->subject = $template->subject;
		$message->body = $this->createBody($template->body, $values);
		$message->createdAt = DateTime::from('now');
		$this->model->persist($message);

		return $message;
	}

	/**
	 * Vytvoří obsah zprávy.
	 *
	 * @param string $bodyTemplate
	 * @param array $values
	 * @return string
	 */
	private function createBody($bodyTemplate, array $values)
	{
		$presenter = $this->application->getPresenter();
		$this->getLatte()->addProvider('uiControl', $presenter);
		$this->getLatte()->addProvider('uiPresenter', $presenter);

		//$values['_presenter'] = $values['_control'] = $presenter;
		$values['hostUrl'] = $this->httpRequest->getUrl()->getHostUrl();
		$values['remoteAddress'] = $this->httpRequest->getRemoteAddress();

		return $this->getLatte()->renderToString($bodyTemplate, $values);
	}

	/**
	 * Inicializuje Latte.
	 *
	 * @return Engine
	 */
	private function getLatte()
	{
		if (!$this->latte) {
			$this->latte = $this->latteFactory->create();
			$this->latte->setLoader(new StringLoader());
			UIMacros::install($this->latte->getCompiler());
			foreach (array('normalize', 'toAscii', 'webalize', 'padLeft', 'padRight', 'reverse') as $name) {
				$this->latte->addFilter($name, 'Nette\Utils\Strings::' . $name);
			}
		}
		return $this->latte;
	}

}
