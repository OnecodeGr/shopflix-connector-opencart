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
 */
class ProductFeed extends Controller
{
    function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('catalog/product');
    }

    function detailed()
    {
    }

    function minimal()
    {
    }

    protected function getProducts()
    {
        $products = $this->model_catalog_product->getProducts()
    }
}