<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Poll\PollVoteHandler;
use \libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Vote extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollVoteHandler($this->MainModel, $auth->data);
        $result = $handler->getVoteTransactions($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function approve_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollVoteHandler($this->MainModel, $auth->data);
        $result = $handler->getVoteTransaction(['id' => $id]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $voteTransaction = $result->data;
        if (empty($voteTransaction)) {
            $result->addError(400, 'Vote transaction is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $action = $handler->approveVoteTransaction($voteTransaction);
        $this->response($action->format(), $action->getStatusCode());
    }

    public function export_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollVoteHandler($this->MainModel, $auth->data);
        $result = $handler->getVoteTransactions($filters, false);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $columns = getExcelColumns();
        $idxRow = 1;
        $idxColumn = 0;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Daftar Calon DPR');

        $sheet->setCellValue('A'.$idxRow, 'ID Transaksi');
        $sheet->setCellValue('B'.$idxRow, 'Kode Order');
        $sheet->setCellValue('C'.$idxRow, 'Nomor Invoice');
        $sheet->setCellValue('D'.$idxRow, 'Nomor Registrasi DPR');
        $sheet->setCellValue('E'.$idxRow, 'No HP Customer');
        $sheet->setCellValue('F'.$idxRow, 'Status');
        $sheet->setCellValue('G'.$idxRow, 'Total Vote');
        $sheet->setCellValue('H'.$idxRow, 'Harga per Vote');
        $sheet->setCellValue('I'.$idxRow, 'Subtotal');
        $sheet->setCellValue('J'.$idxRow, 'Biaya Admin Pembayaran');
        $sheet->setCellValue('K'.$idxRow, 'Nilai Pembayaran');
        $sheet->setCellValue('L'.$idxRow, 'Metode Pembayaran');
        $sheet->setCellValue('M'.$idxRow, 'Kode Referensi Pembayaran');
        $sheet->setCellValue('N'.$idxRow, 'Dibayar pada');
        $sheet->setCellValue('O'.$idxRow, 'Dibuat pada');
        
        $transactions = [];
        if (isset($result->data['result'])) {
            $transactions = $result->data['result'];
        } else {
            $transactions = $result->data;
        }
        foreach ($transactions as $transaction) {
            $idxRow++;
            $sheet->setCellValue('A'.$idxRow, $transaction->id);
            $sheet->setCellValue('B'.$idxRow, $transaction->order_code);
            $sheet->setCellValue('C'.$idxRow, $transaction->invoice_number);
            $sheet->setCellValue('D'.$idxRow, $transaction->poll_member_registration_number);
            $sheet->setCellValue('E'.$idxRow, $transaction->customer_phone_number);
            $sheet->setCellValue('F'.$idxRow, $transaction->status);
            $sheet->setCellValue('G'.$idxRow, $transaction->total_votes);
            $sheet->setCellValue('H'.$idxRow, $transaction->price);
            $sheet->setCellValue('I'.$idxRow, $transaction->total_price);
            $sheet->setCellValue('J'.$idxRow, $transaction->payment_fee_total);
            $sheet->setCellValue('K'.$idxRow, $transaction->payment_amount);
            $sheet->setCellValue('L'.$idxRow, $transaction->payment_method_name);
            $sheet->setCellValue('M'.$idxRow, $transaction->payment_reference_no);
            $sheet->setCellValue('N'.$idxRow, $transaction->paid_at);
            $sheet->setCellValue('O'.$idxRow, $transaction->created_at);
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

}
