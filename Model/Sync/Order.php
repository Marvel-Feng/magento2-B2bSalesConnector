<?php

namespace Ace\B2bSalesConnector\Model\Sync;

use Psr\Log\LoggerInterface;
use Ace\B2bConnector\Api\MapperInterface;
use Ace\B2bConnector\Model\Sync\SyncAbstract;
use Ace\B2bConnector\Model\System\Config\Source\SyncType;
use Ace\B2bConnector\Model\Data\SyncLog;
use Magento\Sales\Model\Order as OrderMdel;


class Order extends SyncAbstract
{
    /**
     * @var \Magento\Framework\Xml\Generator
     */
    protected $generator;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Ace\B2bConnector\Model\SyncLog
     */
    protected $syncLog;


    /**
     * Order constructor.
     * @param LoggerInterface $logger
     * @param MapperInterface $mapper
     */
    public function __construct (LoggerInterface $logger, MapperInterface $mapper,
        $entityType,
        \Magento\Framework\Xml\Generator $generator,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Ace\B2bConnector\Model\SyncLog $syncLog)
    {
        parent::__construct($logger, $mapper, $entityType);
        $this->generator = $generator;
        $this->timezone = $timezone;
        $this->syncLog = $syncLog;

    }

    /**
     * Retrieves, maps, and sync order to ERP.
     *
     * @return $this|bool
     */
    public function syncData ($object)
    {
        try {

            $mappedData = $this->mapper->map($object);

            $this->logger->info("Order Sync");
            $this->logger->info($this->entityType);
            $this->logger->info($this->generator->arrayToXml($mappedData));

            $dataToQueue[SyncLog::QUEUE_STATUS] = SyncType::TYPE_QUEUE_INSTANCE;
            $dataToQueue[SyncLog::REQUEST] = $this->generator->arrayToXml($mappedData);
            $dataToQueue[SyncLog::QUEUE_TIME] = $this->timezone->formatDate();
            $dataToQueue[SyncLog::ENTITY_TYPE] = OrderMdel::ENTITY;
            $dataToQueue[SyncLog::ENTITY_ID] = $object->getId();
//            $dataToQueue[SyncLog::METHOD] = SyncType::SYNC_METHOD_POST;
            $dataToQueue[SyncLog::METHOD] = SyncType::SYNC_METHOD_GET;

            //$dataToQueue[SyncLog::REQUEST_URL] = "http://212.107.102.134:8080/etest/";

            $dataToQueue[SyncLog::REQUEST_URL] = "http://localhost/mvc/";

            $this->syncLog->setData($dataToQueue);
            $this->syncLog->syncData();
            $response = $this->syncLog->getData("response");

            $this->logger->info($this->syncLog->getId());

            $this->logger->log(100,print_r($response->getHeaders(),true) );


        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }



}