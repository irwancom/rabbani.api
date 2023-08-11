<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 10th, 2020
 */

namespace Andri\Engine\Admin\Domain\Order\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Order\Contracts\OrderRepositoryContract;
use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;
use Andri\Engine\Client\Domain\Member\Contracts\MemberRepositoryContract;
use Andri\Engine\Client\Domain\MemberAddress\Contracts\MemberAddressRepositoryContract;

class HandlerOrder {
    private $orderRepo;
    private $productRepo;
    private $shipper;
    private $xendit;
    private $memberAddressRepo;
    private $memberRepo;

    public function __construct(
        OrderRepositoryContract $orderModel,
        ProductRepositoryContract $productModel,
        MemberRepositoryContract $memberModel,
        MemberAddressRepositoryContract $memberAddressModel,
        $shipper,
        $xendit
    ) {
        $this->orderRepo = $orderModel;
        $this->productRepo = $productModel;
        $this->memberRepo = $memberModel;
        $this->memberAddressRepo = $memberAddressModel;
        $this->shipper = $shipper;
        $this->xendit = $xendit;
    }
    
    public function getShippingRates(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationShippingRates($response))
            return $presenter->present($response);

        if (!$this->calculateCarts($response)) 
            return $presenter->present($response);

        $data = $response->incomingData;

        $result = $this->shipper->getDomesticRates($data['id_auth'], $data['origin']['area_id'], $data['destination']['area_id'], $data['total_length'], $data['total_width'], $data['total_height'], $data['total_weight'], $data['total_price'], $type = 0, $cod = 0, $order = 1, $originCoord = null, $destinationCoord = null);
        if ($result->status != 'success') {
            $response->addError('order', 'error.shipper.get_domestic_rates.error');
            return $presenter->present($response);
        }

