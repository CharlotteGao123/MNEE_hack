<?php 
defined('_JEXEC') or die; 
$orderId = "ORD-" . rand(10000, 99999);
?>

<div style="border:1px solid #e5e7eb; padding:24px; border-radius:16px; background:#fff; max-width:350px; font-family: sans-serif; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    // MonkeNee Token (use on Sepolia)
    const TOKEN_ADDRESS = "0x1c24eC77Bb12ffed0Fca6FCE15219E4a66304E74"; 
  // This is the MNEE Token:
  //0x8ccedbAe4916b79da7F3F612EfB2EB93A2bFD6cF; 
    
    // The payment gateway contract
    const GATEWAY_ADDRESS = "0xa8DDF2d31186632613b622d34B0eB094850f85d3";

    const btnApprove = document.getElementById('btn-approve');
    const btnPay = document.getElementById('btn-pay');
    const status = document.getElementById('status');
    const orderId = "<?php echo $orderId; ?>";
    const amountToPay = ethers.utils.parseEther("1.0"); // 1.0 MNEE

    // Simplified ABI, only includes the functions we need
    const tokenAbi = [
        "function approve(address spender, uint256 amount) public returns (bool)",
        "function allowance(address owner, address spender) public view returns (uint256)"
    ];
    const gatewayAbi = [
        "function payOrder(string memory _orderId, uint256 _amount) public"
    ];

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

    // --- Step 1: Approve ---
    btnApprove.addEventListener('click', async () => {
        try {
            await init();
            status.innerText = "Approving currency usage...";
            
            const tokenContract = new ethers.Contract(TOKEN_ADDRESS, tokenAbi, signer);
            
            // Initiate approval transaction
            const tx = await tokenContract.approve(GATEWAY_ADDRESS, amountToPay);
            status.innerText = "Wait for confirmation...";
            await tx.wait(); // Wait for blockchain confirmation

            status.innerText = "Approved! Now you can pay."
            status.style.color = "green";
            
            // Activate the pay button
            btnApprove.style.display = 'none'; // Hide the approve button
            btnPay.disabled = false;
            btnPay.style.background = "#059669"; // Change color to indicate it's active
            btnPay.style.cursor = "pointer";

        } catch (err) {
            console.error(err);
            status.innerText = "Error: " + (err.reason || err.message);
            status.style.color = "red";
        }
    });

    // --- Step 2: Pay ---
    btnPay.addEventListener('click', async () => {
        try {
            status.innerText = "Processing Payment...";
            const gatewayContract = new ethers.Contract(GATEWAY_ADDRESS, gatewayAbi, signer);

            // Call the payOrder function in the contract   
            const tx = await gatewayContract.payOrder(orderId, amountToPay);
            
            status.innerText = "Sending Transaction...";
            await tx.wait(); // Wait for blockchain confirmation

            status.innerHTML = "🎉 <strong>Payment Successful!</strong><br>Recorded on Blockchain.";
            btnPay.innerText = "Paid";
            btnPay.disabled = true;

        } catch (err) {
            console.error(err);
            status.innerText = "Error: " + (err.reason || err.message);
            status.style.color = "red";
        }
    });
});
</script>