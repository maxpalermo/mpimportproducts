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

class MpUtils {
    /**
     * Check if a string is contained in another string
     * @param string $needle String to look for
     * @param type $haystack Container string
     * @return boolean Returns true if string was found, false otherwise
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function contains($needle, $haystack)
    {
        if(empty($needle) || empty($haystack)) {
            MpOutput::getInstance()->add(__FUNCTION__, array(
                'params' => 
                    array('needle' => $needle, 'haystack' => $haystack)));
            return false;
        }
        $pos = Tools::strpos($haystack, $needle);
        if($pos!==false) {
            MpOutput::getInstance()->add(__FUNCTION__, array(
                'search' => 
                    array('needle' => $needle,
                        'haystack' => $haystack,
                        'found' => "<strong style='color: green;'>true</strong>")));
            return true;
        } else {
            MpOutput::getInstance()->add(__FUNCTION__, array(
                'search' => 
                    array('needle' => $needle,
                        'haystack' => $haystack,
                        'found' => "<span style='color: red;'>false</span>")));
            return false;
        }
    }
    
    /**
     * Remove prefix from a string
     * @param string $prefix Prefix to remove
     * @param string $string String container
     * @return string Returns String without prefix
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function removePrefix($prefix, $string)
    {
        $output = Tools::substr($string, Tools::strlen($prefix));
        MpOutput::getInstance()->add(__FUNCTION__, array('prefix' => $prefix, 'return' => $output));
        return $output;
    }
    
    /**
     * Check if a string ends with another string
     * @param string $string String to look in
     * @param string $chunk String to check
     * @return boolean Returns true if ends with given string, false otherwise
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function endsWith($string, $chunk)
    {
        return preg_match('#' . $chunk . '$#i', $string) === 1;
    }
    
    /**
     * Check if a string starts with another string
     * @param string $string String to look in
     * @param string $chunk String to check
     * @return boolean Returns true if starts with given string, false otherwise
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function startsWith($string, $chunk)
    {
        return preg_match('#^' . $chunk . '#i', $string) === 1;
    }
    
    /**
     * Remove first char in a given string
     * @param string $string String to process
     * @return string String without first char
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function removeFirstChar($string)
    {
        return substr($string, 1);
    }
    
    /**
     * Remove last char in a given string
     * @param string $string String to process
     * @return string String without first char
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function removeLastChar($string)
    {
        return substr($string, 0, -1);
    }
    
    /**
     * 
     * @param string $tablename tablename to get data
     * @param string $id field id
     * @param string $value field description
     * @param string $orderBy field order
     * @return array option list for select item
     */
    public static function createOptionListFromTable($tablename,$id,$value,$orderBy)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $options = [];
        
        $sql->select($id)
                ->select($value)
                ->from($tablename)
                ->orderBy($orderBy);
        $result = $db->executeS($sql);
        $options[] = [
            'id' => 0,
            'value' => $this->l('Please select')
        ];
        foreach ($result as $row)
        {
            $options[] = [
                'id' => $row[$id],
                'value' => $row[$value]
            ];
        }
        
        return $options;
    }
    
    /**
     * Create a formatted array for option list
     * @param array $array array to read from
     * @param string $id key for id
     * @param string $value key for value
     * @return array option list array
     */
    public static function createOptionListFromAssociatedArray($array,$id,$value)
    {
        $options = [];
        $options[] = [
            'id' => 0,
            'value' => $this->l('Please select')
        ];
        foreach ($array as $row)
        {
            $options[] = [
                'id' => $row[$id],
                'value' => $row[$value]
            ];
        }
        
        return $options;
    }
    
    /**
     * Create a formatted array for option list
     * @param array $array array to read from
     * @return array option list array
     */
    public static function createOptionListFromArray($array, $firstRow = true, $capitalize = false)
    {
        $options = [];
        
        if ($firstRow) {
            $options[] = [
                'id' => 0,
                'value' => $this->l('Please select')
            ];
        }
        
        foreach ($array as $row)
        {
            if ($capitalize) {
                $row = Tools::strtoupper($row);
            }
            $option = ['id' => $row, 'value' => $row];
            $options[] = $option; 
        }

        return $options;
    }
    
    /**
     * Get product by reference attribute, returns Product object if found, null otherwise
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     * @param string $reference Product reference
     * @return \ProductCore Product object
     */
    public static function getProductByReference($reference)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('id_product')
                ->from('product')
                ->where("reference='" . pSQL($reference) . "'" );
        $value = $db->getValue($sql);
        if ($value===false) {
            return null;
        } else {
            $product = new ProductCore($value);
            return $product;
        }
    }
    
    public static function getAttributeGroups()
    {  
        $attrGroups = AttributeGroupCore::getAttributesGroups($this->_lang);
        return self::createOptionListFromAssociatedArray($attrGroups, 'id_attribute_group', 'name');
        
    }
    
    public static function getFeatures()
    {
        $features = FeatureCore::getFeatures(Context::getContext()->language->id);
        return self::createOptionListFromAssociatedArray($features, 'id_feature', 'name');
    }
    
    /**
     * Check if a product exists in archive by reference
     * @param string $reference product reference to check
     * @return boolean true if exists, false otherwise
     * @author Massimiliano Palermo <maxx.palermo@gmail.com>
     */
    public static function productExistsByReference($reference)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql    ->select("count(*)")
                ->from("product")
                ->where("reference = '" . pSQL($reference) . "'");
        return (bool)$db->getValue($sql);
    }
    
    /**
     * Get hashtag from a string
     * @param string $string
     * @return mixed Returns associated array[string: string without hashtag, hashtag: hashtag found] or same string if
     * hashtag was not found.
     */
    public static function getHashTag($string)
    {
        $output = array();
        $pos = Tools::strpos($string, "#");
        if ($pos!==false) {
            $output['string'] = Tools::toCamelCase(trim(Tools::substr($string, 0, $pos-1)), true);
            $output['hashtag'] = Tools::toCamelCase(trim(Tools::substr($string, $pos+1)), true);
        }
        else {
            $output = $string;
        }
        MpOutput::getInstance()->add(__FUNCTION__, $output);
        return $output;
    }
}