        $response->data = $result->data;
        $presenter->present($response);
    }

    public function createWaitingPayment (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationShippingRates($response))
            return $presenter->present($response);

        if (!$this->validationCreateWaitingPayment($response))
            return $presenter->present($response);

        if (!$this->calculateCarts($response))
            return $presenter->present($response);

        $data = $response->incomingData;
        $invoiceNumber = generate_invoice_number();
        $data['invoice_number'] = $invoiceNumber;

        // final calculation before create invoice, include shipping rate (also insurance) and promotion or voucher code
        $data['final_amount'] = $this->calculate($data);
        
        $resultPayment = $this->xendit->createInvoice($data['id_auth'], $invoiceNumber, $data['member']['email'], sprintf('Payment to %s', $data['member']['name']), $data['final_amount']);

        if (!isset($resultPayment->id) || empty($resultPayment->id)) {
            $response->addError('order', 'error.xendit.create_invoice.error');
            return $presenter->present($response);
        }

        $data['payment']['xendit_id'] = $resultPayment->id;
        $data['payment']['xendit_expiry_date'] = date('Y-m-d H:i:s', strtotime($resultPayment->expiry_date));
        $data['payment']['xendit_status'] = $resultPayment->status;

        $cart = $data['cart'];

        $orderData = [
            'invoice_number' => $data['invoice_number'],
            'id_auth_user' => $data['id_auth_user'],
            'id_auth' => $data['id_auth'],
            'notes' => $data['notes'],
            'description' => $data['description'],
            'total_price' => $data['total_price'],
            'total_qty' => $data['total_qty'],
            'order_source' => $data['order_source'],
            'voucher_code' => $data['voucher_code'],
            'total_weight' => $data['total_weight'],
            'total_length' => $data['total_length'],
            'total_width'  => $data['total_width'],
            'total_height' => $data['total_height'],
            'status' => 'waiting_payment'
        ];

        $orderOrigin = $data['origin'];
        $orderDestination = $data['destination'];
        $orderShipping = $data['shipping'];
        $orderPayment = $data['payment'];

        $preProcess = $this->orderRepo->store($orderData, $cart, $orderOrigin, $orderDestination, $orderShipping, $orderPayment);
        if (!$preProcess) {
            $response->addError('order', 'error.create_order.error');
            return $presenter->present($response);
        }

        $order = $this->orderRepo->detailByFields(['invoice_number' => $invoiceNumber], true);
        $result = [
            'order' => $order,
            'payment' => $resultPayment
        ];
        $response->data = $result;
        $presenter->present($response);
    }

    public function receivePaymentCallback (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationPaymentCallback($response))
            return $presenter->presenter($response);

        $args = [
            'invoice_number' => $data['external_id']
        ];

        $orderData = [
            'status'            => 'open'
        ];

        $paymentData = [
            'xendit_status' => $data['status'],
            'xendit_paid_at' => date('Y-m-d H:i:s', strtotime($data['paid_at'])),
            'xendit_amount' => $data['amount'],
            'xendit_paid_amount' => $data['amount'],
            'xendit_channel' => $data['payment_channel'],
            'xendit_destination' => $data['payment_destination'],
            'xendit_fees_paid_amount' => $data['fees_paid_amount'],
            'xendit_adjusted_received_amount' => $data['adjusted_received_amount'],
            'xendit_currency' => $data['currency'],
            'xendit_callback' => json_encode($data),
        ];
        $result = $this->orderRepo->update($args, $orderData);
        if (!$result) {
            $response->addError('model', 'error.order.global.failed_to_update_order');
            return $presenter->present($response);
        }
        $result = $this->orderRepo->paymentUpdate($args['invoice_number'], $paymentData);
        if (!$result) {
            $response->addError('model', 'error.order.global.payment.failed_to_update_order');
            return $presenter->present($response);
        }

        $response->data = 'success';
        $presenter->present($response);
    }

    public function createDomesticOrderCreation (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationCreateDomesticOrderCreation($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $shipperCarts = $this->createCartsForShipper($order);

        $data = $response->incomingData;

        $result = $this->shipper->domesticOrderCreation($data['id_auth'], $order['origin']['area_id'], $order['destination']['area_id'], $order['total_length'], $order['total_width'], $order['total_height'], $order['total_weight'], $order['total_price'], $order['shipping']['shipper_rate_id'], $order['destination']['receiver_name'], $order['destination']['receiver_phone'], '', '', $order['origin']['address'], null, $order['destination']['address'], null, $shipperCarts, '', $order['shipping']['shipper_use_insurance'], null, null, 2, 0, null, null);
        
        if ($result->status != 'success') {
            $response->addError('order', 'error.shipper.domestic_order_creation.error');
            return $presenter->present($response);
        }

        $trackingResult = $this->shipper->getTrackingId($data['id_auth'], $result->data->id);
        if ($result->status != 'success') {
            $response->addError('order', 'error.shipper.domestic_order_creation.error');
            return $presenter->present($response);
        }

        $orderData['status'] = 'in_process';
        $shippingData['shipper_id'] = $result->data->id;
        $shippingData['shipper_tracking_id'] = $trackingResult->data->id;

        $args = [
            'id_auth' => $data['id_auth'],
            'invoice_number' => $data['invoice_number'],
        ];

        $result = $this->orderRepo->update($args, $orderData);
        if (!$result) {
            $response->addError('model', 'error.order.global.failed_to_update_order');
            return $presenter->present($response);
        }

        $result = $this->orderRepo->shippingUpdate($args['invoice_number'], $shippingData);
        if (!$result) {
            $response->addError('model', 'error.order.shipping.global.failed_to_update_order');
            return $presenter->present($response);
        }

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        $response->data = $order;
        $presenter->present($response);
    }

    public function acceptOrder(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationCreateDomesticOrderCreation($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $data = $response->incomingData;

        $data['status'] = 'in_process';
        $args = [
            'id_auth' => $data['id_auth'],
            'invoice_number' => $data['invoice_number'],
        ];

        $result = $this->orderRepo->update($args, $data);
        if (!$result) {
            $response->addError('model', 'error.order.failed_to_update_order');
            return $presenter->present($response);
        }

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        $response->data = $order;
        $presenter->present($response);
    }

    public function findPickupAgent (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationCreateDomesticOrderCreation($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $shipperResult = $this->shipper->getAgentsBySuburb($data['id_auth'], $order['origin']['suburb_id']);
        if ($shipperResult->status != 'success') {
            $response->addError('model', 'error.order.shipper.failed_to_find_pickup_agent');
            return $presenter->present($response);
        }

        $response->data = $shipperResult->data;
        $presenter->present($response);
    }

    public function requestPickup (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;
    
        if (!$this->validationRequestPickup($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $orderIds[] = $order['shipping']['shipper_tracking_id'];
        $shipperResult = $this->shipper->pickupRequest($data['id_auth'], $orderIds, $data['shipping']['shipper_pickup_date'], $data['shipping']['shipper_pickup_agent_id']);
        if ($shipperResult->status != 'success') {
            $response->addError('model', 'error.order.shipper.failed_to_request_pickup');
            return $presenter->present($response);
        }

        // $shipperResult = $this->shipper->generateAwbNumber($data['id_auth'], $order['invoice_number'], $order['shipping']['shipper_tracking_id']);

        $orderData['status'] = 'in_shipment';
        $result = $this->orderRepo->update(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], $orderData);
        if (!$result) {
            $response->addError('model', 'error.order.failed_to_update_order');
            return $presenter->present($response);
        }

        $result = $this->orderRepo->shippingUpdate($order['invoice_number'], $data['shipping']);
        if (!$result) {
            $response->addError('model', 'error.order.shipping.failed_to_update');
            return $presenter->present($response);
        }        

        $response->data = $shipperResult->data;
        $presenter->present($response);
    }

    public function updateAwbNumber (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationUpdateAwbNumber($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $data = $response->incomingData;

        $data['status'] = 'in_shipment';
        $args = [
            'id_auth' => $data['id_auth'],
            'invoice_number' => $data['invoice_number'],
        ];

        $result = $this->orderRepo->update($args, $data);
        if (!$result) {
            $response->addError('model', 'error.order.failed_to_update_order');
            return $presenter->present($response);
        }

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        $response->data = $order;
        $presenter->present($response);
    }

    public function done (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationCreateDomesticOrderCreation($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $data = $response->incomingData;

        $data['status'] = 'done';
        $args = [
            'id_auth' => $data['id_auth'],
            'invoice_number' => $data['invoice_number'],
        ];

        $result = $this->orderRepo->update($args, $data);
        if (!$result) {
            $response->addError('model', 'error.order.failed_to_update_order');
            return $presenter->present($response);
        }

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        $response->data = $order;
        $presenter->present($response);
    }

    public function cancel (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationCreateDomesticOrderCreation($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $data = $response->incomingData;

        $data['status'] = 'canceled';
        $args = [
            'id_auth' => $data['id_auth'],
            'invoice_number' => $data['invoice_number'],
        ];

        $result = $this->orderRepo->update($args, $data);
        if (!$result) {
            $response->addError('model', 'error.order.failed_to_update_order');
            return $presenter->present($response);
        }

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        $response->data = $order;
        $presenter->present($response);
    }

    public function track (array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validationCreateDomesticOrderCreation($response))
            return $presenter->present($response);

        $order = $this->orderRepo->detailByFields(['id_auth' => $data['id_auth'], 'invoice_number' => $data['invoice_number']], true);
        if (empty($order)) {
            $response->addError('order', 'error.shipper.order.not_found');
            return $presenter->present($response);
        }

        $shipperResult = $this->shipper->orderDetail ($data['id_auth'], $order['shipping']['shipper_tracking_id']);
        if ($shipperResult->status != 'success') {
            $response->addError('model', 'error.order.shipper.failed_to_request_order_detail');
            return $presenter->present($response);
        }
        $response->data = $shipperResult->data;
        $presenter->present($response);
    }


    public function validationShippingRates(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_auth'])) {
            $response->addError('order', 'error.order.id_auth.is_required');
            return false;
        }

        if (!isset($data['order_source']) && empty($data['order_source'])) {
            $response->addError('order', 'error.order.order_source.is_required');
            return false;
        }

        if (!isset($data['origin']['id_warehouse']) && empty($data['origin']['id_warehouse'])) {
            $response->addError('order', 'error.order.origin.id_warehouse.is_required');
            return false;
        }

        if (!isset($data['origin']['area_id']) && empty($data['origin']['area_id'])) {
            $response->addError('order', 'error.order.origin.area_id.is_required');
            return false;
        }

        if (!isset($data['destination']['area_id']) && empty($data['destination']['area_id'])) {
            $response->addError('order', 'error.order.destination.area_id.is_required');
            return false;
        }
                
        // product and product detail
        $carts = $data['cart'];
        $idx = 0;
        foreach ($data['cart'] as $cart) {
            $productDetail = (array)$this->productRepo->detailItemByFields(['id_product_detail' => $cart['id_product_detail']]);
            if (empty($productDetail)) {
                $response->addError('order', 'error.order.cart.product_detail.not_found');
                return false;
            }
            $product = (array)$this->productRepo->detailByFields(['id_product' => $productDetail['id_product']]);
            if (empty($product)) {
                $response->addError('order', 'error.order.cart.product.not_found');
                return false;
            }
            $product['product_detail'] = $productDetail;
            $data['cart'][$idx]['product'] = $product;
            $idx++;
        }

        // member and member address
        $memberAddress = (array)$this->memberAddressRepo->detailByFields(['id_member_address' => (int)$data['destination']['id_member_address']]);
        if (empty($memberAddress)) {
            $response->addError('order', 'error.order.member_address.not_found');
            return false;
        }

        $member = (array)$this->memberRepo->detailByFields(['id_member' => (int)$memberAddress['id_member']]);
        if (empty($member)) {
            $response->addError('order', 'error.order.member.not_found');
            return false;
        }
        $member['member_address'] = $memberAddress;
        $data['member'] = $member;

        $response->incomingData = $data;

        if ($response->hasError()) return false;
        return true;
    }

    public function validationCreateWaitingPayment(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['shipping']['shipper_rate_id'])) {
            $response->addError('order', 'error.order.shipping.rate_id.is_required');
            return false;
        }

        if (!isset($data['shipping']['shipper_name'])) {
            $response->addError('order', 'error.order.shipping.name.is_required');
            return false;
        }

        if (!isset($data['shipping']['shipper_rate_name'])) {
            $response->addError('order', 'error.order.shipping.rate_name.is_required');
        }

        if (!isset($data['shipping']['shipper_final_rate'])) {
            $response->addError('order', 'error.order.shipping.final_rate.is_required');
        }

        if (!isset($data['shipping']['shipper_insurance_rate'])) {
            $response->addError('order', 'error.order.shipping.insurance_rate.is_required');
        }

        if (!isset($data['shipping']['shipper_discount'])) {
            $response->addError('order', 'error.order.shipping.discount.is_required');
        }

        if (!isset($data['shipping']['shipper_min_day'])) {
            $response->addError('order', 'error.order.shipping.min_day.is_required');
        }

        if (!isset($data['shipping']['shipper_max_day'])) {
            $response->addError('order', 'error.order.shipping.max_day.is_required');
        }

        if (!isset($data['shipping']['shipper_pickup_agent'])) {
            $response->addError('order', 'error.order.shipping.pickup_agent.is_required');
        }

        if (!isset($data['shipping']['shipper_use_insurance'])) {
            $response->addError('order', 'error.order.shipping.use_insurance.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }

    public function validationPaymentCallback(Response $response) {
        $data = $response->incomingData;

        $args = [
            'invoice_number' => $data['external_id']
        ];
        $order = $this->orderRepo->detailByFields($args);
        
        if (empty($order)) {
            $response->addError('order', 'error.order.not_found');
            return false;
        }

        $payment = $this->orderRepo->paymentByInvoiceNumber($args['invoice_number']);
        if (empty($payment)) {
            $response->addError('order', 'error.order.payment.not_found');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }

    public function validationCreateDomesticOrderCreation(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_auth']))
            $response->addError('order', 'error.order.id_auth.is_required');


        if (!isset($data['invoice_number']))
            $response->addError('order', 'error.order.invoice_number.is_required');

        if ($response->hasError()) return false;
        return true;
    }

    public function validationUpdateAwbNumber(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_auth']))
            $response->addError('order', 'error.order.id_auth.is_required');


        if (!isset($data['invoice_number']))
            $response->addError('order', 'error.order.invoice_number.is_required');

        if (!isset($data['no_awb']))
            $response->addError('order', 'error.order.no_awb.is_required');

        if ($response->hasError()) return false;
        return true;
    }

    public function validationRequestPickup(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_auth']))
            $response->addError('order', 'error.order.id_auth.is_required');

        if (!isset($data['invoice_number']))
            $response->addError('order', 'error.order.invoice_number.is_required');

        if (!isset($data['shipping']['shipper_pickup_agent_id']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_agent_id.is_required');

        if (!isset($data['shipping']['shipper_pickup_agent_name']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_agent_name.is_required');

        if (!isset($data['shipping']['shipper_pickup_city_id']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_city_id.is_required');

        if (!isset($data['shipping']['shipper_pickup_city_name']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_city_name.is_required');

        if (!isset($data['shipping']['shipper_pickup_location_latitude']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_location_latitude.is_required');

        if (!isset($data['shipping']['shipper_pickup_location_longitude']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_location_longitude.is_required');

        if (!isset($data['shipping']['shipper_pickup_contact_name']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_contact_name.is_required');

        if (!isset($data['shipping']['shipper_pickup_contact_phone']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_contact_phone.is_required');

        if (!isset($data['shipping']['shipper_pickup_date']))
            $response->addError('order', 'error.order.shipping.shipper_pickup_date.is_required');
        
        if ($response->hasError()) return false;
        return true;
    }

    public function calculateCarts (Response $response) {
        $data = $response->incomingData;

        $carts = $data['cart'];
        $totalPrice = 0;
        $totalQty = 0;
        $totalWeight = 0; // berat
        $totalHeight = 0; // tinggi
        $totalLength = 0; // panjang
        $totalWidth = 0; // lebar

        $idx = 0 ;
        foreach ($carts as $cart) {
            $qty = (int)$cart['qty'];
            $price = (int)$cart['price'];
            $weight = (int)$cart['product_weight'];
            $height = (int)$cart['product_height'];
            $length = (int)$cart['product_length'];
            $width = (int)$cart['product_weight'];

            // calcualte
            $totalQty += $qty;
            $totalPrice += $qty*$price;
            $totalWeight += $weight;
            $totalHeight += $height;
            $totalLength += $length;
            $totalWidth += $width;
            $data['cart'][$idx]['final_amount'] = $qty*$price;
        }

        $data['total_qty'] = $totalQty;
        $data['total_price'] = $totalPrice;
        $data['total_height'] = $totalHeight;
        $data['total_weight'] = $totalWeight;
        $data['total_width'] = $totalWidth;
        $data['total_length'] = $totalLength;

        $response->incomingData = $data;

        if ($response->hasError()) return false;
        return true;
    }

    public function createCartsForShipper ($order) {
        $carts = [];
        foreach ($order['details'] as $cart) {
            $cart = (array)$cart;
            $product = [
                'name' => $cart['product_name'],
                'qty' => $cart['qty'],
                'value' => $cart['price']
            ];
            $carts[] = $product;
        }
        return $carts;
    }

    // calculate all the possibility of payment, like order products, shipping, promo and discount
    public function calculate ($data) {
        $totalPrice = $data['total_price'];
        $totalPrice += (int)$data['shipping']['shipper_final_rate'];
        
        if (!empty($data['shipping']['use_insurance'])) {
            $totalPrice += (int)$data['shipping']['insurance_rate'];
        }

        return $totalPrice;
    }

}
