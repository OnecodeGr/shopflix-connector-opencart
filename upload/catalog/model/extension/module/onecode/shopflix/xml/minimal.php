<?php

require_once(dirname(__FILE__) . '/document.php');

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Language $language
 * @property-read \Config $config
 * @property-read \DB $db
 * @property-read \Loader $load
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlMeta $model_extension_module_onecode_shopflix_xml_meta
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlMeta $meta
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlProduct $model_extension_module_onecode_shopflix_xml_product
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlProduct $product
 */
class ModelExtensionModuleOnecodeShopflixXmlMinimal extends ModelExtensionModuleOnecodeShopflixXmlDocument
{
    public function getXML(): string
    {
        $dom_doc = new \DOMDocument('1,0', 'UTF-8');
        $storeElement = $dom_doc->createElement('store');
        $storeElement->setAttribute('name', $this->meta->getStoreName());
        $storeElement->setAttribute('url', $this->config->get('config_ssl') ?? $this->config->get('config_url'));
        $storeElement->setAttribute('encoding', 'utf8');

        $storeElement->appendChild($dom_doc->createElement('created_at', time()));

        $metaElement = $dom_doc->createElement('meta');
        $metaElement->appendChild($dom_doc->createElement('last_updated_at', $this->meta->getLastUpdate()));
        $metaElement->appendChild($dom_doc->createElement('store_code', $this->meta->getStoreName()));
        $metaElement->appendChild($dom_doc->createElement('store_name', $this->meta->getStoreCode()));
        $metaElement->appendChild($dom_doc->createElement('locale', $this->meta->getLocale()));
        $metaElement->appendChild($dom_doc->createElement('count', $this->meta->getCount()));
        $storeElement->appendChild($metaElement);

        $productsElement = $dom_doc->createElement('products');
        foreach ($this->products as $product)
        {
            $productElement = $dom_doc->createElement('product');
            $productElement->appendChild($dom_doc->createElement('product_id', $product->getProductId()));
            $productElement->appendChild($dom_doc->createElement('sku', $product->getSku()));
            $productElement->appendChild($dom_doc->createElement('mpn', $product->getMpn()));
            $productElement->appendChild($dom_doc->createElement('ean', $product->getEan()));
            $nameElement = $dom_doc->createElement('name');
            $nameElement->appendChild($dom_doc->createCDATASection
            ($product->getName()));
            $productElement->appendChild($nameElement);
            $productElement->appendChild($dom_doc->createElement('price', $product->getPrice()));
            $productElement->appendChild($dom_doc->createElement('list_price', $product->getListPrice()));
            $productElement->appendChild($dom_doc->createElement('url', $product->getUrl()));
            $productElement->appendChild($dom_doc->createElement('shipping_lead_time', $product->getShippingTime()));
            $productElement->appendChild($dom_doc->createElement('offer_from', $product->getOfferFrom()));
            $productElement->appendChild($dom_doc->createElement('offer_to', $product->getOfferTo()));
            $productElement->appendChild($dom_doc->createElement('offer_price', $product->getOfferPrice()));
            $productElement->appendChild($dom_doc->createElement('offer_quantity', $product->getOfferQuantity()));
            $productElement->appendChild($dom_doc->createElement('quantity', $product->getQuantity()));
            $productElement->appendChild($dom_doc->createElement('image', $product->getImage()));
            $productsElement->appendChild($productElement);
        }
        $storeElement->appendChild($productsElement);

        return $dom_doc->saveXML($storeElement);
    }
}