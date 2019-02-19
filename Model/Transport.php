<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\Model\SendEmail as EmarsysSendEmail;
use Emarsys\Emarsys\Model\Message as EmarsysMessage;
use Zend\Mail\Transport\Sendmail;

/**
 * Class Transport
 * @package Emarsys\Emarsys\Model
 */
class Transport extends \Magento\Framework\Mail\Transport
{

    /**
     * @var Sendmail
     */
    protected $zendTransport;

    /**
     * @var EmarsysMessage
     */
    protected $message;

    /**
     * @var
     */
    protected $emarsysSendEmail;

    /**
     * @param EmarsysMessage $message
     * @param EmarsysSendEmail $emarsysSendEmail
     * @param null|string|array|\Traversable $parameters
     */
    public function __construct(
        EmarsysMessage $message,
        EmarsysSendEmail $emarsysSendEmail,
        $parameters = null
    ) {
        $this->zendTransport = new Sendmail($parameters);
        $this->emarsysSendEmail = $emarsysSendEmail;
        $this->message = $message;
    }

    /**
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        $mailSendingStatus = $this->emarsysSendEmail->sendMail($this->message);

        if ($mailSendingStatus) {
            parent::sendMessage();
        }
    }
}
