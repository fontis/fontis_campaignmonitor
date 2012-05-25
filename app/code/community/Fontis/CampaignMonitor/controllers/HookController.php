<?php
/**
 * Fontis Campaign Monitor Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   Fontis
 * @package    Fontis_CampaignMonitor
 * @author     Peter Spiller
 * @author     Chris Norton
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include "Mage/Newsletter/controllers/SubscriberController.php";

// Class that 'hooks' newsletter subscriptions from the frontend sign-up box.
// This is necessary because the Mage_Newsletter_Model_Subscriber class
// doesn't extend Mage_Core_Model_Abstract and so can't be observed directly.
// Instead we redirect all requests for newsletter/subscriber to this
// controller, which extends Mage_Newsletter_SubscriberController and
// overrides the newAction method.
class Fontis_CampaignMonitor_HookController extends Mage_Newsletter_SubscriberController {

    // Add the subscription to Campaign Monitor before calling the parent
    // method to handle adding the subscription to Magento.
    public function newAction() {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
            $session   = Mage::getSingleton('core/session');
            $email     = (string)$this->getRequest()->getPost('email');
            
            Mage::log("Fontis_CampaignMonitor: Adding newsletter subscription via frontend 'Sign up' block for $email");

            $apiKey = trim(Mage::getStoreConfig('newsletter/campaignmonitor/api_key'));
            $listID = trim(Mage::getStoreConfig('newsletter/campaignmonitor/list_id'));
        
            if($apiKey && $listID) {
                try {
                    $client = new SoapClient("http://api.createsend.com/api/api.asmx?wsdl", array("trace" => true));
                } catch(Exception $e) {
                    Mage::log("Fontis_CampaignMonitor: Error connecting to CampaignMonitor server: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the subscription'));
                    $this->_redirectReferer();
                }

                // if a user is logged in, fill in the Campaign Monitor custom
                // attributes with the data for the logged-in user
                $customerHelper = Mage::helper('customer');
                if($customerHelper->isLoggedIn()) {
                    $customer = $customerHelper->getCustomer();
                    $name = $customer->getFirstname() . " " . $customer->getLastname();
                    $customFields = Fontis_CampaignMonitor_Model_Customer_Observer::generateCustomFields($customer);
                    try {    
                        $result = $client->AddAndResubscribeWithCustomFields(array(
                                "ApiKey" => $apiKey,
                                "ListID" => $listID,
                                "Email" => $email,
                                "Name" => $name,
                                "CustomFields" => $customFields));
                    } catch(Exception $e) {
                        Mage::log("Fontis_CampaignMonitor: Error in CampaignMonitor SOAP call: ".$e->getMessage());
                        $session->addException($e, $this->__('There was a problem with the subscription'));
                        $this->_redirectReferer();
                    }
                } else {
                    // otherwise if nobody's logged in, ignore the custom
                    // attributes and just set the name to '(Guest)'
                    try {
                        $result = $client->AddAndResubscribe(array(
                                "ApiKey" => $apiKey,
                                "ListID" => $listID,
                                "Email" => $email,
                                "Name" => "(Guest)"));
                    } catch (Exception $e) {
                        Mage::log("Fontis_CampaignMonitor: Error in CampaignMonitor SOAP call: ".$e->getMessage());
                        $session->addException($e, $this->__('There was a problem with the subscription'));
                        $this->_redirectReferer();
                    }
                }
            } else {
                Mage::log("Fontis_CampaignMonitor: Error: Campaign Monitor API key and/or list ID not set in Magento Newsletter options.");
            }
        }

        parent::newAction();
    }
}

?>
