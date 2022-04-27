<?php

/**
 * @property int $product_id
 * @property string $sku
 * @property string $mpn
 * @property string $ean
 * @property string $name
 * @property float $price
 * @property float $list_price
 * @property string $product_url
 *
 * <p>
 * Available values:
 * <ul>
 *  <li>0 => Same Day</li>
 *  <li>1 => Next Day</li>
 *  <li>2 => 2 Days</li>
 *  <li>3 => 3 Days</li>
 *  <li>4 => 4 Days</li>
 *  <li>5 => 5 Days</li>
 *  <li>6 => 6 Days</li>
 *  <li>7 => 7+ Days</li>
 * </ul>
 * </p>
 * @property int $shipping_time
 * @property int $offer_from
 * @property int $offer_to
 * @property float $offer_price
 * @property int $offer_quantity
 * @property int $quantity
 * @property string $image
 * @property string $description
 * @property float $weight
 * @property float $manufacturer
 * @property string[] $category
 * @property-read \Config $config
 * @property-read \Url $url
 * @property-read \Loader $load
 * @property-read \Cart\Tax $tax
 * @property-read \Language $language
 * @property-read \ModelToolImage $model_tool_image
 * @property-read \ModelExtensionModuleOnecodeShopflixXml $model_extension_module_onecode_shopflix_xml
 */
