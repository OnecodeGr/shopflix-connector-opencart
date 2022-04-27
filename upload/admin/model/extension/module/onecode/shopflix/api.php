<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * @property-read \Config $config
 * @property-read \Request $request
 * @property-read \Language $language
 * @property-read \ModelUserApi $model_user_api
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \ModelLocalisationCountry $model_localisation_country
 * @property-read \GuzzleHttp\Client $client
 */
class ModelExtensionModuleOnecodeShopflixApi extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('user/api');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->model('localisation/country');
        $catalog = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        $catalog = parse_url($catalog, PHP_URL_HOST) == 'opencart.test' ? 'http://apache/' : $catalog;
        $this->client = new Client(['base_uri' => $catalog . 'index.php']);
    }

    public function apiLogout(string $token): void
    {
        $this->model_user_api->deleteApiSessionBySessionId($token);
    }

    public function apiLogin(): string
    {
        $api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));
        try
        {
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/login',
                ],
                RequestOptions::FORM_PARAMS => [
                    'key' => $api_info['key'],
                ],
            ]);
            $body = json_decode($res->getBody()->getContents(), true);
            if ($res->getStatusCode() != 200 || ! isset($body['api_token']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, json_encode($body)));
                throw new RuntimeException('Error on OC API login');
            }
            return $body['api_token'];
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                $e->getMessage()));
            return '';
        }
    }

    public function apiCustomer(array $order, string $api_token): bool
    {
        try
        {
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/customer',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'firstname' => strlen($order['customer_firstname']) ? $order['customer_firstname'] : 'unknown',
                    'lastname' => strlen($order['customer_lastname']) ? $order['customer_lastname'] : 'unknown',
                    'email' => $order['customer_email'],
                    'telephone' => '000',
                ],
            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                $error = is_array($body['error']) ? implode(', ', $body['error']) : $body['error'];
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                    $error));
                throw new RuntimeException($error ?? 'Error on customer');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiProduct(array $items, string $api_token): bool
    {
        try
        {
            $products = [];
            foreach ($items as $item)
            {
                $product = $this->model_extension_module_onecode_shopflix_product->getCatalogProductBySku($item['sku']);
                if (! isset($product['product_id']))
                {
                    continue;
                }
                $product_data = [
                    'product_id' => $product['product_id'],
                    'quantity' => $item['quantity'],
                ];
                $products[] = $product_data;
            }
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/cart/add',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'product' => $products,
                ],
            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on products');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiAddressPayment(array $items, string $api_token, string $type): bool
    {
        try
        {
            $record = [];
            $countries = $this->model_localisation_country->getCountries();
            foreach ($items as $row)
            {
                if ($row['type'] != $type)
                {
                    continue;
                }

                $s_c = array_filter($countries, function ($item) use ($row) {
                    return $item['iso_code_2'] == $row['country_id'];
                });
                $s_c = current($s_c);
                $country_id = count($s_c) > 0 && isset($s_c['country_id']) ? $s_c['country_id'] : 0;
                $record = [
                    'firstname' => strlen($row['firstname']) ? $row['firstname'] : 'unknown',
                    'lastname' => strlen($row['lastname']) ? $row['lastname'] : 'unknown',
                    'address_1' => $row['street'],
                    'postcode' => $row['postcode'],
                    'city' => $row['city'],
                    'zone_id' => 0,
                    'country_id' => $country_id,
                    'custom_field' => [
                        'phone' => $row['telephone'],
                        'email' => $row['email'],
                    ],
                ];
                break;
            }
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/payment/address',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => $record,

            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on payment address');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiAddressShipping(array $items, string $api_token, string $type): bool
    {
        try
        {
            $record = [];
            $countries = $this->model_localisation_country->getCountries();
            foreach ($items as $row)
            {
                if ($row['type'] != $type)
                {
                    continue;
                }
                $s_c = array_filter($countries, function ($item) use ($row) {
                    return $item['iso_code_2'] == $row['country_id'];
                });
                $s_c = current($s_c);
                $country_id = count($s_c) > 0 && isset($s_c['country_id']) ? $s_c['country_id'] : 0;
                $record = [
                    'firstname' => strlen($row['firstname']) ? $row['firstname'] : 'unknown',
                    'lastname' => strlen($row['lastname']) ? $row['lastname'] : 'unknown',
                    'address_1' => $row['street'],
                    'postcode' => $row['postcode'],
                    'city' => $row['city'],
                    'zone_id' => 0,
                    'country_id' => $country_id,
                    'custom_field' => [
                        'phone' => $row['telephone'],
                        'email' => $row['email'],
                    ],
                ];
                break;
            }
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/shipping/address',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => $record,

            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on shipping address');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiShippingMethod(string $api_token): bool
    {
        try
        {
            $res_a = $this->client->get('', [
                RequestOptions::QUERY => [
                    'route' => 'api/shipping/methods',
                    'api_token' => $api_token,
                ],
            ]);
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/shipping/method',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'shipping_method' => $this->model_extension_module_onecode_shopflix_config->shippingMethod(),
                ],

            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on shipping method');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiPaymentMethod(string $api_token): bool
    {
        try
        {
            $res_a = $this->client->get('', [
                RequestOptions::QUERY => [
                    'route' => 'api/payment/methods',
                    'api_token' => $api_token,
                ],
            ]);
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/payment/method',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'payment_method' => $this->model_extension_module_onecode_shopflix_config->paymentMethod(),
                ],

            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on payment method');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiOrderAdd(array $order_data, array $invoice_data, string $api_token): int
    {
        try
        {
            $rows = [sprintf('%s: %s', $this->language->get('text_customer_comment'), $order_data['customer_note'])];
            if (! empty($invoice_data))
            {
                $rows[] = sprintf("%s:",$this->language->get('text_invoice_info'));
                $rows[] = '-------------------------';
                $rows[] = sprintf('%s: %s', $this->language->get('text_invoice_customer_name'), $invoice_data['name']);
                $rows[] = sprintf('%s: %s', $this->language->get('text_invoice_customer_owner'), $invoice_data['owner']);
                $rows[] = sprintf('%s: %s', $this->language->get('text_invoice_customer_vat'), $invoice_data['vat']);
                $rows[] = sprintf('%s: %s', $this->language->get('text_invoice_customer_tax_office'), $invoice_data['tax_office']);
                $rows[] = sprintf('%s: %s', $this->language->get('text_invoice_customer_address'), $invoice_data['address']);
                $rows[] = '-------------------------';
            }
            $rows = implode("\r\n", $rows);

            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/order/add',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'order_status_id' => 1,
                    'comment' => $rows,
                ],
            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']) || ! isset($body['order_id']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on order add');
            }
            return intval($body['order_id']);
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }

    public function apiOrderDelete(int $order_id, string $api_token): bool
    {
        try
        {
            $res = $this->client->get('', [
                RequestOptions::QUERY => [
                    'route' => 'api/order/delete',
                    'api_token' => $api_token,
                    'order_id' => $order_id,
                ],
            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new RuntimeException($body['error'] ?? 'Error on order delete');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new RuntimeException($e->getMessage());
        }
    }
}