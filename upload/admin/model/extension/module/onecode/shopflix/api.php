<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;
use Onecode\ShopFlixConnector\Library\Interfaces\OrderInterface;

/**
 * @property-read \Config $config
 * @property-read \Request $request
 * @property-read \ModelUserApi $model_user_api
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
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
        $catalog = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        $catalog = parse_url($catalog, \PHP_URL_HOST) == 'opencart.test' ? 'http://apache/' : $catalog;
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
                throw new \RuntimeException('Error on login');
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
                    'firstname' => $order['customer_firstname'],
                    'lastname' => $order['customer_lastname'],
                    'email' => $order['customer_email'],
                    'telephone' => '000',
                ],
            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                    $body['error']));
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on customer');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
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
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on products');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__,
                $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function apiAddressPayment(array $items, string $api_token, string $type): bool
    {
        try
        {
            $record = [];
            foreach ($items as $row)
            {
                if ($row['type'] != $type)
                {
                    continue;
                }
                $record = [
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'address_1' => $row['street'],
                    'postcode' => $row['postcode'],
                    'city' => $row['city'],
                    'zone_id' => 0,
                    'country_id' => $row['country_id'],
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
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on payment address');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function apiAddressShipping(array $items, string $api_token, string $type): bool
    {
        try
        {
            $record = [];
            foreach ($items as $row)
            {
                if ($row['type'] != $type)
                {
                    continue;
                }
                $record = [
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'address_1' => $row['street'],
                    'postcode' => $row['postcode'],
                    'city' => $row['city'],
                    'zone_id' => 0,
                    'country_id' => $row['country_id'],
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
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on shipping address');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
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
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on shipping method');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
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
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on payment method');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function apiOrderAdd(array $order_data, string $api_token): int
    {
        try
        {
            $res = $this->client->post('', [
                RequestOptions::QUERY => [
                    'route' => 'api/order/add',
                    'api_token' => $api_token,
                ],
                RequestOptions::FORM_PARAMS => [
                    'order_status_id' => 1,
                    'comment' => $order_data['customer_note'],
                ],

            ]);
            $raw = $res->getBody()->getContents();
            $body = json_decode($raw, true);
            if ($res->getStatusCode() != 200 || isset($body['error']) || ! isset($body['order_id']))
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on order add');
            }
            return intval($body['order_id']);
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
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
            if ($res->getStatusCode() != 200 || isset($body['error']) )
            {
                error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $body['error']));
                throw new \RuntimeException(isset($body['error']) ? $body['error'] : 'Error on order delete');
            }
            return true;
        }
        catch (GuzzleException $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            throw new \RuntimeException($e->getMessage());
        }
    }

}