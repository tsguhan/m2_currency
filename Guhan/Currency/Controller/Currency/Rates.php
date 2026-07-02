<?php

namespace Guhan\Currency\Controller\Currency;

use DateMalformedStringException;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Guhan\Currency\Service\CurrencyRates;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Rates implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var CurrencyRates
     */
    protected CurrencyRates $currencyRatesService;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timezone;

    /**
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param CurrencyRates $currencyRatesService
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        ResultFactory    $resultFactory,
        RequestInterface $request,
        CurrencyRates $currencyRatesService,
        TimezoneInterface $timezone
    )
    {
        $this->currencyRatesService = $currencyRatesService;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->timezone = $timezone;
    }

    /**
     * receive formData
     * making 2 calls
     * fetch currency exchange rates
     * fetch chart information by periods of dates
     * return response back to js to show the information
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        if (empty($formData)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('No data received or something not received.')
            ]);
        }

        try {
            $formData = $this->request->getParams();

            $period = $this->dateConvertor($formData['period']);

            $currencyExchangeRates = $this->currencyRatesService->fetchRates($formData, "");

            $currencyRatesPeriod = $this->currencyRatesService->fetchRates($formData, $period);

            $responseData = [
                'success' => true,
                'message' => __('Fetched currency rates successfully'),
                'currency_exchanges' => $currencyExchangeRates,
                'history_data_for_chart' => $currencyRatesPeriod,
            ];

            return $resultJson->setData($responseData);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __($e->getMessage())
            ]);
        }
    }

    /**
     * prepare from and to date for chart
     *
     * @param $period
     * @return false|string
     * @throws DateMalformedStringException
     */
    public function dateConvertor($period): false|string
    {
        $date = $this->timezone->date();
        $currentDateTime = $this->timezone->date()->format('Y-m-d');
        return match ($period) {
            "1" => "from=" . $currentDateTime . "to=" . $date->modify('-365 days')->format('Y-m-d'),
            "5" => "from=" . $currentDateTime . "to=" . $date->modify('-1825 days')->format('Y-m-d'),
            default => "from=" . $currentDateTime . "to=" . $date->modify('-90 days')->format('Y-m-d'),
        };
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
