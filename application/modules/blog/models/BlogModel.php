<?php

class BlogModel extends CI_Model {

    public function __construct() {

        parent::__construct();

        $this->load->database();
    }

    public function getAllCategory() {
        $this->db->select('id, url, menu');
        $this->db->where('level' , 0);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('position', 'ASC');
        $dataCategory = $this->db->get('blog_category')->result();
        
        $d = 1;
        if(!empty($dataCategory)){
            foreach($dataCategory as $dC){
                $this->db->select('id, url, menu');
                $this->db->where('parent_id' , $dC->id);
                $this->db->where('deleted_at IS NULL');
                $subDataCategory = $this->db->get('blog_category')->result();
                $dataResponse[] = array('category' => $dC,'subcategory' => $subDataCategory);
            }
        }
        
        if (!empty($dataResponse)) {
            $response = $dataResponse;
        } else {
            $response = NULL;
        }
        return $response;
    }

    public function getBlog($selectData = '', $page='') {
        if($selectData=='top'){
            $limit = 4;
            $this->db->select('id, url, pic_cover, title, sent_time');
            $this->db->where('deleted_at IS NULL');
            $this->db->where('sent_time < now()');
            $this->db->order_by('id', 'desc');
            $this->db->limit(4);
            $dataResponse = $this->db->get('blog')->result();
        }elseif($selectData=='new'){
            $limit = 10;
            $this->db->select('id, url, pic_cover, title, sent_time');
            $this->db->where('deleted_at IS NULL');
            $this->db->where('sent_time < now()');
            $this->db->order_by('id', 'desc');
            $this->db->limit(10);
            $dataResponse = $this->db->get('blog')->result();
        }else{
            $limit = 4;
            $this->db->select('a.id , a.url , a.pic_cover, a.title, a.sent_time');
            $this->db->where('a.deleted_at IS NULL');
            $this->db->where('a.sent_time < now()');
            $this->db->like('b.menu', $selectData);
            $this->db->join('blog_category as b', 'b.id = a.idCategory');
            $this->db->order_by('a.id', 'desc');
            $this->db->limit(4);
            $dataResponse = $this->db->get('blog as a')->result();
        }

        if($selectData==''){
            $dataResponse = 0;
        }
        
        $countData = $this->db->count_all_results();

        if (!empty($dataResponse)) {
            $response = array(
                'dataBlog'=>$dataResponse, 
                'countData'=>$countData,
                'limit'=>$countData,
                'current_page'=>$countData,
                'max_page'=>$countData,
            );
        } else {
            $response = NULL;
        }
        return $response;
    }

    public function getBlogDetails($selectData = '') {
        $this->db->select('a.pic_cover, a.title, a.seo_title, a.seo_desc, a.desc, a.sent_time, b.first_name');
        $this->db->where('a.deleted_at IS NULL');
        $this->db->where('a.sent_time < now()');
        $this->db->where('a.url',$selectData);
        $this->db->limit(1);
        $this->db->join('admins as b', 'b.id = a.admin_id');
        $dataResponse = $this->db->get('blog as a')->result();
        
        if (!empty($dataResponse)) {
            $response = $dataResponse;
        } else {
            $response = NULL;
        }
        return $response;
    }

    public function searchData($selectData = '') {
        $this->db->select('a.id , a.url , a.pic_cover, a.title, a.sent_time');
        $this->db->where('a.deleted_at IS NULL');
        $this->db->where('a.sent_time < now()');
        $this->db->like('a.title', $selectData);
        $this->db->or_like('a.seo_desc', $selectData);
        $this->db->limit(10);
        $this->db->join('admins as b', 'b.id = a.admin_id');
        $dataResponse = $this->db->get('blog as a')->result();
        
        if (!empty($dataResponse)) {
            $response = $dataResponse;
        } else {
            $response = NULL;
        }
        return $response;
    }

}
