<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2017 Emarsys. (http://www.emarsys.net/)
 */

namespace Emarsys\Emarsys\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for deployment of Sample Data
 */
class EmarsysProductExport extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * EmarsysProductExport constructor.
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\Framework\App\State $state
    )
    {
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('emarsys:export:product')
            ->setDescription('Product bulk export');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $this->updateMemoryLimit();
        $output->writeln('');
        $output->writeln('<info>Starting product bulk export.</info>');

        try {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Emarsys\Emarsys\Model\Product::class)->consolidatedCatalogExport(
                \Emarsys\Emarsys\Helper\Data::ENTITY_EXPORT_MODE_MANUAL
            );
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }


        $error = error_get_last();
        if (!empty($error['message'])) {
            $output->writeln($error);
        }

        $output->writeln('<info>Product bulk export complete</info>');
        $output->writeln('');
    }

    /**
     * @return void
     */
    private function updateMemoryLimit()
    {
        if (function_exists('ini_set')) {
            @ini_set('display_errors', 1);
            $memoryLimit = trim(ini_get('memory_limit'));
            if ($memoryLimit != -1 && $this->getMemoryInBytes($memoryLimit) < 756 * 1024 * 1024) {
                @ini_set('memory_limit', '756M');
            }
        }
    }

    /**
     * @param string $value
     * @return int
     */
    private function getMemoryInBytes($value)
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int)$value;
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
        }
        return $value;
    }
}
