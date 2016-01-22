<?php

namespace Tests\Transaction\Information;

use laravel\pagseguro\Credentials\Credentials;
use laravel\pagseguro\Phone\Phone;
use laravel\pagseguro\Transaction\Status\StatusInterface;
use laravel\pagseguro\Transaction\Transaction;
use laravel\pagseguro\Transaction\Information;
use laravel\pagseguro\Http\Request\Adapter\AdapterInterface;
use laravel\pagseguro\Http\Response\Response;
use laravel\pagseguro\Item\ItemCollection;
use laravel\pagseguro\Remote\Manager;
use laravel\pagseguro\Payment\Method\MethodInterface;
use laravel\pagseguro\Sender\Sender;
use laravel\pagseguro\Shipping\Shipping;
use laravel\pagseguro\Transaction\Status\Status;

/**
 * Information Factory Test
 * @author Isaque de Souza <isaquesb@gmail.com>
 */
class InformationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Response
     * @var string
     */
    protected $xmlResponse;

    /**
     * Information
     * @var Information\Information
     */
    protected $information;

    /**
     * SetUp
     */
    public function setUp()
    {
        $file = new \SplFileObject(__DIR__ . '/simple-response.xml', 'r');
        $this->xmlResponse = $file->fread($file->getSize());
        $adapter = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $response = new Response();
        $response->setRawBody($this->xmlResponse)->setHttpStatus(200);
        $adapter->method('getResponse')->willReturn($response);
        $adapter->method('dispatch')->willReturn(true);
        Manager::setHttpAdapter($adapter);
        $credentials = new Credentials('TOKEN', 'isaquesb@gmail.com');
        $transaction = new Transaction('9E884542-81B3-4419-9A75-BCC6FB495EF1', $credentials);
        $this->information = $transaction ? $transaction->getInformation() : null;
        unset($file);
    }

    public function testHasXml()
    {
        $this->assertNotEmpty($this->xmlResponse);
        $xml = simplexml_load_string($this->xmlResponse);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    /**
     * @depends testHasXml
     */
    public function testFromResponseParsed()
    {
        $this->assertNotNull($this->information);
    }

    /**
     * @depends testFromResponseParsed
     */
    public function testInformationInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(Information\Information::class, $info);
    }

    /**
     * @depends testInformationInstance
     */
    public function testInformationData()
    {
        $info = $this->information;
        $this->assertEquals('9E884542-81B3-4419-9A75-BCC6FB495EF1', $info->getCode());
        $this->assertEquals(1, $info->getInstallmentcount());
        $this->assertEquals(2, $info->getItemcount());
        $this->assertEquals(1, $info->getType());
        $this->assertEquals('REF1234', $info->getReference());
    }

    /**
     * @depends testInformationInstance
     */
    public function testAmountInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(Information\Amounts::class, $info->getAmounts());
    }

    /**
     * @depends testAmountInstance
     */
    public function testAmountData()
    {
        $info = $this->information->getAmounts();
        $this->assertEquals(49900, $info->getGrossamount());
        $this->assertEquals(0, $info->getDiscountamount());
        $this->assertEquals(1.1, $info->getFeeamount());
        $this->assertEquals(49909, $info->getNetamount());
        $this->assertEquals(5, $info->getExtraamount());
    }

    /**
     * @depends testInformationInstance
     */
    public function testDateInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(\DateTime::class, $info->getDate());
    }

    /**
     * @depends testDateInstance
     */
    public function testDateData()
    {
        $info = $this->information->getDate();
        $this->assertEquals('2011-02-10 16:13:41', $info->format('Y-m-d H:i:s'));
    }

    /**
     * @depends testInformationInstance
     */
    public function testItemsInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(ItemCollection::class, $info->getItems());
    }

    /**
     * @depends testInformationInstance
     */
    public function testItemsData()
    {
        $info = $this->information->getItems();
        $item = $info->offsetGet(0);
        $this->assertEquals(2, $info->count());
        $this->assertEquals('0001', $item->getId());
        $this->assertEquals('Notebook Prata', $item->getDescription());
        $this->assertEquals(1, $item->getQuantity());
        $this->assertEquals(24300, $item->getAmount());
    }

    /**
     * @depends testInformationInstance
     */
    public function testLastEventInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(\DateTime::class, $info->getLasteventdate());
    }

    /**
     * @depends testLastEventInstance
     */
    public function testLastEventData()
    {
        $info = $this->information->getLasteventdate();
        $this->assertEquals('2011-02-15 17:39:14', $info->format('Y-m-d H:i:s'));
    }

    /**
     * @depends testInformationInstance
     */
    public function testPaymentMethodInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(MethodInterface::class, $info->getPaymentmethod());
    }

    /**
     * @depends testPaymentMethodInstance
     */
    public function testPaymentMethodData()
    {
        $info = $this->information->getPaymentmethod();
        $this->assertEquals('Cartão de Crédito VISA', $info->getFullName());
    }

    /**
     * @depends testInformationInstance
     */
    public function testSenderInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(Sender::class, $info->getSender());
    }

    /**
     * @depends testSenderInstance
     */
    public function testSenderData()
    {
        $info = $this->information->getSender();
        $phone = new Phone(['areacode' => 11, 'number' => 56273440]);
        $this->assertEquals('José Comprador', $info->getName());
        $this->assertEquals('comprador@uol.com.br', $info->getEmail());
        $this->assertEquals($phone, $info->getPhone());
    }

    /**
     * @depends testInformationInstance
     */
    public function testShippingInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(Shipping::class, $info->getShipping());
    }

    /**
     * @depends testInformationInstance
     */
    public function testShippingData()
    {
        $info = $this->information->getShipping();
        $this->assertEquals('Av. Brig. Faria Lima', $info->getAddress()->getStreet());
        $this->assertEquals(1, $info->getType());
        $this->assertEquals(21.69, $info->getCost());
    }

    /**
     * @depends testInformationInstance
     */
    public function testStatusInstance()
    {
        $info = $this->information;
        $this->assertInstanceOf(Status::class, $info->getStatus());
    }

    /**
     * @depends testInformationInstance
     */
    public function testStatusData()
    {
        $info = $this->information->getStatus();
        $this->assertEquals(new Status(StatusInterface::PAID), $info);
    }
}
