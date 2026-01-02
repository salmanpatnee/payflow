# Payflow

A modern Laravel-based payment collection system with Stripe integration for creating payment links, managing payment collections, and processing transactions.

## Features

- 💳 **Payment Collections Management** - Create and manage payment collections with multiple items
- 🔗 **Payment Link Generation** - Generate shareable payment links for clients
- 💰 **Stripe Integration** - Secure payment processing via Stripe
- 📊 **Transaction Tracking** - Complete audit trail for all transactions
- 📧 **Receipt Generation** - Automated receipt generation and email delivery
- 🔔 **Webhook Processing** - Real-time payment status updates via Stripe webhooks
- 📈 **Reporting & Insights** - Track payment status and collection metrics
- 🔐 **Secure Authentication** - Laravel Fortify-based authentication system

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.3)
- **Frontend:** Vue 3 + Inertia.js v2
- **Styling:** Tailwind CSS v4
- **Payment Processing:** Stripe
- **Database:** MySQL/PostgreSQL
- **Testing:** Pest v4

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/salmanpatnee/payflow.git
   cd payflow
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database and Stripe credentials in `.env`:
   ```
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   STRIPE_KEY=your_stripe_publishable_key
   STRIPE_SECRET=your_stripe_secret_key
   STRIPE_WEBHOOK_SECRET=your_webhook_secret
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Build assets:
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

7. Start the server:
   ```bash
   php artisan serve
   ```

## Usage

### Creating Payment Collections

1. Navigate to the admin panel
2. Create a new payment collection with items
3. Generate a payment link
4. Share the link with your client

### Processing Payments

Clients can:
- View payment collection details
- Pay via Stripe Checkout
- Receive payment confirmation and receipt via email

### Webhook Setup

Configure your Stripe webhook endpoint:
```
https://yourdomain.com/stripe/webhook
```

Events handled:
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `checkout.session.completed`

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific tests:
```bash
php artisan test --filter=PaymentCollectionTest
```

## Development

Format code with Laravel Pint:
```bash
vendor/bin/pint
```

## Documentation

- [Admin and User Flow](docs/admin-and-user-flow.md)
- [Payment Links Plan](docs/payment-links-plan.md)
- [Stripe Integration Guide](docs/stripe-integration-guide.md)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email salmanpatni92@gmail.com instead of using the issue tracker.

## License

This project is open-sourced software licensed under the MIT license.

## Author

**Salman A. Ghani**
- GitHub: [@salmanpatnee](https://github.com/salmanpatnee)
- Email: salmanpatni92@gmail.com
- Location: Karachi, Pakistan

---

Built with ❤️ using Laravel and Vue.js
