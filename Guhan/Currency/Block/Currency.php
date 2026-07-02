<?php

namespace Guhan\Currency\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Currency extends Template
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        array                 $data = []
    )
    {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * return all available currency codes
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAvailableCurrencyCodes(): array
    {
        return $this->storeManager->getStore()->getAvailableCurrencyCodes();
    }
}
