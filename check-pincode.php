<?php
/**
 * BUYOERA - Indian Pincode Serviceability & Delivery Date Checker API
 */

header('Content-Type: application/json');

$pincode = isset($_GET['pincode']) ? trim($_GET['pincode']) : '';

if (!preg_match('/^[1-9][0-9]{5}$/', $pincode)) {
    echo json_encode([
        'serviceable' => false,
        'message' => 'Please enter a valid 6-digit Indian PIN code.'
    ]);
    exit;
}

// Map the first 2 digits to major regions in India
$prefix = (int) substr($pincode, 0, 2);

$regionMap = [
    11 => ['city' => 'New Delhi', 'state' => 'Delhi', 'transit_days' => 2],
    12 => ['city' => 'Gurugram / Faridabad', 'state' => 'Haryana', 'transit_days' => 2],
    14 => ['city' => 'Ludhiana / Amritsar', 'state' => 'Punjab', 'transit_days' => 3],
    16 => ['city' => 'Chandigarh', 'state' => 'Chandigarh', 'transit_days' => 3],
    20 => ['city' => 'Noida / Ghaziabad', 'state' => 'Uttar Pradesh', 'transit_days' => 2],
    22 => ['city' => 'Lucknow', 'state' => 'Uttar Pradesh', 'transit_days' => 3],
    30 => ['city' => 'Jaipur', 'state' => 'Rajasthan', 'transit_days' => 3],
    38 => ['city' => 'Ahmedabad', 'state' => 'Gujarat', 'transit_days' => 2],
    39 => ['city' => 'Surat / Vadodara', 'state' => 'Gujarat', 'transit_days' => 2],
    40 => ['city' => 'Mumbai / Thane', 'state' => 'Maharashtra', 'transit_days' => 1],
    41 => ['city' => 'Pune', 'state' => 'Maharashtra', 'transit_days' => 2],
    44 => ['city' => 'Nagpur', 'state' => 'Maharashtra', 'transit_days' => 3],
    46 => ['city' => 'Bhopal', 'state' => 'Madhya Pradesh', 'transit_days' => 3],
    50 => ['city' => 'Hyderabad', 'state' => 'Telangana', 'transit_days' => 2],
    53 => ['city' => 'Visakhapatnam', 'state' => 'Andhra Pradesh', 'transit_days' => 3],
    56 => ['city' => 'Bengaluru', 'state' => 'Karnataka', 'transit_days' => 2],
    57 => ['city' => 'Mangaluru', 'state' => 'Karnataka', 'transit_days' => 3],
    60 => ['city' => 'Chennai', 'state' => 'Tamil Nadu', 'transit_days' => 2],
    64 => ['city' => 'Coimbatore', 'state' => 'Tamil Nadu', 'transit_days' => 3],
    68 => ['city' => 'Kochi', 'state' => 'Kerala', 'transit_days' => 3],
    69 => ['city' => 'Thiruvananthapuram', 'state' => 'Kerala', 'transit_days' => 3],
    70 => ['city' => 'Kolkata', 'state' => 'West Bengal', 'transit_days' => 3],
    75 => ['city' => 'Bhubaneswar', 'state' => 'Odisha', 'transit_days' => 3],
    78 => ['city' => 'Guwahati', 'state' => 'Assam', 'transit_days' => 4],
    80 => ['city' => 'Patna', 'state' => 'Bihar', 'transit_days' => 3],
    83 => ['city' => 'Ranchi', 'state' => 'Jharkhand', 'transit_days' => 3],
];

$loc = $regionMap[$prefix] ?? [
    'city' => 'Standard Zone',
    'state' => 'India',
    'transit_days' => 4
];

$days = $loc['transit_days'];
$deliveryTimestamp = strtotime("+{$days} days");
$formattedDate = date('D, jS M', $deliveryTimestamp);

echo json_encode([
    'serviceable' => true,
    'pincode' => $pincode,
    'city' => $loc['city'],
    'state' => $loc['state'],
    'estimated_days' => "{$days}-" . ($days + 1) . " Business Days",
    'estimated_date' => $formattedDate,
    'cod_available' => true,
    'express_available' => true
]);
