<?php
namespace Library;
use DigitalOcean\SpacesConnect;
use GuzzleHttp\Client;

class DigitalOceanService {

    private $cdnLink;
    private $key;
    private $secret;
    private $spaceName;
    private $region;

    public function __construct() {
        /* $this->cdnLink = 'https://cdn.1itmedia.co.id';
        $this->key = 'PCN6LIHBK6AEYBHC47JE';
        $this->secret = 'cI00RyzhK9tUe7HxhkooTfUuAOpsw7Lk8q+rSWW6pHk';
        $this->spaceName = 'sim-cdn';
        $this->region = 'nyc3'; */


        $this->cdnLink = 'https://file.1itmedia.co.id';
        $this->key = 'PCN6LIHBK6AEYBHC47JE';
        $this->secret = 'cI00RyzhK9tUe7HxhkooTfUuAOpsw7Lk8q+rSWW6pHk';
        $this->spaceName = 'sim';
        $this->region = 'sgp1';
    }

    public function setCdnLink ($cdnLink) {
        $this->cdnLink = $cdnLink;
    }

    public function setKey ($key) {
        $this->key = $key;
    }

    public function setSecret ($secret) {
        $this->secret = $secret;
    }

    public function setSpaceName ($spaceName) {
        $this->spaceName = $spaceName;
    }

    public function setRegion ($region) {
        $this->region = $region;
    }

    public function upload ($payload, $name) {
        set_time_limit(0);
        $ci =& get_instance();
        $image_path = $ci->config->item('image_path');

        $config = [
            'upload_path' => $image_path,
            'allowed_types' => '*',
            'encrypt_name' => true
        ];

        $ci->load->library('upload', $config);
        if (!isset($_FILES[$name])) return 'File cannot be empty';
        if (empty($_FILES[$name])) return 'File cannot be empty';
        
        if (!is_dir($image_path))
            mkdir($image_path, 0777, true);
        
        if (!$ci->upload->do_upload($name)) throw new \Exception($ci->upload->display_errors());

        $image = $ci->upload->data();
        $image['upload_path'] = $image_path;
        list($currentWidth, $currentHeight) = getimagesize($image['full_path']);
        $image['cloud_path'] = $this->upload_to_cloud($image['full_path'], $image['file_name']);
        $result = [
            'original_name' => $image['orig_name'],
            'file_name' => $image['file_name'],
            'file_ext' => $image['file_ext'],
            'cdn_url' => $image['cloud_path']['cdn_url']
        ];

        if (!empty($resizePercentage) && is_image(mime_content_type($image['full_path']))) {
            $manipulationWidth = $currentWidth*$resizePercentage/100;
            $manipulationHeight = $currentHeight*$resizePercentage/100;
            $config = [
                'image_library' => 'gd2',
                'source_image' => $image_path. '/' . $image['file_name'],
                'create_thumb' => false,
                'maintain_ratio' => true,
                'width' => $manipulationWidth,
                'height' => $manipulationHeight,
                'new_image' => $image_path .'/'. $image['raw_name'] .'-'. $resizePercentage.$image['file_ext'],
                // 'quality' => 70
            ];
            
            $ci->load->library('image_lib', $config);

            $result['cloud']['path'] = $image['cloud_path']['ObjectURL'];
            $result['cloud']['cdn_path'] = $image['cloud_path']['cdn_url'];
            if($ci->image_lib->resize()) {
                $ci->image_lib->clear();
                $image['full_path_resize'] = $config['new_image'];
                $config = [];
                if (in_array($image['image_type'], ['jpg', 'jpeg'])) {
                    $imgdata=exif_read_data($image['full_path'], 'IFD0');
                    $config = [
                        'image_library' => 'gd2',
                        'source_image' => $image['full_path_resize'],
                    ];
                    if (!empty($imgdata)) {
                        switch($imgdata['Orientation']) {
                            case 3:
                                $config['rotation_angle']='180';
                                break;
                            case 6:
                                $config['rotation_angle']='270';
                                break;
                            case 8:
                                $config['rotation_angle']='90';
                                break;
                        }  
                    }
                }

                $ci->image_lib->initialize($config);
                $ci->image_lib->rotate();

                $image['cloud_path_resize'] = $this->upload_to_cloud($image['full_path_resize'], $image['raw_name'].'-'.$resizePercentage.$image['file_ext']);
                $result['resize'] = [
                    'original_name' => $image['orig_name'],
                    'file_name' => $image['raw_name'].'-'.$resizePercentage.$image['file_ext'],
                    'file_ext' => $image['file_ext'],
                    'width' => $manipulationWidth,
                    'height' => $manipulationHeight,
                    'cloud' => [
                        'path' => $image['cloud_path_resize']['ObjectURL'],
                        'cdn_path' => $image['cloud_path_resize']['cdn_url']
                    ]
                ];
                unlink($image['full_path_resize']);
            }
        }
        unlink($image['full_path']);

        return $result;
    }

    public function upload_to_cloud ($path, $filename) {
        $space = new \SpacesConnect($this->key, $this->secret, $this->spaceName, $this->region);

        $mime = mime_content_type($path);
        $allowed_mime = [
            'image/jpeg',
            'image/png',
            'image/svg+xml',
            'image/webp',
            'video/x-msvideo',
            'video/mpeg',
            'video/ogg',
            'video/webm',
            'video/3gpp',
            'video/3gpp2',
            'application/pdf'
        ];
        if (!in_array($mime, $allowed_mime)) {
            $mime = 'application/octet-stream';
        }
        $result = $space->UploadFile($path, "public", $filename, mime_content_type($path));
        $result['cdn_url'] = sprintf('%s/%s', $this->cdnLink, $filename);
        return $result;
    }

    public function delete ($name) {
        $space = new \SpacesConnect($this->key, $this->secret, $this->spaceName, $this->region);
        try {
            $result = $space->DeleteObject($name);
            return $result;
        } catch (\Exception $e) {
            $result = new \stdClass;
            $result->error = $e->getMessage();
            return $result;
        }
    }

}
