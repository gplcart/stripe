[![Build Status](https://scrutinizer-ci.com/g/gplcart/stripe/badges/build.png?b=master)](https://scrutinizer-ci.com/g/gplcart/stripe/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gplcart/stripe/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gplcart/stripe/?branch=master)

Stripe is a [GpL Cart](https://github.com/gplcart/gplcart) module that integrates [Stripe](https://stripe.com) payment gateway into your shopping cart

Dependencies: [Omnipay Library](https://github.com/gplcart/omnipay_library)

Installation:

1. Download and extract to `system/modules` manually or using composer `composer require gplcart/stripe`. IMPORTANT: If you downloaded the module manually, be sure that the name of extracted module folder doesn't contain a branch/version suffix, e.g `-master`. Rename if needed.
2. Go to `admin/module/list` end enable the module
3. Adjust settings at `admin/module/settings/stripe`