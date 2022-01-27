<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \Cart\User $user
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 */
class ControllerExtensionModuleOnecodeShopflixOrder extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Onecode\Shopflix\Helper\BasicHelper($registry);
    }

    public function index()
    {
        $data = [];
        $this->document->setTitle( 'OneCode - ShopFlix' );
        $this->response->setOutput( $this->load->view( Helper\BasicHelper::getPath() . 'order_list', $data ) );
    }
}