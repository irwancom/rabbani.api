<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;

class BankHandler {

    const BANK_BCA = 'bca';
    const BANK_BRI = 'bri';
    const BANK_MANDIRI = 'mandiri';
    const BANK_BNI = 'bni';
    const BANK_BTN = 'btn';
    const BANK_ANZ = 'anz';
    const BANK_CITIBANK = 'citibank';
    const BANK_STANDARD_CHARTERED = 'standard_chartered';
    const BANK_HSBC = 'hsbc';
    const BANK_CIMB = 'cimb';
    const BANK_DANAMON = 'danamon';
    const BANK_KESAWAN = 'kesawan';
    const BANK_MASPION = 'maspion';
    const BANK_MAYAPADA = 'mayapada';
    const BANK_MAYBANK = 'maybank';
    const BANK_BUKOPIN = 'bukopin';
    const BANK_MEGA = 'mega';
    const BANK_OCBC = 'ocbc';
    const BANK_PERMATA = 'permata';
    const BANK_SINARMAS = 'sinarmas';
    const BANK_UOB = 'uob';
    const BANK_BTPN = 'btpn';
    const BANK_MESTIKA = 'mestika';
    const BANK_SUMUT = 'sumut';
    const BANK_NOBU = 'nobu';
    const BANK_DBS = 'dbs';
    const BANK_KEB_HANA = 'keb_hana';
    const BANK_MNC = 'mnc';
    const BANK_MUAMALAT = 'muamalat';
    const BANK_PANIN = 'panin';
    const BANK_ARTHA = 'artha';
    const BANK_TOKYO = 'tokyo';
    const BANK_CAPITAL = 'capital';
    const BANK_CHINA = 'china';
    const BANK_BUMI_ARTA = 'bumi_arta';
    const BANK_RABOBANK = 'rabobank';
    const BANK_BJB = 'bjb';
    const BANK_DKI_JAKARTA = 'dki';
    const BANK_YOGYAKARTA = 'yogyakarta';
    const BANK_JAWA_TENGAH = 'jawa_tengah';
    const BANK_JAWA_TIMUR = 'jawa_timur';
    const BANK_JAMBI = 'jambi';
    const BANK_SUMATERA_BARAT = 'sumbar';
    const BANK_RIAU = 'riau';
    const BANK_SUMATERA_SELATAN = 'sumsel';
    const BANK_LAMPUNG = 'lampung';
    const BANK_KALIMANTAN_SELATAN = 'kalsel';
    const BANK_KALIMANTAN_BARAT = 'kalbar';
    const BANK_KALIMANTAN_TIMUR = 'kaltim';
    const BANK_KALIMANTAN_TENGAH = 'kalteng';
    const BANK_SULAWESI_SELATAN_BARAT = 'sulselbar';
    const BANK_SULAWESI_UTARA = 'sulut';
    const BANK_NUSA_TENGGARA_BARAT = 'ntb';
    const BANK_NUSA_TENGGARA_TIMUR = 'ntt';
    const BANK_BALI = 'bali';
    const BANK_MALUKU = 'maluku';
    const BANK_PAPUA = 'papua';
    const BANK_BENGKULU = 'bengkulu';
    const BANK_SULAWESI_TENGAH = 'sulteng';
    const BANK_SULAWESI_TENGGARA = 'sultra';
    const BANK_NUSANTARA_PARAHYANGAN = 'nusantara_parahyangan';
    const BANK_INDIA = 'india';
    const BANK_GANESHA = 'ganesha';
    const BANK_ICBC = 'icbc';
    const BANK_WOORI_SAUDARA = 'woori_saudara';
    const BANK_MANDIRI_SYARIAH = 'mandiri_syariah';
    const BANK_BRI_SYARIAH = 'bri_syariah';
    const BANK_BJB_SYARIAH = 'bjb_syariah';
    const BANK_JASA_JAKARTA = 'jasa_jakarta';
    const BANK_BRI_AGRONIAGA = 'agroniaga';
    const BANK_SBI_INDONESIA = 'sbi_indonesia';
    const BANK_ROYAL = 'royal';
    const BANK_MEGA_SYARIAH = 'mega_syariah';
    const BANK_INA_PERDANA = 'ina_perdana';
    const BANK_SAHABAT_SAMPOERNA = 'sahabat_sampoerna';
    const BANK_KESEJAHTERAAN_EKONOMI = 'kesejahteraan_ekonomi';
    const BANK_BCA_SYARIAH = 'bca_syariah';
    const BANK_ARTOS = 'artos';
    const BANK_MAYORA = 'mayora';
    const BANK_INDEX_SELINDO = 'index_selindo';
    const BANK_VICTORIA_INTERNATIONAL = 'victoria';
    const BANK_AGRIS = 'agris';
    const BANK_CHINATRUST = 'chinatrust';
    const BANK_COMMONWEALTH = 'commonwealth';
    const BANK_BNI_SYARIAH = 'bni_syariah';
    const ATMB_PLUS = 'atmb_plus';
    const BANK_BANGKOK = 'bank_bangkok';
    const BANK_ACEH_SYARIAH = 'bank_aceh_syariah';
    const BANK_NTB_SYARIAH = 'bank_ntb_syariah';
    const BANK_HARDA = 'bank_harda';
    const HSBC_BANK_EKONOMI = 'hsbc_bank_ekonomi';
    const BANK_MANDIRI_TASPEN = 'bank_mandiri_taspen';
    const BANK_MULTI_ARTA_SENTOSA = 'bank_multi_arta_sentosa';
    const BANK_OF_AMERICA_NA = 'bank_of_america_na';
    const BANK_OKE = 'bank_oke';
    const BANK_PANIN_DUBAI_SYARIAH = 'bank_panin_dubai_syariah';
    const BANK_PRIMA_MASTER = 'bank_prima_master';
    const RABOBANK = 'rabobank';
    const BANK_SHINHAN = 'bank_shinhan';
    const BANK_SYARIAH_BUKOPIN = 'bank_syariah_bukopin';
    const BANK_VICTORIA_SYARIAH = 'bank_victoria_syariah';
    const BANK_YUDHA_BHAKTI = 'bank_yudha_bhakti';
    const BANK_BANTEN = 'bank_banten';
    const BPR_EKA = 'bpr_eka';
    const BPR_KS = 'bpr_ks';
    const BPR_SUPRA = 'bpr_supra';
    const CCB_INDONESIA = 'ccb_indonesia';
    const DEUTSCHE_BANK_AG = 'deutsche_bank_ag';
    const DOKU = 'doku';
    const FINNET = 'finnet';
    const INDOSAT_DOMPETKU = 'indosat_dompetku';
    const CHASE_BANK = 'chase_bank';
    const JTRUST_BANK = 'jtrust_bank';
    const MUFG_BANK_LTD = 'mufg_bank_ltd';
    const BANK_AMAR_INDONESIA = 'bank_amar_indonesia';
    const BANK_BISNIS_INTERNASIONAL = 'bank_bisnis_internasional';
    const BANK_BNP = 'bank_bnp';
    const BANK_FAMA_INTERNATIONAL = 'bank_fama_international';
    const BANK_MIZUHO = 'bank_mizuho';
    const BANK_NET_SYARIAH = 'bank_net_syariah';
    const BANK_RESONA = 'bank_resona';
    const TELKOMSEL_TCASH = 'telkomsel_tcash';
    const XL_TUNAI = 'xl_tunai';
    const BANK_CHINA_CONSTRUCTION_BANK_INDONESIA = 'bank_china_construction_bank_indonesia';
    const MUFG_BANK = 'mufg_bank';
    const BANK_HSBC_INDONESIA = 'bank_hsbc_indonesia';
    const BANK_RABOBANK_INTERNATIONAL_INDONESIA = 'bank_rabobank_international_indonesia';
    const BANK_JTRUST_INDONESIA = 'bank_jtrust_indonesia';
    const BANK_NTB = 'bank_ntb';
    const BANK_SHINHAN_INDONESIA = 'bank_shinhan_indonesia';
    const BANK_WOORI_SAUDARA_INDONESIA_1906 = 'bank_woori_saudara_indonesia_1906';
    const BANK_PANIN_SYARIAH = 'bank_panin_syariah';
    const BANK_BUKOPIN_SYARIAH = 'bank_bukopin_syariah';
    const BANK_OKE_INDONESIA = 'bank_oke_indonesia';
    const BANK_BTPN_SYARIAH = 'bank_btpn_syariah';
    const BANK_HARDA_INTERNATIONAL = 'bank_harda_international';

