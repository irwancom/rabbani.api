<?php
namespace Service\MemberKb;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;
use Library\WablasService;

class MemberKbHandler {

	const APP_ENV = 'dev';
	const MAIN_WABLAS = '6289674545000';
	const WABLAS_MENU_STATE_TWO = 'menu_two';
	const WABLAS_MENU_STATE_CHOICE_TEST = 'menu_choice_test';
	const TOTAL_CHILDREN_NOT_HAVE = 'not_have';
	const TOTAL_CHILDREN_ONE_UNTIL_TWO = 'one_until_two';
	const TOTAL_CHILDREN_MORE_THAN_TWO = 'more_than_two';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;
	private $welcomeText;
	private $diseaseManText;
	private $diseaseWomanText;
	private $generalMenuText;
	private $questionMow;
	private $questionAkdr;
	private $questionImplant;
	private $questionSuntikKbKombinasi;
	private $questionSuntikKbProgestin;
	private $questionPilProgestin;
	private $questionPilKombinasi;
	private $questionMal;
	private $implanUrl;
	private $pilUrl;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->uploadPath = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";
		$this->welcomeText = 'Selamat datang di Layanan WA center konsultasi KB BKKBN Jawa Barat: *PASTI KB*'.PHP_EOL.PHP_EOL.'Mesin penjawab otomatis Kami akan membantu anda memilih kontrasepsi yang sesuai dengan kriteria dan kebutuhan Anda.'.PHP_EOL.'Waktu yang dibutuhkan tak lebih dari 5 menit.'.PHP_EOL.PHP_EOL.'Silahkan menjawab pertanyaan yang kami sampaikan untuk proses penapisan.'.PHP_EOL.PHP_EOL.'SIlahkan isi nama lengkap:';
		$this->diseaseManText = 'Apakah anda memiliki penyakit bawah ini?'.PHP_EOL.'Memiliki masalah dengan alat kelamin: infeksi, bengkak, luka atau benjolan di zakar/buah zakar'.PHP_EOL.'DM'.PHP_EOL.'Pembekuan darah'.PHP_EOL.'Alergi Latex'.PHP_EOL.'Jelaskan dalam 1 pesan';
		$this->diseaseWomanText = 'Sebutkan penyakit yang anda alami:';
		$this->generalMenuText = 'Silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.'.PHP_EOL.'1. Lihat Data Diri'.PHP_EOL.'2. Tes Rekomendasi Alat Kontrasepsi'.PHP_EOL.'3. Histori Konsultasi'.PHP_EOL.PHP_EOL.'Informasi Alat Kontrasepsi https://bit.ly/Informasikontrasepsi';
		$this->generalMenuTextRaw = 'Silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.'.PHP_EOL.'1. Lihat Data Diri'.PHP_EOL.'2. Tes Rekomendasi Alat Kontrasepsi'.PHP_EOL.'3. Histori Konsultasi';
		$this->questionMow = 'Apakah anda berada di salah satu / keseluruhan dari 3 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Ibu yang baru bersalin antara 7 s.d 24 hari'.PHP_EOL.'- Memiliki masalah kewanitaan seperti infeksi atau kanker (radang panggul, kanker payudara, Riwayat operasi perut dan panggul)'.PHP_EOL.'- Memiliki permasalahn jantung, stroke, hipertensi dan Diabetes Melitus/Kencing manis.';
		$this->questionAkdr = 'Apakah anda berada di salah satu / keseluruhan dari 4 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Ibu yang hamil atau diduga hamil'.PHP_EOL.'- Mengalami infeksi setelah melahirkan atau keguguran'.PHP_EOL.'- Mengalami perdarahan pervagina yang tidak seperti biasanya'.PHP_EOL.'- Memiliki masalah kewanitaan seperti kanker serviks (leher rahim) dan radang panggul';
		$this->questionImplant = 'Apakah anda berada di salah satu / keseluruhan dari 4 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Menderita penyakit hati yang aktif dan serius'.PHP_EOL.'- Memiliki masalah serius dengan penggumpalan darah di kaki atau paru-paru'.PHP_EOL.'- Mengalami perdarahan pervaginam yang tidak seperti biasanya/tidak dapat dijelaskan'.PHP_EOL.'- Sedang atau pernah menderita kanker payudara';
		$this->questionSuntikKbKombinasi = 'Apakah anda berada di salah satu / keseluruhan dari 11 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Tidak disarankan bagi ibu meyusui, karena dapat mengganggu produksi ASI'.PHP_EOL.'- Menderita penyakit hati yang aktif dan serius'.PHP_EOL.'- Sedang atau pernah memiliki riwayat tekanan darah tinggi'.PHP_EOL.'- Menderita diabetes atau mengalami kerusakan pembuluh darah, penglihatan, ginjal atau sistem saraf'.PHP_EOL.'- Memiliki penyakit kandung empedu atau sedang mengonsumsi obat untuk sakit kandung empedu'.PHP_EOL.'- Pernah atau sedang  mengalami stroke, penggumpalan darah di kaki atau paru-paru,serangan jantung'.PHP_EOL.'- Sedang atau pernah menderita kanker payudara'.PHP_EOL.'- Mengalami migrain'.PHP_EOL.'- Sedang mengkonsumsi obat untuk kejang atau obat Tubercolosis'.PHP_EOL.'- Sedang merencanakan untuk mendapat prosedur operasi besar'.PHP_EOL.'- Merokok dan berusia lebih dari 35 tahun';
		$this->questionSuntikKbProgestin = 'Apakah anda berada di salah satu / keseluruhan dari 7 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Ibu yang Menderita penyakit hati yang aktif dan serius'.PHP_EOL.'- Memiliki tekanan darah tinggi'.PHP_EOL.'- Menderita diabetes atau mengalami kerusakan pembuluh darah, penglihatan, ginjal atau sistem saraf'.PHP_EOL.'- Memiliki penyakit kandung empedu atau sedang mengonsumsi obat untuk sakit kandung empedu'.PHP_EOL.'- Pernah atau sedang mengalami stroke, penggumpalan darah di kaki atau paru-paru,serangan jantung'.PHP_EOL.'- Wanita dengan perdarahan pervaginam yang tidak diketahui penyebabnya '.PHP_EOL.'- Sedang atau pernah menderita kanker payudara';
		$this->questionPilProgestin = 'Apakah anda berada di salah satu / keseluruhan dari 5 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Memiliki penyakit hati yang aktif dan serius'.PHP_EOL.'- Menderita gangguan pembekuan darah'.PHP_EOL.'- Sedang mengkonsumsi obat untuk kejang atau obat Tubercolosis'.PHP_EOL.'- Pernah atau sedang menderita kanker payudara'.PHP_EOL.'- Orang yang pelupa';
		$this->questionPilKombinasi = 'Apakah anda berada di salah satu / keseluruhan dari 12 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Sedang menyusui'.PHP_EOL.'- Menderita penyakit hati yang aktif dan serius '.PHP_EOL.'- Sedang atau pernah memiliki riwayat tekanan darah tinggi'.PHP_EOL.'- Menderita diabetes atau mengalami kerusakan pembuluh darah, penglihatan, ginjal atau sistem saraf '.PHP_EOL.'- Memiliki penyakit kandung empedu atau sedang mengkonsumsi obat untuk sakit kandung empedu'.PHP_EOL.'- Pernah atau sedang mengalami stroke, penggumpalan darah di kaki atau paru-paru, serangan jantung'.PHP_EOL.'- Sedang atau pernah menderita kanker payudara'.PHP_EOL.'- Mengalami migraine'.PHP_EOL.'- Sedang mengkonsumsi obat kejang dan obat Tubercolosis'.PHP_EOL.'- Sedang merencanakan untuk mendapat prosedur operasi besar'.PHP_EOL.'- Merokok dan berusia > 35 tahun'.PHP_EOL.'- Orang yang pelupa';
		$this->questionMal = 'Apakah anda berada di salah satu / keseluruhan dari 5 poin di bawah? (Ya/Tidak)'.PHP_EOL.'- Sudah mendapat haid setelah bersalin'.PHP_EOL.'- Tidak menyusui secara eksklusif'.PHP_EOL.'- Bayinya sudah berumur lebih dari 6 bulan'.PHP_EOL.'- Kontraindikasi mutlak :Sakit jiwa yang membahayakan anak dan mengidap kanker payudara'.PHP_EOL.'- Kontraindikasi relatif : Hepatitis, Lepra, HIV dan AIDS';
		$this->choiceMenuTest = '1. Pilih Alat Kontrasepsi'.PHP_EOL.'2. Informasi Alat Kontrasepsi'.PHP_EOL.'3. Kembali';

