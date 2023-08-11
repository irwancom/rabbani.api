<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;

class ShippingLocationHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->CI =& get_instance();
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function isPrefixCity() {
		$isPref = array('kota. adm ','kota. adm','kota adm. ','kota adm.','kota adm ','kota adm','kota.adm ','kota.adm','kab. ','kab.','kab ','kota. ','kota.','kota ',' kota');
		return $isPref;
	}

	public function handleDelPrefixCity($city = '') {
		$prefCity = $this->isPrefixCity();
		$result = $city;
		foreach($prefCity as $prf){
			$result = str_replace($prf, '', $result);
		}
        $result = ltrim($result);
        return $result;
    }

    public function handleAddPrefixCity($city = '') {
        $prefCity = $this->isPrefixCity();
        $setCity = [$city];
		foreach($prefCity as $prf){
			$setCity[] = $prf.$city;
		}
		$setCity[] = 'kab'.$city;
		$setCity[] = 'kota'.$city;
        return $setCity;
    }

    public function handleListDataCity($name = '') {
        $nameLower = strtolower($name);
        $nameUpper = strtoupper($name);

        $nameArr = explode(' ', $nameLower);
        $nameArrUpper = array_map('strtoupper', $nameArr);

        $nameStr = str_replace(' ', '', $nameLower);
        $nameStrUpper = strtoupper($nameStr);

        $nameNoDot = str_replace('.', '', $nameStr);
        $nameNoDotUpper = strtoupper($nameNoDot);

        $nameNoPrefix = $this->handleDelPrefixCity($nameLower);
        $nameNoPrefixUpper = strtoupper($nameNoPrefix);

        $namePlusPrefix = $this->handleAddPrefixCity($nameNoPrefix);
        $namePlusPrefixUpper = array_map('strtoupper', $namePlusPrefix);

        return array('lower'=>$nameLower,'upper'=>$nameUpper,'arr'=>$nameArr,'arrUpper'=>$nameArrUpper,'string'=>$nameStr,'stringUpper'=>$nameStrUpper,'noDot'=>$nameNoDot,'noDotUpper'=>$nameNoDotUpper,'noPrefix'=>$nameNoPrefix,'noPrefixUpper'=>$nameNoPrefixUpper,'namePrefix'=>$namePlusPrefix,'namePrefixUpper'=>$namePlusPrefixUpper);
    }

    public function handleSubdistrictName($name = '') {
        $nameUpper = strtoupper($name);
        $nameLower = strtolower($name);
        $nameNoPrefix = $this->handleDelPrefixCity($nameLower);
        $nameNoPrefixLower = strtoupper($nameNoPrefix);
        $nameNoPrefixUpper = strtoupper($nameNoPrefix);

        $nameArr = array();
        $nameArr['lower'] = $nameLower;
        $nameArr['upper'] = $nameUpper;
        $nameArr['no_prf_lower'] = $nameNoPrefixLower;
        $nameArr['no_prf_upper'] = $nameNoPrefixUpper;
        $nameArr['lower_str'] = str_replace(' ', '', $nameLower);
        $nameArr['lower_strr'] = str_replace('-', '', $nameLower);
        $nameArr['upper_str'] = str_replace(' ', '', $nameUpper);
        $nameArr['upper_strr'] = str_replace('-', '', $nameUpper);
        $nameArr['no_prf_lower_str'] = str_replace(' ', '', $nameNoPrefixLower);
        $nameArr['no_prf_lower_strr'] = str_replace('-', '', $nameNoPrefixLower);
        $nameArr['no_prf_upper_str'] = str_replace(' ', '', $nameNoPrefixUpper);
        $nameArr['no_prf_upper_strr'] = str_replace('-', '', $nameNoPrefixUpper);

        return array('lower'=>$nameLower,'upper'=>$nameUpper,'noPrefix'=>$nameNoPrefix,'noPrefixLower'=>$nameNoPrefixLower,'noPrefixUpper'=>$nameNoPrefixUpper,'list'=>$nameArr);
    }

    public function handleFilterSubdistrict($isNameCity = [], $isNameSubdistrict = []) {
        $resultFilter = array();
        $resultFilter['name'] = [];
        $resultFilter['name_space'] = [];
        $resultFilter['name_comma'] = [];
        $resultFilter['name_comma_last'] = [];
        $resultFilter['name_comma_space'] = [];
        $resultFilter['name_comma_first'] = [];

        $filterName = []; $filterNameSpace = []; $filterNameComma = [];
        $filterNameCommaSpaceLast = []; $filterNameCommaSpace = []; $filterNameCommaSpaceFirst = []; 
        foreach($isNameCity as $cName){
            foreach($isNameSubdistrict as $isName){
                $resultFilter['name'][] = $isName.$cName;
                $resultFilter['name_space'][] = $isName.' '.$cName;
                $resultFilter['name_comma'][] = $isName.','.$cName;
                $resultFilter['name_comma_last'][] = $isName.', '.$cName;
                $resultFilter['name_comma_space'][] = $isName.' , '.$cName;
                $resultFilter['name_comma_first'][] = $isName.' ,'.$cName;
            }
        }
        return $resultFilter;
    }

    public function destinationFromDistrict($districtId = null) {
        return $this->originDestiFromDistrict('destination', $districtId);
    }

    public function originFromDistrict($districtId = null) {
        return $this->originDestiFromDistrict('origin', $districtId);
    }

    public function originDestiFromDistrict($isType = null, $districtId = null) {
        $district = $this->CI->db->from('districts')->where(['id_kab'=>$districtId])->get()->row_array();
        if(!$district || is_null($district)){
            $this->delivery->data = null; return $this->delivery;
        }
        $typeData = ($isType=='destination') ? $isType : 'origin';
        $loadData = 'jne_'.$typeData;

        $name = $district['nama'];
        $handleName = $this->handleListDataCity($name);
        $nameLower = $handleName['lower']; $nameUpper = $handleName['upper'];
        $nameArr = $handleName['arr']; $nameArrUpper = $handleName['arrUpper'];
        $nameStr = $handleName['string']; $nameStrUpper = $handleName['stringUpper'];
        $nameNoDot = $handleName['noDot']; $nameNoDotUpper = $handleName['noDotUpper'];
        $nameNoPrefix = $handleName['noPrefix']; $nameNoPrefixUpper = $handleName['noPrefixUpper'];
        $namePlusPrefix = $handleName['namePrefix']; $namePlusPrefixUpper = $handleName['namePrefixUpper'];

        $cekOrigin = $this->CI->db->select(['city_code','city_name'])->from($loadData)->where_in('city_name', $nameArr)->or_where_in('city_name', $nameArrUpper)->or_where_in('city_name', $namePlusPrefix)->or_where_in('city_name', $namePlusPrefixUpper)->get()->result_object();
        if(!$cekOrigin || is_null($cekOrigin)){
            $cekOrigin = $this->CI->db->select(['city_code','city_name'])->from($loadData)->like(['city_name'=>$name])->or_like(['city_name'=>$nameLower])->or_like(['city_name'=>$nameUpper])->or_like(['city_name'=>$nameStr])->or_like(['city_name'=>$nameStrUpper])->or_like(['city_name'=>$nameNoDot])->or_like(['city_name'=>$nameNoDotUpper])->or_like(['city_name'=>$nameNoPrefix])->or_like(['city_name'=>$nameNoPrefixUpper]);
            foreach($namePlusPrefix as $prfData){
                $cekOrigin = $cekOrigin->or_like(['city_name'=>$prfData]);
            }
            foreach($namePlusPrefixUpper as $prfDataUpper){
                $cekOrigin = $cekOrigin->or_like(['city_name'=>$prfDataUpper]);
            }
            $cekOrigin = $cekOrigin->get()->result_object();
        }

        $foundOrigin = []; $suggest = [];
        if($cekOrigin && !is_null($cekOrigin)){
            foreach($cekOrigin as $isOrigin){
                $originName = $isOrigin->city_name;
                $crcOrigin = explode(',', $originName);
                $countCrc = count($crcOrigin);
                $kecName = ($countCrc==2) ? $crcOrigin[0] : '';
                $kotaName = ($countCrc==2) ? $crcOrigin[1] : $crcOrigin[0];

                $nameOrigin = $kotaName;
                $handleNameOrigin = $this->handleListDataCity($nameOrigin);
                $nameOriginLower = $handleNameOrigin['lower']; $nameOriginUpper = $handleNameOrigin['upper'];
                $nameOriginArr = $handleNameOrigin['arr']; $nameOriginArrUpper = $handleNameOrigin['arrUpper'];
                $nameOriginStr = $handleNameOrigin['string']; $nameOriginStrUpper = $handleNameOrigin['stringUpper'];
                $nameOriginNoDot = $handleNameOrigin['noDot']; $nameOriginNoDotUpper = $handleNameOrigin['noDotUpper'];
                $nameOriginNoPrefix = $handleNameOrigin['noPrefix']; $nameOriginNoPrefixUpper = $handleNameOrigin['noPrefixUpper'];
                $nameOriginPlusPrefix = $handleNameOrigin['namePrefix']; $nameOriginPlusPrefixUpper = $handleNameOrigin['namePrefixUpper'];

                if($nameOrigin==$name || $nameOriginLower==$nameLower || $nameOriginUpper==$nameUpper || $nameOriginStr==$nameStr || $nameOriginStrUpper==$nameStrUpper || $nameOriginNoDot==$nameNoDot || $nameOriginNoDotUpper==$nameNoDotUpper){
                    $foundOrigin[] = $isOrigin;
                }else if(in_array($nameOriginUpper, $nameArrUpper) || in_array($nameOriginLower, $nameArr)){
                    $foundOrigin[] = $isOrigin;
                }else if(in_array($nameOriginUpper, $namePlusPrefixUpper) || in_array($nameOriginLower, $namePlusPrefix)){
                    $foundOrigin[] = $isOrigin;
                }

                if(strpos($nameNoPrefix, $nameOriginNoPrefix) !== false || strpos($nameOriginNoPrefix, $nameNoPrefix) !== false){
                    $suggest[] = $isOrigin;
                }else if(strpos($nameNoPrefixUpper, $nameOriginNoPrefixUpper) !== false || strpos($nameOriginNoPrefixUpper, $nameNoPrefixUpper) !== false){
                    $suggest[] = $isOrigin;
                }
            }
        }

        $result = ($foundOrigin && !is_null($foundOrigin)) ? $foundOrigin : $suggest;
        $this->delivery->data = $result; return $this->delivery;
    }

    public function destinationFromSubdistrict($subDistrictId = null) {
        return $this->originDestiFromSubDistrict('destination', $subDistrictId);
    }

    public function originFromSubdistrict($subDistrictId = null) {
        return $this->originDestiFromSubDistrict('origin', $subDistrictId);
    }

    public function originDestiFromSubDistrict($isType = null, $subDistrictId = null) {
        $select = [
            'sub_district.id_kec as sub_district_id', 'sub_district.nama as sub_district_name',
            'districts.id_kab as district_id','districts.nama as district_name',
        ];
        $subdistrict = $this->CI->db->select($select)->from('sub_district')->where(['sub_district.id_kec'=>$subDistrictId]);
        $subdistrict = $subdistrict->join('districts', 'districts.id_kab = sub_district.id_kab', 'left')->get()->row_array();
        if(!$subdistrict || is_null($subdistrict)){
            $this->delivery->data = null; return $this->delivery;
        }

        $typeData = ($isType=='destination') ? $isType : 'origin';
        $loadData = 'jne_'.$typeData;

        $name = $subdistrict['sub_district_name'];
        $handleNameSubdistrict = $this->handleSubdistrictName($name);

        $nameCity = $subdistrict['district_name'];
        $handleCity = $this->handleListDataCity($nameCity);
        $isNameCity = $handleCity['namePrefixUpper'];

        $isFilter = $this->handleFilterSubdistrict($isNameCity, $handleNameSubdistrict['list']);
        $result = $this->CI->db->select(['city_code','city_name'])->from($loadData);
        $result = $result->where_in('city_name', $isFilter['name']);
        foreach($isFilter as $k_filter=>$filter){
            $result = $result->or_where_in('city_name', $filter);
        }
        
        $result = $result->get()->result_object();
        $this->delivery->data = $result; return $this->delivery;
    }

}