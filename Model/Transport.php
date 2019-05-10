<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Magento\Framework\Exception\MailException;
use Magento\Framework\Phrase;
use Magento\Framework\Mail\MessageInterface;
use Zend\Mail\Message as ZendMessage;
use Zend\Mail\Transport\Sendmail as ZendTransport;
use Emarsys\Emarsys\Model\SendEmail;

class Transport implements \Magento\Framework\Mail\TransportInterface
{
    /**
     * @var zendTransport
     */
    protected $zendTransport;

    /**
     * @var SendEmail
     */
    protected $sendEmail;

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @param MessageInterface $message
     * @param SendEmail $sendEmail
     * @param null|string|array|\Traversable $parameters
     */
    public function __construct(
        MessageInterface $message,
        SendEmail $sendEmail,
        $parameters = null
    ) {
        $this->zendTransport = new ZendTransport($parameters);
        $this->sendEmail = $sendEmail;
        $this->message = $message;
    }

    /**
     * @throws MailException
     */
    public function sendMessage()
    {
        $errorStatus = $this->sendEmail->sendMail($this->getMessage());

        if ($errorStatus) {
            try {
                $this->zendTransport->send(
                    ZendMessage::fromString($this->message->getRawMessage())
                );
            } catch (\Exception $e) {
                throw new MailException(new Phrase($e->getMessage()), $e);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
}
