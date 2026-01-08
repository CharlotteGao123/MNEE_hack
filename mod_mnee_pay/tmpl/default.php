<?php 
// No direct access
defined('_JEXEC') or die; 

// Generate a random Order ID
$orderId = "ORD-" . rand(10000, 99999);
$currentDate = date("Y-m-d H:i:s");
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
        margin: 0 auto;
    }
    .hidden { display: none; }
    
    /* Receipt Row Styles */
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

    /* Hash Box */
    .hash-full-display {
        display: block;
        margin-top: 5px;
        font-family: monospace;
        font-size: 11px;
        color: #4B5563;
        background: #F3F4F6;
        padding: 8px;
        border-radius: 6px;
        word-break: break-all;
        text-align: left;
    }

    /* Button Styles */
    .btn-action {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 10px;
        transition: background 0.2s;
    }

    .btn-green { background: #059669; color: white; }
    .btn-green:hover { background: #047857; }

    .btn-gray { background: #D1D5DB; color: white; cursor:not-allowed; }
    
    .btn-indigo { background: #4F46E5; color: white; }
    .btn-indigo:hover { background: #4338CA; }

    .btn-outline { background: white; border: 1px solid #D1D5DB; color: #374151; }
    .btn-outline:hover { background: #F9FAFB; }
</style>

<div id="mnee-module-container">

    <div id="card-payment" class="mnee-card">
        <div style="margin-bottom:15px; color:#6B7280; font-size:12px;">
            Order ID: <strong><?php echo $orderId; ?></strong>
        </div>
        
        <h1 style="color:#111827; margin:0; font-size:32px;">1.00 <small style="font-size:16px; color:#6B7280;">MNEE</small></h1>
        <p style="color:#10B981; font-size:12px; margin-bottom:20px;">Live on Sepolia Testnet</p>

        <button id="btn-approve" class="btn-action btn-indigo">
            Step 1: Approve MNEE
        </button>

        <button id="btn-pay" disabled class="btn-action btn-gray">
            Step 2: Pay & View Receipt
        </button>
        
        <p id="status" style="margin-top:15px; font-size:13px; color:#6B7280; text-align:center; min-height:20px; word-break: break-all;"></p>
    </div>

    <div id="card-receipt" class="mnee-card hidden">
        <div style="text-align:center; margin-bottom:20px;">
            <h2 style="margin:10px 0 5px 0; color:#111827;">Transaction Receipt</h2>
            <p style="color:#059669; font-size:12px; margin:0;">Verified on Blockchain</p>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Status</span>
            <span class="receipt-val" style="color:green;">PAID / CONFIRMED</span>
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
            <span class="receipt-label">Link</span>
            <span class="receipt-val"><a id="rec-link" href="#" target="_blank" style="color:#4F46E5; text-decoration:none;">View on Scan</a></span>
        </div>

        <div style="margin-top:15px;">
            <strong style="font-size:12px; color:#6B7280;">Transaction Hash:</strong>
            <span id="rec-hash-full" class="hash-full-display">Loading...</span>
        </div>

        <button id="btn-finish" class="btn-action btn-green" style="margin-top: 20px;">
            Finish & Return Home
        </button>
    </div>

    <div id="card-success" class="mnee-card hidden" style="text-align:center;">
        <h2 style="color:#111827; margin-top:30px;">Thank You!</h2>
        <p style="color:#6B7280;">Your order is complete.</p>
        
        <button onclick="location.reload()" class="btn-action btn-outline" style="width: auto; padding: 10px 30px; margin-top: 20px;">
            Back to Home
        </button>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const TOKEN_ADDRESS = "0x1c24eC77Bb12ffed0Fca6FCE15219E4a66304E74"; 
    const GATEWAY_ADDRESS = "0xa8DDF2d31186632613b622d34B0eB094850f85d3";
    const VERIFY_URL = "modules/mod_mnee_pay/verify.php";

    // UI Elements
    const cardPayment = document.getElementById('card-payment');
    const cardReceipt = document.getElementById('card-receipt');
    const cardSuccess = document.getElementById('card-success');
    
    const btnApprove = document.getElementById('btn-approve');
    const btnPay = document.getElementById('btn-pay');
    const btnFinish = document.getElementById('btn-finish');
    const status = document.getElementById('status');
    
    const recHashFull = document.getElementById('rec-hash-full');
    const recLink = document.getElementById('rec-link');

    // Data
    const orderId = "<?php echo $orderId; ?>";
    const amountToPay = ethers.utils.parseEther("1.0");

    // ABI
    const tokenAbi = [ "function approve(address spender, uint256 amount) public returns (bool)" ];
    const gatewayAbi = [ "function payOrder(string memory _orderId, uint256 _amount) public" ];

    let provider, signer;

    async function init() {
        if (typeof window.ethereum === 'undefined') {
            status.innerText = "Please install MetaMask!";
            return;
        }
        provider = new ethers.providers.Web3Provider(window.ethereum);
        await provider.send("eth_requestAccounts", []);
        signer = provider.getSigner();
    }

    // Step 1: Approve
    btnApprove.addEventListener('click', async () => {
        try {
            await init();
            status.innerText = "Approving...";
            const tokenContract = new ethers.Contract(TOKEN_ADDRESS, tokenAbi, signer);
            const tx = await tokenContract.approve(GATEWAY_ADDRESS, amountToPay);
            status.innerText = "Waiting for approval...";
            await tx.wait(); 
            
            status.innerText = "Approved!";
            status.style.color = "green";
            btnApprove.style.display = 'none';
            btnPay.disabled = false;
            btnPay.classList.remove('btn-gray');
            btnPay.classList.add('btn-indigo');
            btnPay.innerText = "Step 2: Pay & View Receipt";
        } catch (err) {
            console.error(err);
            status.innerText = "Error: " + (err.reason || err.message);
        }
    });

    // Step 2: Pay with 5s Delay Redirect
    btnPay.addEventListener('click', async () => {
        const receiptWindow = window.open("", "_blank");
        if (receiptWindow) {
            receiptWindow.document.write("<html><head><title>Processing...</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;background:#f9fafb;'><h1>Processing Payment...</h1><p>Please confirm the transaction in your wallet.</p></body></html>");
        }

        try {
            status.innerText = "Please confirm in MetaMask...";
            const gatewayContract = new ethers.Contract(GATEWAY_ADDRESS, gatewayAbi, signer);
            const tx = await gatewayContract.payOrder(orderId, amountToPay);

            // Update new tab to show countdown
            if (receiptWindow) {
                receiptWindow.document.body.innerHTML = `
                    <div style='text-align:center;font-family:sans-serif;margin-top:50px;'>
                        <h1>Transaction Sent!</h1>
                        <p>Connecting to Blockchain Explorer...</p>
                        <p style='color:#6B7280;'>Redirecting in 5 seconds...</p>
                    </div>`;
                
                setTimeout(() => {
                    receiptWindow.location.href = "https://sepolia.etherscan.io/tx/" + tx.hash;
                }, 5000);
            }

            status.innerText = "Verifying on blockchain...";
            const receipt = await tx.wait();

            const response = await fetch(VERIFY_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ txHash: receipt.transactionHash })
            });
            const data = await response.json();

            if (data.success) {
                // Show Clean Receipt (No PDF)
                showReceipt(receipt.transactionHash);
            } else {
                status.innerText = "Verify Failed: " + data.message;
                status.style.color = "red";
            }
        } catch (err) {
            console.error(err);
            status.innerText = "Error: " + (err.reason || err.message);
            if (receiptWindow) receiptWindow.close();
        }
    });

    function showReceipt(hash) {
        cardPayment.style.display = 'none';
        
        // Populate Full Hash
        recHashFull.innerText = hash;
        recLink.href = "https://sepolia.etherscan.io/tx/" + hash;
        
        cardReceipt.classList.remove('hidden');
    }

    btnFinish.addEventListener('click', () => {
        cardReceipt.style.display = 'none';
        cardSuccess.classList.remove('hidden');
    });
});
</script>