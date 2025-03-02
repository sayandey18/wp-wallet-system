=== WooCommerce Wallet System ===
Contributors: sayandey18
Tested up to: 6.7.2
Stable tag: 3.6.0
Requires PHP: 7.4
Tested up to PHP: 8.2
WC tested up to: 9.7.0
WPML Compatible: yes
Multisite Compatible: yes

Tags: woocommerce, wallet, currency, virtual currency, wallet gateway.

WooCommerce Wallet System  allows customers to make the online payment from their Wallet. In this plugin,
the admin can manually debit or credit amount into the customer’s wallet. You can offer your customers a new
convenient way of paying for goods and services. The wallet is an online prepaid account where one can stock
money, to be used when required. There is no chance of a decline in payment since wallet is a prepaid account.

As it is a pre-loaded facility, customers can buy a range of products without having to enter their debit/credit
card details for every online transaction. In this plugin, customer/user can use Wallet Cash during the checkout
and amount will be deducted from their Wallet Cash. They can easily add credit to their wallet.

== Installation ==

1. Upload the wp-wallet-system folder to the /wp-content/plugins/ directory.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to the Customer Wallet

== Frequently Asked Questions ==

No questions asked yet

== Screenshots ==

== Changelog ==

= 3.6.0 =
Added: Caching submodule for fast loading of wallet transaction page.
Added: Wallet gateway as a submodule to generalize the codebase.
Added: Resend OTP feature after its expired.
Added: Wallet notification for each wallet transaction like cashback, recharge, transfer and manual transactions.
Fixed: Coding standard according to WordPress and WooCommerce Coding standard.
Fixed: Issues in cachback calculation and its transfer.
Fixed: Issue in sorting cachback rules table in admin dashboard.
Fixed: Issue in exporting the filter transactions from admin list.
Fixed: Issue in updating wallet recharge price in cart when multiple user recharging the wallet simultaneously.

= 3.5.2 =
* Fixed security issues.
* Minor update regarding notices.
* Major bug fixes

= 3.5.1 =
Fixed security issues.

= 3.5.0 =
* Added feature to manual credit/debit in bulk from admin end.
* Added an image for the wallet product.
* Added feature to restore cart products if customer is recharging his wallet.
* Added feature to set maximum debit amount according to percentage of order total.
* Added feature to export user wallet details into a csv file.
* Added a filter to filter the wallet transactions between entered dates.
* Added feature to export wallet transaction details into a csv file.
* Fixed issue in tax while using wallet as partial payment.
* Fixed security issues.

= 3.4.0 =
* Added feature to add cashback for 2 categories now i.e. either for cart or for wallet recharge.
* Added auto refund of wallet amount if order gets cancelled for any reason.
* Fixed security issues.

= 3.3.1 =
* Added better way for transaction details of transfered money through wallet.
* Added validations.
* Fixed security issues.

= 3.3 =
* Introduced new updated layout.
* Updated list tables.
* Fixed all validation issues.
* Partial payment and max debit amount from wallet issue fixed.
* Configured twilio for otp service through sms.

= 3.2.1 =
* Updated database tables amount column data type for transaction and cashback.
* Translation support added, provided .pot file.

= 3.2.0 =
* Added the new menu for showing transaction.
* Provided the transaction note at admin end.

= 3.0.0 =
* Introduced Wallet to Wallet Amount Transfer.
* Introduced OTP verification over Amount Transfer via SMS or Mail
* A bit more admin controlled Wallet Transaction now.
* Introduced Custom Cashback Rules via Admin.

= 2.0.0 =
* Added support for refund in Wallet Payment Gateway.
* Added Manual Wallet Transaction via Admin
* Fixed pagination issue from wallet order list on customer end.

= 1.0.1 =
Compatible with WooCommerce 3.0.x

= 1.0.0 =
Initial release
