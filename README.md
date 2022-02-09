# OneCodeGR - ShopFlix plugin

This extension is connecting your OpenCart v3.0.3.x with [SHOPFLIX](https://SHOPFLIX.gr)

##  1. How to install

### 1.1. Requirements
* PHP >= 7.4
* [Fix OC 3.x Extension Installer](https://www.opencart.com/index.php?route=marketplace/extension/info&member_token=396ed49ec2c97aab514825fbe62b1b9b&extension_id=33410&filter_category_id=5&filter_license=0&filter_download_id=56&sort=date_added )  
  * Download and install the plugin in order to fix the permissions on `system` directory

### 1.2. Installation
* Upload [onecode-shopflix.ocmod.zip](https://github.com/OnecodeGr/shopflix-connector-opencart/raw/main/onecode-shopflix.ocmod.zip) to your system using **Admin > Extension > Installer**
* Open `Admin > Extensions > Extensions `
* Select `Modules`, and scroll until you find the module name **OneCodeGr - ShopFLix**
* Press install button for the plugin

### 1.3. Grand administration
* Open **Admin > System > Users > User Groups**
* Edit `Administrator`
* Add to `Access Permission` & `Modify Permission` all the choices which contain 
  `extension/module/onecode/*`
* Add Server IP on `Admin > System > Users > APi ` under default Api Username

### 1.3. Configure
* As you have the access on the plugin , you will have a new Left side menu option `OneCode`
* Select `Admin > OneCode > Shopflix > Configuration`
* On configuration screen you will have the ability to enable / disable the plugin, set 
  configuration for shopflix integration, and XML exportation
* As you set your values press *Save*.

##  2. Usage
### 2.1. Product XML
The product XML has two variants (simple/detailed) each one has its own url.
* simple/minimal => **{proto}://{open-cart-domain}/index.php?
  route=extension/module/onecode/shopflix/product/feed/minimal&token={token-hash}**
* full/detailed => **{proto}://{open-cart-domain}/index.php?
  route=extension/module/onecode/shopflix/product/feed/detailed&token={token-hash}**

### 2.2. Sync Orders
In order to fetch your orders from shopflix you can press the `sync` button under 
`Admin > OneCode > Shopflix > Order `

Also, you can use the plugin [oc-cli](https://github.com/iSenseLabs/oc_cli.git) to enable the 
cli access to sync method.

An example call should be : 
```
php ./oc_cli.php admin extension/module/onecode/shopflix/order/manual_sync
```

### 2.2. Accept Order
To Accept an order you must press the `green button` *Accept* on order row

`Admin > OneCode > Shopflix > Order `

### 2.3. Reject Order
To Reject an order you must press the `red button` *Reject* on order row

`Admin > OneCode > Shopflix > Order `

### 2.4. Fetch Shipment
To Fetch Shipments an order you must press the `blue button` *Shipment* on order row

`Admin > OneCode > Shopflix > Order `

### 2.5. Voucher
To Print voucher we have two ways:

* Under `Admin > OneCode > Shopflix > Order ` using `print button`  on order row
* Under `Admin > OneCode > Shopflix > Shipment ` using `print button`  on shipment row, Also you 
  can print voucher for multiple shipments.

### 2.6. Manifest
To Print manifest:

* Under `Admin > OneCode > Shopflix > Shipment ` using `print button`  on shipment row, Also you
  can print voucher for multiple shipments.