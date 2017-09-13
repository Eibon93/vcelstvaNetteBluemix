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

use Nette;
use Nette\Mail\IMailer;
use Nette\Mail\Message as MailMessage;
use Nette\Utils\DateTime;

/**
 * Odesílá e-mailové zprávy.
 *
 * @author Jan Bartoska, Vit Bares, Jakub Simunek, Pavel Junek
 */
class MessageSender extends Nette\Object
{

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * @var IMailer
	 */
	private $mailer;

	/**
	 *  @var string
	 */
	private $from;

	/**
	 * @var bool
	 */
	private $debug;

	/**
	 * @param Model $model
	 * @param IMailer $mailer
	 * @param string $from
	 * @param boolean $debug
	 */
	public function __construct(Model $model, IMailer $mailer, $from, $debug)
	{
		$this->mailer = $mailer;
		$this->model = $model;
		$this->from = $from;
		$this->debug = $debug;
	}

	/**
	 * Odešle všechny neodeslané zprávy.
	 */
	public function sendAll()
	{
		foreach ($this->model->messages->findUnsent() as $message) {
			$this->sendMessage($message);
		}
	}

	/**
	 * Odešle zadanou zprávu.
	 *
	 * @param Message $message
	 */
	public function sendMessage(Message $message)
	{
		$recipient = $message->recipient;

		$mailMessage = new MailMessage();
		$mailMessage->setFrom($this->from);
		$mailMessage->addTo($message->alternateAddress ?: $recipient->email, sprintf('%s %s', $recipient->jmeno, $recipient->prijmeni));
		$mailMessage->setSubject($message->subject);
		$mailMessage->setHtmlBody($message->body);

		if (!$this->debug) {
			$this->mailer->send($mailMessage);
		}

		$message->sentAt = DateTime::from('now');
		$this->model->persistAndFlush($message);
	}

}
