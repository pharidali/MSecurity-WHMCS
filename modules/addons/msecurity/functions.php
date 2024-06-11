<?php

use WHMCS\Database\Capsule;

if (!function_exists('msecurity_purchase_product')) {
    function msecurity_purchase_product($email, $sku, $reference) {
        $options = Capsule::table('tbladdonmodules')->where('module', 'msecurity')->pluck('value', 'setting');
        $apiSecretKey = $options['apiSecretKey'];
        $apiPublicKey = $options['apiPublicKey'];

        // Check balance first
        $balanceResponse = file_get_contents('https://msecurity.app/api/v3/balance', false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "X-API-SECRET-KEY: $apiSecretKey\r\nX-API-PUBLIC-KEY: $apiPublicKey\r\n",
            ],
        ]));

        if ($balanceResponse === FALSE) {
            return ['error' => 'Error: Unable to fetch balance from API.'];
        }

        $balanceData = json_decode($balanceResponse, true);
        if (isset($balanceData['balance']) && $balanceData['balance'] > 0) {
            // Proceed to purchase
            $purchaseResponse = file_get_contents('https://msecurity.app/api/v3/services/buy/mail', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "X-API-SECRET-KEY: $apiSecretKey\r\nX-API-PUBLIC-KEY: $apiPublicKey\r\nContent-Type: application/json\r\n",
                    'content' => json_encode(['email' => $email, 'sku' => $sku, 'reference' => $reference]),
                ],
            ]));

            if ($purchaseResponse === FALSE) {
                return ['error' => 'Error: Unable to complete purchase.'];
            }

            $purchaseData = json_decode($purchaseResponse, true);
            if (isset($purchaseData['message']) && $purchaseData['message'] === 'License purchased successfully.') {
                return ['success' => true, 'message' => $purchaseData['message'], 'license' => $purchaseData['license']];
            } else {
                return ['error' => 'Unable to purchase product.'];
            }
        } else {
            return ['error' => 'Insufficient balance. Please recharge your account.'];
        }
    }
}
