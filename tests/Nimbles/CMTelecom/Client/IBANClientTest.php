<?php
/*
* (c) Nimbles b.v. <wessel@nimbles.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Tests\Nimbles\CMTelecom\Client;

use Http\Client\HttpClient;
use Nimbles\CMTelecom\Client\IBANClient;
use Nimbles\CMTelecom\Model\IBANTransaction;
use Nimbles\CMTelecom\Model\Issuer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class IBANClientTest
 */
class IBANClientTest extends TestCase
{
    /** @var IBANClient */
    private $client;

    /** @var \PHPUnit_Framework_MockObject_MockObject|HttpClient */
    private $httpClient;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ResponseInterface */
    private $response;

    /** @var \PHPUnit_Framework_MockObject_MockObject|StreamInterface */
    private $stream;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Issuer */
    private $issuer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|IBANTransaction */
    private $transaction;

    public function setUp()
    {
        $this->httpClient   = $this->createHttpClientMock();
        $this->response     = $this->createResponseInterfaceMock();
        $this->stream       = $this->createStreamInterfaceMock();
        $this->issuer       = $this->createIssuerMock();
        $this->transaction  = $this->createIBANTransaction();

        $this->client = new IBANClient($this->httpClient, 'secret-token', 'https://test.cm-telecom.nl', 'MyApp');
    }
    
    public function testGetIssuers()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);
        
        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn(file_get_contents(__DIR__ . '/issuers.json'));

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->assertCount(7, $this->client->getIssuers());
    }

    /**
     * @expectedException \Nimbles\CMTelecom\Exception\IssuerConnectionException
     */
    public function testGetIssuersStatusCodeNot200Exception()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);
        
        $this->stream->expects($this->once())
            ->method('getContents');

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(502);

        $this->client->getIssuers();
    }

    /**
     * @expectedException \Nimbles\CMTelecom\Exception\IssuerConnectionException
     */
    public function testGetIssuersNoSetException()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn('[{"foo" : "bar"}]');

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->client->getIssuers();
    }

    public function testGetIDINTransaction()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn(file_get_contents(__DIR__ . '/transaction.json'));

        $this->issuer->expects($this->once())
            ->method('getName');

        $this->issuer->expects($this->exactly(2))
            ->method('getId');

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->assertInstanceOf(IBANTransaction::class, $this->client->getIBANTransaction($this->issuer, 'https://www.myapp.com/redirect'));
    }

    /**
     * @expectedException \Nimbles\CMTelecom\Exception\IBANTransactionException
     */
    public function testGetIBANTransactionException()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->stream->expects($this->once())
            ->method('getContents');
        $this->issuer->expects($this->once())
            ->method('getName');

        $this->issuer->expects($this->exactly(2))
            ->method('getId');

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(502);

        $this->client->getIBANTransaction($this->issuer, 'https://www.myapp.com/redirect');
    }

    public function testGetTransactionInfo()
    {
        $this->transaction->expects($this->once())
            ->method('getTransactionId')
            ->willReturn('transaction-id');

        $this->transaction->expects($this->once())
            ->method('getMerchantReference')
            ->willReturn('transaction-reference');

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn(file_get_contents(__DIR__ . '/status.json'));

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        
        $this->assertTrue(is_array($this->client->getTransactionInfo($this->transaction)));
    }

    /**
     * @expectedException \Nimbles\CMTelecom\Exception\IBANTransactionException
     */
    public function testGetTransactionInfoException()
    {
        $this->transaction->expects($this->once())
            ->method('getTransactionId')
            ->willReturn('transaction-id');

        $this->transaction->expects($this->once())
            ->method('getMerchantReference')
            ->willReturn('transaction-reference');

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->stream->expects($this->once())
            ->method('getContents');

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(502);

        $this->client->getTransactionInfo($this->transaction);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|HttpClient
     */
    private function createHttpClientMock()
    {
        return $this->getMockBuilder(HttpClient::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    private function createResponseInterfaceMock()
    {
        return $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StreamInterface
     */
    private function createStreamInterfaceMock()
    {
        return $this->getMockBuilder(StreamInterface::class)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Issuer
     */
    private function createIssuerMock()
    {
        return $this->getMockBuilder(Issuer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|IBANTransaction
     */
    private function createIBANTransaction()
    {
        return $this->getMockBuilder(IBANTransaction::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
