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

include "Mage/Adminhtml/controllers/Newsletter/SubscriberController.php";

// Class that 'hooks' newsletter unsubscriptions from the admin
// Newsletter->Newsletter Subscribers page. This is necessary because the
// Mage_Newsletter_Model_Subscriber class doesn't extend
// Mage_Core_Model_Abstract and so can't be observed directly. Instead we
// redirect all requests for admin_newsletter_subscriber to this controller,
// which extends Mage_Adminhtml_Newsletter_SubscriberController and overrides
// the unsubscribeAction method.
class Fontis_CampaignMonitor_AdminhookController extends Mage_Adminhtml_Newsletter_SubscriberController {

    public function massUnsubscribeAction() {
        Mage::log("Fontis_CampaignMonitor: massUnsubscribeAction");

        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newsletter')->__('Please select subscriber(s)'));
             $this->_redirect('*/*/index');
        }
        else {
            try {
                $apiKey = trim(Mage::getStoreConfig('newsletter/campaignmonitor/api_key'));
                $listID = trim(Mage::getStoreConfig('newsletter/campaignmonitor/list_id'));
        
                try {
                    $client = new SoapClient("http://api.createsend.com/api/api.asmx?wsdl", array("trace" => true));
                } catch(Exception $e) {
                    Mage::log("Fontis_CampaignMonitor: Error connecting to CampaignMonitor server: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the subscription'));
                    $this->_redirectReferer();
                }

                foreach ($subscribersIds as $subscriberId) {
                    $subscriber = Mage::getModel('newsletter/subscriber')->load($subscriberId);
                    $email = $subscriber->getEmail();
                    Mage::log("Fontis_CampaignMonitor: Unsubscribing: $email");
                    try {
                        $result = $client->Unsubscribe(array(
                                "ApiKey" => $apiKey,
                                "ListID" => $listID,
                                "Email" => $email));
                    } catch (Exception $e) {
                        Mage::log("Fontis_CampaignMonitor: Error in CampaignMonitor SOAP call: ".$e->getMessage());
                    }
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        parent::massUnsubscribeAction();
    }
}

?>
