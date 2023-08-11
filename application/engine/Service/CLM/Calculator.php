<?php
namespace Service\CLM;

use Service\Delivery;
use Library\JNEService;
use Library\TripayGateway;
use Service\CLM\Handler\DiscountHandler;

class Calculator {

    const DISCOUNT_TYPE_VOUCHER = 'voucher';
    const DISCOUNT_TYPE_FLASHSALE = 'flashsale';
    const DISCOUNT_TYPE_PRODUCT_DETAIL = 'discount_product_detail';
    const DISCOUNT_TYPE_PRODUCT = 'product';

    private $discountHandler;

    private $price = 0;
    private $total = 0;
    private $qty = 0;
    private $discountValue = null;
    private $discountType = null;
    private $discountMethod = null;
    private $discountSource = null;
    private $discountAmount = 0;
    private $discountStartTime = null;
    private $discountEndTime = null;

    private $userAddress;
    private $shipmentCode;
    private $shipmentOptions;
    private $shipment;
    private $paymentMethodCode;
    private $paymentMethodOptions;
    private $paymentMethod;

    private $totalQty = 0;
    private $totalWeight = 0;
    private $shoppingPrice = 0;
    private $shippingCost = 0;
    private $totalDiscount = 0; // termasuk voucher code
    private $finalPrice = 0;
    private $paymentFeeMerchant = 0;
    private $paymentFeeCustomer = 0;
    private $paymentFeeTotal = 0;
    private $paymentAmount = 0;
    private $voucher = null;
    private $voucherDiscountAmount = null;
    private $discountBooks = [];

    private $calculateForReseller = false;

    private $productDetails = [];
	
	public function __construct ($repository, $useTripay = true, $user = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
        $this->discountHandler = new DiscountHandler($this->repository);

        if ($useTripay) {
            $tripay = new TripayGateway;
            // $tripay->setEnv('development');
            $tripay->setEnv('production');
            $tripay->setMerchantCode('T1441');
            $tripay->setApiKey('fQGB4tyeCghSF844Um01J6mEDWnH1KqlvB0LHa8N');
            $tripay->setPrivateKey('TLMQO-Y0eMk-Si4DN-V28zC-JU1UK');
            $tripayResult = $tripay->channelPembayaran();
            $tripayResult = $tripayResult->data;
            if (!empty($user) && empty($user->phone)) {
                $options = [];
                foreach ($tripayResult as $tripay) {
                    if ($tripay->group != 'E-Wallet') {
                        $options[] = $tripay;       
                    }
                }
                $this->setPaymentMethodOptions($options);
            } else {
                $this->setPaymentMethodOptions($tripayResult);    
            }
        }
	}

    public function addDiscountBook ($type, $discountAmount, $ucd, $extras = []) {
        // ucd used for unique key each discounts so it wouldn't be duplicate
        $existsDiscount = array_search($ucd, array_column($this->discountBooks, 'ucd'));
        if ($existsDiscount === false) {
            if ($type == self::DISCOUNT_TYPE_VOUCHER) {
                $this->discountBooks[] = [
                    'type' => $type,
                    'ucd' => $ucd,
                    'voucher' => $this->voucher,
                    'discount_amount' => $discountAmount,
                ];
            } else if ($type == self::DISCOUNT_TYPE_FLASHSALE) {
                $this->discountBooks[] = [
                    'type' => $type,
                    'ucd' => $ucd,
                    'flashsale' => $extras['flashsale'],
                    'discount_amount' => $discountAmount,
                ];
            } else if ($type == self::DISCOUNT_TYPE_PRODUCT_DETAIL) {
                $this->discountBooks[] = [
                    'type' => $type,
                    'ucd' => $ucd,
                    'product_detail' => $extras['product_detail'],
                    'discount_amount' => $discountAmount,
                ];
            } else if ($type == self::DISCOUNT_TYPE_PRODUCT) {
                $this->discountBooks[] = [
                    'type' => $type,
                    'ucd' => $ucd,
                    'product' => $extras['product'],
                    'discount_amount' => $discountAmount,
                ];
            }   
        }
    }

