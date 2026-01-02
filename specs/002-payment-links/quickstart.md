# Quickstart Guide: Payment Link Generation & Client Flow

**Feature**: Payment Link Generation & Client Flow
**Date**: 2025-12-30

## Overview
This guide provides a quick overview of how to implement the payment link generation feature. This feature allows admins to generate shareable links for payment collections, which clients can access to view payment details and provide their information.

## Prerequisites
- Laravel 12 application with PHP 8.3
- Inertia.js v2 with Vue 3
- shadcn-vue components installed
- Payment collections and items functionality already implemented
- Database access for migrations

## Implementation Steps

### 1. Database Migrations
First, create and run the necessary database migrations:

```bash
# Create migration for payment collections extension
php artisan make:migration extend_payment_collections_for_payment_links

# Create migration for client access records
php artisan make:migration create_client_access_records_table
```

Then implement the migration files as specified in the data model document.

### 2. Model Updates
Update the PaymentCollection model to include the new fields and relationships:

```php
// In app/Models/PaymentCollection.php
class PaymentCollection extends Model
{
    protected $fillable = [
        // ... existing fields
        'payment_link_token',
        'payment_link_expires_at',
    ];

    protected $dates = [
        // ... existing dates
        'payment_link_expires_at',
    ];

    // Add relationship to client access records
    public function clientAccessRecords()
    {
        return $this->hasMany(ClientAccessRecord::class);
    }

    // Add method to generate payment link
    public function generatePaymentLink($expirationDays = 90)
    {
        $this->payment_link_token = Str::random(32);
        $this->payment_link_expires_at = now()->addDays($expirationDays);
        $this->save();
        
        return $this->payment_link_token;
    }

    // Add method to get payment link URL
    public function getPaymentLinkUrl()
    {
        if (!$this->payment_link_token) {
            return null;
        }
        
        return url('/pay/' . $this->payment_link_token);
    }
}
```

Create the ClientAccessRecord model:

```php
// Create app/Models/ClientAccessRecord.php
class ClientAccessRecord extends Model
{
    protected $fillable = [
        'payment_collection_id',
        'client_name',
        'client_email',
        'access_token',
        'ip_address',
        'accessed_at',
        'user_agent',
    ];

    protected $dates = [
        'accessed_at',
    ];

    public function paymentCollection()
    {
        return $this->belongsTo(PaymentCollection::class);
    }
}
```

### 3. Create Token Validation Middleware
Create middleware to validate payment link tokens:

```bash
php artisan make:middleware ValidatePaymentLink
```

```php
// In app/Http/Middleware/ValidatePaymentLink.php
class ValidatePaymentLink
{
    public function handle($request, Closure $next, $token = null)
    {
        $token = $token ?? $request->route('token');
        
        $collection = PaymentCollection::where('payment_link_token', $token)
            ->where('payment_link_expires_at', '>', now())
            ->where('status', 'active')
            ->first();
            
        if (!$collection) {
            abort(404, 'Payment link not found or expired');
        }
        
        $request->attributes->set('payment_collection', $collection);
        
        return $next($request);
    }
}
```

Register the middleware in `app/Http/Kernel.php`:

```php
// In app/Http/Kernel.php
protected $routeMiddleware = [
    // ... other middleware
    'payment.link' => \App\Http\Middleware\ValidatePaymentLink::class,
];
```

### 4. Create Controllers
Create the necessary controllers for handling payment link functionality:

```bash
php artisan make:controller PaymentLinkController
php artisan make:controller Admin\\PaymentCollectionLinkController
```

Implement the controllers as per the API contracts.

### 5. Define Routes
Add the routes to your `routes/web.php`:

```php
// Public payment link routes
Route::prefix('pay')->group(function () {
    Route::get('/{token}', [PaymentLinkController::class, 'show'])
        ->middleware(['payment.link'])
        ->name('pay.show');
    
    Route::post('/{token}/capture-info', [PaymentLinkController::class, 'captureInfo'])
        ->middleware(['payment.link', 'throttle:10,1'])
        ->name('pay.capture-info');
});

// Admin payment link routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::post('/payment-collections/{id}/generate-link', [PaymentCollectionLinkController::class, 'generate'])
        ->name('admin.payment-collections.generate-link');
    
    Route::get('/payment-collections/{id}/payment-link', [PaymentCollectionLinkController::class, 'show'])
        ->name('admin.payment-collections.show-link');
    
    Route::delete('/payment-collections/{id}/payment-link', [PaymentCollectionLinkController::class, 'destroy'])
        ->name('admin.payment-collections.destroy-link');
});
```

