<?php

chdir('../../');
require_once('api/Simpla.php');
require_once('payment/ConcordPay/ConcordPay.php');
$simpla     = new Simpla();
$concordpay = new ConcordPay();

$data = json_decode(file_get_contents("php://input"), true);

/**
 * Keys for generate response signature.
 *
 * @var string[]
 */
$keysForSignature = array(
    'merchantAccount',
    'orderReference',
    'amount',
    'currency'
);

$order_parse = !empty($data['orderReference']) ? explode('#', $data['orderReference']) : null;

if (is_array($order_parse)) {
    $order_id = $order_parse[0];
} else {
    $order_id = $order_parse;
}

// Get order from database.
$order = $simpla->orders->get_order((int)$order_id);
if (empty($order)) {
    die('Order not found');
}

// Get payment method from database.
$method = $simpla->payment->get_payment_method((int)$order->payment_method_id);
if (empty($method)) {
    die('Unknown payment method');
}

// Currency must match
$payment_currency = $simpla->money->get_currency((int)$method->currency_id);
if ($data['currency'] !== $payment_currency->code) {
    die('Bad currency');
}

// Check type of operation.
if (!isset($data['type']) || !in_array($data['type'], $concordpay->getOperationTypes(), true)) {
    die('Unknown operation type');
}

// Cannot pay for an order that has already been paid.
if ($order->paid && $data['type'] !== ConcordPay::RESPONSE_TYPE_REVERSE) {
    die('Order already paid');
}

$amount = !empty($data['amount']) ? $data['amount'] : null;
$concordpayAmount = round($amount, 2);
$orderAmount = round($simpla->money->convert($order->total_price, $method->currency_id, false), 2);
if ($orderAmount !== $concordpayAmount) {
    die('Invalid payment amount');
}

$settings = unserialize($method->settings, [true]);
$merchant = $settings['concordpay_merchant'];

$sign = array();
foreach ($keysForSignature as $dataKey) {
    if (array_key_exists($dataKey, $data)) {
        $sign [] = $data[$dataKey];
    }
}

$sign = implode(';', $sign);
$sign = hash_hmac('md5', $sign, $settings['concordpay_secretkey']);
if (!empty($data["merchantSignature"]) && $data["merchantSignature"] != $sign) {
    die("Verification signature is not correct");
}

if (isset($data['transactionStatus']) && $data['transactionStatus'] === ConcordPay::PAYMENT_STATUS_DECLINED) {
    $reasonCode = isset($data['reasonCode']) ? $data['reasonCode'] : '';
    $reason = isset($data['reason']) ? $data['reason'] : '';
    die("Error $reasonCode: $reason");
}

// Simpla CMS order statuses:
// 0 - ??????????
// 1 - ????????????
// 2 - ????????????????
// 3 - ????????????

if (isset($data['transactionStatus']) && $data['transactionStatus'] === ConcordPay::PAYMENT_STATUS_APPROVED) {
    $payment_date = new DateTime('now', new DateTimeZone('UTC'));
    if ($data['type'] === ConcordPay::RESPONSE_TYPE_PAYMENT) {
        // Ordinary payment.
        $simpla->orders->update_order(
            (int)$order->id,
            array(
                'paid'         => ConcordPay::ORDER_PAYMENT_STATUS_PAID,
                'payment_date' => $payment_date->format('Y-m-d H:i:s')
            )
        );
        $simpla->orders->close((int)$order->id);
    } elseif ($data['type'] === ConcordPay::RESPONSE_TYPE_REVERSE) {
        // Refunded payment.
        $simpla->orders->update_order(
            (int)$order->id,
            array(
                'paid'         => ConcordPay::ORDER_PAYMENT_STATUS_NOT_PAID,
                'status'       => 2, // ????????????????.
                'payment_date' => $payment_date->format('Y-m-d H:i:s'),
                'note'         => '???????????? ?????????????????? ??????????????'
            )
        );
    }

    $simpla->notify->email_order_user((int)$order->id);
    $simpla->notify->email_order_admin((int)$order->id);
}

// Redirect user to the order page
header('Location: ' . $simpla->config->root_url . '/order/' . $order->url);

exit();
