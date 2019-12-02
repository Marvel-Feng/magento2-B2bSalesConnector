<?php

namespace Ace\B2bSalesConnector\Model\Mapper;

use Psr\Log\LoggerInterface;
use Ace\B2bConnector\Model\Mapper\MapperAbstract;
use Ace\B2bConnector\Helper\Config as ConfigHelper;


class Order extends MapperAbstract
{


    /**
     * Order constructor.
     * @param LoggerInterface $logger
     * @param ConfigHelper $configHelper
     */
    public function __construct (
        LoggerInterface $logger

    ) {
        parent::__construct($logger);
    }


    /**
     * @param $object
     * @return mixed|void
     */
    public function map ($object)
    {

        $this->logger->info("Ace\B2bSalesConnector\Model\Mapper\Order::map function");

    }

    /**
     * @return mixed|LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

}