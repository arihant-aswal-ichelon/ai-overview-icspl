<?php
/**
 * Google Ads API Configuration
 * 
 * This file should return an array with your Google Ads API credentials
 */

return [
    'GOOGLE_ADS' => [
        'developerToken' => 'eCfhMTWQWCUQA2LImrboVA',
        'loginCustomerId' => '6633316111',
        'linkedCustomerId' => '7358608409',
        
        // OAuth2 configuration for Service Account
        'OAUTH2' => [
            'jsonKeyFilePath' => __DIR__ . '/service-account-key.json'
        ]
    ]
];