<?php
/**
 * Connector.php
 *
 * @copyright Copyright © 2021   All rights reserved.
 * @author    Spyros Bodinis {spyros@onecode.gr}
 */

namespace Onecode\ShopFlixConnector\Library;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Spyrmp\JsonSerializerDeserializer\Json;
use Onecode\ShopFlixConnector\Library\Interfaces\AddressInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ItemInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\OrderInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ShipmentInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ShipmentItemInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ShipmentTrackInterface;

/**
 * Class Connector
 * @package Onecode\ShopFlixConnector\Library
 */
class Connector
{

    const MARKETPLACE_NEW_ORDER_STATUS = "O";
    const MARKETPLACE_CANCEL_ORDER_STATUS = "I";
    const MARKETPLACE_PARTIAL_ORDER_STATUS = "E";
    const MARKETPLACE_SHIPPED_ORDER_STATUS = "K";
    const MARKETPLACE_ON_THE_WAY_ORDER_STATUS = "J";

    private $_httpClient;
    private $_jsonSerializer;
    private $_baseUrl;
    private $_path;
    private $_username;
    private $_password;
    /**
     * @var int
     */
    private $_startTime;
    /**
     * @var int
     */
    private $_endTime;


    public function __construct($username, $apikey, $apiUrl, $modifier = "-6 hours")
    {
        $this->_username = $username;
        $this->_password = $apikey;

        $this->_baseUrl = $apiUrl;
        $this->_jsonSerializer = new Json;
        $this->initiateClient();
        $dateTime = new DateTime();

        $this->_endTime = $dateTime->getTimestamp();
        $dateTime->modify($modifier);
        $this->_startTime = $dateTime->getTimestamp();
    }

    private function initiateClient()
    {
        $urlParts = parse_url($this->_baseUrl);

        $uri = preg_replace('/^www\./', '', ($urlParts['scheme'] ?? "http") . "://" . $urlParts['host']);
        $this->_path = $urlParts['path'] . "/" ?? '';
        $this->_httpClient = new Client(["base_uri" => $uri, "timeout" => 90, 'auth' => [$this->_username, $this->_password]]);
    }


    public function getNewOrders(): array
    {
        return $this->getOrders(self::MARKETPLACE_NEW_ORDER_STATUS);
    }

    private function getOrders($orderStatus, $startTime = false, $endTime = false): array
    {
        $data = [];

        $path = $this->_path . "orders";
        $query = $this->getOrderQueryByStatus($orderStatus, $startTime, $endTime);

        if ($orderStatus == self::MARKETPLACE_SHIPPED_ORDER_STATUS) {
            dd($this->_baseUrl . "/orders?" . http_build_query($query), $this->getPageForOrders($query, true));
        }
        for ($page = 1; $page <= $this->getPageForOrders($query); $page++) {
            $query['page'] = $page;

            $response = $this->_httpClient->get($path, ['query' => $query]);
            $responseObject = $this->_jsonSerializer->deserialize($response->getBody()->getContents());
            foreach ($responseObject['orders'] as $order) {
                $data[] = $this->getOrderDetail($order['order_id']);
            }
        }
        return $data;
    }

    private function getOrderQueryByStatus($orderStatus, $startTime, $endTime): array
    {

        $data = [
            "status" => $orderStatus
        ];

        if ($startTime && $endTime) {
            $data['period'] = "C";
            $data['time_from'] = $startTime;
            $data['time_to'] = $endTime;
        }

        return $data;


    }


    private function getPageForOrders($query, $die = false): int
    {
        $path = $this->_path . "orders";
        $response = $this->_httpClient->get($path, ['query' => $query]);

        $responseObject = $this->_jsonSerializer->deserialize($response->getBody()->getContents());
        if ($die) {
            dd(json_encode($responseObject), $this->_username, $this->_password);
        }
        $itemPerPages = $responseObject['params']['items_per_page'];
        $totalItems = $responseObject['params']['total_items'];
        return (int)ceil($totalItems / $itemPerPages);

    }

