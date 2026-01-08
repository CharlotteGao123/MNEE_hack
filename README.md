# Joomla MNEE Payment Gateway

> Submission for **MNEE Hackathon**
> **Track:** Commerce & Creator Tools

A decentralized payment extension for **Joomla CMS** that enables merchants to accept **MNEE Stablecoin** payments directly on their e-commerce sites. Seamlessly bridges Web2 e-commerce with Web3 payments using Smart Contracts.

## Project Overview
This project solves the "crypto-to-commerce" gap by providing a plug-and-play module for Joomla. It allows users to pay for orders using MNEE tokens via MetaMask, with real-time on-chain verification.

### Key Features
- **Plug-and-Play**: Installs as a standard Joomla Module (`mod_mnee_pay`).
- **Web3 Native**: Direct wallet connection via MetaMask (Ethers.js).
- **Secure Gateway**: Uses a dedicated smart contract to verify and record payments on-chain.
- **Fast & Low Cost**: Currently deployed on **Sepolia Testnet** for instant, low-gas demonstrations.
- **Order Tracking**: Links blockchain transactions to Joomla Order IDs automatically.

---

## 🛠 Project Structure

```bash
MNEE_hack/
├── JommlaMNEEGateway.sol    # Solidity Smart Contract for Payment Gateway
├── MyMNEE.sol               # Solidity Smart Contract for Mock MNEE Token
├── mod_mnee_pay/            # Joomla Module Directory
│   ├── mod_mnee_pay.php     # Main Module Entry Point
│   ├── mod_mnee_pay.xml     # Module Manifest File
│   ├── verify.php           # Backend Verification Script
│   └── tmpl/
│       └── default.php      # Frontend Layout & Web3 Logic (Ethers.js)
└── README.md                # Project Documentation
```

## Technical Stack
- **Frontend**: JavaScript, Ethers.js (v5.7)
- **Backend**: PHP (Joomla Module Architecture)
- **Blockchain**: Solidity (ERC-20 Standard & Custom Payment Gateway)
- **Network**: Sepolia Testnet (Ethereum)

---

## Contract Addresses (Sepolia Demo)
For the hackathon demonstration, we deployed a **Mock MNEE Token** to simulate the mainnet token behavior.

| Contract Name | Address | Description |
| :--- | :--- | :--- |
| **Mock MNEE Token** | `0x1c24eC77Bb12ffed0Fca6FCE15219E4a66304E74` | Simulates the official MNEE ERC-20 token |
| **Payment Gateway** | `0xa8DDF2d31186632613b622d34B0eB094850f85d3` | Handles `transferFrom` and emits payment events |

> **Note:** The official MNEE Mainnet contract is `0x8ccedbAe4916b79da7F3F612EfB2EB93A2bFD6cF`. The plugin code is designed to be easily switched to this address for production.

---

## How to Install & Test

### Prerequisites
- A Joomla 4.x or 5.x website.
- **MetaMask** installed in your browser.
- Some **Sepolia ETH** (for gas) and **Mock MNEE tokens**.

### Installation Steps
1. **Prepare the Module**:
   - Zip the contents of the `mod_mnee_pay` folder into `mod_mnee_pay.zip`.
2. **Install in Joomla**:
   - Log in to your Joomla Admin Panel (`/administrator`).
   - Navigate to **System** -> **Install** -> **Extensions**.
   - Upload the `mod_mnee_pay.zip` file.
3. **Configure**:
   - Go to **Content** -> **Site Modules**.
   - Find "MNEE Crypto Payment" and open it.
   - Set **Position** to a visible slot (e.g., `sidebar-right`, `content-top`, or `debug`).
   - Set **Status** to **Published**.
   - Save & Close.

### Usage (Demo Flow)
1. Go to your Joomla website page where the module is published.
2. The module will display a payment card with a random **Order ID** (e.g., `ORD-12345`).
3. **Step 1**: Click **"Approve MNEE"** to allow the gateway to spend your MNEE tokens. Confirm the transaction in MetaMask.
4. **Step 2**: Once approved, the button changes. Click **"Pay Now"** to execute the payment.
5. **Success**: Wait for the transaction to be mined. The module will verify the transaction with the backend and display a success receipt with the Transaction Hash.

---

## Future Improvements
- **Admin Configuration**: Add comprehensive admin panel to input Merchant Wallet Address securely.
- **Order Integration**: Automate "Order Status Update" in Joomla database (VirtueMart/WooCommerce integration) upon payment event.
- **Mainnet Launch**: Deploy contracts to Ethereum Mainnet for real-world usage.

---

## License
This project is licensed under the MIT License.
