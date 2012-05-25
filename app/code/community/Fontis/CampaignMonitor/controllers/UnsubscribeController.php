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

class Fontis_CampaignMonitor_UnsubscribeController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        // don't do anything if we didn't get the email parameter
        if(isset($_GET['email']))
        {
            $email = $_GET['email'];
            $apiKey = trim(Mage::getStoreConfig('newsletter/campaignmonitor/api_key'));
            $listID = trim(Mage::getStoreConfig('newsletter/campaignmonitor/list_id'));
            
            // Check that the email address actually is unsubscribed in
            // Campaign Monitor.
            if($apiKey && $listID)
            {
                try {
                    $client = new SoapClient("http://api.createsend.com/api/api.asmx?wsdl");
                    $result = (array)$client->GetSingleSubscriber(array(
                            "ApiKey" => $apiKey,
                            "ListID" => $listID,
                            "EmailAddress" => $email));
                } catch(Exception $e) {
                    Mage::log("Fontis_CampaignMonitor: Error in SOAP call: ".$e->getMessage());
                    $session->addException($e, $this->__('There was a problem with the unsubscription'));
                    $this->_redirectReferer();
                }

                $state = "";
                try
                {
                    $state = (String)$result['Subscribers.GetSingleSubscriberResult']->enc_value->State;
                }
                catch(Exception $e)
                {}
                
                // If we are unsubscribed in Campaign Monitor, mark us as
                // unsubscribed in Magento.
                if($state == "Unsubscribed")
                {
                    try
                    {
                        Mage::log("Fontis_CampaignMonitor: Unsubscribing $email");
                        $collection = Mage::getModel('newsletter/subscriber')
                                ->loadByEmail($email)
                                ->unsubscribe();

                        Mage::getSingleton('customer/session')->addSuccess($this->__('You were successfully unsubscribed'));
                    }
                    catch (Exception $e)
                    {
                        Mage::log("Fontis_CampaignMonitor: ".$e->getMessage());
                        Mage::getSingleton('customer/session')->addError($this->__('There was an error while saving your subscription details'));
                    }
                }
                else
                {
                    Mage::log("Fontis_CampaignMonitor: Not unsubscribing $email, not unsubscribed in Campaign Monitor");
                }
            }
        }
        
        $this->_redirect('customer/account/');
    }
}
?>
