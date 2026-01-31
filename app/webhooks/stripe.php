<?php
// app/webhooks/stripe.php

// 1. Setup Environment
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Ensure Composer autoload exists

// 2. Fetch API Keys from DB
$params = [];
$stmt = $pdo->query("SELECT conf_key, conf_value FROM system_config WHERE conf_key IN ('stripe_secret_key', 'stripe_webhook_secret')");
while ($row = $stmt->fetch()) {
    $params[$row['conf_key']] = $row['conf_value'];
}

$stripeSecret = $params['stripe_secret_key'] ?? '';
$endpointSecret = $params['stripe_webhook_secret'] ?? '';

if (!$stripeSecret || !$endpointSecret) {
    http_response_code(500);
    echo "Stripe not configured.";
    exit;
}

\Stripe\Stripe::setApiKey($stripeSecret);

// 3. Receive Payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpointSecret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// 4. Handle Events
switch ($event->type) {
    case 'checkout.session.completed':
        handleCheckoutCompleted($pdo, $event->data->object);
        break;
        
    case 'invoice.payment_succeeded':
        handleInvoicePaymentSucceeded($pdo, $event->data->object);
        break;

    case 'invoice.payment_failed':
        handleInvoicePaymentFailed($pdo, $event->data->object);
        break;

    case 'customer.subscription.deleted':
        handleSubscriptionDeleted($pdo, $event->data->object);
        break;
    
    // Add other event types as needed
    default:
        // Unexpected event type
        echo 'Received unknown event type ' . $event->type;
}

http_response_code(200);


// --- Handler Functions ---

function handleCheckoutCompleted($pdo, $session) {
    // Fired when a user successfully enters their credit card and starts a trial/subscription
    
    $company_id = $session->client_reference_id; // Passed during checkout creation
    $stripe_customer_id = $session->customer;
    $subscription_id = $session->subscription;

    if ($company_id && $subscription_id) {
        $stmt = $pdo->prepare("UPDATE companies SET 
            external_customer_id = ?, 
            stripe_subscription_id = ?, 
            plan_status = 'Active' 
            WHERE id = ?");
        $stmt->execute([$stripe_customer_id, $subscription_id, $company_id]);
    }
}

function handleInvoicePaymentSucceeded($pdo, $invoice) {
    // Fired every month when a payment works
    
    $subscription_id = $invoice->subscription;
    
    // Calculate Next Billing Date (from Stripe timestamp)
    // Stripe invoices usually have 'period_end'
    $next_billing = date('Y-m-d H:i:s', $invoice->lines->data[0]->period->end);

    $stmt = $pdo->prepare("UPDATE companies SET 
        plan_status = 'Active', 
        next_billing_at = ? 
        WHERE stripe_subscription_id = ?");
    $stmt->execute([$next_billing, $subscription_id]);
}

function handleInvoicePaymentFailed($pdo, $invoice) {
    // Fired when payment fails
    
    $subscription_id = $invoice->subscription;
    
    $stmt = $pdo->prepare("UPDATE companies SET plan_status = 'Past_Due' WHERE stripe_subscription_id = ?");
    $stmt->execute([$subscription_id]);
}

function handleSubscriptionDeleted($pdo, $subscription) {
    // Fired when subscription is Canceled
    
    $subscription_id = $subscription->id;
    
    $stmt = $pdo->prepare("UPDATE companies SET plan_status = 'Canceled' WHERE stripe_subscription_id = ?");
    $stmt->execute([$subscription_id]);
}
?>
