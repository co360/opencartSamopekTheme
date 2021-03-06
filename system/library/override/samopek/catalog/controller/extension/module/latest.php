<?php
class samopek_ControllerExtensionModuleLatest extends ControllerExtensionModuleLatest {
	public function index($setting) {
		$this->load->language('extension/module/latest');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

        $this->load->model('account/wishlist');

        $cartlistIds = [];

        $cartlist = $this->cart->getProducts();
        foreach ($cartlist as $one) {
            $cartlistIds[] = $one['product_id'];
        }

        $wishListProductsIds = $this->model_account_wishlist->getWishlistProductsList();

        $data['products'] = array();

		$filter_data = array(
			'sort'  => 'p.date_added',
			'order' => 'DESC',
			'start' => 0,
			'limit' => $setting['limit']
		);

		$results = $this->model_catalog_product->getProducts($filter_data);

		if ($results) {
			foreach ($results as $result) {

                $product_path = $this->model_catalog_product->getPath($result['product_id']);

				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = $result['rating'];
				} else {
					$rating = false;
				}

                // mako0216
                // Added quantity
                if ((int)$result['quantity']) {
                    $quantity = $result['quantity'];
                } else {
                    $quantity = 0;
                }

                if ($result['quantity'] <= 0) {
                    $stock = $result['stock_status'];
                } elseif ($this->config->get('config_stock_display')) {
                    $stock = $result['quantity'];
                } else {
                    $stock = $this->language->get('text_instock');
                }

                $hasOptions = $this->model_catalog_product->hasOptions($result['product_id']);

                $attributes = [];
                foreach ($this->model_catalog_product->getProductAttributes($result['product_id']) as $one) {
                    foreach ($one['attribute'] as $item) {
                        if (count($attributes) == 2) break;
                        $attributes[] = $item['text'];
                    }
                }

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', $product_path . '&product_id=' . $result['product_id']),
                    'quantity'    => $quantity,
                    'stock'       => $stock,
                    'attributes' => $attributes,
                    'in_wishlist' => in_array($result['product_id'], $wishListProductsIds),
                    'in_cart' => in_array($result['product_id'], $cartlistIds),
                    'hasOptions'  => $hasOptions
				);
			}

			return $this->load->view('extension/module/latest', $data);
		}
	}
}
