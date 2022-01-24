<?php
/**
 * ConnectorTest.php
 *
 * @copyright Copyright © 2022 ${ORGANIZATION_NAME}  All rights reserved.
 * @author    Spyros Bodinis {spyros@onecode.gr}
 */

namespace Spyrmp\ShopFlixConnector\Tests;

use Onecode\ShopFlixConnector\Library\Connector;
use PHPUnit\Framework\TestCase;

class ConnectorTest extends TestCase
{


    private $connector;

    public function setUp(): void
    {
        parent::setUp();
        $this->connector = new Connector($_ENV['USERNAME'], $_ENV['PASSWORD'], $_ENV["API_URL"]);
    }

    /**
     * @dataProvider getShipmentIds
     */
    public function testPrintManifest($shipments)
    {
        $manifest = $this->connector->printManifest($shipments);
        $this->assertArrayHasKey("status", $manifest);
        $this->assertArrayHasKey("manifest", $manifest);
        $this->assertIsArray($manifest);
    }


    public function getShipmentIds(): iterable
    {
        yield [
            [73, 71, 62]
        ];
    }
}
