# onecode/shopflix_connector_library

Library to connect with Shopflix (https://shopflix.gr) for vendors

# Usage

```php
use \Onecode\ShopFlixConnector\Library\Connector;
$connector = new Connector("username", "appi_key", "api_url");
```

Get new orders

```php
$newOrders = $connector->getNewOrders();
```

Get canceled orders

```php
$canceledOrders = $connector->getCancelOrders();
```

Get partial shipped orders

```php
$partialShipped = $connector->getPartialShipped();
```

Get shipped order

```php
$shipped = $connector->getShipped();
```

Update order to shopflix set status to picking mode use on acceptance.

```php
 $order = 123;#Shopflix Order id
 $connector->picking($orderId);
```

Reject order

```php
 $order = 123;#Shopflix Order id
 $connector->reject($orderId, "The product has been removed");
```

Get shipment for specific order

```php
 $order = 123;#Shopflix Order id
 $shipments =  $connector->getShipment($orderId);
```

Create tracking voucher

```php
 $shipmentId = 123;#Shopflix Shipment id
 $voucher = $connector->createVoucher($shipmentId);
```

Print tracking voucher number

```php
 $trackingVoucher = "tracking_voucher";
 $voucher = $connector->printVoucher($trackingVoucher); 
```

Mass print tracking voucher.Max 20 vouchers

```php
 $trackingVouchers = [
     "tracking_voucher1",
     "tracking_voucher2",
     "tracking_voucher3",
     ...
     "tracking_voucher19",
 ];
 $voucher = $connector->printVouchers($trackingVoucher); 
```

Get tracking voucher number from specific shipment

```php
 $shipmentId = 123;#Shopflix Shipment id
 $voucher = $connector->getVoucher($shipmentId); 
```