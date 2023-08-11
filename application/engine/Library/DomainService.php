<?php
namespace Library;

use GuzzleHttp\Client;

class DomainService {

    private $authUserId;
    private $apiKey;
    private $client;

    public function __construct() {
        $this->authUserId = '603018';
        $this->apiKey = 'twttNQNg8n744sxkLnwp9nzbGEH3KHA6';
    }

    public function checkAvailability($domains, $tlds) {
        $this->client = new Client([
            'base_uri' => 'https://domaincheck.httpapi.com',
        ]);
        $queryParams = [
            'auth-userid' => $this->authUserId,
            'api-key' => $this->apiKey
        ];
        $queryParams['domain-name'] = $domains;
        $queryParams['tlds'] = $tlds;
        $resp = $this->httpRequest('GET', '/api/domains/available.json', null, $queryParams);
        return $resp;
    }

    public function register ($domainName, $tld, $customerId, $regContactId, $adminContactId, $techContactId, $billingContactId, $invoiceOption = 'NoInvoice', $autoRenew) {
        $this->client = new Client([
            'base_uri' => 'https://httpapi.com',
        ]);
        $queryParams = [
            'auth-userid' => $this->authUserId,
            'api-key' => $this->apiKey,
            'domain-name' => sprintf('%s.%s', $domainName, $tld),
            'years' => 1,
            'ns' => sprintf('ns1.%s.%s', $domainName, $tld),
            'ns' => sprintf('ns2.%s.%s', $domainName, $tld),
            'customer-id' => $customerId,
            'reg-contact-id' => $regContactId,
            'admin-contact-id' => $adminContactId,
            'tech-contact-id' => $techContactId,
            'billing-contact-id' => $billingContactId,
            'invoice-option' => $invoiceOption,
            'auto-renew' => 1
        ];
        $resp = $this->httpRequest('POST', '/api/domains/register.xml', null, $queryParams);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null, $queryParams = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
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
