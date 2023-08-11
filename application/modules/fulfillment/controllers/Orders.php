<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Fulfillment\FulfillmentOrderHandler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class Orders extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function accept_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $transactionId = $this->input->post('salesorder_id');
        if (empty($transactionId)) {
            $this->delivery->addError(400, 'Transaction ID should not be empty');
            $this->response($this->delivery->format());
        }

        $fulfillmentOrderHandler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $fulfillmentOrderHandler->saveOrder($transactionId);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $fulfillmentOrderHandler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $fulfillmentOrderHandler->getOrders($this->input->get());

        $this->response($result->format(), $result->getStatusCode());        
    }

    public function export_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $handler->getOrders($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $columns = getExcelColumns();
        $idxRow = 1;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Fulfilled Orders');

        foreach(range('A','N') as $columnID) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($columnID)
                ->setAutoSize(true);
        }

        $spreadsheet->getActiveSheet()
            ->getStyle('A1:N2')
            ->getFont()
            ->setBold( true );

        $spreadsheet->getActiveSheet()
            ->getStyle('A1:N1')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()
            ->getStyle('A1:N2')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $spreadsheet->getActiveSheet()
            ->getStyle('L')
            ->getNumberFormat()
            ->setFormatCode('"Rp" #');

        $spreadsheet->getActiveSheet()
            ->getStyle('N')
            ->getNumberFormat()
            ->setFormatCode('"Rp" #');


        $sheet->setCellValue('A'.$idxRow, 'FULFILLMENT ORDERS');
        $spreadsheet->getActiveSheet()->mergeCells('A1:N1');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(0.50, 'in');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(0.25, 'in');
        $idxRow += 1;

        $sheet->setCellValue('A'.$idxRow, 'No');
        $sheet->setCellValue('B'.$idxRow, 'No Order');
        $sheet->setCellValue('C'.$idxRow, 'No Resi');
        $sheet->setCellValue('D'.$idxRow, 'Date');
        $sheet->setCellValue('E'.$idxRow, 'Customer');
        $sheet->setCellValue('F'.$idxRow, 'Source Name');
        $sheet->setCellValue('G'.$idxRow, 'Courier');
        $sheet->setCellValue('H'.$idxRow, 'Shipping');
        $sheet->setCellValue('I'.$idxRow, 'Payment');
        $sheet->setCellValue('J'.$idxRow, 'Item Code');
        $sheet->setCellValue('K'.$idxRow, 'Item Name');
        $sheet->setCellValue('L'.$idxRow, 'Unit Price');
        $sheet->setCellValue('M'.$idxRow, 'Qty');
        $sheet->setCellValue('N'.$idxRow, 'Subtotal');
        $idxRow += 1;

        foreach ($result->data as $group) {
            foreach ($group['transaction_items'] as $transaction) {
                $sheet->setCellValue('A'.$idxRow, $idxRow - 2);
                $sheet->setCellValue('B'.$idxRow, $group['salesorder_no']);
                $sheet->setCellValue('C'.$idxRow, $group['tracking_number']);
                $sheet->setCellValue('D'.$idxRow, date('d/m/Y', strtotime($group['transaction_date'])));
                $sheet->setCellValue('E'.$idxRow, $group['customer_name']);
                $sheet->setCellValue('F'.$idxRow, $group['source_name']);
                $sheet->setCellValue('G'.$idxRow, $group['courier']);
                $sheet->setCellValue('H'.$idxRow, $group['shipping_cost']);
                $sheet->setCellValue('I'.$idxRow, $group['payment_method']);
                $sheet->setCellValue('J'.$idxRow, $transaction['item_code']);
                $sheet->setCellValue('K'.$idxRow, $transaction['item_name']);
                $sheet->setCellValue('L'.$idxRow, $transaction['price']);
                $sheet->setCellValue('M'.$idxRow, $transaction['qty']);
                $sheet->setCellValue('N'.$idxRow, intval($transaction['price']) * intval($transaction['qty']));
                $idxRow++;
            }
        }

        try {
            $filename = uniqid().'xlsx';
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header(sprintf('Content-Disposition: attachment; filename="%s.xlsx"', $filename));
            $writer = new Xlsx($spreadsheet);
            $writer->save("php://output");
            die();
        } catch (\Exception $e) {
            $this->delivery->addErrors(500, $e->getMessage());
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }
    }

    public function delete_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $handler->delete($this->input->post('id'));
        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_batch_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $salesOrderIds = $this->input->post('ids');
        if (!is_array($salesOrderIds)) {
            $this->delivery->addError(422, 'Need one or more existing fulfilled orders.');
            return $this->delivery;
		}

        $handler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $handler->deleteBatch($salesOrderIds);
        $this->response($result->format(), $result->getStatusCode());
    }
}
