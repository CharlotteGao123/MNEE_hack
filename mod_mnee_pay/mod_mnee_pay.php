<?php
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

$contractAddress = $params->get('contract_address', '0x80a1c2f43161020A2D431FaeEBA0f642e6D9B2ff'); 

require ModuleHelper::getLayoutPath('mod_mnee_pay', 'default');
?>