    private $delivery;
	
    public function __construct () {
    	$this->delivery = new Delivery;
    }

    /**
     * Get bank choices
     *
     * @return []
     */
    public function getBankChoices()
    {
        $choices = [
            'BCA' => self::BANK_BCA,
            'BRI' => self::BANK_BRI,
            'Mandiri' => self::BANK_MANDIRI,
            'BNI' => self::BANK_BNI,
            'BTN' => self::BANK_BTN,
            'ANZ' => self::BANK_ANZ,
            'Citibank' => self::BANK_CITIBANK,
            'Standard Chartered' => self::BANK_STANDARD_CHARTERED,
            'HSBC' => self::BANK_HSBC,
            'CIMB' => self::BANK_CIMB,
            'Danamon' => self::BANK_DANAMON,
            'Kesawan' => self::BANK_KESAWAN,
            'Maspion' => self::BANK_MASPION,
            'Mayapada' => self::BANK_MAYAPADA,
            'Maybank' => self::BANK_MAYBANK,
            'Bukopin' => self::BANK_BUKOPIN,
            'Mega' => self::BANK_MEGA,
            'OCBC' => self::BANK_OCBC,
            'Permata' => self::BANK_PERMATA,
            'Sinarmas' => self::BANK_SINARMAS,
            'UOB' => self::BANK_UOB,
            'BTPN' => self::BANK_BTPN,
            'Mestika' => self::BANK_MESTIKA,
            'Sumut' => self::BANK_SUMUT,
            'Nobu' => self::BANK_NOBU,
            'DBS' => self::BANK_DBS,
            'Keb Hana' => self::BANK_KEB_HANA,
            'MNC' => self::BANK_MNC,
            'Muamalat' => self::BANK_MUAMALAT,
            'Panin' => self::BANK_PANIN,
            'Artha Graha Internasional' => self::BANK_ARTHA,
            'Tokyo' => self::BANK_TOKYO,
            'Capital' => self::BANK_CAPITAL,
            'China' => self::BANK_CHINA,
            'Bumi Arta' => self::BANK_BUMI_ARTA,
            'Rabobank' => self::BANK_RABOBANK,
            'BJB' => self::BANK_BJB,
            'DKI Jakarta' => self::BANK_DKI_JAKARTA,
            'Yogyakarta' => self::BANK_YOGYAKARTA,
            'Jawa Tengah' => self::BANK_JAWA_TENGAH,
            'Jawa Timur' => self::BANK_JAWA_TIMUR,
            'Jambi' => self::BANK_JAMBI,
            'Sumbar' => self::BANK_SUMATERA_BARAT,
            'Riau' => self::BANK_RIAU,
            'Sumsel' => self::BANK_SUMATERA_SELATAN,
            'Lampung' => self::BANK_LAMPUNG,
            'Kalsel' => self::BANK_KALIMANTAN_SELATAN,
            'Kalbar' => self::BANK_KALIMANTAN_BARAT,
            'Kaltim' => self::BANK_KALIMANTAN_TIMUR,
            'Kalteng' => self::BANK_KALIMANTAN_TENGAH,
            'Sulselbar' => self::BANK_SULAWESI_SELATAN_BARAT,
            'Sulut' => self::BANK_SULAWESI_UTARA,
            'Ntb' => self::BANK_NUSA_TENGGARA_BARAT,
            'Ntt' => self::BANK_NUSA_TENGGARA_TIMUR,
            'Bali' => self::BANK_BALI,
            'Maluku' => self::BANK_MALUKU,
            'Papua' => self::BANK_PAPUA,
            'Bengkulu' => self::BANK_BENGKULU,
            'Sulteng' => self::BANK_SULAWESI_TENGAH,
            'Sultra' => self::BANK_SULAWESI_TENGGARA,
            'Nusantara Parahyangan' => self::BANK_NUSANTARA_PARAHYANGAN,
            'India' => self::BANK_INDIA,
            'Ganesha' => self::BANK_GANESHA,
            'Icbc' => self::BANK_ICBC,
            'Woori Saudara' => self::BANK_WOORI_SAUDARA,
            'Mandiri Syariah' => self::BANK_MANDIRI_SYARIAH,
            'BRI Syariah' => self::BANK_BRI_SYARIAH,
            'BJB Syariah' => self::BANK_BJB_SYARIAH,
            'Jasa Jakarta' => self::BANK_JASA_JAKARTA,
            'Agroniaga' => self::BANK_BRI_AGRONIAGA,
            'SBI Indonesia' => self::BANK_SBI_INDONESIA,
            'Royal' => self::BANK_ROYAL,
            'Mega Syariah' => self::BANK_MEGA_SYARIAH,
            'Ina Perdana' => self::BANK_INA_PERDANA,
            'Sahabat Sampoerna' => self::BANK_SAHABAT_SAMPOERNA,
            'Kesejahteraan Ekonomi' => self::BANK_KESEJAHTERAAN_EKONOMI,
            'BCA Syariah' => self::BANK_BCA_SYARIAH,
            'Artos' => self::BANK_ARTOS,
            'Mayora' => self::BANK_MAYORA,
            'Index Selindo' => self::BANK_INDEX_SELINDO,
            'Victoria' => self::BANK_VICTORIA_INTERNATIONAL,
            'Agris' => self::BANK_AGRIS,
            'Chinatrust' => self::BANK_CHINATRUST,
            'Commonwealth' => self::BANK_COMMONWEALTH,
            'ATMB Plus' => self::ATMB_PLUS,
            'BANK BANGKOK' => self::BANK_BANGKOK,
            'BANK ACEH SYARIAH' => self::BANK_ACEH_SYARIAH,
            'BANK NTB SYARIAH' => self::BANK_NTB_SYARIAH,
            'BANK HARDA' => self::BANK_HARDA,
            'HSBC (D/H BANK EKONOMI)' => self::HSBC_BANK_EKONOMI,
            'BANK MANDIRI TASPEN' => self::BANK_MANDIRI_TASPEN,
            'BANK MULTI ARTA SENTOSA' => self::BANK_MULTI_ARTA_SENTOSA,
            'BANK OF AMERICA NA' => self::BANK_OF_AMERICA_NA,
            'BANK OKE' => self::BANK_OKE,
            'BANK PANIN DUBAI SYARIAH' => self::BANK_PANIN_DUBAI_SYARIAH,
            'BANK PRIMA MASTER' => self::BANK_PRIMA_MASTER,
            'RABOBANK' => self::RABOBANK,
            'BANK SHINHAN' => self::BANK_SHINHAN,
            'BANK SYARIAH BUKOPIN' => self::BANK_SYARIAH_BUKOPIN,
            'BANK VICTORIA SYARIAH' => self::BANK_VICTORIA_SYARIAH,
            'BANK YUDHA BHAKTI' => self::BANK_YUDHA_BHAKTI,
            'BANK BANTEN' => self::BANK_BANTEN,
            'BPR EKA' => self::BPR_EKA,
            'BPR KS' => self::BPR_KS,
            'BPR SUPRA' => self::BPR_SUPRA,
            'CCB INDONESIA' => self::CCB_INDONESIA,
            'DEUTSCHE BANK AG.' => self::DEUTSCHE_BANK_AG,
            'DOKU' => self::DOKU,
            'FINNET' => self::FINNET,
            'INDOSAT (DOMPETKU)' => self::INDOSAT_DOMPETKU,
            'CHASE BANK' => self::CHASE_BANK,
            'JTRUST BANK' => self::JTRUST_BANK,
            'MUFG BANK LTD' => self::MUFG_BANK_LTD,
            'BANK AMAR INDONESIA' => self::BANK_AMAR_INDONESIA,
            'BANK BISNIS INTERNASIONAL' => self::BANK_BISNIS_INTERNASIONAL,
            'BANK BNP' => self::BANK_BNP,
            'BANK FAMA INTERNATIONAL' => self::BANK_FAMA_INTERNATIONAL,
            'BANK MIZUHO' => self::BANK_MIZUHO,
            'BANK NET SYARIAH' => self::BANK_NET_SYARIAH,
            'BANK RESONA' => self::BANK_RESONA,
            'TELKOMSEL (TCASH)' => self::TELKOMSEL_TCASH,
            'XL TUNAI' => self::XL_TUNAI,
            'BANK CHINA CONSTRUCTION BANK INDONESIA' => self::BANK_CHINA_CONSTRUCTION_BANK_INDONESIA,
            'MUFG BANK, LTD.' => self::MUFG_BANK,
            'BANK HSBC INDONESIA' => self::BANK_HSBC_INDONESIA,
            'BANK RABOBANK INTERNATIONAL INDONESIA' => self::BANK_RABOBANK_INTERNATIONAL_INDONESIA,
            'BANK JTRUST INDONESIA' => self::BANK_JTRUST_INDONESIA,
            'BANK NTB' => self::BANK_NTB,
            'BANK SHINHAN INDONESIA' => self::BANK_SHINHAN_INDONESIA,
            'BANK WOORI SAUDARA INDONESIA 1906' => self::BANK_WOORI_SAUDARA_INDONESIA_1906,
            'BANK PANIN SYARIAH' => self::BANK_PANIN_SYARIAH,
            'BANK BUKOPIN SYARIAH' => self::BANK_BUKOPIN_SYARIAH,
            'BANK OKE INDONESIA' => self::BANK_OKE_INDONESIA,
            'BANK BTPN SYARIAH' => self::BANK_BTPN_SYARIAH,
            'BANK HARDA INTERNATIONAL' => self::BANK_HARDA_INTERNATIONAL,
        ];

        $this->delivery->data = $choices;
        return $this->delivery;
    }
}