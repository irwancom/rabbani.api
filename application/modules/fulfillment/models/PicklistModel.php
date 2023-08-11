<?php

class PicklistModel extends MainModel {

    public function __construct() {

        parent::__construct();

        $this->load->database();
    }

    public function getLastRowId($table) {
		$row = $this->db->select("*")->limit(1)->order_by('id',"DESC")->get($table)->row();
        return $row !== NULL && is_object($row) && isset($row->id) ? $row->id : 0;
    }

    public function insertBatch($table, $data) {
        $insertedRows = $this->db->insert_batch($table, $data);
        return $insertedRows;
    }

    public function deleteSoft($table, $key, $value) {
		$result = $this->update($table, [
			'deleted_at' => date('Y-m-d H:i:s')
		], [$key => $value]);
        return $result;
    }

    public function deleteBatchSoft($table, $key, $value) {
		$result = $this->update($table, [
			'deleted_at' => date('Y-m-d H:i:s')
		], [$key => [
			'condition' => 'where_in',
			'value' => $value,
		]]);
    }

    public function errorMessage() {
		return $this->db->_error_message();
    }


}
