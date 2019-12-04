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
        $returnArray = array();
        $returnArray['status'] = $status = $object->getStatus();
        $returnArray['order_date'] = $order_date = $object->getCreatedAt();
        $returnArray['order_date'] = $order_date = date('Y-m-dH:i:s', strtotime( $order_date) );
        $returnArray['tax_info'] = $tax_info = $object->getFullTaxInfo();

        return $returnArray;

        //$this->logger->info("Ace\B2bSalesConnector\Model\Mapper\Order::map function");

    }

    /**
     * @return mixed|LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

}