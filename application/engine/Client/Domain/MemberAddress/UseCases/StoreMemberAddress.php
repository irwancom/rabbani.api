<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Client\Domain\MemberAddress\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Client\Domain\MemberAddress\Contracts\MemberAddressRepositoryContract;

class StoreMemberAddress {
    private $memberAddressRepo;

    public function __construct(MemberAddressRepositoryContract $memberAddressModel) {
        $this->memberAddressRepo = $memberAddressModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $data = $response->incomingData;
        
        $result = $this->memberAddressRepo->store($data);
        
        if (!$result) return $response->addError('model', $result['message']);
        
        $memberAddress = $this->memberAddressRepo->detailBy('id', $result);
        $response->data = $result ? $memberAddress : null;
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

        if (!key_exists('user_id', $data) || strlen((string)$data['user_id']) === 0) {
            $response->addError('member_address_user_id', 'error.member_address.user_id.is_required');
        }

        if (!key_exists('received_name', $data) || strlen($data['received_name']) === 0) {
            $response->addError('received_name', 'error.member_address.name.is_required');
        }

        if (!key_exists('phone_number', $data) || strlen($data['phone_number']) === 0) {
            $response->addError('phone_number', 'error.member_address.name.is_required');
        }

        if (!key_exists('city_code', $data) || strlen($data['city_code']) === 0) {
            $response->addError('city_code', 'error.member_address.city_code.is_required');
        }

        if (!key_exists('city_name', $data) || strlen($data['city_name']) === 0) {
            $response->addError('city_name', 'error.member_address.city_name.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }


}