    /**
     * @param $orderId
     * @return array
     * @throws GuzzleException
     */
    private function getOrderDetail($orderId): array
    {
        $data = [];
        if ($orderId) {
            $path = $this->_path . "orders/$orderId";
            $response = $this->_httpClient->get($path);
            $responseObject = $this->_jsonSerializer->deserialize($response->getBody()->getContents());

            $data = [
                "order" =>
                    [
                        OrderInterface::SHOPFLIX_ORDER_ID => $responseObject['order_id'],
                        OrderInterface::INCREMENT_ID => $responseObject['order_id'],
                        OrderInterface::STATE => $this->getState($responseObject['status']),
                        OrderInterface::STATUS => $this->getStatus($responseObject['status']),
                        OrderInterface::SUBTOTAL => $responseObject['subtotal'],
                        OrderInterface::DISCOUNT_AMOUNT => $responseObject['discount'],
                        OrderInterface::TOTAL_PAID => $responseObject['total'],
                        OrderInterface::CUSTOMER_EMAIL => $responseObject['email'],
                        OrderInterface::CUSTOMER_FIRSTNAME => $responseObject['firstname'],
                        OrderInterface::CUSTOMER_LASTNAME => $responseObject['lastname'],
                        OrderInterface::CUSTOMER_REMOTE_IP => $responseObject['ip_address'] ?? "",
                        OrderInterface::CUSTOMER_NOTE => $responseObject['notes'],],
                "addresses" => [
                    [
                        AddressInterface::FIRSTNAME => !empty($responseObject["s_firstname"]) ? $responseObject["s_firstname"] : $responseObject['firstname'],
                        AddressInterface::LASTNAME => !empty($responseObject["s_lastname"]) ? $responseObject["s_lastname"] : $responseObject['lastname'],
                        AddressInterface::POSTCODE => $responseObject["s_zipcode"],
                        AddressInterface::TELEPHONE => !empty($responseObject["s_phone"]) ? $responseObject["s_phone"] : $responseObject['phone'],
                        AddressInterface::STREET => $responseObject["s_address"],
                        AddressInterface::ADDRESS_TYPE => "shipping",
                        AddressInterface::CITY => $responseObject['s_city'],
                        AddressInterface::EMAIL => $responseObject['email'],
                        AddressInterface::COUNTRY_ID => $responseObject['s_country'],

                    ],
                    [
                        AddressInterface::FIRSTNAME => !empty($responseObject["b_firstname"]) ? $responseObject["b_firstname"] : $responseObject['firstname'],
                        AddressInterface::LASTNAME => !empty($responseObject["b_lastname"]) ? $responseObject["b_lastname"] : $responseObject['lastname'],
                        AddressInterface::POSTCODE => $responseObject["b_zipcode"],
                        AddressInterface::TELEPHONE => !empty($responseObject["b_phone"]) ? $responseObject["b_phone"] : $responseObject['phone'],
                        AddressInterface::STREET => $responseObject["b_address"],
                        AddressInterface::ADDRESS_TYPE => "billing",
                        AddressInterface::CITY => $responseObject['b_city'],
                        AddressInterface::EMAIL => $responseObject['email'],
                        AddressInterface::COUNTRY_ID => $responseObject['b_country'],
                    ]


                ], "items" => [],];
            foreach ($responseObject['products'] as $product) {
                $data["items"][] = [
                    ItemInterface::SKU => $product['product_code'],
                    ItemInterface::PRICE => $product['price'],
                    ItemInterface::QTY => $product['amount']
                ];
            }

        }
        return $data;
    }


    private function getState($status)
    {

        switch ($status) {
            case "O":
                return "pending_acceptance";
            case "C":
                return "accepted";
            case "D":
                return "cancelled";
        }
    }

    private function getStatus($status)
    {

        switch ($status) {
            case "O":
                return "pending_acceptance";
            case "C":
                return "completed";
            case "D":
                return "cancelled";
        }

    }

    public function getPartialShipped()
    {

        return $this->getOrders(
            self::MARKETPLACE_PARTIAL_ORDER_STATUS,
            $this->_startTime,
            $this->_endTime
        );
    }

    public function getShipped()
    {

        return $this->getOrders(
            self::MARKETPLACE_SHIPPED_ORDER_STATUS,
            $this->_startTime,
            $this->_endTime
        );
    }


    public function getCancelOrders()
    {

        return $this->getOrders(
            self::MARKETPLACE_CANCEL_ORDER_STATUS,
            $this->_startTime,
            $this->_endTime
        );
    }


    public function getOnTheWayOrders()
    {

        return $this->getOrders(
            self::MARKETPLACE_ON_THE_WAY_ORDER_STATUS,
            $this->_startTime,
            $this->_endTime
        );
    }

    /**
     * @throws Exception
     */
    public function picking($orderId)
    {
        $requestData = ["status" => 'G', "notify_user" => 0, "notify_department" => 0, "notify_vendor" => 0];

        $this->updateOrder($orderId, $requestData);

    }

    /**
     * @throws Exception
     */
    private function updateOrder($orderId, $requestData = [])
    {
        $path = $this->_path . "orders/$orderId";
        $response = $this->_httpClient->put($path, [RequestOptions::JSON => $requestData]);
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() <= 500) {
            throw new Exception($response->getBody()->getContents());
        }


