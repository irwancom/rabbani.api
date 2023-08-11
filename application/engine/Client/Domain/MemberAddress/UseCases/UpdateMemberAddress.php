<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 8th, 2020
 */

namespace Andri\Engine\Client\Domain\MemberAddress\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Client\Domain\MemberAddress\Contracts\MemberAddressRepositoryContract;

class UpdateMemberAddress {

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
        $memberAddress = $this->checkMemberAddress((int)$data['id_auth'], (int) $data['user_id'], (int)$data['id']);
        if (!$memberAddress) {
            $response->addError('member_address', 'error.member_address.not_found');
            return $presenter->present($response); 
        }

        $result = $this->memberAddressRepo->update($memberAddress, $data);
        if ($result) $result = $memberAddress->id;
        
        $memberAddress = $this->memberAddressRepo->detailBy('id', $memberAddress->id);
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

        if (!isset($data['id_auth'])) {
            $response->addError('model', 'error.id_auth.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Check is member address already stored
     * 
     * @param int $id_user
     * @param int $id_member_address
     * @return object
     */
    public function checkMemberAddress(int $id_auth, int $id_member, int $id_member_address) {

        $fields = [
            // 'id_auth'       => $id_auth,
            'user_id'         => $id_member,
            'id'   => $id_member_address
        ];

        $memberAddress = $this->memberAddressRepo->detailByFields($fields);
        return $memberAddress;
    }

}