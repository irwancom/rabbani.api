<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\OrderHandler;
use Service\CLM\Handler\QcHandler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill as SpreadsheetFill;
use PhpOffice\PhpSpreadsheet\Style\Border as SpreadsheetBorder;
use PhpOffice\PhpSpreadsheet\Style\Color as SpreadsheetColor;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat as SpreadsheetFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Qc extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function check_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $admin = $auth->data;
        $payload = $this->input->get();

        if(!isset($payload['code']) || !$payload['code'] || empty($payload['code']) || is_null($payload['code'])){
            $this->delivery->addError(400, 'Code or invoice is required'); $this->response($this->delivery->format());
        }
        $code = $payload['code'];

        $handlerQc = new QcHandler($this->MainModel);
        $order = $handlerQc->getOrder(['code'=>$code]);
        if(!$order || is_null($order)){
            $this->delivery->addError(400, 'Order not found'); $this->response($this->delivery->format());
        }

        $detailOrder = $handlerQc->getDetailOrder($order);
        $order->order_products = ($detailOrder && isset($detailOrder['products'])) ? $detailOrder['products'] : [];
        $order->order_stores = ($detailOrder && isset($detailOrder['stores'])) ? $detailOrder['stores'] : [];
        //$order->order_discounts = ($detailOrder && isset($detailOrder['discounts'])) ? $detailOrder['discounts'] : [];

        $existQc = $handlerQc->getQc(['qc_order'=>$order->order_code]);
        $order->order_qc = $existQc;

        $this->delivery->data = $order;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function check_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $admin = $auth->data;

        $payload = $this->input->post();
        if(!isset($payload['code']) || !$payload['code'] || empty($payload['code']) || is_null($payload['code'])){
            $this->delivery->addError(400, 'Order code or invoice is required'); $this->response($this->delivery->format());
        }

        $code = $payload['code'];
        $handlerQc = new QcHandler($this->MainModel);

        $order = $handlerQc->getOrder(['code'=>$code]);
        if(!$order || is_null($order)){
            $this->delivery->addError(400, 'Order not found'); $this->response($this->delivery->format());
        }

        $existQc = $handlerQc->getQc(['qc_order'=>$order->order_code]);
        //$existQc = $handlerQc->getQc(['qc_auth'=>$admin['id_auth'],'qc_admin'=>$admin['id'],'qc_order'=>$order->order_code]);
        $existItems = [];
        if($existQc && !is_null($existQc)){
            if($existQc->qc_admin!=$admin['id']){
                $this->delivery->addError(400, 'Order tersebut sudah di QC sebelumnya oleh ('.$existQc->first_name.'). Silahkan login dengan akun tersebut atau hubungi admin yang bersangkutan.'); $this->response($this->delivery->format());
            }
            if($existQc->qc_item && !empty($existQc->qc_item) && !is_null($existQc->qc_item)){
                $existItems = json_decode(json_encode($existQc->qc_item), true);
            }
        }

        $currentDate = date('Y-m-d H:i:s');
        $sendData = array('updated_at'=>$currentDate);

        if(isset($payload['item']) && $payload['item'] && !empty($payload['item']) && !is_null($payload['item']) && is_array($payload['item'])){
            $items = $payload['item'];

            foreach($items as $k_itm=>$itm){
                $check_key = array_search($itm['id'], array_column($existItems, 'id'));
                if($check_key === FALSE){
                    $existItems []= array('id'=>$itm['id'], 'id_detail'=>$itm['id_detail'], 'sku'=>$itm['sku'],'date'=>$currentDate);
                }else{
                    $existItems[$check_key]['id_detail'] = $itm['id_detail'];
                    $existItems[$check_key]['sku'] = $itm['sku'];
                    $existItems[$check_key]['date'] = $currentDate;
                }
            }
            $sendData['qc_item'] = json_encode($existItems, true);
        }
        if($existQc && !is_null($existQc)){
            $upData = $this->db->set($sendData)->where(['qc_id'=>$existQc->qc_id])->update('qc_orders');
        }else{
            $sendData['qc_auth'] = $admin['id_auth'];
            $sendData['qc_admin'] = $admin['id'];
            $sendData['qc_order'] = $order->order_code;
            $sendData['qc_status'] = 1;
            $sendData['created_at'] = $currentDate;
            $upData = $this->db->insert('qc_orders', $sendData);
        }

        $this->delivery->data = 'done';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function export_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $admin = $auth->data;
        $payload = $this->input->get();

        $filter = ['qc_orders.qc_item !='=>NULL]; $startdate = false;
        if(isset($payload['startdate']) && $payload['startdate'] && !empty($payload['startdate']) && !is_null($payload['startdate'])){
            $startdate = strtotime($payload['startdate']);
            if($startdate && !empty($startdate) && !is_null($startdate) && is_numeric($startdate)){
                $filter['DATE(qc_orders.updated_at) >='] = date('Y-m-d', $startdate);
            }
        }
        $enddate = false;
        if(isset($payload['enddate']) && $payload['enddate'] && !empty($payload['enddate']) && !is_null($payload['enddate'])){
            $enddate = strtotime($payload['enddate']);
            if($enddate && !empty($enddate) && !is_null($enddate) && is_numeric($enddate)){
                $filter['DATE(qc_orders.updated_at) <='] = date('Y-m-d', $enddate);
            }
        }

        $title = 'QC Report'; $subtitle = '';
        $nameFile = 'Qc_Report'; $subNameFile = 'IN'.date('dmyhis');
        if($startdate && $enddate){
            $subtitle = ': ('.date('d/m/Y', $startdate).' - '.date('d/m/Y', $enddate).')';
            $subNameFile = 'F'.date('dmy', $startdate).'_T'.date('dmy', $enddate);
        }else if($startdate && !$enddate){
            $subtitle = ': '.date('d/m/Y', $startdate);
            $subNameFile = 'F'.date('dmy', $startdate);
        }else if(!$startdate && $enddate){
            $subtitle = ': '.date('d/m/Y', $enddate);
            $subNameFile = 'T'.date('dmy', $enddate);
        }
        $nameFile = $nameFile.'_'.$subNameFile;

        $handlerQc = new QcHandler($this->MainModel);

        $attribute = [];
        if(isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data']) && is_numeric($payload['data'])){
            $attribute['data'] = intval($payload['data']);
        }
        $isPage = 1;
        if(isset($payload['page']) && $payload['page'] && !empty($payload['page']) && !is_null($payload['page']) && is_numeric($payload['page'])){
            $isPage = intval($payload['page']);
            $attribute['page'] = ($isPage<=1) ? 1 : $isPage;
        }
        $defaultName = $nameFile;
        $nameFile = $nameFile.'_page'.$isPage;

        $getReports = $handlerQc->getHistory($filter, false, 'paginate', $attribute);
        $reports = $getReports['result'];
        $report_page = [
            'limit' => $getReports['limit'],
            'total_data' => $getReports['total_data'],
            'current_page' => $getReports['current_page'],
            'max_page' => $getReports['max_page'],
        ];
        //print_r(json_encode($getReports));

        if(!$reports || is_null($reports)){
            $this->delivery->addError(400, 'Qc history not found'); $this->response($this->delivery->format());
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Page-'.$isPage);

        $header = [
            'no' => array('name'=>'No','width'=>'10'),
            'code' => array('name'=>'No Order','width'=>'30'),
            'resi' => array('name'=>'No Resi','width'=>'30'),
            'date' => array('name'=>'Date','width'=>'15'),
            'name' => array('name'=>'Customer','width'=>'30'),
            'source' => array('name'=>'Source Name','width'=>'20'),
            'store' => array('name'=>'Store','width'=>'20'),
            'courier' => array('name'=>'Courier','width'=>'20'),
            'payment' => array('name'=>'Payment','width'=>'20'),
            'item' => array('name'=>'Item Code','width'=>'25'),
            'product' => array('name'=>'Item Name','width'=>'35'),
            'color' => array('name'=>'Item Color','width'=>'10'),
            'size' => array('name'=>'Item Size','width'=>'10'),
            'qty' => array('name'=>'Qty','width'=>'10'),
            'price' => array('name'=>'Unit Price','width'=>'20'),
            'subtotal' => array('name'=>'Subtotal','width'=>'20'),
            'discount' => array('name'=>'Discount','width'=>'20'),
            'total' => array('name'=>'Total','width'=>'20'),
        ];

        $fillSheet = new SpreadsheetFill();
        $borderSheet = new SpreadsheetBorder();
        $colorSheet = new SpreadsheetColor();
        $formatSheet = new SpreadsheetFormat();

        $styleHeader = [
            'font' => [
                'bold' => true,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => $borderSheet::BORDER_THIN,
                ],
                'right' => [
                    'borderStyle' => $borderSheet::BORDER_THIN,
                ],
                'bottom' => [
                    'borderStyle' => $borderSheet::BORDER_THICK,
                    'color' => array('argb' => $colorSheet::COLOR_DARKBLUE),
                ],
                'left' => [
                    'borderStyle' => $borderSheet::BORDER_THIN,
                ],
            ],
            //'fill' => [
                //'fillType' => $fillSheet::FILL_GRADIENT_LINEAR,
                //'rotation' => 90,
                //'startColor' => [
                    //'argb' => 'FFA0A0A0',
                //],
                //'endColor' => [
                    //'argb' => 'FFFFFFFF',
                //],
            //],
        ];

        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('B1', $subtitle);

        $idxRow = 3; $headNo = 'A'; $lastHeadNo = 'B';
        foreach($header as $k_head=>$head){
            $headCol = $headNo.$idxRow;
            $sheet->setCellValue($headCol, strtoupper($head['name']));
            $sheet->getStyle($headCol)->applyFromArray($styleHeader);
            $sheet->getStyle($headCol)->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($headNo)->setAutoSize(true);
            //$sheet->getColumnDimension($headNo)->setWidth($head['width']);
            if($head==end($header)){
                $lastHeadNo = $headNo;
            }
            $headNo++;
        }

        $titleBlock = 'A1:'.$lastHeadNo.'1';
        $headerBlock = 'A'.$idxRow.':'.$lastHeadNo.$idxRow;
        $sheet->getStyle($titleBlock)->getFont()->setBold(true);
        //$sheet->getStyle('B1')->getFont()->setItalic(true);
        //$sheet->mergeCells($titleBlock);
        $sheet->getRowDimension($idxRow)->setRowHeight(25);

        $idxRow += 1; $startRowBody = $idxRow; $noReport = 1;
        foreach($reports as $report){
            $items = null;
            if($report->qc_item && !empty($report->qc_item) && !is_null($report->qc_item)){
                $items = json_decode($report->qc_item);
            }
            if($items && !empty($items) && !is_null($items)){

                $detailOrder = null;
                if(isset($report->id_order) && $report->id_order && !is_null($report->id_order)){
                    $detailOrder = $handlerQc->getDetailOrder($report);
                }

                $nameMember = (isset($report->user_first_name) && $report->user_first_name && !empty($report->user_first_name)) ? $report->user_first_name : '';
                if((!$nameMember || empty($nameMember) || $nameMember==$report->user_phone) && $report->member_address_receiver_name){
                    $nameMember = $report->member_address_receiver_name;
                }

                $storeName = 'Rabbani Official';
                $orderSource = (isset($report->order_source) && $report->order_source && !empty($report->order_source)) ? $report->order_source : '';
                if($report->jubelio_store_name && !empty($report->jubelio_store_name) && !is_null($report->jubelio_store_name)){
                    //if(!$orderSource || empty($orderSource) || is_null($orderSource)){
                        //$orderSource = $report->jubelio_store_name;
                    //}
                    $storeName = $report->jubelio_store_name;
                }

                $paymentName = (isset($report->payment_method_name) && $report->payment_method_name && !empty($report->payment_method_name)) ? $report->payment_method_name : '';
                $resiNomor = (isset($report->no_awb) && $report->no_awb && !empty($report->no_awb) && !is_null($report->no_awb)) ? $report->no_awb : '';

                $courierName = '';
                if(isset($report->shipping_courier) && $report->shipping_courier && !empty($report->shipping_courier) && !is_null($report->shipping_courier)){
                    $courierName = $report->shipping_courier;
                }else if(isset($report->logistic_name) && $report->logistic_name && !empty($report->logistic_name) && !is_null($report->logistic_name)){
                    $courierName = $report->logistic_name;
                }

                $existItemStore = [];
               // $existProduct = [];

                //$detailOrder = ($report->detail_order && !empty($report->detail_order) && !is_null($report->detail_order)) ? $report->detail_order : false;
                if($detailOrder && !is_null($detailOrder)){

                    if(isset($detailOrder['products']) && $detailOrder['products'] && !empty($detailOrder['products']) && !is_null($detailOrder['products'])){
                        $orderProducts = $detailOrder['products'];
                        if($orderProducts && !is_null($orderProducts)){
                            foreach($orderProducts as $orProduct){
                                if(!isset($existItemStore[$orProduct->id_order_detail])){
                                    $existItemStore[$orProduct->id_order_detail] = array();
                                    $existItemStore[$orProduct->id_order_detail]['name'] = $orProduct->product_name;
                                    $existItemStore[$orProduct->id_order_detail]['qty'] = $orProduct->qty;
                                    $existItemStore[$orProduct->id_order_detail]['price'] = $orProduct->price;
                                    $existItemStore[$orProduct->id_order_detail]['subtotal'] = $orProduct->subtotal;
                                    $existItemStore[$orProduct->id_order_detail]['discount'] = abs($orProduct->discount_amount);
                                    $existItemStore[$orProduct->id_order_detail]['total'] = $orProduct->total;
                                    $isColorItem = ''; $isSizeItem = '';
                                    if($orProduct->product_detail_variable && !empty($orProduct->product_detail_variable) && !is_null($orProduct->product_detail_variable)){
                                        $isColorItem = $orProduct->product_detail_variable->COLOR;
                                        $isSizeItem = $orProduct->product_detail_variable->SIZE;
                                    }
                                    $existItemStore[$orProduct->id_order_detail]['color'] = $isColorItem;
                                    $existItemStore[$orProduct->id_order_detail]['size'] = $isSizeItem;
                                }
                            }
                        }
                    }

                    if(isset($detailOrder['stores'] ) && $detailOrder['stores'] && !empty($detailOrder['stores']) && !is_null($detailOrder['stores'])){
                        $orderStores = $detailOrder['stores'];
                        if($orderStores && !is_null($orderStores)){
                            foreach($orderStores as $orStore){

                                $shipmentStore = $orStore->shipment;
                                $shipmentStoreService = false;
                                $shipmentStoreTracking = false;

                                if($shipmentStore && !is_null($shipmentStore)){
                                    if(isset($shipmentStore->service) && $shipmentStore->service && !empty($shipmentStore->service) && !is_null($shipmentStore->service)){
                                        $shipmentStoreService = $shipmentStore->service;
                                    }
                                    if(isset($shipmentStore->tracking) && $shipmentStore->tracking && !empty($shipmentStore->tracking) && !is_null($shipmentStore->tracking)){
                                        $shipmentStoreTracking = $shipmentStore->tracking;
                                    }
                                }

                                $orderStoreDetail = false;
                                if($orStore->store_detail && !empty($orStore->store_detail) && !is_null($orStore->store_detail)){
                                    $orderStoreDetail = $orStore->store_detail;
                                }

                                if($orStore->products && !empty($orStore->products) && !is_null($orStore->products)){
                                    foreach($orStore->products as $storeProduct){
                                        if(!isset($existItemStore[$storeProduct->id_order_detail])){
                                            $existItemStore[$storeProduct->id_order_detail] = array();
                                        }
                                        $existItemStore[$storeProduct->id_order_detail]['courier'] = ($shipmentStoreService) ? $shipmentStoreService->service_display : '';
                                        $existItemStore[$storeProduct->id_order_detail]['resi'] = ($shipmentStoreTracking) ? $shipmentStoreTracking->awb_no : '';
                                        $existItemStore[$storeProduct->id_order_detail]['store'] = ($orderStoreDetail) ? $orderStoreDetail->name : '';
                                    }
                                }
                            }
                        }
                    }
                }

                foreach($items as $item){
                    $itemOrderDetailId = $item->id;
                    $itemProductDetailId = $item->id_detail;
                    $itemProductDetailSku = $item->sku;
                    $itemCheckDate = $item->date;

                    $nameProduct = '-'; $priceProduct = 0; $qtyProduct = 0; $discProduct = 0;
                    $subtotalProduct = 0; $totalProduct = 0; $colorProduct = ''; $sizeProduct = '';

                    if($existItemStore && isset($existItemStore[$itemOrderDetailId])){
                        if(isset($existItemStore[$itemOrderDetailId]['name'])){
                            $nameProduct = $existItemStore[$itemOrderDetailId]['name'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['color'])){
                            $colorProduct = $existItemStore[$itemOrderDetailId]['color'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['size'])){
                            $sizeProduct = $existItemStore[$itemOrderDetailId]['size'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['price'])){
                            $priceProduct = $existItemStore[$itemOrderDetailId]['price'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['qty'])){
                            $qtyProduct = $existItemStore[$itemOrderDetailId]['qty'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['discount'])){
                            $discProduct = $existItemStore[$itemOrderDetailId]['discount'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['subtotal'])){
                            $subtotalProduct = $existItemStore[$itemOrderDetailId]['subtotal'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['total'])){
                            $totalProduct = $existItemStore[$itemOrderDetailId]['total'];
                        }

                        if(isset($existItemStore[$itemOrderDetailId]['resi']) && !empty($existItemStore[$itemOrderDetailId]['resi'])){
                            $resiNomor = $existItemStore[$itemOrderDetailId]['resi'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['courier']) && !empty($existItemStore[$itemOrderDetailId]['courier'])){
                            $courierName = $existItemStore[$itemOrderDetailId]['courier'];
                        }
                        if(isset($existItemStore[$itemOrderDetailId]['store']) && !empty($existItemStore[$itemOrderDetailId]['store'])){
                            $storeName = $existItemStore[$itemOrderDetailId]['store'];
                        }
                    }

                    $sheet->setCellValue('A'.$idxRow, $noReport);
                    $sheet->setCellValue('B'.$idxRow, $report->qc_order);
                    $sheet->setCellValue('C'.$idxRow, $resiNomor);
                    $sheet->setCellValue('D'.$idxRow, date('d/m/Y', strtotime($itemCheckDate)));
                    $sheet->setCellValue('E'.$idxRow, $nameMember);
                    $sheet->setCellValue('F'.$idxRow, $orderSource);
                    $sheet->setCellValue('G'.$idxRow, $storeName);
                    $sheet->setCellValue('H'.$idxRow, $courierName);
                    $sheet->setCellValue('I'.$idxRow, $paymentName);
                    $sheet->setCellValue('J'.$idxRow, $itemProductDetailSku);
                    $sheet->setCellValue('K'.$idxRow, $nameProduct);
                    $sheet->setCellValue('L'.$idxRow, $colorProduct);
                    $sheet->setCellValue('M'.$idxRow, $sizeProduct);
                    $sheet->setCellValue('N'.$idxRow, $qtyProduct);
                    $sheet->setCellValue('O'.$idxRow, $priceProduct);
                    $sheet->setCellValue('P'.$idxRow, $subtotalProduct);
                    $sheet->setCellValue('Q'.$idxRow, $discProduct);
                    $sheet->setCellValue('R'.$idxRow, $totalProduct);

                    $noReport++; $idxRow++;
                }
            }
        }

        if($idxRow == $startRowBody){
            $this->delivery->addError(400, 'Qc history item not found'); $this->response($this->delivery->format());
        }

        $sheet->getStyle('A'.$startRowBody.':R'.$idxRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A'.$startRowBody.':A'.$idxRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C'.$startRowBody.':C'.$idxRow)->getNumberFormat()->setFormatCode($formatSheet::FORMAT_NUMBER);
        $sheet->getStyle('C'.$startRowBody.':C'.$idxRow)->getAlignment()->setHorizontal('left');
        $sheet->getStyle('L'.$startRowBody.':N'.$idxRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('O'.$startRowBody.':R'.$idxRow)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* \(#,##0.00\);_("Rp"* "-"??_);_(@_)');

        try {
            $filename = $nameFile.'.xlsx';
            $filenamedefault = $defaultName.'.xlsx';
            $attributePage = json_encode($report_page);
            header('Access-Control-Allow-Origin: *');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; filename='.$filename.'; defaultname='.$filenamedefault.'; page='.$attributePage);
            header(sprintf('Content-Disposition: attachment; filename="%s.xlsx"', $filename));
            $writer = new Xlsx($spreadsheet);
            $writer->save("php://output");
            die();
        } catch (\Exception $e) {
            $this->delivery->addErrors(500, $e->getMessage());
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }
    }

    

//=================================== END LINE ===================================//
}