        try {
            $this->_jsonSerializer->deserialize($response->getBody()->getContents());
        } catch (InvalidArgumentException $e) {
            throw new Exception($response->getBody()->getContents());
        }


    }

    /**
     * @throws Exception
     */
    public function forShipment($shipmentId)
    {
        $requestData = ["status" => 'A',];

        $this->updateShipment($shipmentId, $requestData);
    }

    private function updateShipment($shipmentId, $requestData = [])
    {

        $path = $this->_path . "shipments/$shipmentId";
        $response = $this->_httpClient->put($path, [RequestOptions::JSON => $requestData]);
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() <= 500) {
            throw new Exception($response->getBody()->getContents());
        }


        try {
            $this->_jsonSerializer->deserialize($response->getBody()->getContents());
        } catch (InvalidArgumentException $e) {
            throw new Exception($response->getBody()->getContents());
        }
    }

    /**
     * @param $orderId
     * @param $message
     * @throws Exception
     */
    public function rejected($orderId, $message)
    {
        $requestData = [
            "status" => 'E',
            "notify_user" => 0,
            "notify_department" => 0,
            "notify_vendor" => 0,
            "details" => $message
        ];

        $this->updateOrder($orderId, $requestData);

    }

    /**
     * @param $orderId
     * @throws Exception
     */
    public function readyToBeShipped($orderId)
    {
        $requestData = [
            "status" => 'H',
            "notify_user" => 0,
            "notify_department" => 0,
            "notify_vendor" => 0
        ];
        $this->updateOrder($orderId, $requestData);
    }


    public function printManifest($shipments)
    {
        $path = $this->_path . "courier";
        $response = $this->_httpClient->get($path, [
                "query" => [
                    "custom_manifest" => 1,
                    "shipments" => implode(",", $shipments)
                ]
            ]
        );
        $content = $response->getBody()->getContents();
        return $this->_jsonSerializer->deserialize($content);
    }

    public function getManifest()
    {
        $path = $this->_path . "courier";

        $response = $this->_httpClient->get($path, ['query' => ['manifest' => 1,]]);

        $content = $response->getBody()->getContents();
        return $this->_jsonSerializer->deserialize($content);
    }

    public function printVoucher($voucher)
    {
        $path = $this->_path . "courier";
        $response = $this->_httpClient->get($path, ['query' => ['print' => $voucher, 'labelFormat' => 'pdf']]);
        $content = $response->getBody()->getContents();
        return $this->_jsonSerializer->deserialize($content);
    }

    public function createVoucher($shipmentId)
    {
        $path = $this->_path . "courier/{$shipmentId}";
        $response = $this->_httpClient->get($path);
        $content = $response->getBody()->getContents();
        return $this->_jsonSerializer->deserialize($content);
    }

    public function getShipmentUrl($shipmentId)
    {
        $path = $this->_path . "shipments";
        $response = $this->_httpClient->get($path, ['query' => ['shipment_id' => $shipmentId,]]);
        $content = $response->getBody()->getContents();
        $json = $this->_jsonSerializer->deserialize($content);

        return $json['shipments'][0]['carrier_info']['tracking_url'];
    }

    public function getVoucher($shipmentId)
    {
        $path = $this->_path . "shipments";
        $response = $this->_httpClient->get($path, ['query' => ['shipment_id' => $shipmentId,]]);
        $content = $response->getBody()->getContents();
        $json = $this->_jsonSerializer->deserialize($content);

        return $json['shipments'][0]['tracking_number'] ?? "";
    }

    public function printVouchers($vouchers)
    {
        $path = $this->_path . "courier";

        $response = $this->_httpClient->get($path, ['query' => ['printmass' => implode(",", $vouchers), 'labelFormat' => 'pdf']]);
        $content = $response->getBody()->getContents();
        return $this->_jsonSerializer->deserialize($content);
    }

    public function getShipment($orderId)
    {
        $path = $this->_path . "shipments";
        $response = $this->_httpClient->get($path, ['query' => ['order_id' => $orderId,

        ]]);
        $content = $response->getBody()->getContents();
        $json = $this->_jsonSerializer->deserialize($content);
        $data = [];
        foreach ($json['shipments'] as $key => $shipment) {
            $data[$key] = [
                "shipment" =>
                    [
                        ShipmentInterface::INCREMENT_ID => $shipment["shipment_id"],
                        ShipmentInterface::SHIPMENT_STATUS => $this->getShippingStatus($shipment['status']),
                        ShipmentInterface::CREATED_AT => $shipment['shipment_timestamp'],
                    ],
                ShipmentInterface::ITEMS => [],
                ShipmentInterface::TRACKS => [
                    ShipmentTrackInterface::TRACK_NUMBER => $shipment['tracking_number'],
                    ShipmentTrackInterface::TRACKING_URL => $shipment['carrier_info']['tracking_url'],

                ],
            ];
            foreach ($shipment['products_info'] as $product) {
                $data[$key][ShipmentInterface::ITEMS][] = [
                    ShipmentItemInterface::SKU => $product['product_id'],
                    ShipmentItemInterface::QTY => $product['product_qty']
                ];
            }
        }

        return $data;
    }

    private function getShippingStatus($status)
    {
        switch ($status) {
            case "P":
                return 1; #pending
            case "A":
                return 2; #creted voucher
            case "S":
                return 3; #on the way
        }
    }
}
