# Setup Tool for RtbSape and GAM (previously DFP)
An automated line item generator for [RtbSape](https://rtb.sape.ru/) and Google Ad Manager (previously DFP)

## Overview
This tool automates create new place in RtbSape and setup line item in GAM. You define the advertiser, placements and ad units, and Prebid settings; then, it creates an order with one line item per price level, attaches creatives, sets placement and/or ad units, and Prebid key-value targeting.

While this tool covers typical use cases, it might not fit your needs.

## Getting Started

### Requirements
* PHP version >= 7.2
* Access to create a service account in the Google Developers Console
* Admin access to your Google Ad Manager account

### Creating Google Credentials
_You will need credentials to access your GAM account programmatically. This summarizes steps from [GAM docs](https://developers.google.com/ad-manager/docs/authentication) and the Google Ads Python libary [auth guide](https://github.com/googleads/googleads-python-lib)._

1. If you haven't yet, sign up for a [GAM account](https://admanager.google.com/).
2. Create Google developer credentials
   * Go to the [Google Developers Console Credentials page](https://console.developers.google.com/apis/credentials).
   * On the **Credentials** page, select **Create credentials**, then select **Service account key**.
   * Select **New service account**, and select JSON key type. You can leave the role blank.
   * Click **Create** to download a file containing a `.json` private key.
3. Enable API access to GAM
   * Sign into your [GAM account](https://admanager.google.com/). You must have admin rights.
   * In the **Admin** section, select **Global settings**
   * Ensure that **API access** is enabled.
   * Click the **Add a service account user** button.
     * Use the service account email for the Google developer credentials you created above.
     * Set the role to "Administrator".
     * Click **Save**.

### Setting Up
1. Clone this repository.
2. Install dependencies
   * Run `php composer.phar install`
   * **Important:** PHP version 7.2 or higher is required.
3. Rename key
   * Rename the Google credentials key you previously downloaded (`[something].json`) to `config/key.json`
4. Make a copy of `config/app.example.yaml` and name it `config/app.yaml`.
5. Edit `config/app.yaml`:
   * `ad_manager.application_name` is the name of the Google project you created when creating the service account credentials. It should appear in the top-left of the [credentials page](https://console.developers.google.com/apis/credentials).
   * `ad_manager.network_code` is your GAM network number; e.g., for `https://admanager.google.com/12398712#delivery`, the network code is `12398712`.

## Creating Line Items

Modify the following settings in `config/app.yaml`:

Setting | Description | Type
------------ | ------------- | -------------
`sape.login` | Login in RtbSape | string
`sape.token` | Token for access RtbSape api. Generate token: [https://passport.sape.ru/security/token/](https://passport.sape.ru/security/token/) | string
`sape.site_id` | Site ID in RtbSape | string
`ad_manager.application_name` | Is the name of the Google project you created when creating the service account credentials. It should appear in the top-left of the [credentials page](https://console.developers.google.com/apis/credentials). | string
`ad_manager.network_code` | Is your GAM network number; e.g., for `https://admanager.google.com/12398712#delivery`, the network code is `12398712`. | string
`app.order_name` | What you want to call your new GAM order | string
`app.user_email_address` | The email of the GAM user who will be the trafficker for the created order | string
`app.advertiser_name` | The name of the GAM advertiser for the created order | string
`app.targeted_ad_unit_names` | The names of GAM ad units the line items should target | array of strings
`app.targeted_placement_names` | The names of GAM placements the line items should target | array of strings
`app.sizes` | List of sizes. Item format: ['width' => xxx, 'height' => yyy] | array of objects
`app.targeting.bidder` | Name for custom targeting by bidder | string
`app.targeting.price` | Name for  custom targeting by price | string
`app.price_buckets` | The price granularity used to set `hb_pb` for each line item | object

Then, from the root of the repository, run:

`php bin/run.php create-items`

You should be all set! Review your order, line items, and creatives to make sure they are correct. Then, approve the order in GAM.

*Note: GAM might show a "Needs creatives" warning on the order for ~15 minutes after order creation. Typically, the warning is incorrect and will disappear on its own.*