### 6. Create Frontend Components
Create Vue components for the public payment page and admin link management:

```bash
# Create the public payment page component
# This should go in resources/js/Pages/PaymentLink/Show.vue
```

```vue
<!-- resources/js/Pages/PaymentLink/Show.vue -->
<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const props = defineProps({
  payment_collection: Object,
  client_access_record: Object
});

const formData = ref({
  client_name: '',
  client_email: ''
});

const captureClientInfo = () => {
  // Submit client information to the backend
  axios.post(`/pay/${props.payment_collection.uuid}/capture-info`, formData.value)
    .then(response => {
      // Handle success
      console.log('Client info captured:', response.data);
    })
    .catch(error => {
      // Handle error
      console.error('Error capturing client info:', error);
    });
};
</script>

<template>
  <Head title="Payment Details" />

  <div class="container mx-auto py-10">
    <Card class="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>{{ payment_collection.title }}</CardTitle>
        <CardDescription>{{ payment_collection.description }}</CardDescription>
      </CardHeader>
      
      <CardContent>
        <div class="mb-6">
          <h3 class="text-lg font-medium mb-4">Payment Items</h3>
          <div class="space-y-4">
            <Card v-for="item in payment_collection.items" :key="item.id">
              <CardHeader>
                <CardTitle class="text-base">{{ item.description }}</CardTitle>
                <CardDescription>Due: {{ item.due_date }}</CardDescription>
              </CardHeader>
              <CardContent>
                <p class="text-2xl font-bold">${{ item.amount }}</p>
              </CardContent>
              <CardFooter>
                <Button>Pay Now</Button>
              </CardFooter>
            </Card>
          </div>
        </div>
        
        <div class="border-t pt-6">
          <h3 class="text-lg font-medium mb-4">Your Information</h3>
          <form @submit.prevent="captureClientInfo" class="space-y-4">
            <div>
              <Label for="client_name">Name</Label>
              <Input 
                id="client_name" 
                v-model="formData.client_name" 
                placeholder="Your name" 
              />
            </div>
            
            <div>
              <Label for="client_email">Email</Label>
              <Input 
                id="client_email" 
                v-model="formData.client_email" 
                type="email" 
                placeholder="your.email@example.com" 
              />
            </div>
            
            <Button type="submit">Save Information</Button>
          </form>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
```

### 7. Update Admin Interface
Add payment link generation functionality to the existing payment collection admin interface:

```vue
<!-- Example button to generate/copy payment link -->
<template>
  <div class="flex items-center space-x-2">
    <Button 
      v-if="!paymentLinkUrl" 
      @click="generatePaymentLink"
      :disabled="generating"
    >
      {{ generating ? 'Generating...' : 'Generate Payment Link' }}
    </Button>
    
    <div v-else class="flex items-center space-x-2">
      <Input :value="paymentLinkUrl" readonly class="w-64" />
      <Button @click="copyToClipboard(paymentLinkUrl)">Copy Link</Button>
      <Button @click="revokePaymentLink" variant="destructive">Revoke</Button>
    </div>
  </div>
</template>
```

### 8. Run Migrations and Test
Run the migrations and test the functionality:

```bash
php artisan migrate
php artisan test --filter=PaymentLink
```

## Running the Application
After completing the implementation:

1. Ensure your development server is running:
   ```bash
   php artisan serve
   # or if using Laravel Sail
   sail up
   ```

2. Access the admin interface to create payment collections and generate links

3. Test the public payment link functionality with the generated URLs

## Troubleshooting
- If payment links return 404, verify the token is correctly generated and not expired
- If client information isn't saving, check the form submission and validation
- For UI issues, ensure shadcn-vue components are properly installed and configured