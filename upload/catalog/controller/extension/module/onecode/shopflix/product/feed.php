<?php

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \Cart\User $user
 * @property-read \ModelCatalogProduct $model_catalog_product
 * @property-read \ModelCatalogCategory $model_catalog_category
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlDocument $model_extension_module_onecode_shopflix_xml_document
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlDocument $xmlDocument
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlMinimal $model_extension_module_onecode_shopflix_xml_minimal
 * @property-read \ModelExtensionModuleOnecodeShopflixXmlMinimal $xmlMinimal
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \ModelExtensionModuleOnecodeShopflixXml $model_extension_module_onecode_shopflix_xml
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \Onecode\Shopflix\Helper\ConfigHelper $configHelper
 */
class ControllerExtensionModuleOnecodeShopflixProductFeed extends Controller
{
    function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('catalog/product');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('extension/module/onecode/shopflix/xml/document');
        $this->load->model('extension/module/onecode/shopflix/xml/minimal');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/xml');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Onecode\Shopflix\Helper\BasicHelper($registry);
        $this->load->helper('onecode/shopflix/ConfigHelper');
        $this->configHelper = new Onecode\Shopflix\Helper\ConfigHelper($registry);
        $this->xmlDocument = new ModelExtensionModuleOnecodeShopflixXmlDocument($registry);
        $this->xmlMinimal = new ModelExtensionModuleOnecodeShopflixXmlMinimal($registry);
    }

    protected function getParameters(): array
    {
        return $this->request->get;
    }

    protected function getQueryParameter(string $key)
    {
        return key_exists($key, $this->getParameters()) ? $this->getParameters()[$key] : '';
    }

    protected function validation(): bool
    {
        if (! $this->model_extension_module_onecode_shopflix_xml->isEnabled()
            || $this->getQueryParameter('token') != $this->model_extension_module_onecode_shopflix_xml->token()
        )
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, 'xml validation'));
            return false;
        }
        return true;
    }

    protected function response404()
    {
        $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
        $data['continue'] = $this->url->link('common/home');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $this->response->setOutput($this->load->view('error/not_found', $data));
    }

    function detailed()
    {
        if (! $this->validation())
        {
            $this->response404();
            return;
        }
        $products = $this->getProducts();
        foreach ($products as $product)
        {
            $this->xmlDocument->addProduct($product);
        }
        $this->response->addHeader('Content-Type: text/xml');
        $this->response->setOutput($this->xmlDocument->getXML($products));
    }

    function minimal()
    {
        if (! $this->validation())
        {
            $this->response404();
            return;
        }
        $products = $this->getProducts();
        foreach ($products as $product)
        {
            $this->xmlMinimal->addProduct($product);
        }
        $this->response->addHeader('Content-Type: text/xml');
        $this->response->setOutput($this->xmlMinimal->getXML($products));
    }

    /**
     * @return void
     */
    protected function getProducts(): array
    {
        $products = [];
        $db_prod = $this->model_extension_module_onecode_shopflix_product->getAllEnabledProducts([
            'sort' => 'p.product_id',
            'order' => 'DESC',
        ]);

        $loadCategories = $this->model_extension_module_onecode_shopflix_xml->exportCategories();
        if (count($db_prod))
        {
            foreach ($db_prod as $product)
            {
                $product['categories'] = [];
                if ($loadCategories)
                {
                    $product['attributes'] = $this->model_catalog_product->getProductAttributes($product['product_id']);
                    $categories = $this->model_catalog_product->getCategories($product['product_id']);
                    $product['categories'] = array_map(function ($item): string {
                        $row = $this->model_catalog_category->getCategory($item['category_id']);
                        return key_exists('name', $row) ? $row['name'] : '';
                    }, $categories);
                }
                $products[] = $product;
            }
        }
        return $products;
    }
}