// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/**
 * @title Joomla MNEE Payment Gateway
 * @dev Joomla Plug-in design for cashier contracts
 */

// 1. Define a simple ERC20 interface that we will use to interact with the MNEE token
interface IERC20 {
    function transferFrom(address sender, address recipient, uint256 amount) external returns (bool);
    function transfer(address recipient, uint256 amount) external returns (bool);
    function balanceOf(address account) external view returns (uint256);
}

contract JoomlaMneeGateway {
    
    address public owner;           // Address
    IERC20 public mneeToken;        // MNEE address

    // successfully makes a payment, this line of log will be permanently recorded on the blockchain
    // The Joomla plugin will listen for this event in the future to enable "automatic delivery"
    event PaymentReceived(string orderId, address indexed payer, uint256 amount, uint256 timestamp);

    // Constructor: Executed once when the contract is deployed.
    constructor(address _mneeTokenAddress) {
        owner = msg.sender; 
        mneeToken = IERC20(_mneeTokenAddress);
    }

    // User payment function
    //The front-end webpage will call this function instead of directly transferring money.
    function payOrder(string memory _orderId, uint256 _amount) external {
        // 1. Transfer MNEE from the user's wallet to this contract
        // Users must first "Approve" (authorize) the process on the front end before they can call this step.
        bool success = mneeToken.transferFrom(msg.sender, address(this), _amount);
        require(success, "Payment failed! Please check allowance and balance.");

        // 2. notify the Joomla backend
        emit PaymentReceived(_orderId, msg.sender, _amount, block.timestamp);
    }

    // Owner withdrawal function
    // Withdraw the MNEE earned from the contract to the shop owner's wallet.
    function withdraw() external {
        require(msg.sender == owner, "Only owner can withdraw");
        
        uint256 balance = mneeToken.balanceOf(address(this));
        require(balance > 0, "No funds to withdraw");

        mneeToken.transfer(owner, balance);
    }

    // Update the MNEE token address (if the contract needs to be changed).
    function setTokenAddress(address _newToken) external {
        require(msg.sender == owner, "Only owner can set token");
        mneeToken = IERC20(_newToken);
    }
}