    public function setShoppingPrice ($shoppingPrice) {
        $this->shoppingPrice = $shoppingPrice;
    }

    public function getShoppingPrice () {
        return $this->shoppingPrice;
    }

    public function setTotalDiscount ($totalDiscount) {
        $this->totalDiscount = $totalDiscount;
    }

    public function getTotalDiscount () {
        return $this->totalDiscount;
    }

    public function setShippingCost ($shippingCost) {
        $this->shippingCost = $shippingCost;
    }

    public function getShippingCost () {
        return $this->shippingCost;
    }

    public function setPaymentAmount ($paymentAmount) {
        $this->paymentAmount = $paymentAmount;
    }

    public function getPaymentAmount () {
        return $this->paymentAmount;
    }

    public function setFinalPrice ($finalPrice) {
        $this->finalPrice = $finalPrice;
    }

    public function getFinalPrice () {
        return $this->finalPrice;
    }

    public function setCalculateForReseller ($calculateForReseller) {
        $this->calculateForReseller = $calculateForReseller;
    }

    public function isCalculateForReseller () {
        return $this->calculateForReseller;
    }

    public function setShipmentCode ($shipmentCode) {
        $this->shipmentCode = $shipmentCode;
    }

    public function getShipmentCode () {
        return $this->shipmentCode;
    }

    public function setUserAddress ($userAddress) {
        $this->userAddress = $userAddress;
    }

    /**
     * Shipment yang dipilih oleh user
     **/
    public function setShipment ($shipment) {
        $this->shipment = $shipment;
    }

    public function getShipment () {
        return $this->shipment;
    }

    /**
     * Shipment options untuk user pilih code
     **/
    public function setShipmentOptions ($shipmentOptions) {
        $this->shipmentOptions = $shipmentOptions;
    }

    public function getShipmentOptions () {
        return $this->shipmentOptions;
    }

    public function getUserAddress () {
        return $this->userAddress;
    }

    public function setPaymentMethodCode ($paymentMethodCode) {
        $this->paymentMethodCode = $paymentMethodCode;
        $paymentMethodOptions = $this->getPaymentMethodOptions();
        foreach ($paymentMethodOptions as $options) {
            if ($options->code == $this->getPaymentMethodCode()) {
                $this->setPaymentMethod($options);
                $feeMerchant = ($this->paymentAmount * $options->fee_merchant->percent / 100) + $options->fee_merchant->flat;
                $feeCustomer = ($this->paymentAmount * $options->fee_customer->percent / 100) + $options->fee_customer->flat;
                $totalFee = ($this->paymentAmount * $options->total_fee->percent / 100) + $options->total_fee->flat;

                $this->paymentFeeMerchant = ceil($feeMerchant);
                $this->paymentFeeCustomer = ceil($feeCustomer);
                $this->paymentFeeTotal = ceil($totalFee);
                $this->paymentAmount += ceil($feeCustomer);
            }
        }
    }

    public function getPaymentMethodCode () {
        return $this->paymentMethodCode;
    }

    public function getPaymentMethodOptions () {
        return $this->paymentMethodOptions;
    }

    public function setPaymentMethodOptions ($paymentMethodOptions) {
        $this->paymentMethodOptions = $paymentMethodOptions;
    }

    public function getPaymentMethod () {
        return $this->paymentMethod;
    }

    public function setPaymentMethod ($paymentMethod) {
        $this->paymentMethod = $paymentMethod;
    }

    public function addProductDetail ($productDetail, $qty, $isCheckout = true) {
        if($isCheckout){
            $result = $this->getDetails($productDetail, $qty);
            $this->shoppingPrice += $result->subtotal;
            $this->totalDiscount += $result->discount_amount;
            $this->finalPrice += $result->total;
            $this->paymentAmount += $result->total;
            $this->weight = $result->weight;
            $this->totalWeight += $result->total_weight;
            $this->totalQty += $qty;
        }
    }

