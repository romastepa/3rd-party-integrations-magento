<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */
namespace Emarsys\Emarsys\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Emarsys\Emarsys\Model\Logs;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ControllerActionPredispatchObserver
 * @package Emarsys\Emarsys\Observer
 */
class ControllerActionPredispatchObserver implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Emarsys\Emarsys\Model\Logs
     */
    protected $emarsysLogs;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ControllerActionPredispatchObserver constructor.
     * @param Session $customerSession
     * @param Logs $emarsysLogs
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Session $customerSession,
        Logs $emarsysLogs,
        StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->emarsysLogs = $emarsysLogs;
        $this->storeManager = $storeManager;
    }

    /**
     * Handle controller_action_predispatch event
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $controllerAction = $observer->getEvent()->getControllerAction();
        $request = $controllerAction->getRequest();

        try {
            if ($request->getParams() && $request->getParam('email')) {
                $this->customerSession->setWebExtendCustomerEmail($request->getParam('email'));
            }

            if ($request->getPost() && $request->getPost('email')) {
                $this->customerSession->setWebExtendCustomerEmail($request->getPost('email'));
            }
        } catch (\Exception $e) {
            $this->emarsysLogs->addErrorLog(
                $e->getMessage(),
                $this->storeManager->getStore()->getId(),
                'ControllerActionPredispatchObserver'
            );
        }
    }
}

