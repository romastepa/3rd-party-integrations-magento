<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for deployment of Sample Data
 */
class EmarsysOrderExport extends Command
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * EmarsysOrderExport constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $state
    ) {
        $this->storeManager = $storeManager;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                '--from="Y-m-d" [2017-12-31]'
            ),
            new InputOption(
                'to',
                null,
                InputOption::VALUE_OPTIONAL,
                '--to="Y-m-d" [2017-12-31]'
            ),
            new InputOption(
                'queue',
                null,
                InputOption::VALUE_OPTIONAL,
                '--queue="true"',
                false
            ),
        ];

        $this->setName('emarsys:export:order')
            ->setDescription('Order bulk export (--from=\'Y-m-d\' --to=\'Y-m-d\' --queue=\'true\')')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $output->writeln('');
        $output->writeln('<info>Order customer bulk export.</info>');

        /** @var \Magento\Store\Model\Store $store */
        foreach ($this->storeManager->getStores() as $storeId => $store) {
            if ($store->getConfig(\Emarsys\Emarsys\Helper\Data::XPATH_EMARSYS_ENABLED) && $store->getConfig(\Emarsys\Emarsys\Helper\Data::XPATH_SMARTINSIGHT_ENABLED)) {

                $fromDate = $input->getOption('from');
                $toDate = $input->getOption('to');
                $queue = $input->getOption('queue');
                try {
                    \Magento\Framework\App\ObjectManager::getInstance()->get(\Emarsys\Emarsys\Model\Order::class)->syncOrders(
                        $storeId,
                        ($queue ? \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_AUTOMATIC : \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_MANUAL),
                        $fromDate,
                        $toDate
                    );
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                }
            }
        }

        $error = error_get_last();
        if (!empty($error['message'])) {
            $output->writeln($error);
        }

        $output->writeln('<info>Order bulk export complete</info>');
        $output->writeln('');
    }
}