    /**
     * Same with getDetails(), but for product
     **/
    public function getDetailsForProduct ($product, $qty) {
        $this->price = $product->price;
        $this->discountType = null;
        $this->discountMethod = null;
        $this->discountValue = null;
        $this->discountAmount = 0;
        $this->discountSource = null;
        $this->discountStartTime = null;
        $this->discountEndTime = null;

        if ($discount = $this->getDiscountProduct($product->id_product, $qty)) {
            $this->discountType = $discount->discount_type;
            $this->discountValue = $discount->discount_value;
            $this->discountSource = $discount->discount_source;
            $this->discountStartTime = (isset($discount->start_time)) ? $discount->start_time : null;
            $this->discountEndTime = (isset($discount->start_time)) ? $discount->end_time : null;
        }
        $result = new \stdClass;
        $result->item_price = $this->price;
        $result->qty = $qty;
        $result->weight = $product->weight;
        $result->subtotal = (int) $this->price * $qty;
        $result->discount_type = $this->discountType;
        $result->discount_value = $this->discountValue;
        
        if ($this->discountType == 1) {
            $this->discountAmount = $this->discountValue;
            $this->discountMethod = 'amount';
        } else if ($this->discountType == 2) {
            $this->discountAmount = $result->subtotal * $this->discountValue / 100;
            $this->discountMethod = 'percentage';
        }
        
        $result->discount_method = $this->discountMethod;
        $result->discount_amount = -1*$this->discountAmount;
        $result->discount_source = $this->discountSource;
        $result->discount_start_time = $this->discountStartTime;
        $result->discount_end_time = $this->discountEndTime;
        $result->total = $result->subtotal + $result->discount_amount;
        return $result;
    }

    /**
     * Get Details Calculator for Product Detail
     */
    public function getDetails ($productDetail, $qty) {
        $this->price = $productDetail->price;
        $this->discountType = null;
        $this->discountMethod = null;
        $this->discountValue = null;
        $this->discountAmount = 0;
        $this->discountSource = null;

        $action = $this->checkDiscount($productDetail, $qty);
        if ($this->isCalculateForReseller()) {
            if ($productDetail->reseller_discount_percentage_amount > 0) {
                $resellerDiscount = intval($this->price * $productDetail->reseller_discount_percentage_amount/100);
                $this->discountValue += $productDetail->reseller_discount_percentage_amount;
                $this->discountMethod = 'percentage';
                $this->discountAmount += $resellerDiscount * $qty;
            }

            $resellerDiscountPercentage = 25;
            $fixResellerDiscount = intval($resellerDiscountPercentage*$this->price/100);
            $this->discountValue += $resellerDiscountPercentage;
            $this->discountMethod = 'percentage';
            $this->discountAmount += $fixResellerDiscount * $qty;
        }

        $result = new \stdClass;
        $result->item_price = $this->price;
        $result->qty = $qty;
        $result->weight = $productDetail->weight;
        $result->total_weight = $productDetail->weight * $qty;
        $result->subtotal = (int) $this->price * $qty;
        $result->discount_type = $this->discountType;
        $result->discount_value = $this->discountValue;
        $result->discount_start_time = $this->discountStartTime;
        $result->discount_end_time = $this->discountEndTime;

        if ($this->discountType == 1) {
            $this->discountAmount = $this->discountValue;
            $this->discountMethod = 'amount';
        } else if ($this->discountType == 2) {
            $this->discountAmount = $result->subtotal * $this->discountValue / 100;
            $this->discountMethod = 'percentage';
        }
        
        $result->discount_method = $this->discountMethod;
        $result->discount_amount = -1*$this->discountAmount;
        $result->discount_source = $this->discountSource;
        $result->total = $result->subtotal + $result->discount_amount;
        $result->voucher = $this->voucher;
        $result->voucher_discount_amount = $this->voucherDiscountAmount;
        return $result;
    }

