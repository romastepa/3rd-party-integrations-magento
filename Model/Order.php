<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2018 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Model;

use Emarsys\Emarsys\{
    Helper\Data as EmarsysHelper,
    Model\ResourceModel\Order as OrderResourceModel,
    Model\ResourceModel\OrderExport\CollectionFactory as EmarsysOrderExportFactory,
    Model\ResourceModel\CreditmemoExport\CollectionFactory as EmarsysCreditmemoExportFactory
};
use Magento\{
    Framework\Model\AbstractModel,
    Framework\Model\Context,
    Framework\Registry,
    Framework\Message\ManagerInterface as MessageManagerInterface,
    Framework\Model\ResourceModel\AbstractResource,
    Framework\Data\Collection\AbstractDb,
    Framework\Stdlib\DateTime\DateTime,
    Framework\Model\ResourceModel\Db\VersionControl\SnapshotFactory,
    Framework\Stdlib\DateTime\Timezone as TimeZone,
    Framework\App\Filesystem\DirectoryList,
    Sales\Model\OrderFactory,
    Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory,
    Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory as CreditmemoItemCollectionFactory,
    ConfigurableProduct\Model\Product\Type\Configurable,
    Store\Model\StoreManagerInterface
};
use Psr\Log\LoggerInterface;

use Emartech\Emarsys\{
    Helper\ConfigReader,
    Api\Data\ConfigInterface
};

/**
 * Class Order
 * @package Emarsys\Emarsys\Model
 */
class Order extends AbstractModel
{
    CONST BATCH_SIZE = 500;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var EmarsysHelper
     */
    protected $emarsysHelper;

    /**
     * @var OrderResourceModel
     */
    protected $orderResourceModel;

    /**
     * @var OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var EmarsysOrderExportFactory
     */
    protected $emarsysOrderExportFactory;

    /**
     * @var OrderQueueFactory
     */
    protected $orderQueueFactory;

    /**
     * @var CreditmemoExportStatusFactory
     */
    protected $creditmemoExportStatusFactory;

    /**
     * @var OrderExportStatusFactory
     */
    protected $orderExportStatusFactory;

    /**
     * @var TimeZone
     */
    protected $timezone;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var array
     */
    protected $salesCsvHeader = [];

    /**
     * @var null | resource
     */
    protected $handle = null;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var SnapshotFactory
     */
    protected $snapshotFactory;

    /**
     * @var CreditmemoItemCollectionFactory
     */
    private $creditmemoItemCollectionFactory;

    /**
     * @var EmarsysCreditmemoExportFactory
     */
    private $emarsysCreditmemoExportFactory;

