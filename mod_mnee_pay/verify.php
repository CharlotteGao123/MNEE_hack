<?php
// File Location: modules/mod_mnee_pay/verify.php

// 1. Set Headers (Enable CORS to prevent browser errors during demo)
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// 2. Get txHash from Frontend Request
$input = json_decode(file_get_contents('php://input'), true);
$txHash = isset($input['txHash']) ? $input['txHash'] : '';

// Validation: Ensure hash is provided
if (empty($txHash)) {
    echo json_encode(['success' => false, 'message' => 'No transaction hash provided']);
    exit;
}

// 3. Configure Blockchain Node (RPC URL)
// Using Sepolia Public Node. If it's slow, replace with your Alchemy/Infura URL.
$rpcUrl = "https://rpc.sepolia.org"; 

// 4. Your Gateway Contract Address
// IMPORTANT: Must match the GATEWAY_ADDRESS in your default.php
$myGatewayAddress = strtolower("0xa8DDF2d31186632613b622d34B0eB094850f85d3");

// 5. Construct JSON-RPC Request (eth_getTransactionReceipt)
$data = [
    "jsonrpc" => "2.0",
    "method" => "eth_getTransactionReceipt",
    "params" => [$txHash],
    "id" => 1
];

// 6. Send Request via cURL
$ch = curl_init($rpcUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set 10s timeout to prevent hanging

$response = curl_exec($ch);

// Handle Connection Errors
if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'Node connection error: ' . curl_error($ch)]);
    exit;
}
curl_close($ch);

// 7. Parse Blockchain Response
$result = json_decode($response, true);

// Check if transaction exists on chain
if (!isset($result['result']) || $result['result'] === null) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found yet (Still Mining...)']);
    exit;
}

$txData = $result['result'];

// === CORE VERIFICATION LOGIC ===

// Check A: Status must be 0x1 (Success)
$isSuccess = ($txData['status'] === '0x1');

// Check B: Recipient must be YOUR Gateway Contract
// Note: 'to' address might be null if it's a contract creation, so we check existence.
$txTo = isset($txData['to']) ? $txData['to'] : '';
$isToMyContract = (strtolower($txTo) === $myGatewayAddress);

// 8. Final Decision
if ($isSuccess && $isToMyContract) {
    //VERIFIED
    echo json_encode([
        'success' => true, 
        'message' => 'Payment Verified Successfully!'
    ]);
} else {
    //FAILED
    $errorMsg = 'Verification failed.';
    
    if (!$isSuccess) {
        $errorMsg = 'Transaction failed on blockchain (Reverted).';
    } elseif (!$isToMyContract) {
        $errorMsg = 'Invalid Recipient: Payment was not sent to this store.';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMsg
    ]);
}
?>