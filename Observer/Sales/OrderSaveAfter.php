<?php


namespace Ace\B2bSalesConnector\Observer\Sales;

use Ace\B2bConnector\Model\Sync;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var Sync
     */
    private $sync;

    /**
     * OrderSaveAfter constructor.
     * @param Sync $sync
     * @param ManagerInterface $messageManager
     */
    public function __construct (
        Sync $sync,
        ManagerInterface $messageManager)
    {

        $this->messageManager = $messageManager;
        $this->sync = $sync;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ) {

        $order = $observer->getEvent()->getOrder();
        $controller = $observer->getControllerAction();
        $this->messageManager->addSuccess(__('Order Event executed change successfully'));

        $this->sync->syncData($order);


    }
}
