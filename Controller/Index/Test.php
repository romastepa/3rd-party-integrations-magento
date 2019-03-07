<?php

namespace Emarsys\Emarsys\Controller\Index;

use Magento\Framework\App\Action\Context;

class Test extends \Magento\Framework\App\Action\Action
{
    /**
     * Test constructor.
     * @param Context $context
     * @param \Emarsys\Emarsys\Helper\Event $eventHelper
     */
    public function __construct(
        Context $context,
        \Emarsys\Emarsys\Helper\Event $eventHelper
    ) {
        parent::__construct($context);
        $this->eventHelper = $eventHelper;
    }

    /**
     * Test Action
     */
    public function execute()
    {
        $emarsysApiIds = [];
        $eventSchema = $this->eventHelper->getEventSchema();
        foreach ($eventSchema['data'] as $_eventSchema) {
            $emarsysApiIds[] = $_eventSchema['id'];
        }
        $emarsysLocalIds = $this->eventHelper->getLocalEmarsysEvents();
        $result = array_diff($emarsysApiIds, $emarsysLocalIds);
        if (!empty($result)) {
            $this->eventHelper->saveEmarsysEventSchemaNotification();
        }
    }
}
