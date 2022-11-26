<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>xmlrpc - Introspect demo</title></head>
<body>
<h1>Introspect demo</h1>
<h2>Query server for available methods and their description</h2>
<h3>The code demonstrates usage of multicall and introspection methods</h3>
<p>You can see the source to this page here: <a href="introspect.php?showSource=1">introspect.php</a></p>
');

function display_error($r)
{
    output("An error occurred: ");
    output("Code: " . $r->faultCode() . " Reason: '" . $r->faultString() . "'<br/>");
}

$client = new PhpXmlRpc\Client(XMLRPCSERVER);

// First off, let's retrieve the list of methods available on the remote server
output("<h3>methods available at http://" . $client->server . $client->path . "</h3>\n");
$req = new PhpXmlRpc\Request('system.listMethods');
$resp = $client->send($req);

if ($resp->faultCode()) {
    display_error($resp);
} else {
    $v = $resp->value();

    // Then, retrieve the signature and help text of each available method
    foreach ($v as $methodName) {
        output("<h4>" . htmlspecialchars($methodName->scalarval()) . "</h4>\n");
        // build messages first, add params later
        $m1 = new PhpXmlRpc\Request('system.methodHelp');
        $m2 = new PhpXmlRpc\Request('system.methodSignature');
        $val = new PhpXmlRpc\Value($methodName->scalarval(), "string");
        $m1->addParam($val);
        $m2->addParam($val);
        // Send multiple requests in one http call.
        // If server does not support multicall, client will automatically fall back to 2 separate calls
        $ms = array($m1, $m2);
        $rs = $client->send($ms);
        if ($rs[0]->faultCode()) {
            display_error($rs[0]);
        } else {
            $val = $rs[0]->value();
            $txt = $val->scalarval();
            if ($txt != "") {
                output("<h4>Documentation</h4><p>${txt}</p>\n");
            } else {
                output("<p>No documentation available.</p>\n");
            }
        }
        if ($rs[1]->faultCode()) {
            display_error($rs[1]);
        } else {
            output("<h4>Signature</h4><p>\n");
            // note: using PhpXmlRpc\Encoder::decode() here would lead to cleaner code
            $val = $rs[1]->value();
            if ($val->kindOf() == "array") {
                foreach ($val as $x) {
                    $ret = $x[0];
                    output("<code>" . htmlspecialchars($ret->scalarval()) . " "
                        . htmlspecialchars($methodName->scalarval()) . "(");
                    if ($x->count() > 1) {
                        for ($k = 1; $k < $x->count(); $k++) {
                            $y = $x[$k];
                            output(htmlspecialchars($y->scalarval()));
                            if ($k < $x->count() - 1) {
                                output(", ");
                            }
                        }
                    }
                    output(")</code><br/>\n");
                }
            } else {
                output("Signature unknown\n");
            }
            output("</p>\n");
        }
    }
}

output("</body></html>\n");