class ModelExtensionModuleOnecodeShopflixXmlProduct extends Model
{
    const SHIPPING_TIME_SAME_DAY = 0;
    const SHIPPING_TIME_NEXT_DAY = 1;
    const SHIPPING_TIME_2_DAYS = 2;
    const SHIPPING_TIME_3_DAYS = 3;
    const SHIPPING_TIME_4_DAYS = 4;
    const SHIPPING_TIME_5_DAYS = 5;
    const SHIPPING_TIME_6_DAYS = 6;
    const SHIPPING_TIME_7_PLUS_DAYS = 7;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/onecode/shopflix/xml');
        $this->load->model('tool/image');
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->product_id;
    }

    /**
     * @param int $productId
     */
    public function setProductId(int $productId): void
    {
        $this->product_id = $productId;
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getMpn(): string
    {
        return $this->mpn;
    }

    /**
     * @param string $mpn
     */
    public function setMpn(string $mpn): void
    {
        $this->mpn = $mpn;
    }

    /**
     * @return string
     */
    public function getEan(): string
    {
        return $this->ean;
    }

    /**
     * @param string $ean
     */
    public function setEan(string $ean): void
    {
        $this->ean = $ean;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getListPrice(): ?float
    {
        return $this->list_price;
    }

    /**
     * @param float $list_price
     */
    public function setListPrice(float $list_price): void
    {
        $this->list_price = $list_price;
    }

    /**
     * @return string
     */
    public function getProductUrl(): ?string
    {
        return $this->product_url;
    }

    /**
     * @param string $url
     */
    public function setProductUrl(string $url): void
    {
        $this->product_url = $url;
    }

    /**
     * @return int
     */
    public function getShippingTime(): int
    {
        return $this->shipping_time || self::SHIPPING_TIME_SAME_DAY;
    }

    /**
     * @param int $shippingTime
     */
    public function setShippingTime(int $shippingTime): void
    {
        $accepted = [
            self::SHIPPING_TIME_2_DAYS,
            self::SHIPPING_TIME_3_DAYS,
            self::SHIPPING_TIME_4_DAYS,
            self::SHIPPING_TIME_5_DAYS,
            self::SHIPPING_TIME_6_DAYS,
            self::SHIPPING_TIME_7_PLUS_DAYS,
            self::SHIPPING_TIME_SAME_DAY,
            self::SHIPPING_TIME_NEXT_DAY,
        ];
        $this->shipping_time = in_array($shippingTime, $accepted) ? $shippingTime : self::SHIPPING_TIME_SAME_DAY;
    }

    /**
     * @return int
     */
    public function getOfferFrom(): ?int
    {
        return $this->offer_from;
    }

    /**
     * @param int $offer_from
     */
    public function setOfferFrom(int $offer_from): void
    {
        $this->offer_from = $offer_from;
    }

    /**
     * @return int
     */
    public function getOfferTo(): ?int
    {
        return $this->offer_to;
    }

    /**
     * @param int $offer_to
     */
    public function setOfferTo(int $offer_to): void
    {
        $this->offer_to = $offer_to;
    }

    /**
     * @return float
     */
    public function getOfferPrice(): ?float
    {
        return $this->offer_price;
    }

    /**
     * @param float $offer_price
     */
    public function setOfferPrice(float $offer_price): void
    {
        $this->offer_price = $offer_price;
    }

    /**
     * @return int
     */
    public function getOfferQuantity(): ?int
    {
        return $this->offer_quantity;
    }

    /**
     * @param int $offer_quantity
     */
    public function setOfferQuantity(int $offer_quantity): void
    {
        $this->offer_quantity = $offer_quantity;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return string
     */
    public function getManufacturer(): string
    {
        return $this->manufacturer;
    }

    /**
     * @param string $manufacturer
     */
    public function setManufacturer(string $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @return string[]
     */
    public function getCategory(): array
    {
        return $this->category;
    }

    /**
     * @param string[] $category
     */
    public function setCategory(array $category): void
    {
        $this->category = $category;
    }

    public function loadFromCatalogProduct(array $product): self
    {
        $this->setProductId($product['product_id']);
        $this->setSku($product['sku'] ?? '');
        $price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
        $this->setPrice($price);
        $this->setProductUrl($this->url->link('product/product', 'product_id=' . $product['product_id']));
        $this->setCategory($product['categories']);
        $this->setQuantity($product['quantity']);
        $image_url = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' .
            $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' .
            $this->config->get('config_theme') . '_image_thumb_height'));

        $this->setImage($image_url ?? '');

        $attr = $this->model_extension_module_onecode_shopflix_xml->mpnAttribute();
        $attr = key_exists($attr, $product) ? $attr : 'mpn';
        $this->setMpn($product[$attr] ?? '');

        $attr = $this->model_extension_module_onecode_shopflix_xml->eanAttribute();
        $attr = key_exists($attr, $product) ? $attr : 'ean';
        $this->setEan($product[$attr] ?? '');

        $attr = $this->model_extension_module_onecode_shopflix_xml->nameAttribute();
        $attr = key_exists($attr, $product) ? $attr : 'title';
        $this->setName($product[$attr] ?? '');

        $attr = $this->model_extension_module_onecode_shopflix_xml->descriptionAttribute();
        $attr = key_exists($attr, $product) ? $attr : 'description';
        $this->setDescription($product[$attr] ?? '');

        $attr = $this->model_extension_module_onecode_shopflix_xml->brandAttribute();
        $attr = key_exists($attr, $product) ? $attr : 'manufacturer';
        $this->setManufacturer($product[$attr] ?? '');

        $attr = $this->model_extension_module_onecode_shopflix_xml->weightAttribute();
        $attr = key_exists($attr, $product) ? $attr : 'weight';
        $this->setWeight($product[$attr] ?? 0);

        if (isset($product['attributes']) && count($product['attributes']))
        {
            $attr = $this->model_extension_module_onecode_shopflix_xml->listPriceAttr();
            $list_price = 0.0;
            array_walk($product['attributes'], function ($item) use ($attr, &$list_price) {
                if (array_key_exists('attribute_id', $item) && $item['attribute_id'] == $attr)
                {
                    $list_price = floatval($item['name']);
                }
            });
            $this->setListPrice($list_price);

            $attr = $this->model_extension_module_onecode_shopflix_xml->shippingTimeAttr();
            $shipping_time = 0;
            array_walk($product['attributes'], function ($item) use ($attr, &$list_price) {
                if(array_key_exists('attribute_id', $item) && $item['attribute_id'] == $attr){
                    $list_price = intval($item['name']);
                }
            });
            $this->setShippingTime($shipping_time);

            $attr = $this->model_extension_module_onecode_shopflix_xml->offerFromAttr();
            $offer_from = 0;
            array_walk($product['attributes'], function ($item) use ($attr, &$list_price) {
                if(array_key_exists('attribute_id', $item) && $item['attribute_id'] == $attr){
                    $list_price = intval($item['name']);
                }
            });
            $this->setOfferFrom($offer_from);

            $attr = $this->model_extension_module_onecode_shopflix_xml->offerToAttr();
            $offer_to = 0;
            array_walk($product['attributes'], function ($item) use ($attr, &$list_price) {
                if(array_key_exists('attribute_id', $item) && $item['attribute_id'] == $attr){
                    $list_price = intval($item['name']);
                }
            });
            $this->setOfferTo($offer_to);

            $attr = $this->model_extension_module_onecode_shopflix_xml->offerPriceAttr();
            $offer_price = 0;
            array_walk($product['attributes'], function ($item) use ($attr, &$list_price) {
                if(array_key_exists('attribute_id', $item) && $item['attribute_id'] == $attr){
                    $list_price = floatval($item['name']);
                }
            });
            $this->setOfferPrice($offer_price);

            $attr = $this->model_extension_module_onecode_shopflix_xml->offerQuantityAttr();
            $offer_quantity = 0;
            array_walk($product['attributes'], function ($item) use ($attr, &$list_price) {
                if(array_key_exists('attribute_id', $item) && $item['attribute_id'] == $attr){
                    $list_price = floatval($item['name']);
                }
            });
            $this->setOfferQuantity($offer_quantity);
        }
        return $this;
    }
}