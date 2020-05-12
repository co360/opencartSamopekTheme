<?php
class samopek_ControllerCheckoutRegister extends ControllerCheckoutRegister {

    public function simpleSave() {

        $this->load->language('checkout/checkout');

        $json = array();

        // Validate if customer is already logged out.
        if ($this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/checkout', '', true);
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart');
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('checkout/cart');

                break;
            }
        }

        if (!$json) {
            $this->load->model('account/customer');

            if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
                $json['error']['email'] = $this->language->get('error_email');
            }

            if ($this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
                $json['error']['warning'] = $this->language->get('error_exists');
            }

            // Captcha
            if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
                $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

                if ($captcha) {
                    $json['error']['captcha'] = $captcha;
                }
            }

            if (!$json) {
                if (!isset($this->request->post['password'])) {
                    $this->request->post['password'] = "123123123";
                }
                if (!isset($data['lastname'])) {
                    $this->request->post['lastname'] = "";
                }
                if (!isset($data['telephone'])) {
                    $this->request->post['telephone'] = "";
                }

                $customer_id = $this->model_account_customer->addCustomer($this->request->post);

                // Clear any previous login attempts for unregistered accounts.
                $this->model_account_customer->deleteLoginAttempts($this->request->post['email']);

                $this->session->data['account'] = 'register';

                $this->load->model('account/customer_group');

                // TEMP Default group
                $customer_group_id = $this->config->get('config_customer_group_id');
                $customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

                if ($customer_group_info && !$customer_group_info['approval']) {
                    $this->customer->login($this->request->post['email'], $this->request->post['password']);
                } else {
                    $json['redirect'] = $this->url->link('account/success');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
?>