<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Model;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;

/**
 * Class Transport
 * @package Emarsys\Emarsys\Model
 */
class Transport extends \Zend_Mail_Transport_Sendmail implements TransportInterface
{
    /**
     * @var MessageInterface|\Zend_Mail
     */
    protected $_message;

    /**
     * @var SendEmail
     */
    protected $sendEmail;

    /**
     * Transport constructor.
     * @param MessageInterface $message
     * @param SendEmail $sendEmail
     * @param null $parameters
     */
    public function __construct(
        MessageInterface $message,
        SendEmail $sendEmail,
        $parameters = null
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        parent::__construct($parameters);
        $this->_message = $message;
        $this->sendEmail = $sendEmail;
    }

    /**
     * @throws \Zend_Mail_Transport_Exception
     */
    public function sendMessage()
    {
        $mailSendingStatus = $this->sendEmail->sendMail($this->_message);

        if ($mailSendingStatus) {
            parent::send($this->_message);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->_message;
    }
}
