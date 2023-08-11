<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberKb\MemberKbManager;
use Service\MemberKb\MemberKbHandler;

class Member_kb extends REST_Controller {

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
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->getMemberKbs($filters);
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
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->getMemberKb($filters);
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
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->createMemberKb($payload);
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
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->updateMemberKbs($payload, $filters);
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
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->deleteMemberKbs($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function result_get ($id) {
        // https://cdn.1itmedia.co.id/2ed7905dee075517330fdd7c6c9c38d6.jpg
        $filters['id'] = $id;
        $manager = new MemberKbManager($this->MainModel);
        $result = $manager->getMemberKbRecord($filters);
        $record = $result->data;
        if (empty($record)) {
            return true;
        }
        $member = $record->member;
            
        $handler = new MemberKbHandler($this->MainModel);
        $result = $handler->getSuitableClassification($member, $record);
        $classifications = $result->data;


        $tempDir = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";

        $genderText = [
            'female' => 'Perempuan',
            'male' => 'Laki-laki'
        ];

        $shortGenderText = [
            'male' => 'Bpk',
            'female' => 'Ibu'
        ];

        $classificationText = '';
        $index = 1;
        foreach ($classifications as $classification) {
            $idxChr = 65;
            $classificationText .= '<p>'.$index.'. '.$classification->name.'</p>';
            $classificationText .= '<div style="width:50%; float:left;">';
            $classificationText .= '<table style="width: 100%;padding-left: 10px; float:left; border-right: 1px solid black;">';
            $classificationText .= '
                <tr>
                    <td colspan="2"><b>Kriteria dibolehkan</b></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
            ';
            foreach ($classification->allowed_criterias as $criteria) {
                $classificationText .= '
                    <tr>
                        <td style="vertical-align: top;">'.strtolower(chr($idxChr)).'. </td>
                        <td>'.$criteria.'</td>
                    </tr>
                ';
                $idxChr++;
            }
            $classificationText .= '</table>';
            $classificationText .= '</div>';

            $classificationText .= '<div style="width:50%; float:left;">';
            $classificationText .= '<table style="width: 100%;padding-left: 10px;float:left;">';
            $classificationText .= '
                <tr>
                    <td colspan="2"><b>Kriteria tidak dibolehkan</b></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
            ';
            $idxChr = 65;
            foreach ($classification->allowed_criterias as $criteria) {
                $classificationText .= '
                    <tr>
                        <td style="vertical-align: top;">'.strtolower(chr($idxChr)).'. </td>
                        <td>'.$criteria.'</td>
                    </tr>
                ';
                $idxChr++;
            }
            $classificationText .= '</table>';
            $classificationText .= '</div>';
            $index++;
        }

        $html = '<html>
            <head>
            </head>
            <body>
                <div>
                    <p>Hasil diagnosa '.$shortGenderText[$member->gender].' '.$member->name.' sebagai gambaran alat kontrasepsi yang bisa dipilih sebagai berikut.</p>
                    <p>Berikut data hasil konsultasi :</p>
                    <div style="width: 100%; float:left;">
                        <table style="width:80%;padding-left: 30px;font-size:14px;">
                            <tr>
                                <td style="width: 150px;">No Konsul</td>
                                <td style="width: 15px;">:</td>
                                <td>'.$id.'</td>
                            </tr>
                            <tr>
                                <td>Diterbitkan</td>
                                <td>:</td>
                                <td>'.date('d M Y', strtotime($record->created_at)).'</td>
                            </tr>
                            <tr>
                                <td>Nama</td>
                                <td>:</td>
                                <td>'.$member->name.'</td>
                            </tr>
                            <tr>
                                <td>Usia</td>
                                <td>:</td>
                                <td>'.age($member->birthday).'</td>
                            </tr>
                            <tr>
                                <td>Jenis Kelamin</td>
                                <td>:</td>
                                <td>'.$genderText[$member->gender].'</td>
                            </tr>
                            <tr>
                                <td>Catatan keluhan</td>
                                <td>:</td>
                                <td>'.$record->diseases.'</td>
                            </tr>
                            <tr>
                                <td>Alamat</td>
                                <td>:</td>
                                <td>'.$member->address.'</td>
                            </tr>
                        </table>
                    </div>
                    <div style="width:100%;height:20px;float:left;"></div>
                    <div style="width:100%;float:left;">
                        '.$classificationText.'
                    </div>
                </div>
            </body>
        </html>';
        // echo $html;

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'margin_top' => 40,
            'margin_bottom' => 40,
        ]);
        $mpdf->setHTMLHeader('<img src="https://cdn.1itmedia.co.id/2ed7905dee075517330fdd7c6c9c38d6.jpg" style="width:182px;height:92px;margin-right: 10px;" />
            <img src="https://cdn.1itmedia.co.id/faca9d41e0c3b2117da5a2267d64522f.png" style="width:30px; height: 92px;"/>
            <img src="https://cdn.1itmedia.co.id/9687dbeba2bc5b45d9b3ca9c2ae3268e.png" style="margin-left:10px; width:89px; height: 92px;"/>');
        $mpdf->setHTMLFooter('<span><b>Lembar Diagnosa</b></span><span> : “Hasil screening ini merupakan alat bantu memilih alat kontrasepsi yang sesuai tetapi bukan merupakan penentu pengambilan keputusan final, masih diperlukan pemeriksaan oleh tenaga kesehatan secara langsung”</span>');
        $mpdf->WriteHTML($html);
        $mpdf->Output();
    }

    public function record_get ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters['id'] = $id;
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->getMemberKbRecord($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        if (empty($result->data)) {
            $result->addError(400, 'Member record is required');
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
