<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Please use POST.']);
    exit;
}

// Get and decode the JSON body
$jsonBody = file_get_contents('php://input');
$data = json_decode($jsonBody, true);

// Validate the request data
if (!$data || !isset($data['amount']) || empty($data['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request. Amount is required.']);
    exit;
}

// Convert amount from decimal (e.g., 10.50) to integer cents (1050)
$amountCents = (int) (floatval($data['amount']) * 100);

// Prepare the API request data
$requestData = [
    'customer' => [
        'document' => [
            'number' => '12345678909',
            'type' => 'cpf'
        ],
        'name' => 'TESTE',
        'email' => 'TESTE@gmail.com'
    ],
    'amount' => $amountCents,
    'paymentMethod' => 'pix',
    'items' => [
        [
            'tangible' => false,
            'title' => 'teste',
            'unitPrice' => $amountCents,
            'quantity' => 1
        ]
    ]
];

// Add tracking data if available
if (isset($data['utm']) && !empty($data['utm'])) {
    $requestData['metadata'] = $data['utm'];
}

// Elite Pay API credentials and endpoint
$apiKey = 'sk_live_v2vPIRGc4U7NuujmhiI2VwhSIovbGLzFO3N4aCNyHr:x';
$apiUrl = 'https://api.elitepayoficial.com/v1/transactions';

// Initialize cURL session
$ch = curl_init($apiUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($apiKey),
    'Content-Type: application/json'
]);

// Execute the cURL request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to connect to payment service: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Close cURL session
curl_close($ch);

// Process the API response
$responseData = json_decode($response, true);

// Check if the request was successful
if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['id']) && isset($responseData['pix'])) {
    // Prepare the success response
    $pixData = $responseData['pix'];
    
    // Extract the QR code data
    $qrCode = isset($pixData['qrcode']) ? $pixData['qrcode'] : '';
    
    // Generate base64 image if not provided by API
    // Note: Usually the API should provide this, but we're providing a fallback
    $base64Image = '';
    if (!empty($qrCode)) {
        // In a real scenario, you might want to use a QR code generation library here
        // For now, we'll assume the QR code text is usable as is
        $base64Image = $qrCode;
    }
    
    // Return the required data
    echo json_encode([
        'transactionId' => $responseData['id'],
        'clientIdentifier' => $responseData['customer']['id'],
        'pix' => [
            'code' => $qrCode,
            'base64' => $base64Image
        ],
        'status' => $responseData['status']
    ]);
} else {
    // Handle API error response
    $errorMessage = isset($responseData['error']) ? $responseData['error'] : 'Unknown error occurred';
    
    http_response_code(400);
    echo json_encode([
        'error' => 'Payment service error: ' . $errorMessage,
        'details' => $responseData
    ]);
}
?>