		$this->implanUrl = 'https://cdn.1itmedia.co.id/5b9d6b2127b6725478cfd026646c19e5.png';
		$this->iudUrl = 'https://cdn.1itmedia.co.id/e80f421d9d94f4a4f6adf55a79127975.png';
		$this->tubektomiUrl = 'https://cdn.1itmedia.co.id/72bce7f61eac5ef782588d320ecccb75.png';
		$this->suntikUrl = 'https://cdn.1itmedia.co.id/d0d5c65381af0c17691896d46161e47b.png';
		$this->vasektomiUrl = 'https://cdn.1itmedia.co.id/2831f705d7357463434096dd9f51cd2c.png';
		$this->pilUrl = 'https://cdn.1itmedia.co.id/c7a7f723d9c528ef21dfd3d0b6db3c93.png';

	}

	/**
	 * Minta nama, tanggal lahir, jenis kelamin, alamat, provinsi, kabupaten, kota. Setelah itu generate image dan member id
	 * 
	 * 
	 **/
	public function callbackAction ($payload) {
		$result = '';

		if (empty(trim($payload['message']))) {
			$this->delivery->addError(409, 'Silahkan masukkan pesan');
			return $this->delivery;
		}

		$authApi = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $payload['receiver']]);
		if (empty($authApi)) {
			$this->delivery->addError(409, 'Config error!');
			return $this->delivery;
		}

		$existsMember = $this->repository->findOne('member_kbs', ['phone_number' => $payload['phone'], 'id_auth_api' => $authApi->id_auth_api]);
		if (empty($existsMember)) {
			$newMember = [
				'id_auth_api' => $authApi->id_auth_api,
				'provinsi' => 'Jawa Barat',
				'phone_number' => $payload['phone'],
				'created_at' => date('Y-m-d H:i:s'),
				'wablas_phone_number_receiver' => $payload['receiver']
			];
			$action = $this->repository->insert('member_kbs', $newMember);
			$newMember['id'] = $action;
			$existsMember = $this->repository->findOne('member_digitals', ['id' => $newMember['id']]);
			$this->delivery->addError(409, $this->welcomeText);
			return $this->delivery;
		}
		if (self::APP_ENV != 'dev') {
			if ($existsMember->last_wablas_id == $payload['id']) {
				die();
			} else {
				$action = $this->repository->update('member_kbs', ['last_wablas_id' => $payload['id']], ['id' => $existsMember->id]);
			}
		}

		if (empty($existsMember->name)) {
			if (preg_match('/[\^£$:%&*()}{@#~?><>|=_+¬-]/', $payload['message']) > 0) {
				$this->delivery->addError(400, 'Silahkan isi nama anda: (contoh: Agus Sopian)');
				return $this->delivery;
			}
			$data = [
				'name' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
			return $this->delivery;
		}

		if (empty($existsMember->birthday)) {
			if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/", $payload['message'])) {
			    $this->delivery->addError(409, 'Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
			    return $this->delivery;
			}
			$formattedDate = date('Y-m-d', strtotime($payload['message']));
			if (age($formattedDate) <= 21) {
				$this->delivery->addError(400, 'Anda masih dibawah umur yang disarankan. Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
				return $this->delivery;
			}
			$data = [
				'birthday' => $formattedDate,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi jenis kelamin anda: (P/L)');
			return $this->delivery;
		}

		if (empty($existsMember->gender)) {
			$options = [
				'P' => 'female',
				'L' => 'male',
				'p' => 'male',
				'l' => 'female'
			];

			if (!isset($options[$payload['message']])) {
				$this->delivery->addError(409,' Silahkan isi jenis kelamin anda: (P/L)');
				return $this->delivery;
			}
			$data = [
				'gender' => $options[$payload['message']],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kota/kabupaten anda:');
			return $this->delivery;
		}

		/* if (empty($existsMember->provinsi)) {
			$argsProvince['name'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findProvince = $this->repository->findOne('provinces', $argsProvince);
			if (empty($findProvince) || strtolower($findProvince->name) != 'jawa barat') {
				$this->delivery->addError(400, 'Saat ini layanan kami hanya tersedia di Jawa Barat. Silahkan masukkan Jawa Barat untuk melanjutkan.'.PHP_EOL.PHP_EOL.'Silahkan isi provinsi anda:');
				return $this->delivery;
			}
			$message = $findProvince->name;
			$data = [
				'provinsi' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kota/kabupaten anda:');
			return $this->delivery;
		} */

		if (empty($existsMember->kabupaten)) {
			// untuk isi `kabupaten` cari di table `districts`
			$argsKabupaten['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findProvince = $this->repository->findOne('provinces', ['name' => $existsMember->provinsi]);
			$args['id_prov'] = $findProvince->id;
			$findKabupaten = $this->repository->find('districts', $argsKabupaten);
			if (count($findKabupaten) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findKabupaten).' kota/kabupaten dengan nama tersebut. Silahkan ketik salah satu kota/kabupaten dengan lengkap.';
				foreach ($findKabupaten as $kabupaten) {
					$message .= PHP_EOL.'- '.$kabupaten->nama;
					if (strtolower($payload['message']) == strtolower($kabupaten->nama)) {
						$findKabupaten[0] = $kabupaten;
						$isExact = true;
						break;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findKabupaten)) {
				$allKabupaten = $this->repository->find('districts', ['id_prov' => $findProvince->id]);
				$kabupatenMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu kota/kabupaten berikut:';
				foreach ($allKabupaten as $kabupaten) {
					$kabupatenMessage .= PHP_EOL.'- '.$kabupaten->nama;
				}
				$this->delivery->addError(400, $kabupatenMessage);
				return $this->delivery;
			}
			$message = $findKabupaten[0]->nama;
			$data = [
				'kabupaten' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kecamatan anda:');
			return $this->delivery;
		}

		if (empty($existsMember->kecamatan)) {
			// untuk isi `kecamatan` cari di table `sub_district`
			$argsKecamatan['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findKabupaten = $this->repository->findOne('districts', ['nama' => $existsMember->kabupaten]);
			$argsKecamatan['id_kab'] = $findKabupaten->id_kab;
			$findKecamatan = $this->repository->find('sub_district', $argsKecamatan);
			if (count($findKecamatan) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findKecamatan).' kecamatan dengan nama tersebut. Silahkan ketik salah satu kecamatan dengan lengkap.';
				foreach ($findKecamatan as $kecamatan) {
					$message .= PHP_EOL.'- '.$kecamatan->nama;
					if (strtolower($payload['message']) == strtolower($kecamatan->nama)) {
						$isExact = true;
						$findKecamatan[0] = $kecamatan;
						break;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findKecamatan)) {
				$allKecamatan = $this->repository->find('sub_district', ['id_kab' => $findKabupaten->id_kab]);
				$kecamatanMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu kecamatan berikut:';
				foreach ($allKecamatan as $kecamatan) {
					$kecamatanMessage .= PHP_EOL.'- '.$kecamatan->nama;
				}
				$this->delivery->addError(400, $kecamatanMessage);
				return $this->delivery;
			}

			$message = $findKecamatan[0]->nama;
			$data = [
				'kecamatan' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi desa/kelurahan anda:');
			return $this->delivery;
		}

		if (empty($existsMember->kelurahan)) {
			// untuk isi `kelurahan` cari di table `urban_village`
			$argsKelurahan['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findKecamatan = $this->repository->findOne('sub_district', ['nama' => $existsMember->kecamatan]);
			$argsKelurahan['id_kec'] = $findKecamatan->id_kec;
			$findKelurahan = $this->repository->find('urban_village', $argsKelurahan);
			if (count($findKelurahan) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findKelurahan).' desa/kelurahan dengan nama tersebut. Silahkan ketik salah satu desa/kelurahan dengan lengkap.';
				foreach ($findKelurahan as $kelurahan) {
					$message .= PHP_EOL.'- '.$kelurahan->nama;
					if (strtolower($payload['message']) == strtolower($kelurahan->nama)) {
						$isExact = true;
						$findKelurahan[0] = $kelurahan;
						break;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findKelurahan)) {
				$allKelurahan = $this->repository->find('urban_village', ['id_kec' => $findKecamatan->id_kec]);
				$kelurahanMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu desa/kelurahan berikut:';
				foreach ($allKelurahan as $kelurahan) {
					$kelurahanMessage .= PHP_EOL.'- '.$kelurahan->nama;
				}
				$this->delivery->addError(400, $kelurahanMessage);
				return $this->delivery;
			}

			$message = $findKelurahan[0]->nama;
			$data = [
				'kelurahan' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi alamat lengkap anda:');
			return $this->delivery;
		}

		if (empty($existsMember->address)) {
			$data = [
				'address' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, $this->generalMenuText);
			return $this->delivery;
		}

		if ($existsMember->wablas_menu_state == self::WABLAS_MENU_STATE_TWO) {
			$this->handleCallbackMenuTes($existsMember, $payload['message']);
			return $this->delivery;
		} else if ($existsMember->wablas_menu_state == self::WABLAS_MENU_STATE_CHOICE_TEST) {
			$this->handleCallbackMenuChoiceTestAction($existsMember, $payload['message']);
			return $this->delivery;
		}

		$message = strtolower($payload['message']);
		if (!in_array($message, ['1', '2', '3'])) {
			$this->delivery->addError(400, $this->generalMenuText);
			return $this->delivery;
		} else {
			if ($message == '1') {
				$this->getMe($existsMember);
				return $this->delivery;
			} else if ($message == '2') {
				$data = [
					'wablas_menu_state' => self::WABLAS_MENU_STATE_CHOICE_TEST,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_kbs', $data, ['id' => $existsMember->id]);
				$this->handleCallbackMenuChoiceTest($existsMember, $payload['message']);
				return $this->delivery;
			} else if ($message == '3') {
				$this->handleCallbackMenuHistory($existsMember, $payload['message']);
				return $this->delivery;
			}
		}

	}

	private function getMe ($member) {
		$genderText = [
			'male' => 'Laki-laki',
			'female' => 'Perempuan'
		];
		$meText = 'Nama: '.$member->name.PHP_EOL.'Tanggal Lahir: '.$member->birthday.PHP_EOL.'Umur: '.age($member->birthday).PHP_EOL.'Jenis Kelamin: '.$genderText[$member->gender].PHP_EOL.'Provinsi: '.$member->provinsi.PHP_EOL.'Kabupaten: '.$member->kabupaten.PHP_EOL.'Kecamatan: '.$member->kecamatan.PHP_EOL.'Kelurahan: '.$member->kelurahan.PHP_EOL.'Alamat: '.$member->address.PHP_EOL.PHP_EOL.$this->generalMenuText;
		$this->delivery->data = $meText;
	}

	private function handleCallbackMenuChoiceTest ($member, $message) {
		$this->delivery->data = $this->choiceMenuTest;
	}

	private function handleCallbackMenuChoiceTestAction ($member, $message) {
		if ($message == '1') {
			$dataMember = [
				'wablas_menu_state' => self::WABLAS_MENU_STATE_TWO,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$actionMember = $this->repository->update('member_kbs', $dataMember, ['id' => $member->id]);
			$this->handleCallbackMenuTes($member, $message);
		} else if ($message == '2') {
			$dataMember = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$actionMember = $this->repository->update('member_kbs', $dataMember, ['id' => $member->id]);
			$text = 'Informasi Alat Kontrasepsi https://bit.ly/Informasikontrasepsi'.PHP_EOL.PHP_EOL.$this->generalMenuTextRaw;
			$this->delivery->data = $text;
		} else if ($message == '3') {
			$dataMember = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$actionMember = $this->repository->update('member_kbs', $dataMember, ['id' => $member->id]);
			$this->delivery->data = $this->generalMenuText;
		} else {
			$text = $this->choiceMenuTest;
			$this->delivery->data = $text;
		}
		return true;
	}

	private function handleCallbackMenuTes($member, $message) {
		$existsRecord = $this->repository->findOne('member_kb_records', ['is_finish' => 0, 'member_kb_id' => $member->id]);
		if (empty($existsRecord)) {
			$newRecord = [
				'member_kb_id' => $member->id,
				'is_finish' => 0,
				'created_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('member_kb_records', $newRecord);
			$this->delivery->data = 'Jumlah anak: (Pilih angka yang tersedia)'.PHP_EOL.'1. Belum Punya'.PHP_EOL.'2. 1 sampai 2'.PHP_EOL.'3. Lebih dari 2';
			return $newRecord;
		}

		if (empty($existsRecord->total_children)) {
			$options = [
				'1' => self::TOTAL_CHILDREN_NOT_HAVE,
				'2' => self::TOTAL_CHILDREN_ONE_UNTIL_TWO,
				'3' => self::TOTAL_CHILDREN_MORE_THAN_TWO,
			];

			if (!isset($options[$message])) {
				$this->delivery->addError(409, 'Jumlah anak: (Pilih angka yang tersedia)'.PHP_EOL.'1. Belum Punya'.PHP_EOL.'2. 1 sampai 2'.PHP_EOL.'3. Lebih dari 2');
				return $existsRecord;
			}
			$data = [
				'total_children' => $options[$message],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);
			$this->delivery->addError(409, 'Apakah anda ingin masih ingin memiliki anak lagi? (Ya/Tidak)');
			return $existsRecord;
		}

		if (empty($existsRecord->is_want_more_children)) {
			$options = [
				'tidak' => 'tidak',
				'ya' => 'ya',
			];

			if (!isset($options[strtolower($message)])) {
				$this->delivery->addError(409, 'Apakah anda ingin masih ingin memiliki anak lagi? (Ya/Tidak)');
				return $this->delivery;
			}
			$data = [
				'is_want_more_children' => $options[strtolower($message)],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);
			if ($member->gender == 'female' && empty($existsRecord->is_breastfeeding)) {
				$this->delivery->addError(409, 'Apakah anda saat ini dalam kondisi menyusui? (Ya/Tidak)');
			} else {
				if ($member->gender == 'female') {
					$this->delivery->addError(400, $this->diseaseWomanText);
				} else if ($member->gender == 'male') {
					$this->delivery->addError(400, $this->diseaseManText);
				}
				return $this->delivery;
			}
			return $this->delivery;
		}

		if ($member->gender == 'female' && empty($existsRecord->is_breastfeeding)) {
			$options = [
				'tidak' => 'tidak',
				'ya' => 'ya',
			];

			if (!isset($options[strtolower($message)])) {
				$this->delivery->addError(409, 'Apakah anda saat ini dalam kondisi menyusui? (Ya/Tidak)');
				return $this->delivery;
			}
			$data = [
				'is_breastfeeding' => $options[strtolower($message)],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);
			if ($member->gender == 'female') {
				$this->delivery->addError(400, $this->diseaseWomanText);
			} else if ($member->gender == 'male') {
				$this->delivery->addError(400, $this->diseaseManText);
			}
			return $existsRecord;
		}

		if (empty($existsRecord->diseases)) {
			$data = [
				'diseases' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);

			$additionalQuestion = $this->handleAdditionalQuestion($member, $existsRecord);
			if (!empty($additionalQuestion['message'])) {
				$this->delivery->addError(400, $additionalQuestion['message']);
				return $this->delivery;
			} else {
				$data = [
					'is_finish' => 1,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);
				$dataMember = [
					'wablas_menu_state' => null,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$actionMember = $this->repository->update('member_kbs', $dataMember, ['id' => $member->id]);
			}
		}

		$additionalQuestion = $this->handleAdditionalQuestion($member, $existsRecord);
		if (!empty($additionalQuestion['message'])) {
			$options = $additionalQuestion['options'];
			if (!isset($options[strtolower($message)])) {
				$this->delivery->addError(400, $additionalQuestion['message']);
				return $this->delivery;
			}
			$column = $additionalQuestion['column'];
			$data = [];
			$data[$column] = strtolower($message);
			$data['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);
			$existsRecord = $this->repository->findOne('member_kb_records', ['id' => $existsRecord->id]);
		}

		$additionalQuestion = $this->handleAdditionalQuestion($member, $existsRecord);
		if (!empty($additionalQuestion['message'])) {
			$this->delivery->addError(400, $additionalQuestion['message']);
			return $this->delivery;
		} else {
			$data = [
				'is_finish' => 1,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_kb_records', $data, ['id' => $existsRecord->id]);
			$dataMember = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$actionMember = $this->repository->update('member_kbs', $dataMember, ['id' => $member->id]);
		}

		$existsRecord = $this->repository->findOne('member_kb_records', ['id' => $existsRecord->id]);
		$link = 'https://api.1itmedia.co.id/auth_api/member_kb/result/'.$existsRecord->id;
		$text = 'Hasil anda telah keluar dan dapat dilihat di link berikut:'.PHP_EOL.$link.PHP_EOL.PHP_EOL;

		$classifications = $this->getSuitableClassification($member, $existsRecord)->data;
		if (empty($classifications)) {
			$text .= 'Informasi Alat Kontrasepsi https://bit.ly/Informasikontrasepsi'.PHP_EOL.PHP_EOL;
		}

		$findPenyuluh = $this->repository->findOne('member_kb_penyuluhs', ['kelurahan' => $member->kelurahan]);
		if (empty($findPenyuluh)) {
			$findPenyuluh = $this->repository->findOne('member_kb_penyuluhs');
		}

		$findBidan = $this->repository->findOne('member_kb_bidans', ['kelurahan' => $member->kelurahan]);
		if (empty($findBidan)) {
			$findBidan = $this->repository->findOne('member_kb_bidans');
		}

		if (!empty($findPenyuluh)) {
			$waNumber = $findPenyuluh->whatsapp_number;
			if ($waNumber[0] == '0') {
				$waNumber = substr($waNumber, 1);
			}
			if (substr($waNumber,0, 2) == '62') {
				$waNumber = substr($waNumber, 2);
			}
			$text .= 'Info Penyuluh: https://wa.me/62'.$waNumber.PHP_EOL;
		}

		if (!empty($findBidan)) {
			$waNumber = $findBidan->whatsapp_number;
			if ($waNumber[0] == '0') {
				$waNumber = substr($waNumber, 1);
			}
			if (substr($waNumber,0, 2) == '62') {
				$waNumber = substr($waNumber, 2);
			}
			$text .= 'Info Bidan: https://wa.me/62'.$waNumber.PHP_EOL;
		}
		$text .= 'Satgas PPS Jawa Barat: https://wa.me/message/3PAYOZLYEC4JB1'.PHP_EOL;

		$menuText = $this->generalMenuText;
		if (empty($classifications)) {
			$menuText = PHP_EOL.$this->generalMenuTextRaw;
		}


		$formattedPayload = [];
		$formattedPayload[] = [
			'category' => 'text',
			'message' => $text
		];
		$formattedPayload[] = [
			'category' => 'text',
			'message' => $menuText
		];
		$publishPayload = [];

		$imageArray = [];
		if ($member->gender == 'male') {
			if (!empty($classifications)) {
				foreach ($classifications as $classification) {
					if (strpos(strtolower($classification->name), 'vasektomi') !== false) {
						$formattedPayload[] = [
							'category' => 'image',
							'message' => 'Tes Gambar',
							'mime_type' => 'image/png',
							'url_file' => $this->vasektomiUrl
						];
						$publishPayload[] = [
							'phone' => $member->phone_number,
							'image' => $this->vasektomiUrl,
							'caption' => ''
						];
					}
				}
			}
		} else if ($member->gender == 'female') {
			if (!empty($classifications)) {
				foreach ($classifications as $classification) {
					if (!isset($imageArray[$classification->image_url])) {
						$imageArray[$classification->image_url] = true;
						$publishPayload[] = [
							'phone' => $member->phone_number,
							'image' => $classification->image_url,
							'caption' => ''
						];
					}
				}
			}
		}

		$extras = null;
		if (!empty($publishPayload)) {
			$extras = $this->sendWablasToMember($member, $publishPayload);
		}


		header('Content-Type: application/json');
		$this->delivery->addError(409, json_encode(['data' => $formattedPayload, 'extras' => $extras]));
		return $existsRecord;
	}

	private function handleCallbackMenuHistory ($member, $message) {
		$records = $this->repository->find('member_kb_records', ['is_finish' => 1, 'member_kb_id' => $member->id]);
		if (empty($records)) {
			$text = 'Anda belum melakukan tes rekomendasi alat kontrasepsi. Silahkan pilih menu nomor 2 terlebih dahulu.'.PHP_EOL;
		} else {
			$text = 'Berikut histori konsultasi anda:'.PHP_EOL;
			$index = 1;
			foreach ($records as $record) {
				$link = 'https://api.1itmedia.co.id/auth_api/member_kb/result/'.$record->id;
				$text .= $index.'. '.$link.PHP_EOL;
				$index++;
			}
		}

		$text .= PHP_EOL.$this->generalMenuText;
		$this->delivery->data = $text;
		return $records;
	}

	public function getSuitableClassification ($member, $record) {
		$args = [];
		if ($member->gender == 'male') {
			$args['gender'] = 'male';
		} else if ($member->gender == 'female') {
			$args['gender'] = 'female';
			$args['is_breastfeeding'] = $record->is_breastfeeding;
		}

		$totalChildren = 0;
		if ($record->total_children == self::TOTAL_CHILDREN_NOT_HAVE) {
			$totalChildren = 0;
		} else if ($record->total_children == self::TOTAL_CHILDREN_ONE_UNTIL_TWO) {
			$totalChildren = 1;
		} else if ($record->total_children == self::TOTAL_CHILDREN_MORE_THAN_TWO) {
			$totalChildren = 3;
		}

		$args['min_total_children <='] = $totalChildren;
		$args['max_total_children >='] = $totalChildren;

		$age = age($member->birthday);
		$args['min_age <='] = $age;
		$args['max_age >='] = $age;

		$questionList = [
			'is_question_tubektomi',
			'is_question_akdr',
			'is_question_implant',
			'is_question_suntik_kb_kombinasi',
			'is_question_suntik_kb_progestin',
			'is_question_pil_progestin',
			'is_question_pil_kombinasi',
			'is_question_mal',
		];
		$argsOrWhere = [];
		foreach ($questionList as $question) {
			if ($record->{$question} == 'tidak') {
				$argsOrWhere[$question] = 'tidak';
			}
		}


		$classifications = $this->repository->find('member_kb_classifications', $args, $argsOrWhere);
		if (!empty($classifications)) {
			foreach ($classifications as $classification) {
				$classification->allowed_criterias = json_decode($classification->allowed_criterias);
				$classification->not_allowed_criterias = json_decode($classification->not_allowed_criterias);
			}
		}
		$this->delivery->data = $classifications;
		return $this->delivery;
	}

	public function getTotalChildren ($value) {
		$totalChildren = 0;
		if ($value == self::TOTAL_CHILDREN_NOT_HAVE) {
			$totalChildren = 0;
		} else if ($value == self::TOTAL_CHILDREN_ONE_UNTIL_TWO) {
			$totalChildren = 1;
		} else if ($value == self::TOTAL_CHILDREN_MORE_THAN_TWO) {
			$totalChildren = 3;
		}
		return $totalChildren;
	}

	private function handleAdditionalQuestion ($member, $record) {
		$options = [
			'tidak' => 'tidak',
			'ya' => 'ya',
		];
		$result = [
			'message' => null,
			'column' => null,
			'options' => $options
		];
		if ($member->gender == 'female') {
			$totalChildren = $this->getTotalChildren($record->total_children);
			if (age($member->birthday) >= 36 && $totalChildren >= 3 && $record->is_breastfeeding == 'tidak' && empty($record->is_question_tubektomi)) {
				$result['message'] = $this->questionWow;
				$result['column'] = 'is_question_tubektomi';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 49 && $totalChildren >= 1 && $record->is_breastfeeding == 'ya' && empty($record->is_question_akdr)) {
				$result['message'] = $this->questionAkdr;
				$result['column'] = 'is_question_akdr';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 49 && $totalChildren >= 1 && $record->is_breastfeeding == 'ya' && empty($record->is_question_implant)) {
				$result['message'] = $this->questionImplant;
				$result['column'] = 'is_question_implant';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 49 && $record->total_children == self::TOTAL_CHILDREN_ONE_UNTIL_TWO && $record->is_breastfeeding == 'tidak' && empty($record->is_question_suntik_kb_kombinasi)) {
				$result['message'] = $this->questionSuntikKbKombinasi;
				$result['column'] = 'is_question_suntik_kb_kombinasi';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 49 && $record->total_children == self::TOTAL_CHILDREN_ONE_UNTIL_TWO && $record->is_breastfeeding == 'ya' && empty($record->is_question_suntik_kb_progestin)) {
				$result['message'] = $this->questionSuntikKbProgestin;
				$result['column'] = 'is_question_suntik_kb_progestin';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 49 && $record->total_children == self::TOTAL_CHILDREN_ONE_UNTIL_TWO && $record->is_breastfeeding == 'ya' && empty($record->is_question_pil_progestin)) {
				$result['message'] = $this->questionPilProgestin;
				$result['column'] = 'is_question_pil_progestin';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 49 && $record->total_children == self::TOTAL_CHILDREN_ONE_UNTIL_TWO && $record->is_breastfeeding == 'tidak' && empty($record->is_question_pil_kombinasi)) {
				$result['message'] = $this->questionPilKombinasi;
				$result['column'] = 'is_question_pil_kombinasi';
			} else if (age($member->birthday) >= 21 && age($member->birthday) <= 35 && $record->total_children == self::TOTAL_CHILDREN_ONE_UNTIL_TWO && $record->is_breastfeeding == 'ya' && empty($record->is_question_mal)) {
				$result['message'] = $this->questionMal;
				$result['column'] = 'is_question_mal';
			}

		}

		return $result;
	}

	private function sendWablasToMember ($member, $payload) {
		$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => self::MAIN_WABLAS]);
		if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
			return $result;
		}
		$waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
		$sendWa = $waService->publishMessage('send_bulk_image', null, null, null, null, null, null, null, null, json_encode($payload));
		return $sendWa;
	}

}