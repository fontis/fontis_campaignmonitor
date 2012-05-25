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
class Fontis_CampaignMonitor_Block_Linkedattributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $magentoOptions;

    public function __construct()
    {
        $this->addColumn('magento', array(
            'label' => Mage::helper('adminhtml')->__('Magento customer attribute'),
            'size'  => 28,
        ));
        $this->addColumn('campaignmonitor', array(
            'label' => Mage::helper('adminhtml')->__('Campaign Monitor custom field personalization tag'),
            'size'  => 28
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add linked attribute');
        
        parent::__construct();
        $this->setTemplate('fontis/campaignmonitor/system/config/form/field/array_dropdown.phtml');
        
        // customer options
        $magentoAttributes = Mage::getModel('customer/customer')->getAttributes();
        $this->magentoOptions = array();
        foreach(array_keys($magentoAttributes) as $att)
        {
            if($att != 'entity_type_id'
                    and $att != 'entity_id'
                    and $att != 'attribute_set_id'
                    and $att != 'password_hash'
                    and $att != 'increment_id'
                    and $att != 'updated_at'
                    and $att != 'created_at'
                    and $att != 'email'
                    and $att != 'default_billing'
                    and $att != 'default_shipping')
            {
                // give nicer names to the attributes we're translating
                // from IDs to values
                if($att == 'store_id')
                    $name = 'Store';
                else if($att == 'group_id')
                    $name = 'Customer Group';
                else if($att == 'website_id')
                    $name = 'Website';
                else $name = $att;
                
                $this->magentoOptions[$att] = $name;
            }
        }
        asort($this->magentoOptions);
        // address options
        $this->magentoOptions['FONTIS-billing-firstname'] = 'Billing Address: First name';
        $this->magentoOptions['FONTIS-billing-lastname'] = 'Billing Address: Last name';
        $this->magentoOptions['FONTIS-billing-company'] = 'Billing Address: Company';
        $this->magentoOptions['FONTIS-billing-telephone'] = 'Billing Address: Phone';
        $this->magentoOptions['FONTIS-billing-fax'] = 'Billing Address: Fax';
        $this->magentoOptions['FONTIS-billing-street'] = 'Billing Address: Street';
        $this->magentoOptions['FONTIS-billing-city'] = 'Billing Address: City';
        $this->magentoOptions['FONTIS-billing-region'] = 'Billing Address: State/Province';
        $this->magentoOptions['FONTIS-billing-postcode'] = 'Billing Address: Zip/Postal Code';
        $this->magentoOptions['FONTIS-billing-country_id'] = 'Billing Address: Country';
        
        $this->magentoOptions['FONTIS-shipping-firstname'] = 'Shipping Address: First name';
        $this->magentoOptions['FONTIS-shipping-lastname'] = 'Shipping Address: Last name';
        $this->magentoOptions['FONTIS-shipping-company'] = 'Shipping Address: Company';
        $this->magentoOptions['FONTIS-shipping-telephone'] = 'Shipping Address: Phone';
        $this->magentoOptions['FONTIS-shipping-fax'] = 'Shipping Address: Fax';
        $this->magentoOptions['FONTIS-shipping-street'] = 'Shipping Address: Street';
        $this->magentoOptions['FONTIS-shipping-city'] = 'Shipping Address: City';
        $this->magentoOptions['FONTIS-shipping-region'] = 'Shipping Address: State/Province';
        $this->magentoOptions['FONTIS-shipping-postcode'] = 'Shipping Address: Zip/Postal Code';
        $this->magentoOptions['FONTIS-shipping-country_id'] = 'Shipping Address: Country';
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if($columnName == 'magento')
        {
            $rendered = '<select name="'.$inputName.'">';
            foreach($this->magentoOptions as $att => $name)
            {
                $rendered .= '<option value="'.$att.'">'.$name.'</option>';
            }
            $rendered .= '</select>';
        }
        else
        {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }
        
        return $rendered;
    }
}