    public function checkCart () {
        $result = new \stdClass;
        $result->total_qty = $this->totalQty;
        $result->total_weight = $this->totalWeight;
        $result->shopping_price = $this->shoppingPrice;
        $result->voucher_discount_amount = $this->voucherDiscountAmount;
        $result->total_discount = $this->totalDiscount;
        $result->final_price = $this->finalPrice;
        $result->shipping_cost = $this->shippingCost;
        $result->payment_fee_merchant = $this->paymentFeeMerchant;
        $result->payment_fee_customer = $this->paymentFeeCustomer;
        $result->payment_fee_total = $this->paymentFeeTotal;
        $result->payment_amount = $this->paymentAmount;
        $result->discount_books = $this->discountBooks;
        return $result;
    }

    public function checkDiscount($productDetail, $qty) {
        if ($discount = $this->getFlashsale($productDetail, $qty)) {
            $this->discountType = $discount->discount_type;
            $this->discountValue = $discount->discount_value;
            $this->discountStartTime = $discount->start_time;
            $this->discountEndTime = $discount->end_time;
            $this->discountSource = 'flashsale';
            $extras = [];
            $ucd = 'flashsale-'.$discount->id_flash_sale;
            $extras['flashsale'] = $discount;
            $this->addDiscountBook(self::DISCOUNT_TYPE_FLASHSALE, $this->discountValue, $ucd, $extras);
            return true;
        }

        if ($discount = $this->getDiscountProductDetail($productDetail, $qty)) {
            $this->discountType = $discount->discount_type;
            $this->discountValue = $discount->discount_value;
            $this->discountSource = 'discount_product_detail';
            $extras = [];
            $ucd = 'discount_product_detail-'.$discount->id;
            $extras['product_detail'] = $discount;
            $this->addDiscountBook(self::DISCOUNT_TYPE_PRODUCT_DETAIL, $this->discountValue, $extras);
            return true;
        }

        //include discount main product//
        if ($discount = $this->getDiscountProduct($productDetail->id_product, $qty)) {
            if($discount->discount_source=='discount_product'){
                $this->discountType = $discount->discount_type;
                $this->discountValue = $discount->discount_value;
                $this->discountSource = $discount->discount_source;
                $extras = [];
                $ucd = 'discount_product-'.$discount->id;
                $extras['product'] = $discount;
                $this->addDiscountBook(self::DISCOUNT_TYPE_PRODUCT, $this->discountValue, $ucd, $extras);
                return true;
            }
        }

        return false;
    }

    public function getFlashsale ($productDetail, $qty) {
        $filters = [
            'id_product_detail' => $productDetail->id_product_detail,
            'start_time <=' => date('Y-m-d H:i:s'),
            'end_time >=' => date('Y-m-d H:i:s'),
            'min_qty <=' => $qty,
            'max_qty >=' => $qty
        ];
        $findFlashsale = $this->repository->findOne('flash_sales', $filters);
        return $findFlashsale;
    }

    public function getDiscountProduct ($idProduct, $qty) {
        $filters = [
            'product_details.id_product' => $idProduct,
            'flash_sales.start_time <=' => date('Y-m-d H:i:s'),
            'flash_sales.end_time >=' => date('Y-m-d H:i:s'),
            'flash_sales.min_qty <=' => $qty,
            'flash_sales.max_qty >=' => $qty
        ];
        $join = [
            'product_details' => [
                'type' => 'left',
                'value' => 'flash_sales.id_product_detail = product_details.id_product_detail'
            ]
        ];
        $findFlashsale = $this->repository->findOne('flash_sales', $filters, null, $join);
        if (!empty($findFlashsale)) {
            $findFlashsale->discount_source = 'flashsale';
            return $findFlashsale;
        }

        $findDiscountProduct = $this->discountHandler->getDiscountProduct(['current_time' => date('Y-m-d H:i:s'), 'id_product' => $idProduct]);
        if (!empty($findDiscountProduct->data)) {
            $findDiscountProduct->data->discount_source = 'discount_product';
        }
        return $findDiscountProduct->data;
    }

