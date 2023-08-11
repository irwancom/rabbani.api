<?php // this class is duplicate of Tripaypayment.php
namespace Library;

use GuzzleHttp\Client;

class CPanelService {

    private $client;
    private $token;
    private $username;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://serverx1314.extremhost.net:2087',
        ]);
        $this->token = 'Z4LYQPILNZGYF8SSGBFPDP3UCT4L4IR2';
        $this->username = 'qubisco';
    }

    public function createCPanelAccount($username, $domain, $password) {
        $queryParams = [
            'api.version' => 1,
            'username' => $username,
            'domain' => $domain,
            'password' => $password
        ];
        $resp = $this->httpRequest('GET', '/cpsess'.generateRandomDigit(10).'/json-api/createacct', null, $queryParams);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null, $queryParams = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Authorization' => sprintf('whm %s:%s', $this->username, $this->token)
                ],
                'query' => $queryParams
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
        }
        return $resp;
    }

}
