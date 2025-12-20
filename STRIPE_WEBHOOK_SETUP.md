# Stripe Connect Webhook Setup

## Why Connect Webhooks?

For **Stripe Apps/Connect**, you cannot create webhook endpoints on connected accounts. Instead, you create **Connect webhooks** on your platform account that receive events from all connected accounts.

## Setup Instructions

### 1. Go to Stripe Dashboard

Visit: [https://dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks)

### 2. Add Endpoint

Click **"Add endpoint"** button

### 3. Configure Endpoint

**Endpoint URL:**
```
https://wardianlabs.test/stripe/webhook
```
(Replace with your production domain in production)

**Description:**
```
Wardian App - Subscription Management
```

**Listen to:**
Select **"Events on connected accounts"** (this is crucial!)

**Events to send:**
Select the following events:
- ✅ `customer.subscription.created`
- ✅ `customer.subscription.updated`
- ✅ `customer.subscription.deleted`

### 4. Copy Webhook Secret

After creating the endpoint:
1. Click on the endpoint to view details
2. Click **"Reveal"** next to "Signing secret"
3. Copy the secret (starts with `whsec_`)

### 5. Update .env File

Open your `.env` file and update:

```bash
STRIPE_WEBHOOK_SECRET=whsec_your_actual_secret_here
```

Replace `whsec_your_actual_secret_here` with the secret you copied.

### 6. Restart Application

```bash
make stop
make start
```

Or just clear cache:
```bash
docker compose -p wardianapp exec app bin/console cache:clear
```

## How It Works

### Installation Flow

When a user installs your Stripe App:
1. OAuth callback connects their Stripe account
2. Account details saved to database
3. User can now create subscriptions

### Webhook Flow

When a subscription is created on a connected account:
1. **Stripe sends webhook** to your platform endpoint: `https://wardianlabs.test/stripe/webhook`
2. **Webhook payload includes**:
   - Event data (subscription details)
   - `account` field with connected account ID
3. **Your app validates** signature using your platform webhook secret
4. **Finds tenant** by looking up the `price_id` in `tenant_price_to_roles_mapping`
5. **Creates linking token** and sends email to customer
6. **Customer clicks link** → connects Discord → roles assigned

## Webhook Payload Example

```json
{
  "id": "evt_xxxxx",
  "type": "customer.subscription.created",
  "account": "acct_connected_account_id",  ← Connected account ID
  "data": {
    "object": {
      "id": "sub_xxxxx",
      "customer": "cus_xxxxx",
      "items": {
        "data": [
          {
            "price": {
              "id": "price_xxxxx"  ← Used to find tenant
            }
          }
        ]
      }
    }
  }
}
```

## Testing

### Test in Stripe Dashboard

1. Go to: Webhooks → Your endpoint → Send test webhook
2. Select event: `customer.subscription.created`
3. Click "Send test webhook"

### Test with Real Subscription

1. Install your Stripe App on a test account
2. Create a product and price in the connected account
3. Configure the price in your app's tenant settings
4. Create a test subscription with that price
5. Check your logs for webhook processing

## Troubleshooting

### Signature Verification Failed

**Error:**
```
Stripe webhook signature verification failed
```

**Solution:**
- Verify `STRIPE_WEBHOOK_SECRET` in `.env` matches the secret in Stripe Dashboard
- Make sure webhook is configured for "Events on connected accounts"
- Clear cache after updating `.env`

### No Tenant Found

**Error (in logs):**
```
No tenant found for price ID
```

**Solution:**
- Verify the price_id is configured in `tenant_price_to_roles_mapping` table
- Check that tenant has connected both Stripe and Discord

### Webhook Not Received

**Checklist:**
- ✅ Endpoint URL is accessible from internet
- ✅ HTTPS is enabled (Stripe requires HTTPS)
- ✅ Webhook is configured for "Events on connected accounts"
- ✅ Selected subscription events in webhook configuration

## Production Deployment

When deploying to production:

1. **Create production webhook** in Stripe Dashboard
   - Use production domain URL
   - Configure for "Events on connected accounts"
   - Select subscription events

2. **Update production .env**
   ```bash
   STRIPE_WEBHOOK_SECRET=whsec_production_secret_here
   ```

3. **Deploy and test**
   - Create test subscription
   - Verify webhook is received
   - Check email is sent
   - Test Discord linking flow

## Architecture Notes

### Why Not Per-Account Webhooks?

For Stripe Connect, attempting to create webhooks on connected accounts results in:
```
You are not permitted to configure webhook endpoints on a connected account.
```

This is by design - Stripe Apps must use Connect webhooks on the platform account.

### Security

- All webhooks are signature-verified using `STRIPE_WEBHOOK_SECRET`
- Webhook secret is unique to your platform
- Connected account ID in payload ensures correct tenant association
- Linking tokens expire after 7 days

## Related Files

- **Webhook Controller**: `lib/modules/src/Frontend/Ui/Adapter/Http/Stripe/WebhookController.php`
- **Webhook Validator**: `lib/modules/src/Shared/Infrastructure/Stripe/StripeWebhookValidator.php`
- **Subscription Created Handler**: `lib/modules/src/Frontend/Application/Stripe/Webhook/HandleSubscriptionCreatedUseCase.php`
- **Account Linking**: `lib/modules/src/Frontend/Ui/Adapter/Http/AccountLinkingController.php`
