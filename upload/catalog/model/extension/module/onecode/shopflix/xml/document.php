<?php

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
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlProduct[] $products
 */
class ModelExtensionModuleOnecodeShopflixXmlDocument extends Model
{
    protected $products;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/onecode/shopflix/xml/meta');
        $this->load->model('extension/module/onecode/shopflix/xml/product');
        $this->meta = new ModelExtensionModuleOnecodeShopflixXmlMeta($registry);
        $this->product = new ModelExtensionModuleOnecodeShopflixXmlProduct($registry);
        $this->products = [];
        $this->clearProducts();
    }

    /**
     * @return ModelExtensionModuleOnecodeShopflixXmlProduct[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    protected function updateMeta(): self
    {
        $this->meta->setCount(count($this->products));
        $this->meta->setLastUpdate(time());
        $this->meta->setStoreCode($this->config->get('config_store_id'));
        $this->meta->setStoreName($this->config->get('config_name'));
        $this->meta->setLocale($this->language->get('code') || '');

        return $this;
    }

    protected function clearProducts(): self
    {
        $this->products = [];
        $this->updateMeta();
        return $this;
    }

    public function addProduct(array $product): self
    {
        $this->products[] = $this->product->loadFromCatalogProduct($product);
        $this->updateMeta();
        return $this;
    }

    public function getXML(array $products): string
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
        $this->clearProducts();
        foreach ($products as $product)
        {
            $this->addProduct($product);
            $r = $this->getProducts();
            $product = end($r);

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
            $productElement->appendChild($dom_doc->createElement('url', $product->getProductUrl()));
            $productElement->appendChild($dom_doc->createElement('shipping_lead_time', $product->getShippingTime()));
            $productElement->appendChild($dom_doc->createElement('offer_from', $product->getOfferFrom()));
            $productElement->appendChild($dom_doc->createElement('offer_to', $product->getOfferTo()));
            $productElement->appendChild($dom_doc->createElement('offer_price', $product->getOfferPrice()));
            $productElement->appendChild($dom_doc->createElement('offer_quantity', $product->getOfferQuantity()));
            $productElement->appendChild($dom_doc->createElement('quantity', $product->getQuantity()));
            $descElement = $dom_doc->createElement('description');
            $descElement->appendChild($dom_doc->createCDATASection
            ($product->getName()));
            $productElement->appendChild($descElement);
            $productElement->appendChild($dom_doc->createElement('weight', $product->getWeight()));
            $productElement->appendChild($dom_doc->createElement('manufacturer', $product->getManufacturer()));
            $productElement->appendChild($dom_doc->createElement('image', $product->getImage()));
            $categoryElement = $dom_doc->createElement('category');
            $categoryElement->appendChild($dom_doc->createCDATASection
            (implode($product->getCategory(), ',')));
            $productElement->appendChild($categoryElement);
            $productsElement->appendChild(clone $productElement);
        }
        $storeElement->appendChild($productsElement);

        return $dom_doc->saveXML($storeElement);
    }
}