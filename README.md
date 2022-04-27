# OneCodeGR - ShopFlix plugin

This extension is connecting your OpenCart v3.0.3.x with [SHOPFLIX](https://SHOPFLIX.gr)

## Requirements
* PHP >= 7.4
* [Fix OC 3.x Extension Installer](https://www.opencart.com/index.php?route=marketplace/extension/info&member_token=396ed49ec2c97aab514825fbe62b1b9b&extension_id=33410&filter_category_id=5&filter_license=0&filter_download_id=56&sort=date_added )
  * Download and install the plugin in order to fix the permissions on `system` directory
## Installation
* Upload [onecode-shopflix.ocmod.zip](https://github.com/OnecodeGr/shopflix-connector-opencart/raw/main/onecode-shopflix.ocmod.zip) to your system using **Admin > Extension > Installer**
* Open `Admin > Extensions > Extensions `
* Select `Modules`, and scroll until you find the module name **OneCodeGr - ShopFLix**
* Press install button for the plugin
  ### Grand administration
  * Open **Admin > System > Users > User Groups**
  * Edit `Administrator`
  * Add to `Access Permission` & `Modify Permission` all the choices which contain 
    `extension/module/onecode/*`
  * Add Server IP on `Admin > System > Users > APi ` under default Api Username

## Configuration
* As you have the access on the plugin , you will have a new Left side menu option `OneCode`
* Select `Admin > OneCode > Shopflix > Configuration`
* On configuration screen you will have the ability to enable / disable the plugin, set 
  configuration for shopflix integration, and XML exportation
* As you set your values press *Save*.

## Usage
### Product XML
For Xml process we have two (2) options, an online (produce the XML on each request), & an offline (serve always the latest created XML or created if not exists). More over the XML has two variants (simple/detailed).

For the **_online_** case the urls are:
1. simple/minimal => **{proto}://{open-cart-domain}/index.php?
  route=extension/module/onecode/shopflix/product/feed/createMinimal&token={token-hash}**
2. full/detailed => **{proto}://{open-cart-domain}/index.php?
  route=extension/module/onecode/shopflix/product/feed/createDetailed&token={token-hash}**

For the **_offline_** case the urls are:
* simple/minimal => **{proto}://{open-cart-domain}/index.php?
  route=extension/module/onecode/shopflix/product/feed/minimal&token={token-hash}**
* full/detailed => **{proto}://{open-cart-domain}/index.php?
  route=extension/module/onecode/shopflix/product/feed/detailed&token={token-hash}**

Hints:
  - If you have many products, in order to reduce the execution and synchronization time to the minimum, you can combine both versions (online & offline). You can use the `online` URL
    on your cron engine, and provide to shopflix the `offline` URL.
  - On the other hand, if you want always to provide a fresh product list you can provide it to
      shopflix only `online` URL, without to required from your side to maintain any cron engine.

### Sync Orders
In order to fetch your orders from shopflix you can press the `sync` button under 
`Admin > OneCode > Shopflix > Order `

Also, you can use the plugin [oc-cli](https://github.com/iSenseLabs/oc_cli.git) to enable the 
cli access to sync method.

An example call should be : 
```
php ./oc_cli.php admin extension/module/onecode/shopflix/order/manual_sync
```

#### Accept Order
To Accept an order you must press the `green button` *Accept* on order row

`Admin > OneCode > Shopflix > Order `

#### Reject Order
To Reject an order you must press the `red button` *Reject* on order row

`Admin > OneCode > Shopflix > Order `

#### Fetch Shipment
To Fetch Shipments an order you must press the `blue button` *Shipment* on order row

`Admin > OneCode > Shopflix > Order `

#### Voucher
To Print voucher we have two ways:

* Under `Admin > OneCode > Shopflix > Order ` using `print button`  on order row
* Under `Admin > OneCode > Shopflix > Shipment ` using `print button`  on shipment row, Also you 
  can print voucher for multiple shipments.

#### Manifest
To Print manifest:

* Under `Admin > OneCode > Shopflix > Shipment ` using `print button`  on shipment row, Also you
  can print voucher for multiple shipments.