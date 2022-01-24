<?php
/**
 * OrderInterface.php
 *
 * @copyright Copyright © 2021 Onecode  All rights reserved.
 * @author    Spyros Bodinis {spyros@onecode.gr}
 */

namespace Onecode\ShopFlixConnector\Library\Interfaces;


interface OrderInterface
{
    const SHOPFLIX_ORDER_ID = "shopflix_order_id";
    const INCREMENT_ID = "increment_id";
    const STATUS = "status";
    const SUBTOTAL = "subtotal";
    const DISCOUNT_AMOUNT = "discount_amount";
    const TOTAL_PAID = "total_paid";
    const CUSTOMER_EMAIL = "customer_email";
    const CUSTOMER_FIRSTNAME = "customer_firstname";
    const CUSTOMER_LASTNAME = "customer_lastname";
    const CUSTOMER_REMOTE_IP = "customer_remote_ip";
    const CUSTOMER_NOTE = "customer_note";
    const STATE = "state";

}
