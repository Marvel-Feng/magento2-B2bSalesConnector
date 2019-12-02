<?php

namespace Ace\B2bSalesConnector\Model\Sync;

use Psr\Log\LoggerInterface;
use Ace\B2bConnector\Api\MapperInterface;
use Ace\B2bConnector\Model\Sync\SyncAbstract;

class Order extends SyncAbstract
{
    /**
     * @var \Magento\Framework\Xml\Generator
     */
    private $generator;

    /**
     * Order constructor.
     * @param LoggerInterface $logger
     * @param MapperInterface $mapper
     */
    public function __construct (LoggerInterface $logger, MapperInterface $mapper,
        \Magento\Framework\Xml\Generator $generator)
    {
        parent::__construct($logger, $mapper);
        $this->generator = $generator;
    }

    /**
     * Retrieves, maps, and sync order to ERP.
     *
     * @return $this|bool
     */
    public function syncData ($object)
    {
        try {

            $this->logger->info("Order Sync");
            $this->logger->info(get_class($this->mapper));
//
            $mappedData = $this->mapper->map($object);

            // now to API CALL

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}