<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Tour reviews

The tour detail page shows the five most recent active reviews. To create a review link, sign in with a user whose `role` is `admin`, open **Tour**, and select **Review link** for the tour. Enter the guest email and booking number; they must match a `reservations` record whose `code_service` is the tour code. Send the resulting signed link privately to the guest. It expires after 30 days and contains the guest email and booking number.

Ratings on the home page, tour listings, and tour or product details average all active reviews with a valid 1–5 star rating, even though detail pages show only the five most recent reviews.

## Orders

Users with the `admin` role can open **Booking** in the admin sidebar to search, sort, and page through records from `reservations`. Bookings made through WhatsApp are not automatically stored in this table.

Order details can generate a tour review link when the service code matches a tour package. Deleting an order permanently removes its associated room details and invalidates its review link; it does not restore stock, change payments, or process a refund.

Order details show the matching product or tour name alongside its service code and can generate a review link for a matching product (Mix or Activity). Product reviews appear on the product detail page. If a service code matches both a tour and a product, both names are shown as ambiguous and the link is unavailable until the code is unambiguous.

Order details show reviews matching the booking number and service code. Admins can permanently delete individual reviews; deleted reviews no longer contribute to public ratings. Existing unexpired review links can still be used to submit another review.

Admins can update the payment method, payment status, paid amount, and booking status on order details. `PAID` sets the paid amount to the total; `PENDING` sets it to zero; `Downpayment` requires an amount greater than zero and less than the total. These manual changes do not process payments or refunds. Existing payment-provider statuses can be retained when editing an order.

Run the short review link migration before generating links from order details. These links use a random token and redirect to the existing signed review URL; both expire after 30 days. Previously generated signed links remain valid until their original expiry. The Tour review-link form continues to generate signed links directly.

## Tour and product checkout

Run the checkout migration before enabling booking. In the Tour admin form, set the USD price per person for each tour that should be bookable online; existing price descriptions remain unchanged. Mix and activity products use `products.price` in USD per person. Checkout displays the selected currency using the session exchange rate; IDR prices are estimates rounded per person in the same way as product listings. The reservation total remains in USD. Checkout marks the reservation pending and unpaid, and does not check inventory or process a payment. For these day trips, check-in and check-out both represent the selected travel date.

New checkout booking numbers use `BOOK-` followed by eight letters or digits; existing numbers remain unchanged. Run the reservation-number uniqueness migration before deploying this format. Resolve any duplicate existing reservation numbers before running the migration, as it enforces uniqueness across all reservations.

## Airport transfer requests

Run the transfer orders migrations before accepting requests. The transfer form stores requests in `transfer_orders` and shows a receipt; it does not confirm availability, calculate a final fare, or process payment. When Google Places is configured, a selected destination includes its place name and formatted address. Admins can view all submitted fields under **Transfer Order**. Transfer requests are separate from tour bookings and are not shown in customer booking tracking or the booking dashboard.

## Customer booking tracking

The public **Track Booking** page accepts the booking number and booking email. Customers can also sign in to see bookings associated with their verified email address. First-time email users with an existing booking can request a password setup link; it expires after 60 minutes and verifies the email when the password is saved. Existing signed-in users with an unverified email can request a verification link from the dashboard prompt. Google sign-in requires a verified Google email. Customers can change their password in the dashboard, and Google-only customers can set their first password there.

Run the customer password flag migration before enabling these features. Configure `APP_URL`, an actual delivery mailer such as SMTP, its credentials and sender address, and Google OAuth credentials and callback URL (`/auth/google/callback`). Register the same callback URL in Google Cloud. The `log` and `array` mailers cannot deliver setup links and are blocked for password reset; mailer credentials should be configured privately in the deployment environment. Existing Google users whose local password is unknown can use the email setup link to set one.

Customer accounts cannot access admin routes or the file manager. Email sign-in directs customers to their booking dashboard rather than a previously visited admin page, while preserving pending email verification links. The legacy `guest-order` endpoint now requires a verified login and returns only the authenticated user's bookings. Booking lookup is rate-limited but knowledge of both the booking number and email grants access to that booking's displayed details; share these privately.

## Product itineraries

Each itinerary stop can reference an activity or destination and include an optional note. Referenced places are automatically added to the product's activity or destination categories; removing a stop does not remove a category selected separately. Product details show links and up to three photos from the referenced place. Existing text itineraries remain supported. New itineraries are stored as versioned JSON in `products.itinerary`.

## Public language URLs and sitemap

Indexable public pages use `/en` or `/id` prefixes. The site default (`locale` in `config/app.php`) is used on the first visit; subsequent visits use the selected language. Old public URLs redirect to the appropriate prefixed URL. Admin, checkout, customer pages, OAuth callbacks, and existing signed review links keep their original URLs.

Language switches preserve search parameters and use the translated detail slug for records sharing the same service code. If a translation is missing, the visitor remains on the original-language page and sees a notice. Prefixes do not automatically translate content that has not been provided.

Set `APP_URL` to the public production origin before deploying. `/sitemap` dynamically returns a Spatie XML sitemap with available public URLs for both languages, excluding private pages. To publish a static `public/sitemap.xml`, run `php artisan sitemap:generate` after configuring `APP_URL` and repeat it whenever public content or translations change. Existing static files may be served directly by the web server; regenerating the file is necessary to replace an older sitemap at `/sitemap.xml`.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
