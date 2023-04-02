<?php
namespace pdaleramirez\commercepaymongo\services;

use craft\base\Component;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use yii\web\Response;

class Payment extends Component
{
    private string $secretKey;
    private string $clientKey;

    public function setSecretKey($secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    public function setClientKey($clientKey): void
    {
        $this->clientKey = $clientKey;
    }

    /**
     * @param $endpoint
     * @param $attributes
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function payMongoRequest($endpoint, $attributes)
    {
        $secretKey = $this->secretKey;

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentIntent($id): ResponseInterface
    {
        $secretKey = $this->secretKey;

        $credentials = base64_encode($secretKey);
        $client = new Client(['base_uri' => 'https://api.paymongo.com/v1']);

        return $client->get('/payment_intents/' . $id, [
            'query' => [
                'client_key' => $this->clientKey
            ],
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);
    }
}