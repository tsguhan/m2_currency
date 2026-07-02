<?php
namespace Guhan\Currency\Service;

use Guhan\Currency\Helper\Data;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class CurrencyRates
{
    private const string API_ENDPOINT = 'form_settings/general/api_endpoint';

    /**
     * @var ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * @var Json
     */
    protected Json $jsonSerializer;

    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param ClientInterface $httpClient
     * @param Json $jsonSerializer
     * @param Data $dataHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterface $httpClient,
        Json $jsonSerializer,
        Data $dataHelper,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    /**
     * API call to fetch currency exchange rates and chart information
     *
     * @param $formData
     * @param null $period
     * @return array
     */
    public function fetchRates($formData, $period = null): array
    {

        $apiEndpoint = $this->dataHelper->getApiEndpoint(self::API_ENDPOINT);

        if (!$apiEndpoint) {
            return [];
        }

        if (!$formData['primary'] || !$formData['secondary']) {
            return [];
        }

        $queryParams = http_build_query([
            'base'   => $formData['primary'],
            'quotes' => $formData['secondary'],
            $period['from'] ? "'from' => " . $period['from'] . ",\n 'to' => ". $period['to'] . "," : ",",
        ]);

        $requestUrl = $apiEndpoint . '?' . $queryParams;

        try {
            // Configure security constraints and connection rules
            $this->httpClient->setOption(CURLOPT_TIMEOUT, 15);        // Drop slow connections (Prevent hanging threads)
            $this->httpClient->setOption(CURLOPT_CONNECTTIMEOUT, 5); // Fail fast if server is unresponsive
            $this->httpClient->setOption(CURLOPT_RETURNTRANSFER, true);

            $this->httpClient->setOption(CURLOPT_SSL_VERIFYPEER, true);
            $this->httpClient->setOption(CURLOPT_SSL_VERIFYHOST, 2);

            // Dispatch the GET request
            $this->httpClient->get($requestUrl);

            $responseStatus = $this->httpClient->getStatus();
            $responseBody   = $this->httpClient->getBody();

            if ($responseStatus !== 200) {
                throw new \Exception("External API returned unexpected status code: {$responseStatus}");
            }

            return $this->jsonSerializer->unserialize($responseBody);

        } catch (\InvalidArgumentException $jsonException) {
            $this->logger->critical('Failed parsing JSON format response from Frankfurter API: ' . $jsonException->getMessage());
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Secure API Call Exception: ' . $e->getMessage());
            return [];
        }
    }
}