    public function getDiscountProductDetail ($productDetail, $qty) {
        $findDiscountProductDetail = $this->discountHandler->getDiscountProductDetail(['current_time' => date('Y-m-d H:i:s'), 'id_product_detail' => $productDetail->id_product_detail]);
        return $findDiscountProductDetail->data;
    }

    public function getShipmentDetails () {
        if ($this->totalWeight <= 0) {
            $this->delivery->addError(400, 'Weight is required');
            return $this->delivery;
        }

        if (empty($this->userAddress)) {
            $this->delivery->addError(400, 'User address is required');
            return $this->delivery;
        }

        $jneFrom = 'BDO10000';

        $jneService = new JNEService;
        $jneService->setEnv('production');
        // total weight harus dibikin ke gram, dibagi 1000
        $result = $jneService->getTariff($jneFrom, $this->userAddress->city_code, intval($this->totalWeight/1000));
        $jneCodes = [
        //     'YES', 'REG', 'OKE'
        ];
        $result = $result['price'];
        $availableOptions = [];
        foreach ($result as $key => $value) {
            // if (in_array($value['service_display'], $jneCodes)) {
                if ($this->shipmentCode == $value['service_display']) {
                    $this->shippingCost = (int)$value['price'];
                    $this->finalPrice += $this->shippingCost;
                    $this->paymentAmount += $this->shippingCost;
                    $this->setShipment($value);
                }
                $availableOptions[] = $value;
            // }
        }

        $this->setShipmentOptions($availableOptions);
        $result = new \stdClass;
        $result->shipment_options = $availableOptions;
        $result->shipment = $this->shipment;
        $this->delivery->data = $availableOptions;
        return $this->delivery;
    }

    public function setVoucher ($voucher, $cart = null) {
        if (empty($voucher)) {
            return null;
        }

        if (isset($voucher->voucher_products) && !empty($voucher->voucher_products)) {
            $voucherProducts = $voucher->voucher_products;
            $allowedProductIds = [];
            foreach ($voucherProducts as $vp) {
                $allowedProductIds[] = $vp->id_product;
            }
            $myCart = $cart->cart;
            foreach ($myCart as $mine) {
                $product = $mine->product;
                if (!in_array($product->id_product, $allowedProductIds)) {
                    $this->delivery->addError(400, sprintf('Product %s is not allowed to use this voucher', $product->product_name));
                    return $this->delivery;
                }
            }
        }

        // belum ada pengaturan untuk voucher products
        $currentCheckoutAmount = $this->shoppingPrice - $this->discountAmount;
        if ($currentCheckoutAmount < $voucher->min_shopping_amount) {
            $this->delivery->addError(400, 'Checkout amount less than '.toRupiahFormat($voucher->min_shopping_amount));
            return $this->delivery;
        }

        $discountAmount = 0;
        if ($voucher->discount_type == 'percentage') {
            $discountAmount = $currentCheckoutAmount * $voucher->discount_value / 100;
        } else if ($voucher->discount_type == 'amount') {
            $discountAmount = $voucher->discount_value;
        }

        if ($discountAmount > $voucher->max_discount_amount) {
            $discountAmount = $voucher->max_discount_amount;
        }

        $discountAmount *= -1;
        $this->voucher = $voucher;
        $this->voucherDiscountAmount = $discountAmount;
        $this->totalDiscount += $discountAmount;
        $this->finalPrice += $discountAmount;
        $this->paymentAmount += $discountAmount;

        $ucd = 'voucher-'.$voucher->id;
        $this->addDiscountBook(self::DISCOUNT_TYPE_VOUCHER, $discountAmount, $ucd);
    }

    public function getVoucher () {
        return $this->voucher;
    }

}