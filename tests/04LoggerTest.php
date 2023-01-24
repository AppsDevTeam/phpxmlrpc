<?php

include_once __DIR__ . '/PolyfillTestCase.php';

use PhpXmlRpc\Helper\Charset;
use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;

class LoggerTest extends PhpXmlRpc_PolyfillTestCase
{
    protected $debugBuffer = '';
    protected $errorBuffer = '';

    protected function set_up()
    {
        $this->debugBuffer = '';
        $this->errorBuffer = '';
    }

    public function testCharsetAltLogger()
    {
        $ch = Charset::instance();
        $l = $ch->getLogger();
        Charset::setLogger($this);

        ob_start();
        $ch->encodeEntities('hello world', 'UTF-8', 'NOT-A-CHARSET');
        $o = ob_get_clean();
        $this->assertEquals('', $o);
        $this->assertStringContainsString("via mbstring: failed", $this->errorBuffer);

        Charset::setLogger($l);
    }

    public function testHttpAltLogger()
    {
        $h = new Http();
        $l = $h->getLogger();
        Http::setLogger($this);

        $s = "HTTP/1.0 200 OK\r\n" .
            "Content-Type: unknown\r\n" .
            "\r\n" .
            "body";
        ob_start();
        $h->parseResponseHeaders($s, false, 1);
        $o = ob_get_clean();
        $this->assertEquals('', $o);
        $this->assertStringContainsString("HEADER: content-type: unknown", $this->debugBuffer);
        Http::setLogger($l);
    }

    public function testXPAltLogger()
    {
        $xp = new XMLParser();
        $l = $xp->getLogger();
        XMLParser::setLogger($this);

        ob_start();
        $xp->parse('<?xml version="1.0" ?><methodResponse><params><param><value><boolean>x</boolean></value></param></params></methodResponse>');
        $o = ob_get_clean();
        $this->assertEquals('', $o);
        $this->assertStringContainsString("invalid data received in BOOLEAN value", $this->errorBuffer);

        XMLParser::setLogger($l);
    }

    // logger API

    public function debugMessage($message, $encoding = null)
    {
        $this->debugBuffer .= $message;
    }

    public function errorLog($message)
    {
        $this->errorBuffer .= $message;
    }
}
