<?php

/**
 * 2017 mpSOFT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    mpSOFT <info@mpsoft.it>
 *  @copyright 2017 mpSOFT Massimiliano Palermo
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */

require_once 'MpOutput.php';
require_once 'MpUtils.php';

class MpImportAttributes{
    private $id_product;
    private $row;
    private $lang;
    
    public function __construct($id_product, $row)
    {
        $this->id_product = $id_product;
        $this->row = $row;
        $this->lang = Context::getContext()->language->id;
        
        MpOutput::getInstance()->add(__FUNCTION__, array('id_product' => $id_product));
        MpOutput::getInstance()->add(__FUNCTION__, array('row' => $row));
    }
    
    public function process()
    {
        return $this->splitAttributesFromRow();
    }
    
    private function splitAttributesFromRow()
    {
        $db = Db::getInstance();
        
        foreach($this->row as $key=>$value)
        {
            if(MpUtils::contains('attr ', $key)) {
                $attribute = MpUtils::removePrefix('attr ', $key); //Remove hastag to get attribute group name
                $attribute_group_id = $this->getAttributeGroup($attribute); //get attribute group id
                $attribute_ids = $this->getAttribute($value); //Get attribute ids
                
                MpOutput::getInstance()->add(__FUNCTION__, 
                        array(
                            'attribute group id' => $attribute . " id: " . (int)$attribute_group_id,
                            'attribute id' => print_r(explode(";", $value), 1) . PHP_EOL .  print_r($attribute_ids,1)
                        ));
                
                $product = new ProductCore($this->id_product); //Get product object
                
                //Get default_on for this reference
                $sql_default_on = new DbQueryCore();
                $sql_default_on->select('count(*)')
                        ->from('product_attribute')
                        ->where("reference = '" . pSQL($product->reference) . "'")
                        ->where('default_on = 1');
                $result = $db->getValue($sql_default_on);
                
                if($result>0) {
                    $default_on = 'NULL';
                } else {
                    $default_on = '1';
                }
                
                try {
                    //Insert attribute product
                    $db->insert(
                            'product_attribute',
                            array(
                                'id_product' => $product->id,
                                'reference' => $product->reference,
                                'supplier_reference' => $product->supplier_reference,
                                'default_on' => $default_on,
                            )
                    );
                    $id_product_attribute = $db->Insert_ID();
                    MpOutput::getInstance()->add(__FUNCTION__, 
                            array('product_attribute' => $product->reference . " = " . $id_product_attribute));
                } catch (Exception $exc) {
                    MpOutput::getInstance()->add(__FUNCTION__, array('error' => $exc->getMessage()));
                    return false;
                }
                
                //Add attributes list
                foreach($attribute_ids as $attribute_id) {
                   if($attribute_group_id===false || $attribute_id===false) {
                       MpOutput::getInstance()->add(__FUNCTION__, array(
                           'error' => array(
                               'message' => 'Unable to insert attributes.',
                               'attribute group' => $attribute . ":" . (int)$attribute_group_id,
                               'attribute value' => $value . ": " . (int)$attribute_id)));
                    } else {
                        try {
                            $db->insert(
                                    'product_attribute_combination',
                                    array(
                                        'id_attribute' => $attribute_id,
                                        'id_product_attribute' => $id_product_attribute,
                                    )
                            );
                            
                            MpOutput::getInstance()->add(__FUNCTION__, array(
                                'add product combination' => array(
                                    'id_attribute' => $attribute_id,
                                    'id_product_attribute' => $id_product_attribute)));
                            
                            $db->insert(
                                    'product_attribute_shop',
                                    array(
                                        'id_product_attribute' => $id_product_attribute,
                                        'id_shop' => Context::getContext()->shop->id,
                                        'default_on' => $default_on,
                                        'id_product' => $product->id,
                                    )
                            );
                            
                            MpOutput::getInstance()->add(__FUNCTION__, array(
                                'product attribute shop' => 'OK'));
                            
                            return true;

                        } catch (Exception $exc) {
                            MpOutput::getInstance()->add(__FUNCTION__, array(
                                'error' => $exc->getMessage()));
                            return false;
                        }
                    }  
                } 
            }
        }
    }
    
    /**
     * Get attribute group id from attribute name, or create a new attribute group if not exists
     * @param string $attribute Attribute name with optional hastag type
     * @return mixed Id attribute or false if there is some error
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    private function getAttributeGroup($attribute)
    {
        //Get type of attribute
        $type = '';
        $hashtag = MpUtils::getHashTag($attribute);
        if (is_array($hashtag)) {
           $attribute = $hashtag['string'];
           $type = $hashtag['hashtag'];
        }
        
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('id_attribute_group')
                ->from('attribute_group_lang')
                ->where('id_lang = ' . $this->lang)
                ->where("name like '" . pSQL($attribute) . "'");
        $value = $db->getValue($sql);
        MpOutput::getInstance()->add(__FUNCTION__, array('GroupID' => $value));
        
        if(empty($value)) {
            //Create new attribute group
            $objAttributeGroup = new AttributeGroupCore();
            $objAttributeGroup->name[$this->lang] = $attribute;
            $objAttributeGroup->group_type = $type;
            $objAttributeGroup->id_shop_list = array(1);
            $objAttributeGroup->position = 1;
            $objAttributeGroup->public_name[$this->lang] = $attribute;
            try {
                $objAttributeGroup->save();
                MpOutput::getInstance()->add(__FUNCTION__, array('New Attribute group id' => $objAttributeGroup->id));
                return $objAttributeGroup->id;
            } catch (Exception $exc) {
                MpOutput::getInstance()->add(__FUNCTION__, array('error' => 
                    array(
                        'message' => $exc->getMessage())));
                return false;
            }
        } else {
            return (int)$value;
        }      
    }
    
    /**
     * Get attribute id from attribute name, or create a new attribute if not exists
     * Attribute name may be comma separated for multiple values
     * @param string $attribute Attribute name with optional hastag type
     * @return mixed Id attribute or false if there is some error
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    private function getAttribute($attribute)
    {
        $db = Db::getInstance();
        $values = explode(";", $attribute);
        $output = array();

        foreach ($values as $value) {
            //Get color code
            $color = '';
            $hashtag = MpUtils::getHashTag($value);
            if (is_array($hashtag)) {
               $value = $hashtag['string'];
               $color = $hashtag['hashtag'];
            }
            
            $sql = new DbQueryCore();
            $sql->select('id_attribute')
                    ->from('attribute_lang')
                    ->where('id_lang = ' . $this->lang)
                    ->where("name like '" . pSQL($value) . "'");
            $dbvalue = $db->getValue($sql);
            if(empty($dbvalue)) {
                //Create new attribute
                $objAttribute = new AttributeCore();
                $objAttribute->name[$this->lang] = $value;
                $objAttribute->position = 1;
                $objAttribute->default = true;
                $objAttribute->color = $color;
                try {
                    $objAttribute->save();
                    $output[] = $objAttribute->id;
                    
                    MpOutput::getInstance()->add(__FUNCTION__, array('New Attribute id' => $objAttribute->id));
                } catch (Exception $exc) {
                    MpOutput::getInstance()->add(__FUNCTION__, array('error' => 
                    array(
                        'message' => $exc->getMessage())));
                    $output[] = false;
                }
            }   else {
                $output[] =  (int)$dbvalue;
            }   
        }
        return $output;
    }
    
}
