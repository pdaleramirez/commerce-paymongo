<?php
namespace pdaleramirez\commercepaymongo\services;

use craft\base\Component;
use GuzzleHttp\Client;
use yii\web\Response;

class Payment extends Component
{
    private string $secretKey;

    public function setSecretKey($secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param $endpoint
     * @param $attributes
     * @return \Psr\Http\Message\ResponseInterface
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
        $response = $client->post('/' . $endpoint, [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => $data
        ]);

        return $response;
    }
}