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

namespace App\Presenters;

use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\ComponentReflection;
use Nette\Http\IRequest as IHttpRequest;
use Nette\Http\IResponse as IHttpResponse;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Nextras\Application\InvalidLinkException;
use Nextras\Application\LinkFactory;
use ReflectionClass;
use ReflectionMethod;

/**
 * Bázový presenter pro implementaci vlastního API.
 *
 * Presenter zpracovává volání pomocí HTTP metod GET, POST, PUT a DELETE.
 * Potomci této třídy mohou zpracovávat požadavky pomocí metod doGet&lt;Action&gt;,
 * doPost&lt;Action&gt;, doPut&lt;Action&gt; a doDelete&lt;Action&gt;. Pokud
 * požadavek obsahuje tělo ve formátu JSON a příslušná metoda má parametr $json,
 * dostane metoda data již dekódovaná (objekty jako stdClass, pole jako array).
 * Metoda může vracet data ve formátu JSON metodou {@link sendJson}.
 *
 * @author Pavel Junek
 */
class ApiPresenter implements IPresenter
{

	use SmartObject;

	const ACTION_KEY = 'action';
	const DEFAULT_ACTION = 'default';

	/** @var string */
	private $action;

	/** @var string */
	private $method;

	/** @var array */
	private $params;

	/** @var Request */
	private $request;

	/** @var IResponse */
	private $response;

	/** @var IHttpRequest */
	private $httpRequest;

	/** @var IHttpResponse */
	private $httpResponse;

	/** @var User */
	private $user;

	/** @var LinkFactory */
	private $linkFactory;

	/** @var array */
	private $methods = [];

	/**
	 * Returns the current request.
	 *
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Returns the action name.
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Returns all parameters.
	 *
	 * @return array
	 */
	public function getParameters()
	{
		return $this->params;
	}

	/**
	 * Returns the given parameter.
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParameter($name, $default = NULL)
	{
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}

	/**
	 * Executes the presenter action.
	 *
	 * @param Request $request
	 * @return IResponse
	 */
	public function run(Request $request)
	{
		try {
			$this->scanMethods();

			$this->request = $request;
			$this->initParameters();

			$this->startup();
			$this->callMethod($this->action, $this->method, $this->params);
			return NULL;
		} catch (AbortException $ex) {
			return $this->response;
		}
	}

	/**
	 * Initializes presenter parameters and action.
	 */
	protected function initParameters()
	{
		$params = $this->request->getParameters();
		$action = isset($params[self::ACTION_KEY]) ? $params[self::ACTION_KEY] : self::DEFAULT_ACTION;
		if (!is_string($action) || !Strings::match($action, '#^[a-zA-Z0-9][a-zA-Z0-9_]*\z#')) {
			$this->error('Invalid action name.');
		}
		if (Strings::match($this->httpRequest->getHeader('Content-Type'), '#^application/json\b#')) {
			try {
				$params['json'] = Json::decode($this->httpRequest->getRawBody(), Json::FORCE_ARRAY);
			} catch (JsonException $ex) {
				$this->error('Malformed JSON content (' . $ex->getMessage() . ')');
			}
		}
		$this->params = $params;
		$this->action = Strings::lower($action);
		$this->method = Strings::lower($this->httpRequest->getMethod());
	}

	/**
	 * Called on startup.
	 */
	protected function startup()
	{
		// Nothing to do
	}

	/**
	 * Scans all available action methods.
	 */
	protected function scanMethods()
	{
		$rc = new ReflectionClass($this);
		foreach ($rc->getMethods() as $rm) {
			if (!$rm->isPublic() || $rm->isAbstract() || $rm->isStatic()) {
				continue;
			}
			$matches = Strings::match($rm->getName(), '#^do(get|post|put|delete)([a-z0-9][a-z0-9_]*)\z#i');
			if (!$matches) {
				continue;
			}
			$this->addMethod(Strings::lower($matches[1]), Strings::lower($matches[2]), $rm);
		}
	}

	/**
	 * Adds action method.
	 *
	 * @param string $method
	 * @param string $action
	 * @param ReflectionMethod $rm
	 */
	protected function addMethod($method, $action, ReflectionMethod $rm)
	{
		if (!isset($this->methods[$action])) {
			$this->methods[$action] = [];
		}
		$this->methods[$action][$method] = $rm;
	}

	/**
	 * Calls action method if exists.
	 *
	 * @param string
	 * @param string
	 * @param array
	 */
	protected function callMethod($action, $method, array $params)
	{
		if (!isset($this->methods[$action])) {
			$this->error('Unknown action.', IHttpResponse::S404_NOT_FOUND);
		}
		if (!isset($this->methods[$action][$method])) {
			$this->error('Method not allowed.', IHttpResponse::S405_METHOD_NOT_ALLOWED);
		}

		$rm = $this->methods[$action][$method];
		if (!$rm->isPublic() || $rm->isAbstract() || $rm->isStatic()) {
			$this->error('Invalid action handler.', IHttpResponse::S500_INTERNAL_SERVER_ERROR);
		}

		$rm->invokeArgs($this, ComponentReflection::combineArgs($rm, $params));
	}

	/**
	 * Sends JSON data to the output.
	 *
	 * @param mixed $data
	 * @throws AbortException
	 */
	public function sendJson($data)
	{
		$this->sendResponse(new JsonResponse($data));
	}

	/**
	 * Sends response and terminates presenter.
	 *
	 * @param IResponse $response
	 * @throws AbortException
	 */
	public function sendResponse(IResponse $response)
	{
		$this->response = $response;
		$this->terminate();
	}

	/**
	 * Correctly terminates presenter.
	 *
	 * @throws AbortException
	 */
	public function terminate()
	{
		throw new AbortException();
	}

	/**
	 * Throws HTTP error.
	 *
	 * @param string
	 * @param int
	 * @throws BadRequestException
	 */
	public function error($message = NULL, $code = IHttpResponse::S400_BAD_REQUEST)
	{
		throw new BadRequestException($message, $code);
	}

	/**
	 * Generates URL to presenter, action or signal.
	 * @param  string   destination in format "[//] [[[module:]presenter:]action | signal! | this] [#fragment]"
	 * @param  array|mixed
	 * @return string
	 * @throws InvalidLinkException
	 */
	public function link($destination, $args = array())
	{
		return $this->linkFactory->link($destination, $args);
	}

	/**
	 * Initializes the instance.
	 *
	 * @param IHttpRequest $httpRequest
	 * @param IHttpResponse $httpResponse
	 * @param User $user
	 * @param LinkFactory $linkFactory
	 */
	public function injectPrimary(IHttpRequest $httpRequest, IHttpResponse $httpResponse, User $user, LinkFactory $linkFactory)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->user = $user;
		$this->linkFactory = $linkFactory;
	}

	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @return IHttpRequest
	 */
	protected function getHttpRequest()
	{
		return $this->httpRequest;
	}

	/**
	 * @return IResponse
	 */
	protected function getHttpResponse()
	{
		return $this->httpResponse;
	}

}
