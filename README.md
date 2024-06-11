MSecurity WHMCS Plugin
======================

Overview
--------

The MSecurity WHMCS Plugin allows MSecurity partners to sell antivirus products through their WHMCS installation. This plugin integrates MSecurity services into WHMCS, enabling automatic product setup, license purchasing, and product management.

Features
--------

- Import MSecurity products into WHMCS
- Automatically set up products as soon as the first payment is received
- Purchase licenses automatically upon order payment
- Display license keys on the user's service page
- Manage commission percentages for products

Requirements
------------

- WHMCS 7.0 or later
- MSecurity partner account with API credentials

Installation
------------

1. **Download the Plugin**

   Download the plugin files and extract them to your WHMCS installation directory.

2. **Upload Files**

   Ensure the following directory structure in your WHMCS installation:

   modules/
   ├── addons/
   │   └── msecurity/
   │       ├── functions.php
   │       ├── hooks.php
   │       └── msecurity.php
   └── servers/
       └── msecurity/
           └── msecurity.php

3. **Activate the Addon Module**

   - Log in to your WHMCS admin area.
   - Navigate to Setup > Addon Modules.
   - Find MSecurity and click the Activate button.
   - Click Configure and enter your X-API-SECRET-KEY, X-API-PUBLIC-KEY, and Commission Percentage.

4. **Configure the Server Module**

   - Navigate to Setup > Products/Services > Products/Services.
   - Create a new product group called MSecurity.
   - Create a new product under the MSecurity group.
   - In the Module Settings tab, choose MSecurity from the Module Name dropdown.
   - Configure the SKU and other required fields.

Usage
-----

1. **Import Products**

   - Go to Addons > MSecurity.
   - Click the Import Products button to import products from MSecurity.

2. **Place an Order**

   - Customers can place an order for any MSecurity product.
   - Upon successful payment, the product will be automatically set up, and the license will be purchased from MSecurity.
   - The license key will be displayed on the user's service page under Product Details.

Troubleshooting
---------------

- Ensure your API keys are correctly configured.
- Check that the product SKU matches the SKU in MSecurity.
- Verify that the autosetup field is set to payment for automatic setup upon payment.

Support
-------

For support, please contact contact@msecurity.app or visit https://msecurity.app .

License
-------

This plugin is licensed under the MIT License. See the LICENSE file for details.

Author: MSecurity Lab Pvt. Ltd.
Version: 1.0
