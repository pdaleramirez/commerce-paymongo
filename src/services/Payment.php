<?php
namespace pdaleramirez\commercepaymongo\services;

use craft\base\Component;
use craft\helpers\App;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use yii\web\Response;

class Payment extends Component
{
    private string $secretKey;
    private ?string $clientKey = null;

    public function setSecretKey($secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param bool $parse
     * @return string|null
     */
    public function getSecretKey(bool $parse = true): ?string
    {
        return $parse ? App::parseEnv($this->secretKey) : $this->secretKey;
    }

    public function setClientKey($clientKey): void
    {
        $this->clientKey = $clientKey;
    }

    /**
     * @param bool $parse
     * @return string|null
     */
    public function getClientKey(bool $parse = true): ?string
    {
        return $parse ? App::parseEnv($this->clientKey) : $this->clientKey;
    }

    /**
     * @param $endpoint
     * @param $attributes
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function payMongoRequest($endpoint, $attributes)
    {
        $secretKey = $this->getSecretKey();

        $data = [
            'data' => $attributes
        ];
        $credentials = base64_encode($secretKey);
        $client = new Client(['base_uri' => 'https://api.paymongo.com/v1']);
        return $client->post('/' . $endpoint, [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => $data
        ]);
    }

    /**
     * @param $id
     * @return ResponseInterface
     */
    public function getPaymentIntent($id): ResponseInterface
    {
        return $this->getRequest('payment_intents', $id);
    }

    /**
     * @param $id
     * @return ResponseInterface
     */
    public function getSource($id): ResponseInterface
    {
        return $this->getRequest('sources', $id);
    }

    private function getRequest($action, $id): ResponseInterface
    {
        $secretKey = $this->getSecretKey();

        $credentials = base64_encode($secretKey);
        $client = new Client(['base_uri' => 'https://api.paymongo.com/v1']);

        $options = [];
        if ($this->clientKey !== null) {
            $options['query'] = [
                'client_key' => $this->clientKey
            ];
        }

        $options['headers'] = [
            'Authorization' => 'Basic ' . $credentials,
            'accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        return $client->get("/$action/" . $id, $options);
    }
}