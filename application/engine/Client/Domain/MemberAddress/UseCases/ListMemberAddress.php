<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Client\Domain\MemberAddress\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\RequestExtraction;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Client\Domain\MemberAddress\Contracts\MemberAddressRepositoryContract;


class ListMemberAddress {
    private $memberAddressRepo;

    public function __construct(MemberAddressRepositoryContract $memberAddressModel) {
        $this->memberAddressRepo = $memberAddressModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        $options = self::queryExtraction($options, $this->memberAddressRepo::TABLE);
        $response = new Response;
        
        $response->incomingData = $options;

        if (!$this->validation($response)) 
            return $presenter->present($response);
        
        $data = $this->memberAddressRepo->list($options);
        $totalItem = $this->memberAddressRepo->totalItem($options);
        $totalPage = ceil($totalItem/$options['perPage']);

        $response->data = [
            'data'      => $data,
            'totalItem' => $totalItem,
            'totalPage' => $totalPage,
            'page'      => $options['page'],
            'perPage'   => $options['perPage']
        ];
        $presenter->present($response);
    }


    /**
     * Validation
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return bool
     */
    public function validation(Response $response) {
        $data = $response->incomingData;

        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Query Extraction
     * 
     * @param array $options
     */
    public static function queryExtraction(array $options, $alias = null) {
        $query = RequestExtraction::default($options);
        
        if (RequestExtraction::check('status', $options)) {
            $query['status'] = $options['status'];
        }

        if (RequestExtraction::check('id_auth', $options)) {
            $query['id_auth'] = $options['id_auth'];
        }

        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }
        if ($alias) {
            foreach($options as $key => $value) {
                if (!in_array($key, ['q', 'sorted', 'page', 'perPage'])) {
                    $query["{$alias}.{$key}"] = $value;
                } else {
                    $query[$key] = $value;
                }
            }
        }
        return $query;
    }

}