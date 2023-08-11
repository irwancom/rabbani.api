<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Member\MemberHandler;
use Service\Poll\PollMemberHandler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Member extends REST_Controller {

    private $validator;
    private $delivery;

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
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitals($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($iden) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters['iden'] = $iden;
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigital($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigital($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitals($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitals($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transaction_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigitalTransaction($slug, $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transactions_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalTransactions($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transactions_export_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalTransactionsAll($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $rawTransactions = $result->data;
        $formattedTransactions = [];
        foreach ($rawTransactions as $transaction) {
            if (isset($formattedTransactions[$transaction->member_source_id])) {
                $formattedTransactions[$transaction->member_source_id]['transactions'][] = $transaction;
            } else {
                $formattedTransactions[$transaction->member_source_id]['member_source_id'] = $transaction->member_source_id;
                $formattedTransactions[$transaction->member_source_id]['member_source_name'] = $transaction->member_source_name;
                $formattedTransactions[$transaction->member_source_id]['member_source_code'] = $transaction->member_source_code;
                $formattedTransactions[$transaction->member_source_id]['transactions'][] = $transaction;
            }
        }

        $columns = getExcelColumns();
        $idxRow = 2;
        $idxColumn = 0;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Transaksi');

        foreach ($formattedTransactions as $group) {
            $sheet->setCellValue('A'.$idxRow, $group['member_source_name']);

            $idxRow += 1;
            $sheet->setCellValue('A'.$idxRow, 'DATE');
            $spreadsheet->getActiveSheet()->mergeCells('A'.$idxRow.':'.'A'.($idxRow+1));

            $sheet->setCellValue('B'.$idxRow, 'ORDER');
            $spreadsheet->getActiveSheet()->mergeCells('B'.$idxRow.':'.'B'.($idxRow+1));
            $sheet->setCellValue('C'.$idxRow, 'PHONE');
            $spreadsheet->getActiveSheet()->mergeCells('C'.$idxRow.':'.'C'.($idxRow+1));

            $sheet->setCellValue('D'.$idxRow, 'AMOUNT');
            $spreadsheet->getActiveSheet()->mergeCells('D'.$idxRow.':'.'G'.($idxRow));

            $sheet->setCellValue('D'.($idxRow+1), 'HPP');
            $sheet->setCellValue('E'.($idxRow+1), 'FEE 1');
            $sheet->setCellValue('F'.($idxRow+1), 'FEE 2');
            $sheet->setCellValue('G'.($idxRow+1), 'FEE BANK');

            $sheet->setCellValue('H'.$idxRow, 'PAYMENT');
            $spreadsheet->getActiveSheet()->mergeCells('H'.$idxRow.':'.'H'.($idxRow+1));
            $sheet->setCellValue('I'.$idxRow, 'REF.NO');
            $spreadsheet->getActiveSheet()->mergeCells('I'.$idxRow.':'.'I'.($idxRow+1));
            $sheet->setCellValue('J'.$idxRow, 'STATUS');
            $spreadsheet->getActiveSheet()->mergeCells('J'.$idxRow.':'.'J'.($idxRow+1));


            $idxRow += 2;
            $theTransactions = $group['transactions'];
            foreach ($theTransactions as $transaction) {
                $sheet->setCellValue('A'.$idxRow, date('d/m/Y', strtotime($transaction->created_at)));
                $sheet->setCellValue('B'.$idxRow, $transaction->order_id);
                $sheet->setCellValue('C'.$idxRow, $transaction->phone_number);
                $sheet->setCellValue('D'.$idxRow, $transaction->shopping_amount);
                $sheet->setCellValue('E'.$idxRow, $transaction->admin_fee);
                $sheet->setCellValue('F'.$idxRow, $transaction->additional_fee);
                $sheet->setCellValue('G'.$idxRow, $transaction->payment_fee);
                $sheet->setCellValue('H'.$idxRow, $transaction->payment_method_name);
                $sheet->setCellValue('I'.$idxRow, $transaction->payment_reference_no);
                $sheet->setCellValue('J'.$idxRow, $transaction->status);
                $idxRow++;
            }
            
            $idxRow += 3;
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



    public function temp_poll_members_export_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMembers($filters, false);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $members = $result->data;
        $formattedMembers = [];
        if (isset($members['result'])) {
            $formattedMembers = $members['result'];
        } else {
            $formattedMembers = $members;
        }

        $columns = getExcelColumns();
        $idxRow = 1;
        $idxColumn = 0;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Daftar Calon DPR');


        $sheet->setCellValue('A'.$idxRow, 'ID');
        $sheet->setCellValue('B'.$idxRow, 'Tanggal Daftar');
        $sheet->setCellValue('C'.$idxRow, 'Nama');
        $sheet->setCellValue('D'.$idxRow, 'Nomor Registrasi');
        $sheet->setCellValue('E'.$idxRow, 'Tingkat Peserta');
        $sheet->setCellValue('F'.$idxRow, 'Outlet');
        $sheet->setCellValue('G'.$idxRow, 'Asal Sekolah');
        $sheet->setCellValue('H'.$idxRow, 'Tanggal Lahir');
        $sheet->setCellValue('I'.$idxRow, 'Alamat');
        $sheet->setCellValue('J'.$idxRow, 'Bakat');
        $sheet->setCellValue('K'.$idxRow, 'Penghargaan');
        $sheet->setCellValue('L'.$idxRow, 'Level 1 Status');
        $sheet->setCellValue('M'.$idxRow, 'Level 1 Total Vote');
        $sheet->setCellValue('N'.$idxRow, 'Level 2 Status');
        $sheet->setCellValue('O'.$idxRow, 'Level 2 Total Vote');
        $sheet->setCellValue('P'.$idxRow, 'Level 3 Status');
        $sheet->setCellValue('Q'.$idxRow, 'Level 3 Total Vote');

        $idxRow++;

        foreach ($formattedMembers as $member) {
            $idxRow++;
            $sheet->setCellValue('A'.$idxRow, $member->id);
            $sheet->setCellValue('B'.$idxRow, $member->created_at);
            $sheet->setCellValue('C'.$idxRow, $member->name);
            $sheet->setCellValue('D'.$idxRow, $member->registration_number);
            $sheet->setCellValue('E'.$idxRow, $member->title);
            $sheet->setCellValue('F'.$idxRow, $member->store_name);
            $sheet->setCellValue('G'.$idxRow, $member->from_school);
            $sheet->setCellValue('H'.$idxRow, $member->birthdate);
            $sheet->setCellValue('I'.$idxRow, $member->address);
            $sheet->setCellValue('J'.$idxRow, $member->talent);
            if (is_array($member->achievements)) {
                $sheet->setCellValue('K'.$idxRow, join(",", $member->achievements));
            } else {
                $sheet->setCellValue('K'.$idxRow, $member->achievements);
            }
            $sheet->setCellValue('L'.$idxRow, $member->level_1_status);
            $sheet->setCellValue('M'.$idxRow, $member->level_1_total_votes);
            $sheet->setCellValue('N'.$idxRow, $member->level_2_status);
            $sheet->setCellValue('O'.$idxRow, $member->level_2_total_votes);
            $sheet->setCellValue('P'.$idxRow, $member->level_3_status);
            $sheet->setCellValue('Q'.$idxRow, $member->level_3_total_votes);
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

    public function transaction_update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitalTransactions($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transaction_delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitalTransactions($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function vouchers_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalVouchers($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigitalVouchers($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitalVouchers($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitalVouchers($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_variants_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalVoucherVariants($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_variant_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigitalVoucherVariant($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_variant_update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitalVoucherVariants($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_variant_delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitalVoucherVariants($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_code_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalVoucherCodes($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_code_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigitalVoucherCodes($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_web_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $result = $this->delivery;
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);

        $source = $handler->getSource(['code' => $payload['source_code']])->data;
        if (empty($source)) {
            $result->addError(400, 'Source is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $voucher = $handler->getMemberDigitalVoucherVariant(['id' => $payload['member_voucher_variant_id']])->data;
        if (empty($voucher)) {
            $result->addError(400, 'Voucher variant is required');
            $this->response($result->format(), $result->getStatusCode());
        }
        $result->data = $voucher;

        $paymentMethods = $handler->generateTripayPaymentMethod();
        $hasPaymentMethod = false;
        $paymentMethod = null;
        foreach ($paymentMethods['tripay'] as $pm) {
            if ($pm->code == $payload['payment_method_code']) {
                $paymentMethod = $pm;
            }
        }
        if (empty($paymentMethod)) {
            $result->addError(400, 'Payment method is required');
            $this->response($result->format(), $result->getStatusCode());
        }
        $member = $handler->getMemberDigital(['iden' => $payload['phone_number']])->data;
        if (empty($member)) {
            $memberPayload = [
                'phone_number' => $payload['phone_number'],
                'wablas_phone_number_receiver' => MemberHandler::MAIN_WABLAS,
            ];
            $member = $handler->createMemberDigital($memberPayload)->data;
        }
        $action = $handler->generateTripayInvoice($member, $source, $voucher, $paymentMethod);
        if (isset($action['transaction'])) {
            $tripay = $action['tripay'];
            $message = 'Terima kasih telah order voucher kami. Langkah selanjutnya silahkan ikuti cara pembayaran melalui link berikut: '.PHP_EOL.$tripay->data->checkout_url;
            $notif = $handler->sendWablasToMember($member, $message);
            $action['extras']['wablas'] = $notif->data;
        }

        $result->data = $action;
        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_code_update_post ($code) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['code' => $code];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitalVoucherCodes($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function voucher_code_delete_post ($code) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['code' => $code];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitalVoucherCodes($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_voucher_code_post ($memberVoucherVariantId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $handler = new MemberHandler($this->MainModel, $auth->data);
        $existsVoucher = $handler->getMemberDigitalVoucherVariant(['id' => (int)$memberVoucherVariantId]);
        $voucher = $existsVoucher->data;
        if (empty($voucher)) {
            $this->delivery->addError(400, 'Voucher is required');
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }
        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=0; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][0])) {
                    $rowData = [
                        'code' => $sheetData[$i][0],
                        'password' => (empty($sheetData[$i][1]) ? null : $sheetData[$i][1]),
                    ];
                    if (!empty($rowData['code'])) {
                        $data[] = $rowData;
                    } else {
                        $dataFailed[] = $rowData;
                    }
                }
            }
        }

        $newData = [];
        $updateData = [];
        foreach ($data as $d) {
            $existsData = $handler->getMemberDigitalVoucherCode(['code' => $d['code']]);
            if (empty($existsData->data)) {

                $payload = [
                    'code' => $d['code'],
                    'password' => $d['password'],
                    'member_voucher_variant_id' => $voucher->id,
                    'is_purchased' => 0,
                    'is_used' => 0,
                ];
                // create
                $newData[] = $d;
                $action = $handler->createMemberDigitalVoucherCodes($payload);
            }
        }

        $result = [
            'new_data' => $newData,
            'update_data' => $updateData,
            'failed_data' => $dataFailed
        ];
        $this->delivery->data = $result;
        $this->response($this->delivery->format());
    }

    public function sources_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->getSources($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function source_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->createSource($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_source_post ($code) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['code' => $code];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateSource($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_source_post ($code) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['code' => $code];
        $payload = $this->input->post();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteSource($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_source_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $handler = new MemberHandler($this->MainModel, $auth->data);
        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $rowData = [
                        'code' => $sheetData[$i][0],
                        'name' => $sheetData[$i][1]
                    ];
                    if (!empty($rowData['code'])) {
                        $data[] = $rowData;
                    } else {
                        $dataFailed[] = $rowData;
                    }
                }
            }
        }

        $newData = [];
        $updateData = [];
        foreach ($data as $d) {
            $existsData = $handler->getSource(['code' => $d['code']]);
            $payload = [
                'code' => $d['code'],
                'name' => $d['name']
            ];
            if (empty($existsData->data)) {
                // create
                $newData[] = $d;
                $action = $handler->createSource($payload);
            } else {
                // update
                $updateData[] = $d;
                $action = $handler->updateSource($payload, ['id' => $existsData->data->id]);
            }
        }

        $result = [
            'new_data' => $newData,
            'update_data' => $updateData,
            'failed_data' => $dataFailed
        ];
        $this->delivery->data = $result;
        $this->response($this->delivery->format());
    }

    public function payment_methods_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberHandler($this->MainModel, $auth->data);
        $result = $handler->generateTripayPaymentMethod();
        $this->delivery->data = $result['tripay'];

        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
