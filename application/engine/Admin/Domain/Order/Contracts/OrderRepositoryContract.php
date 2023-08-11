<?php declare(strict_types=1);

namespace Andri\Engine\Admin\Domain\Order\Contracts;

interface OrderRepositoryContract {
    public function list(array $options);
    public function store(array $data, $cart, $origin = null, $destination = null, $shipping = null);
    public function detailByFields(array $conditions, bool $with_detail);
    public function detailItemByFields(array $condition);
    public function update(array $args, array $data);
    // public function updateDetail($object, array $data);

    // order origin
    public function originStore($invoiceNumber, array $data);
    public function originByInvoiceNumber($invoiceNumber);
    public function originUpdate($invoiceNumber, array $data);

    // order destination
    public function destinationStore($invoiceNumber, array $data);
    public function destinationByInvoiceNumber($invoiceNumber);
    public function destinationUpdate($invoiceNumber, array $data);

    // order payment
    public function paymentStore($invoiceNumber, array $data);
    public function paymentByInvoiceNumber($invoiceNumber);
    public function paymentUpdate($invoiceNumber, array $data);

    // order shipping
    public function shippingStore($invoiceNumber, array $data);
    public function shippingByInvoiceNumber($invoiceNumber);
    public function shippingUpdate($invoiceNumber, array $data);
}