<?php
use WHMCS\Database\Capsule;

add_hook('AfterModuleCreate', 1, function($vars) {
    $serviceId = $vars['serviceid'];
    $command = 'GetClientsProducts';
    $postData = array(
        'serviceid' => $serviceId,
    );
    $results = localAPI($command, $postData);

    if ($results['result'] == 'success') {
        $product = $results['products']['product'][0];
        $email = $results['clientemail'];
        $sku = $product['configoptions']['0']; // Assuming SKU is stored in the first config option
        $reference = uniqid('ms_');

       // require_once __DIR__ . '/../servers/msecurity/msecurity.php';
		  require_once __DIR__ . '/../../servers/msecurity/msecurity.php';
        $purchaseResponse = msecurity_purchase_product($email, $sku, $reference);

        if ($purchaseResponse['success']) {
            Capsule::table('tblhosting')->where('id', $serviceId)->update(['notes' => 'License Key: ' . $purchaseResponse['license']]);
        } else {
            Capsule::table('tblhosting')->where('id', $serviceId)->update(['domainstatus' => 'Pending', 'notes' => 'Error: ' . $purchaseResponse['error']]);
        }
    }
});

add_hook('OrderPaid', 1, function($vars) {
    $orderId = $vars['orderid'];
    $orderData = Capsule::table('tblorders')->where('id', $orderId)->first();
    $userId = $orderData->userid;
    $invoiceId = $orderData->invoiceid;

    // Get the client email
    $clientEmail = Capsule::table('tblclients')->where('id', $userId)->value('email');

    // Get the products from the order
    $products = Capsule::table('tblhosting')->where('orderid', $orderId)->get();

   // require_once __DIR__ . '/../servers/msecurity/msecurity.php';
	  require_once __DIR__ . '/../../servers/msecurity/msecurity.php';
    foreach ($products as $product) {
        $serviceId = $product->id;
        $sku = $product->configoption1; // Assuming SKU is stored in the configoption1 field
        $reference = uniqid('ms_');

        $purchaseResponse = msecurity_purchase_product($clientEmail, $sku, $reference);

        if ($purchaseResponse['success']) {
            Capsule::table('tblhosting')->where('id', $serviceId)->update(['notes' => 'License Key: ' . $purchaseResponse['license']]);
        } else {
            Capsule::table('tblhosting')->where('id', $serviceId)->update(['domainstatus' => 'Pending', 'notes' => 'Error: ' . $purchaseResponse['error']]);
        }
    }
});