    /**
     * @var ConfigReader
     */
    protected $configReader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Order constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param MessageManagerInterface $messageManager
     * @param DateTime $date
     * @param EmarsysHelper $emarsysHelper
     * @param OrderResourceModel $orderResourceModel
     * @param OrderFactory $salesOrderFactory
     * @param EmarsysOrderExportFactory $emarsysOrderExportFactory
     * @param EmarsysCreditmemoExportFactory $emarsysCreditmemoExportFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param CreditmemoItemCollectionFactory $creditmemoItemCollectionFactory
     * @param SnapshotFactory $snapshotFactory
     * @param OrderQueueFactory $orderQueueFactory
     * @param CreditmemoExportStatusFactory $creditmemoExportStatusFactory
     * @param OrderExportStatusFactory $orderExportStatusFactory
     * @param TimeZone $timezone
     * @param DirectoryList $directoryList
     * @param ConfigReader $configReader
     * @param LoggerInterface $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        MessageManagerInterface $messageManager,
        DateTime $date,
        EmarsysHelper $emarsysHelper,
        OrderResourceModel $orderResourceModel,
        OrderFactory $salesOrderFactory,
        EmarsysOrderExportFactory $emarsysOrderExportFactory,
        EmarsysCreditmemoExportFactory $emarsysCreditmemoExportFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        CreditmemoItemCollectionFactory $creditmemoItemCollectionFactory,
        SnapshotFactory $snapshotFactory,
        OrderQueueFactory $orderQueueFactory,
        CreditmemoExportStatusFactory $creditmemoExportStatusFactory,
        OrderExportStatusFactory $orderExportStatusFactory,
        TimeZone $timezone,
        DirectoryList $directoryList,
        ConfigReader $configReader,
        LoggerInterface $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,

        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->date = $date;
        $this->emarsysHelper = $emarsysHelper;
        $this->orderResourceModel = $orderResourceModel;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->emarsysOrderExportFactory = $emarsysOrderExportFactory;
        $this->emarsysCreditmemoExportFactory = $emarsysCreditmemoExportFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->creditmemoItemCollectionFactory = $creditmemoItemCollectionFactory;
        $this->snapshotFactory = $snapshotFactory;
        $this->orderQueueFactory = $orderQueueFactory;
        $this->creditmemoExportStatusFactory = $creditmemoExportStatusFactory;
        $this->orderExportStatusFactory = $orderExportStatusFactory;
        $this->timezone = $timezone;
        $this->directoryList = $directoryList;
        $this->configReader = $configReader;
        $this->logger = $logger;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Emarsys\Emarsys\Model\ResourceModel\Order');
    }

    /**
     * @param $storeId
     * @param $mode
     * @param null $exportFromDate
     * @param null $exportTillDate
     * @return bool
     * @throws \Exception
     */
    public function syncOrders($storeId, $mode, $exportFromDate = null, $exportTillDate = null)
    {
        //export data using ftp
        try {
            $store = $this->storeManager->getStore($storeId);
            if ($this->configReader->isEnabledForWebsite(ConfigInterface::CONFIG_ENABLED, $store->getWebsiteId())) {
                $logsArray['action'] = 'synced to FTP';
                $this->exportOrdersDataUsingFtp($storeId, $mode, $exportFromDate, $exportTillDate, $logsArray);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }

    /**
     * @param $storeId
     * @param $mode
     * @param $exportFromDate
     * @param $exportTillDate
     * @param $logsArray
     * @throws \Exception
     */
    public function exportOrdersDataUsingFtp($storeId, $mode, $exportFromDate, $exportTillDate, $logsArray)
    {
        $store = $this->storeManager->getStore($storeId);
        $errorCount = true;

        $bulkDir = $store->getConfig(EmarsysHelper::XPATH_EMARSYS_FTP_BULK_EXPORT_DIR);

        if ($this->emarsysHelper->checkFtpConnectionByStore($store)) {
            try {
                //ftp connection established successfully
                $outputFile = $this->getSalesCsvFileName($store->getCode());
                $fileDirectory = $this->emarsysHelper->getEmarsysMediaDirectoryPath(
                    \Magento\Sales\Model\Order::ENTITY
                );
                $moveFile = false;

                //Check and create directory for csv generation
                $this->emarsysHelper->checkAndCreateFolder($fileDirectory);
                $filePath = $fileDirectory . "/" . $outputFile;

                //prepare order collection
                /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
                $orderCollection = $this->getOrderCollection(
                    $mode,
                    $storeId,
                    $exportFromDate,
                    $exportTillDate
                );
                $orderCollectionClone = false;

                //Generate Sales CSV
                if ($orderCollection && (is_object($orderCollection)) && ($orderCollection->getSize())) {
                    $orderCollection->setPageSize(self::BATCH_SIZE);
                    $moveFile = true;
                    $pages = $orderCollection->getLastPageNumber();
                    for ($i = 1; $i <= $pages; $i++) {
                        //echo "$i/$pages => " . date('Y-m-d H:i:s') . "\n";
                        $orderCollection->clear();
                        $orderCollection->setPageSize(self::BATCH_SIZE)->setCurPage($i);
                        $orderCollectionClone = clone $orderCollection;
                        $this->generateOrderCsv($storeId, $filePath, $orderCollection, false, true);

                        $logsArray['emarsys_info'] = __('Order\'s iteration %1 of %2', $i, $pages);
                        $logsArray['description'] = __('Order\'s iteration %1 of %2', $i, $pages);
                        $logsArray['message_type'] = 'Success';
                    }
                }
            } catch (\Exception $e) {
                $logsArray['emarsys_info'] = __('Export Orders Data Using FTP');
                $logsArray['description'] = __($e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logger->critical($e);
            }

            try {
                //prepare credit-memo collection
                /** @var  $creditMemoCollection */
                $creditMemoCollection = $this->getCreditMemoCollection(
                    $mode,
                    $storeId,
                    $exportFromDate,
                    $exportTillDate
                );
                $creditMemoCollectionClone = false;

                if ($creditMemoCollection && (is_object($creditMemoCollection)) && ($creditMemoCollection->getSize())) {
                    $moveFile = true;
                    $creditMemoCollection->setPageSize(self::BATCH_SIZE);
                    $pages = $creditMemoCollection->getLastPageNumber();
                    for ($i = 1; $i <= $pages; $i++) {
                        //echo "$i/$pages => " . date('Y-m-d H:i:s') . "\n";
                        $creditMemoCollection->clear();
                        $creditMemoCollection->setPageSize(self::BATCH_SIZE)->setCurPage($i);
                        $creditMemoCollectionClone = clone $creditMemoCollection;
                        $this->generateOrderCsv($storeId, $filePath, false, $creditMemoCollection, true);

                        $logsArray['emarsys_info'] = __('CreditMemo\'s iteration %1 of %2', $i, $pages);
                        $logsArray['description'] = __('CreditMemo\'s iteration %1 of %2', $i, $pages);
                        $logsArray['message_type'] = 'Success';
                    }
                }
            } catch (\Exception $e) {
                $logsArray['emarsys_info'] = __('Export CreditMemos Data Using FTP');
                $logsArray['description'] = __($e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logger->critical($e);
            }

            //CSV upload to FTP process starts

            try {
                $url = $this->emarsysHelper->getEmarsysMediaUrlPath(\Magento\Sales\Model\Order::ENTITY, $filePath);

                if ($moveFile) {
                    $remoteDirPath = $bulkDir;
                    if ($remoteDirPath == '/') {
                        $remoteFileName = $outputFile;
                    } else {
                        $remoteDirPath = rtrim($remoteDirPath, '/');
                        $remoteFileName = $remoteDirPath . "/" . $outputFile;
                    }

                    //Upload CSV to FTP
                    if ($this->emarsysHelper->moveFileToFtp($store, $filePath, $remoteFileName)) {
                        //file uploaded to FTP server successfully
                        $errorCount = false;
                        $logsArray['emarsys_info'] = __('File uploaded to FTP server successfully');
                        $logsArray['description'] = $url . ' > ' . $remoteFileName;
                        $logsArray['message_type'] = 'Success';
                        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                            $this->messageManager->addSuccessMessage(
                                __("File uploaded to FTP server successfully !!!")
                            );
                        }
                    } else {
                        //Failed to upload file on FTP server
                        $errorMessage = error_get_last();
                        $msg = isset($errorMessage['message']) ? $errorMessage['message'] : '';
                        $logsArray['emarsys_info'] = __('Failed to upload file on FTP server');
                        $logsArray['description'] = __('Failed to upload %1 on FTP server. %2', $url, $msg);
                        $logsArray['message_type'] = 'Error';
                        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                            $this->messageManager->addErrorMessage(
                                __("Failed to upload file on FTP server !!! %1", $msg)
                            );
                        }
                    }
                    //unset file handle
                    $this->unsetFileHandle();

                    //remove file after sync
                    $this->emarsysHelper->removeFilesInFolder($this->emarsysHelper->getEmarsysMediaDirectoryPath(\Magento\Sales\Model\Order::ENTITY));
                } else {
                    //no sales data found for the store
                    $logsArray['emarsys_info'] = __('No Sales Data found for the store . ' . $store->getCode());
                    $logsArray['description'] = __('No Sales Data found for the store . ' . $store->getCode());
                    $logsArray['message_type'] = 'Error';
                }
            } catch (\Exception $e) {
                $logsArray['emarsys_info'] = __('Failed to Upload CSV to FTP.');
                $logsArray['description'] = __($e->getMessage());
                $logsArray['message_type'] = 'Error';
                $this->logger->critical($e);
            }
        } else {
            //failed to connect with FTP server with given credentials
            $logsArray['emarsys_info'] = __('Failed to connect with FTP server.');
            $logsArray['description'] = __('Failed to connect with FTP server.');
            $logsArray['message_type'] = 'Error';
            if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_MANUAL) {
                $this->messageManager->addErrorMessage(
                    __('"Failed to connect with FTP server. Please check your settings and try again !!!"')
                );
            }
        }

        if ($errorCount) {
            $logsArray['status'] = 'error';
            $logsArray['messages'] = __('Order export have an error. Please check');
        } else {
            //clean the queue table after SI export
            $this->cleanOrderQueueTable($orderCollectionClone, $creditMemoCollectionClone);
            $logsArray['status'] = 'success';
            $logsArray['messages'] = __('Order export completed');
        }
        $logsArray['finished_at'] = $this->date->date('Y-m-d H:i:s', time());

        return;
    }

    public function unsetFileHandle()
    {
        $this->handle = null;
        return;
    }

    /**
     * @param $storeId
     * @param $filePath
     * @param $orderCollection
     * @param $creditMemoCollection
     * @param bool $sameFile
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateOrderCsv($storeId, $filePath, $orderCollection, $creditMemoCollection, $sameFile = false)
    {
        $taxIncluded = 1;
        $useBaseCurrency = 0;

        if ($sameFile && !$this->handle) {
            $this->handle = fopen($filePath, 'w');

            //Get Header for sales csv
            $header = $this->getSalesCsvHeader($storeId);

            //put headers in sales csv
            fputcsv($this->handle, $header);
        } elseif (!$sameFile) {
            $this->handle = fopen($filePath, 'w');

            //Get Header for sales csv
            $header = $this->getSalesCsvHeader($storeId);

            //put headers in sales csv
            fputcsv($this->handle, $header);
        }

        //write data for orders into csv
        if ($orderCollection) {
            $dummySnapshot = $this->snapshotFactory->create();
            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection as $order) {
                $orderId = $order->getRealOrderId();
                $createdDate = date('Y-m-d', strtotime($order->getCreatedAt()));
                $customerEmail = $order->getCustomerEmail();

                $fullyInvoiced = false;
                if ($order->getTotalPaid() == $order->getGrandTotal()) {
                    $fullyInvoiced = true;
                }

                $parentId = null;
                $items = $this->orderItemCollectionFactory->create(['entitySnapshot' => $dummySnapshot])
                    ->addFieldToFilter('order_id', ['eq' => $order->getId()]);

                /** @var \Magento\Sales\Model\Order\Item $item */
                foreach ($items as $item) {
                    if ($item->getProductType() == Configurable::TYPE_CODE) {
                        $parentId = $item->getId();
                    }
                    if ($parentId && $item->getParentItemId() == $parentId) {
                        $parentId = null;
                        continue;
                    }
                    $values = [];
                    //s_steren_card
                    $values[] = '';
                    //s_store
                    $values[] = '';
                    //order
                    $values[] = $orderId;
                    //item
                    $values[] = $item->getSku();

                    $rowTotal = 0;
                    $qty = 0;
                    if ($fullyInvoiced) {
                        $qty = (int)$item->getQtyInvoiced();
                        if ($taxIncluded) {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotalInclTax()
                                : $item->getRowTotalInclTax();
                        } else {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotal()
                                : $item->getRowTotal();
                        }
                        if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)) {
                            $parentId = null;
                            $productOptions = $item->getProductOptions();
                            if (isset($productOptions['product_calculations'])
                                && $productOptions['product_calculations'] == 0
                            ) {
                                $rowTotal = 0;
                            }
                        }
                    }

                    //set quantity
                    $values[] = $qty;

                    $unitPrice = 0;
                    if ($fullyInvoiced) {
                        if ($taxIncluded) {
                            $unitPrice = $useBaseCurrency
                                ? $item->getBasePriceInclTax()
                                : $item->getPriceInclTax();
                        } else {
                            $unitPrice = $useBaseCurrency
                                ? $item->getBasePrice()
                                : $item->getPrice();
                        }
                        if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)) {
                            $parentId = null;
                            $productOptions = $item->getProductOptions();
                            if (isset($productOptions['product_calculations'])
                                && $productOptions['product_calculations'] == 0
                            ) {
                                $unitPrice = 0;
                            }
                        }
                    }

                    //unit_price
                    if ($unitPrice) {
                        $values[] = number_format($unitPrice, 2, '.', '');
                    } else {
                        $values[] = 0;
                    }

                    //c_sales_amount
                    if ($rowTotal) {
                        $values[] = number_format($rowTotal, 2, '.', '');
                    } else {
                        $values[] = 0;
                    }


                    //s_sub_category
                    $values[] = '';
                    //s_movement_type
                    $values[] = '';
                    //s_state
                    $values[] = '';

                    //customer
                    $values[] = $customerEmail;
                    //date
                    $values[] = $createdDate;

                    fputcsv($this->handle, $values);
                }
            }
        }

        //write data for credit-memo into csv
        if ($creditMemoCollection) {
            $dummySnapshot = $this->snapshotFactory->create();
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditMemo */
            foreach ($creditMemoCollection as $creditMemo) {
                $orderId = $creditMemo->getOrderId();
                $createdDate = date('Y-m-d', strtotime($creditMemo->getCreatedAt()));
                $customerEmail = $creditMemo->getOrder()->getCustomerEmail();

                $parentId = null;
                $items = $this->creditmemoItemCollectionFactory->create(['entitySnapshot' => $dummySnapshot])
                    ->addFieldToFilter('parent_id', ['eq' => $creditMemo->getId()]);

                /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
                foreach ($items as $item) {
                    if ($item->getOrderItem()->getParentItem()) {
                        continue;
                    }

                    $values = [];
                    //s_steren_card
                    $values[] = '';
                    //s_store
                    $values[] = '';
                    //order
                    $values[] = $orderId;
                    //item
                    $values[] = $item->getSku();

                    $rowTotal = 0;
                    $qty = (int)$item->getQty();
                    if ($fullyInvoiced) {
                        $qty = '-' . abs($qty);
                        if ($taxIncluded) {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotalInclTax()
                                : $item->getRowTotalInclTax();
                        } else {
                            $rowTotal = $useBaseCurrency
                                ? $item->getBaseRowTotal()
                                : $item->getRowTotal();
                        }
                        if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)) {
                            $parentId = null;
                            $productOptions = $item->getProductOptions();
                            if (isset($productOptions['product_calculations'])
                                && $productOptions['product_calculations'] == 0
                            ) {
                                $rowTotal = 0;
                            }
                        }
                    }

                    //set quantity
                    $values[] = $qty;

                    $unitPrice = 0;
                    if ($fullyInvoiced) {
                        if ($taxIncluded) {
                            $unitPrice = $useBaseCurrency
                                ? $item->getBasePriceInclTax()
                                : $item->getPriceInclTax();
                        } else {
                            $unitPrice = $useBaseCurrency
                                ? $item->getBasePrice()
                                : $item->getPrice();
                        }
                        if (($item->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE)) {
                            $parentId = null;
                            $productOptions = $item->getProductOptions();
                            if (isset($productOptions['product_calculations'])
                                && $productOptions['product_calculations'] == 0
                            ) {
                                $unitPrice = 0;
                            }
                        }
                    }

                    //unit_price
                    if ($unitPrice) {
                        $values[] = '-' . number_format(abs($unitPrice), 2, '.', '');
                    } else {
                        $values[] = 0;
                    }

                    //c_sales_amount
                    if ($rowTotal) {
                        $values[] = '-' . number_format(abs($rowTotal), 2, '.', '');
                    } else {
                        $values[] = 0;
                    }

                    //s_sub_category
                    $values[] = '';
                    //s_movement_type
                    $values[] = '';
                    //s_state
                    $values[] = '';

                    //customer
                    $values[] = $customerEmail;
                    //date
                    $values[] = $createdDate;


                    fputcsv($this->handle, $values);
                }
            }
        }
        return true;
    }

    /**
     * @param $suffix
     * @return string
     */
    public function getSalesCsvFileName($suffix)
    {
        $uniqueId = str_replace([' ', '.'], ['', ''], microtime(true));

        return "sales_items_magento_" . $suffix . "_" . $this->date->date('Ymd') . '_' . $uniqueId . ".csv";
    }

    /**
     * Get Sales CSV Header
     * @param int $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSalesCsvHeader($storeId = 0)
    {
        if (!isset($this->salesCsvHeader[$storeId])) {
            //default header
            $this->salesCsvHeader[$storeId] = $this->emarsysHelper->getSalesOrderCsvDefaultHeader();
        }
        return $this->salesCsvHeader[$storeId];
    }

    /**
     * @param $mode
     * @param $storeId
     * @param $exportFromDate
     * @param $exportTillDate
     * @return $this|array
     */
    public function getOrderCollection($mode, $storeId, $exportFromDate, $exportTillDate)
    {
        $orderCollection = [];

        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_AUTOMATIC) {
            $orderQueueCollection = $this->orderQueueFactory->create()->getCollection()
                ->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addFieldToFilter('entity_type_id', 1);

            if ($orderQueueCollection && $orderQueueCollection->getSize()) {
                $orderIds = [];
                foreach ($orderQueueCollection as $orderQueue) {
                    $orderIds[] = $orderQueue->getEntityId();
                }
                $orderCollection = $this->emarsysOrderExportFactory->create()
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->addFieldToFilter('entity_id', ['in' => $orderIds])
                    ->addFieldToFilter('status', ['nin' => \Magento\Sales\Model\Order::STATE_CLOSED]);
            }
        } else {
            $orderCollection = $this->emarsysOrderExportFactory->create()
                ->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addOrder('created_at', 'ASC')
                ->addFieldToFilter('status', ['nin' => \Magento\Sales\Model\Order::STATE_CLOSED]);

            if (isset($exportFromDate) && isset($exportTillDate) && $exportFromDate != '' && $exportTillDate != '') {
                $toTimezone = $this->timezone->getDefaultTimezone();
                $fromDate = $this->timezone->date($exportFromDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $toDate = $this->timezone->date($exportTillDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');


                $orderCollection->addFieldToFilter(
                    'created_at',
                    [
                        'from' => $fromDate,
                        'to' => $toDate,
                        'date' => true,
                    ]
                );
            }
        }

        return $orderCollection;
    }

    /**
     * @param $mode
     * @param $storeId
     * @param $exportFromDate
     * @param $exportTillDate
     * @return $this|array
     */
    public function getCreditMemoCollection($mode, $storeId, $exportFromDate, $exportTillDate)
    {
        $creditMemoCollection = [];

        if ($mode == EmarsysHelper::ENTITY_EXPORT_MODE_AUTOMATIC) {
            $creditMemoQueueCollection = $this->orderQueueFactory->create()->getCollection()
                ->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addFieldToFilter('entity_type_id', 2);

            if ($creditMemoQueueCollection && $creditMemoQueueCollection->getSize()) {
                $creditMemoIds = [];
                foreach ($creditMemoQueueCollection as $creditMemoQueue) {
                    $creditMemoIds[] = $creditMemoQueue->getEntityId();
                }
                $creditMemoCollection = $this->emarsysCreditmemoExportFactory->create()
                    ->addFieldToFilter('store_id', ['eq' => $storeId])
                    ->addFieldToFilter('entity_id', ['in' => $creditMemoIds]);
            }
        } else {
            $creditMemoCollection = $this->emarsysCreditmemoExportFactory->create()
                ->addFieldToFilter('store_id', ['eq' => $storeId]);

            if (isset($exportFromDate) && isset($exportTillDate) && $exportFromDate != '' && $exportTillDate != '') {
                $toTimezone = $this->timezone->getDefaultTimezone();
                $fromDate = $this->timezone->date($exportFromDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $toDate = $this->timezone->date($exportTillDate)
                    ->setTimezone(new \DateTimeZone($toTimezone))
                    ->format('Y-m-d H:i:s');

                $creditMemoCollection->addFieldToFilter(
                    'created_at',
                    [
                        'from' => $fromDate,
                        'to' => $toDate,
                        'date' => true,
                    ]
                );
            }
        }

        return $creditMemoCollection;
    }

    /**
     * clean Order Queue Table
     *
     * @param bool $orderCollection
     * @param bool $creditMemoCollection
     * @throws \Exception
     */
    public function cleanOrderQueueTable($orderCollection = false, $creditMemoCollection = false)
    {
        //remove order records from queue table
        if ($orderCollection) {
            $allOrderIds = $orderCollection->getAllIds();
            $orderIdsArrays = array_chunk($allOrderIds, 100);

            foreach ($orderIdsArrays as $orderIds) {
                $orderExportStatusCollection = $this->orderExportStatusFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('order_id', ['in' => $orderIds]);

                foreach ($orderExportStatusCollection as $orderExportStat) {
                    $eachOrderStat = $this->orderExportStatusFactory->create()->load($orderExportStat['id']);
                    $eachOrderStat->setExported(1);
                    $eachOrderStat->save();
                }

                $orderQueueCollection = $this->orderQueueFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $orderIds])
                    ->load();
                $orderQueueCollection->walk('delete');
            }
        }

        //remove credit-memo records from queue table
        if ($creditMemoCollection) {
            $allCreditmemoOrderIds = $creditMemoCollection->getAllIds();
            $creditmemoIdsArrays = array_chunk($allCreditmemoOrderIds, 100);
            foreach ($creditmemoIdsArrays as $creditmemoIds) {
                $creditmemoExportStatusCollection = $this->creditmemoExportStatusFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('order_id', ['in' => $creditmemoIds]);

                foreach ($creditmemoExportStatusCollection as $orderExportStat) {
                    $eachOrderStat = $this->creditmemoExportStatusFactory->create()->load($orderExportStat['id']);
                    $eachOrderStat->setExported(1);
                    $eachOrderStat->save();
                }

                $creditMemoQueueCollection = $this->orderQueueFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $creditmemoIds])
                    ->load();
                $creditMemoQueueCollection->walk('delete');
            }
        }

        return;
    }

    /**
     * @param $emarsysAttribute
     * @param $value
     * @return mixed
     */
    protected function getValueForType($emarsysAttribute, $value)
    {
        if (substr($emarsysAttribute, 0, 2) === "s_") {
            $value = trim(preg_replace('/\s+/', ' ', $value));
        }

        return $value;
    }
}
