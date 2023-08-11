<?php declare(strict_types=1);

namespace Andri\Engine\Admin\Domain\Product\Entities;

use Andri\Engine\Shared\Response;

class ProductDetail {

    /**
     * Reformat data
     * 
     * @param array $product
     * @param Andri\Engine\Shared\Response $response
     * @return void
     */
    public static function reformat(array $product, Response $response) {
        $data = $response->incomingData;

        if (isset($data['price']) && (float)$data['price'] < 1) {
            $data['price'] = 100.0;
        }

        if (isset($data['stock']) && (int)$data['stock'] < 1) {
            $data['stock'] = 1;
        }

        if (isset($data['variable'])) {
            $data['variable']    = json_encode($data['variable']);
        }
        $data['sku_product'] = $product['sku'];

        if (isset($data['image_path']) && empty($data['image_path'])) {
            $data['image_path'] = null;
        }

        unset($data['id_auth']);
        $response->incomingData = (array)$data;
    }


}