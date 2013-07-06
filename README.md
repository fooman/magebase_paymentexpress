Magebase DPS Payment Express
=======================

Summary: Accept credit card payments from DPS Payment Express using either via PxPay or PxPost.

### Features

* Accept credit card payments from DPS Payment Express using either via PxPay or PxPost
* Adds new order statuses and configuration choices to fit your order workflow - Pending Payment (DPS), Processing (DPS – Amount Authorised), Processing (DPS – Amount Paid)
* Displays transaction details in the backend so you can be confident what you ship is what you have been paid for

Tested and approved by DPS.

For advanced functionality and security features, check out the commercial [Fooman DPS Pro](http://store.fooman.co.nz/extensions/magento-extension-dps-pro.html) extension. 

### PxPay Requirements

* Internet connectivity. The extension uses the fail-proof notification by DPS to get notified of received payments (full testing on localhost will not work).
* Your php / server configurations must allow access to the $_GET variable when it contains very long strings (2000+ characters) - if your server disallows this, the extension will not work. Check with your hosting company if you're not sure.

For the manual, questions and discussion around this extension please head over to this [magebase](http://magebase.com/magento-articles/updated-paymentexpress-extension-manual/) article.
   
   


