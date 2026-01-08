<?php 
// No direct access
defined('_JEXEC') or die; 

// Generate a random Order ID for demo purposes
$orderId = "ORD-" . rand(10000, 99999);
?>

<style>
    .mnee-card {
        border: 1px solid #e5e7eb;
        padding: 24px;
        border-radius: 16px;
        background: #fff;
        max-width: 350px;
        font-family: sans-serif;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin: 0 auto; /* Center alignment */
    }
    .receipt-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 13px;
        color: #374151;
        border-bottom: 1px dashed #e5e7eb;
        padding-bottom: 5px;
    }
    .receipt-label { font-weight: bold; color: #6B7280; }
    .receipt-val { font-family: monospace; color: #111827; }
    
    /* Utility class to hide elements */
    .hidden { display: none; }
</style>

<div id="mnee-module-container">

    <div id="card-payment" class="mnee-card">
        <div style="margin-bottom:15px; color:#6B7280; font-size:12px;">
            Order ID: <strong><?php echo $orderId; ?></strong>
        </div>
        
        <h1 style="color:#111827; margin:0; font-size:32px;">1.00 <small style="font-size:16px; color:#6B7280;">MNEE</small></h1>
        <p style="color:#10B981; font-size:12px; margin-bottom:20px;">⚡️ Live on Sepolia Testnet</p>

        <button id="btn-approve" style="width:100%; padding:12px; background:#4F46E5; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer; margin-bottom:10px;">
            Step 1: Approve MNEE
        </button>

        <button id="btn-pay" disabled style="width:100%; padding:12px; background:#D1D5DB; color:white; border:none; border-radius:8px; font-weight:bold; cursor:not-allowed;">
            Step 2: Pay Now
        </button>
        
        <p id="status" style="margin-top:15px; font-size:13px; color:#6B7280; text-align:center; min-height:20px; word-break: break-all;"></p>
    </div>

    <div id="card-receipt" class="mnee-card hidden">
        <div style="text-align:center; margin-bottom:20px;">
            <div style="font-size:40px;">🧾</div>
            <h2 style="margin:10px 0 5px 0; color:#111827;">Transaction Receipt</h2>
            <p style="color:#059669; font-size:12px; margin:0;">Payment Verified via Blockchain</p>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Status</span>
            <span class="receipt-val" style="color:green;">CONFIRMED</span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Order ID</span>
            <span class="receipt-val"><?php echo $orderId; ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Amount</span>
            <span class="receipt-val">1.00 MNEE</span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Network</span>
            <span class="receipt-val">Sepolia</span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Tx Hash</span>
            <span class="receipt-val"><a id="rec-link" href="#" target="_blank" style="color:#4F46E5; text-decoration:none;">View on Explorer ↗</a></span>
        </div>

        <div style="margin-top:20px; background:#F3F4F6; padding:10px; font-size:11px; color:#6B7280; border-radius:6px;">
            <strong>Tx ID:</strong> <span id="rec-hash-full">Loading...</span>
        </div>

        <button id="btn-finish" style="width:100%; margin-top:20px; padding:12px; background:#059669; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">
            ✅ Confirm & Finish Order
        </button>
    </div>

    <div id="card-success" class="mnee-card hidden" style="text-align:center;">
        <div style="font-size:60px; margin-bottom:20px;">🎉</div>
        <h2 style="color:#111827;">Thank You!</h2>
        <p style="color:#6B7280;">Your order has been processed successfully.</p>
        <button onclick="location.reload()" style="margin-top:20px; padding:10px 20px; border:1px solid #D1D5DB; background:white; border-radius:6px; cursor:pointer;">
            Back to Home
        </button>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    // === Configuration ===
    const TOKEN_ADDRESS = "0x1c24eC77Bb12ffed0Fca6FCE15219E4a66304E74"; 
    const GATEWAY_ADDRESS = "0xa8DDF2d31186632613b622d34B0eB094850f85d3";
    
    // Path to your verify.php (Ensure this file exists in modules/mod_mnee_pay/)
    const VERIFY_URL = "modules/mod_mnee_pay/verify.php";

    // === DOM Elements ===
    const cardPayment = document.getElementById('card-payment');
    const cardReceipt = document.getElementById('card-receipt');
    const cardSuccess = document.getElementById('card-success');
    
    const btnApprove = document.getElementById('btn-approve');
    const btnPay = document.getElementById('btn-pay');
    const btnFinish = document.getElementById('btn-finish');
    const status = document.getElementById('status');
    
    // Receipt Specific Elements
    const recHashFull = document.getElementById('rec-hash-full');
    const recLink = document.getElementById('rec-link');

    const orderId = "<?php echo $orderId; ?>";
    const amountToPay = ethers.utils.parseEther("1.0");

    // Minimal ABI
    const tokenAbi = [ "function approve(address spender, uint256 amount) public returns (bool)" ];
    const gatewayAbi = [ "function payOrder(string memory _orderId, uint256 _amount) public" ];

    let provider, signer;

    // Initialize Web3
    async function init() {
        if (typeof window.ethereum === 'undefined') {
            status.innerText = "Please install MetaMask!";
            return;
        }
        provider = new ethers.providers.Web3Provider(window.ethereum);
        await provider.send("eth_requestAccounts", []);
        signer = provider.getSigner();
    }

    // --- Step 1: Approve Token Usage ---
    btnApprove.addEventListener('click', async () => {
        try {
            await init();
            status.innerText = "Approving currency usage...";
            const tokenContract = new ethers.Contract(TOKEN_ADDRESS, tokenAbi, signer);
            
            // Send Approve Transaction
            const tx = await tokenContract.approve(GATEWAY_ADDRESS, amountToPay);
            status.innerText = "Wait for confirmation...";
            await tx.wait(); 
            
            // UI Update
            status.innerText = "Approved! Now you can pay.";
            status.style.color = "green";
            btnApprove.style.display = 'none';
            btnPay.disabled = false;
            btnPay.style.background = "#059669";
            btnPay.style.cursor = "pointer";

        } catch (err) {
            console.error(err);
            status.innerText = "Error: " + (err.reason || err.message);
            status.style.color = "red";
        }
    });

    // --- Step 2: Pay & Generate Receipt ---
    btnPay.addEventListener('click', async () => {
        try {
            status.innerText = "Processing Payment...";
            const gatewayContract = new ethers.Contract(GATEWAY_ADDRESS, gatewayAbi, signer);
            
            // Send Payment Transaction
            const tx = await gatewayContract.payOrder(orderId, amountToPay);
            
            status.innerText = "Mining transaction... (Please wait)";
            const receipt = await tx.wait(); // Wait for blockchain confirmation

            // --- Server-Side Verification ---
            status.innerText = "Verifying with server...";
            
            // Call verify.php to check the hash on-chain
            const response = await fetch(VERIFY_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ txHash: receipt.transactionHash })
            });
            const data = await response.json();

            if (data.success) {
                // ✅ Success: Switch to Receipt View
                showReceipt(receipt.transactionHash);
            } else {
                status.innerText = "Server Verify Failed: " + data.message;
                status.style.color = "red";
            }

        } catch (err) {
            console.error(err);
            status.innerText = "Error: " + (err.reason || err.message);
            status.style.color = "red";
        }
    });

    // --- Helper Function: Show Receipt ---
    function showReceipt(hash) {
        // 1. Hide Payment Card
        cardPayment.style.display = 'none';
        
        // 2. Populate Receipt Data
        recHashFull.innerText = hash;
        recLink.href = "https://sepolia.etherscan.io/tx/" + hash;

        // 3. Show Receipt Card
        cardReceipt.classList.remove('hidden');
    }

    // --- Step 3: User Confirmation ---
    btnFinish.addEventListener('click', () => {
        // Hide receipt, show final success message
        cardReceipt.style.display = 'none';
        cardSuccess.classList.remove('hidden');
    });
});
</script>