<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Controller\Adminhtml\Support;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Emarsys\Emarsys\Helper\Data;
use Emarsys\Emarsys\Helper\Email;

/**
 * Class Save
 * @package Emarsys\Emarsys\Controller\Adminhtml\Support
 */
class Save extends Action
{
    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var
     */
    protected $data;

    /**
     * @var
     */
    protected $email;

    /**
     * Save constructor.
     * @param Session $authSession
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     * @param Email $email
     * @param Context $context
     */
    public function __construct(
        Session $authSession,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        Data $data,
        Email $email,
        Context $context
    ) {
        parent::__construct($context);
        $this->authSession = $authSession;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->helper =$data;
        $this->emailHelper = $email;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $req = $this->helper->getRequirementsInfo();

        if ($this->getRequest()->getParams()) {
            $data = $this->getRequest()->getParams();
            try {
                $typeArray = explode('||', $data['type']);
                $type = trim($typeArray[0]);    // $typeArray[0] is the type
                $name  = trim($data['firstname']);
                $email = trim($typeArray[1]);   // $typeArray[1] are email recievers
                $subject = trim($data['subject']);
                $priority = trim($data['priority']);
                $message = trim($data['message']);
                $store_name = $this->storeManager->getStore()->getName();
                $base_url = $this->helper->getBaseUrl();

                //systemrequirement details
                $phpvalue = $req['php_version']['current']['value'];
                $memoryvalue = $req['memory_limit']['current']['value'];
                $magentovalue = $req['magento_version']['current']['value'];
                $curlvalue = $req['curl_enabled']['current']['value'];
                $soapvalue = $req['soap_enabled']['current']['value'];

                // it depends on the template variables
                $emailTemplateVariables = [];
                $emailTemplateVariables['type'] = $type;
                $emailTemplateVariables['name'] = $name;
                $emailTemplateVariables['email'] = $email;
                $emailTemplateVariables['subject'] = $type. ' - ' . $subject;
                $emailTemplateVariables['priority'] = $priority;
                $emailTemplateVariables['message'] = $message;
                $emailTemplateVariables['store_name'] = $store_name;
                $emailTemplateVariables['domain'] = $base_url;
                $emailTemplateVariables['phpvalue'] = $phpvalue;
                $emailTemplateVariables['memoryvalue'] = $memoryvalue;
                $emailTemplateVariables['magentovalue'] = $magentovalue;
                $emailTemplateVariables['curlvalue'] = $curlvalue;
                $emailTemplateVariables['soapvalue'] = $soapvalue;

                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );

                $user = $this->authSession->getUser();
                $senderInfo = [
                    'email' => $user->getEmail(),
                    'name' => $user->getUsername()
                ];

                $storeId = $this->storeManager->getStore()->getId();

                $emailRecievers = explode(',', $typeArray[1]);
                foreach ($emailRecievers as $emailReciever) {
                    $recieverInfo = [
                        'email' => $emailReciever,
                        'name' => $name
                    ];
                    $this->emailHelper->sendEmail(
                        $storeId,
                        $emailTemplateVariables,
                        $senderInfo,
                        $recieverInfo
                    );
                }
                $data = $inputFilter->getUnescaped();
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
                }
                $this->messageManager->addSuccessMessage(__('Request send succesfully'));
                $this->_redirect('emarsys_emarsys/support/index');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__($e->getMessage()));
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('emarsys_emarsys/support/index');
                return;
            }
        }
        $this->_redirect('emarsys_emarsys/support/index');
    }
}
