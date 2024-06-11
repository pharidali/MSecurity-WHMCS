<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

function msecurity_config() {
    return [
        'name' => 'MSecurity',
        'description' => 'A module for MSecurity partners to sell antivirus products through WHMCS.',
        'version' => '1.0',
        'author' => 'Your Name',
        'fields' => [
            'apiSecretKey' => [
                'FriendlyName' => 'X-API-SECRET-KEY',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Enter your API Secret Key',
            ],
            'apiPublicKey' => [
                'FriendlyName' => 'X-API-PUBLIC-KEY',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Enter your API Public Key',
            ],
            'commissionPercentage' => [
                'FriendlyName' => 'Commission Percentage',
                'Type' => 'text',
                'Size' => '10',
                'Description' => 'Enter the commission percentage',
            ],
        ]
    ];
}

function msecurity_activate() {
    try {
        Capsule::schema()->create('mod_msecurity', function ($table) {
            $table->increments('id');
            $table->string('apikey');
            $table->text('value');
        });

        // Create MSecurity product group if it doesn't exist
        if (!Capsule::table('tblproductgroups')->where('name', 'MSecurity')->exists()) {
            Capsule::table('tblproductgroups')->insert([
                'name' => 'MSecurity',
                'headline' => 'MSecurity Products',
                'tagline' => 'Top security solutions for your devices.',
            ]);
        }

        // Debugging: Check if the product group is created
        $groupId = Capsule::table('tblproductgroups')->where('name', 'MSecurity')->value('id');
        if (!$groupId) {
            return [
                'status' => 'error',
                'description' => 'Error: MSecurity product group could not be created.',
            ];
        }

        return [
            'status' => 'success',
            'description' => 'MSecurity module activated successfully.',
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Unable to activate module: ' . $e->getMessage(),
        ];
    }
}

function msecurity_deactivate() {
    try {
        Capsule::schema()->dropIfExists('mod_msecurity');

        return [
            'status' => 'success',
            'description' => 'MSecurity module deactivated successfully.',
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Unable to deactivate module: ' . $e->getMessage(),
        ];
    }
}

function msecurity_output($vars) {
    $apiSecretKey = $vars['apiSecretKey'];
    $apiPublicKey = $vars['apiPublicKey'];
    $commissionPercentage = $vars['commissionPercentage'];

    if ($_POST['action'] == 'import_products') {
        $result = msecurity_import_products($apiSecretKey, $apiPublicKey, $commissionPercentage);
        echo '<div class="alert alert-info">' . $result . '</div>';
    }

    echo '<form method="post">
        <input type="hidden" name="action" value="import_products">
        <input type="submit" value="Import Products" class="btn btn-primary">
    </form>';
}

function msecurity_import_products($apiSecretKey, $apiPublicKey, $commissionPercentage) {
    $url = 'https://msecurity.app/api/v3/services';
    $headers = [
        'X-API-SECRET-KEY: ' . $apiSecretKey,
        'X-API-PUBLIC-KEY: ' . $apiPublicKey,
    ];

    $response = file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
        ],
    ]));

    if ($response === FALSE) {
        return 'Error: Unable to fetch products from API.';
    }

    $data = json_decode($response, true);

    if ($data['success']) {
        // Get MSecurity product group ID
        $groupId = Capsule::table('tblproductgroups')->where('name', 'MSecurity')->value('id');
        if (!$groupId) {
            return 'Error: MSecurity product group not found.';
        }

        foreach ($data['platform'] as $service) {
            $cost = $service['cost'];
            if ($commissionPercentage > 0) {
                $cost += ($cost * $commissionPercentage / 100);
            }

            // Check if product with SKU already exists
            $existingProduct = Capsule::table('tblproducts')->where('servertype', 'msecurity')->where('configoption1', $service['sku'])->first();

            if ($existingProduct) {
                // Update existing product
                Capsule::table('tblproducts')->where('id', $existingProduct->id)->update([
                    'name' => $service['title'],
                    'description' => implode("\n", json_decode($service['description'], true)),
                    'gid' => $groupId,
                    'servertype' => 'msecurity',
                    'configoption1' => $service['sku'],
                    'paytype' => 'onetime',
                    'hidden' => 0,
                    'showdomainoptions' => 0,
                ]);

                $productId = $existingProduct->id;
            } else {
                // Insert new product
                $productId = Capsule::table('tblproducts')->insertGetId([
                    'type' => 'other',
                    'gid' => $groupId, // Add to MSecurity group
                    'name' => $service['title'],
                    'description' => implode("\n", json_decode($service['description'], true)),
                    'servertype' => 'msecurity',
                    'configoption1' => $service['sku'],
                    'paytype' => 'onetime',
					'autosetup' => 'payment',
                    'hidden' => 0,
                    'showdomainoptions' => 0,
                ]);
            }

            // Handle pricing
            $currency = 1; // Default currency ID (adjust if needed)
            $pricingData = [
                'type' => 'product',
                'currency' => $currency,
                'relid' => $productId,
                'msetupfee' => 0,
                'qsetupfee' => 0,
                'ssetupfee' => 0,
                'asetupfee' => 0,
                'bsetupfee' => 0,
                'tsetupfee' => 0,
                'monthly' => $cost,
                'quarterly' => -1,
                'semiannually' => -1,
                'annually' => -1,
                'biennially' => -1,
                'triennially' => -1,
            ];

            $existingPricing = Capsule::table('tblpricing')->where('type', 'product')->where('currency', $currency)->where('relid', $productId)->first();

            if ($existingPricing) {
                Capsule::table('tblpricing')->where('id', $existingPricing->id)->update($pricingData);
            } else {
                Capsule::table('tblpricing')->insert($pricingData);
            }
        }
        return 'Products imported successfully.';
    } else {
        return 'Unable to import products.';
    }
}
