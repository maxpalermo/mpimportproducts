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

ini_set('max_execution_time', 300); //300 seconds = 5 minutes

require_once _PS_MODULE_DIR_ . 'mpimportproducts/classes/PHPExcel.php';
require_once _PS_MODULE_DIR_ . 'mpimportproducts/classes/MpUtils.php';
require_once _PS_MODULE_DIR_ . 'mpimportproducts/classes/MpOutput.php';
require_once _PS_MODULE_DIR_ . 'mpimportproducts/classes/MpImportAttributes.php';

class AdminMpImportProductsController extends ModuleAdminController
{
    public $lang;
    private $products;
    private $display_products;
    private $file;
    private $categories;
    private $titles;
    private $currentPage;
    private $currentPagination;
    private $opt_pages;
    private $opt_paginations;
    private $input_hidden_pagination;
    private $output;
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        
        parent::__construct();
        
        $this->products = array();
        $this->display_products = array();
        $this->file = array();
        $this->categories = array();
        $this->titles = array();
        $this->currentPage = 1;
        $this->currentPagination = 50;
        $this->lang = Context::getContext()->language->id;
        $this->output = array();
        $this->opt_pages = array();
        $this->opt_paginations = array();
        $this->input_hidden_pagination = 50;
    }
    
    public function initToolbar()
    {
        parent::initToolbar();
    }

    public function initContent()
    {
        parent::initContent();
        $this->postProcess();
    }
    
    public function postProcess($params = array())
    {
        $this->getCategories();
        
        if(Tools::isSubmit('submit_pagination')) {
            $this->pagination();
        } else {
                if(!empty($_FILES['input_file_upload'])) {
                $this->file = $_FILES['input_file_upload'];
                $this->readFile($this->file);
                $this->pagination();
                $this->importFile();
            } else {
                $this->file = array();
            }
        }
        
        $this->displayPage();
    }
    
    private function pagination()
    {
        //Change page
        $this->currentPage = Tools::getValue('input_select_current_page', '1');
        $this->currentPagination = Tools::getValue('input_select_current_pagination', '50');
        $current_pagination = Tools::getValue('input_hidden_current_pagination', '50');
        //Start session
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        //Get products from session
        $this->products = $_SESSION['rowData'];
        //Set start pagination
        if ($this->currentPagination!=$current_pagination) {
            $start = 1;
        } else {
            $start = (($this->currentPage-1)*$this->currentPagination)+1;
        }
        //Get pagination from whole array
        $this->display_products = array_slice($this->products, $start-1, $this->currentPagination);
        
        //Calculate pagination
        $rows = count($this->products);
        $pages = ceil($rows/ (int)$this->currentPagination);
        $opt_pages = array();
        $opt_paginations = array();
        
        //Option Pagination
        $step = 25;
        for($i=0; $i<10; $i++)
        {
            $start = ($i+1) * $step;
            if($start == $this->currentPagination) {
                $selected = " selected='selected' ";
            } else {
                $selected = "";
            }
            $opt_paginations[] = "<option value='" . $start . "'" . $selected . ">" . $start . "</option>";
        }
        
        //Option Pages
        if($this->currentPagination!=$current_pagination) {
            $current_pagination = $this->currentPagination;
            $this->currentPage=1;
        }
        
        for($i=0; $i<$pages; $i++)
        {
            if((int)($i+1) == (int)$this->currentPage) {
                $selected = " selected='selected' ";
            } else {
                $selected = "";
            }
            
            $opt_pages[] = "<option value='" . ($i+1) . "'" . $selected . ">" . ($i+1) . "</option>";
        }
        
        $this->opt_pages = $opt_pages;
        $this->opt_paginations = $opt_paginations;
        $this->input_hidden_pagination = $current_pagination;
    }
    
    private function displayPage()
    {
        $this->context->smarty->assign('status', MpOutput::getInstance()->add());//$this->output);
        $this->context->smarty->assign('opt_pages', implode(PHP_EOL, $this->opt_pages));
        $this->context->smarty->assign('opt_paginations', implode(PHP_EOL, $this->opt_paginations));
        $this->context->smarty->assign('titles', $this->titles);
        $this->context->smarty->assign('rows', $this->display_products);
        $this->context->smarty->assign('file', $this->file);
        $this->context->smarty->assign('currentPage', $this->currentPage);
        $this->context->smarty->assign('currentPagination', $this->currentPagination);
        $this->context->smarty->assign('hidden_curr_pagination', $this->input_hidden_pagination);
        $navigator = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/navigator.tpl');
        $pagination = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/pagination.tpl');
        $this->context->smarty->assign("navigator", $navigator);
        $this->context->smarty->assign("pagination", $pagination);
        $content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/adminPage.tpl');
        $this->context->smarty->assign(array('content' => $this->content . $content));
    }
    
    private function readFile($file)
    {
        if (Tools::isSubmit('submit_file_upload')) {
            if ($file) {
                $this->readUploadedFile($file);
            } 
        }
    }
    
    private function importFile()
    {
        if (Tools::isSubmit('submit_file_import'))
        {
            $this->importUploadedFile();
        }
    }
    
    private function importUploadedFile()
    {
        $db = Db::getInstance();
        $this->output = array();
        $this->output[__FUNCTION__] = array();
        
        $checks = Tools::getValue('checkRow', array());
        foreach ($checks as $key=>$value) {
            $product_row = $this->display_products[(int)$key-1];
            /**
             * @var ProductCore $product
             */
            $product = $this->getProductByReference($product_row['reference']);
            $reference = Tools::strtoupper(trim($product_row['reference']));
            
            if(!empty($reference)) {
                if(!empty($product)) {
                    $product->reference = $reference;
                    $product->name[$this->lang] = $product_row['name'];
                    $product->description[$this->lang] = $product_row['description long'];
                    $product->description_short[$this->lang] = Tools::substr($product_row['description'],0,599);
                    $product->id_category_default = $product_row['category default'];
                    $save = $product->save();

                    $this->importImage($product_row['image'], $product->id, $product->name);

                    //Delete old features reference
                    $db->delete('feature_product','id_product = ' . $product->id);
                    //Add features
                    foreach ($product_row as $key=>$value)
                    {
                        if(trim($value)) {
                            if(Tools::strpos(Tools::strtolower($key), 'feat ')!==false) {
                                $featureName = Tools::substr($key, Tools::strlen('feat '));
                                $status = $this->addFeatureProduct($product->id, $featureName, $value, $reference);
                                $this->output[__FUNCTION__][$reference]['addFeatureProduct'][] = array($status);
                            }
                        }
                    }
                    
                    //Delete old categories reference
                    $deleteCategories = $this->deleteCategories($product->id);
                    //Add new categories
                    $categoryDefault = $product_row['category default'];
                    $categoryOther = $product_row['category other'];
                    $addDefaultCategory = $this->addCategories($product, $categoryDefault);
                    $addOtherCategories = $this->addCategories($product, $categoryOther);

                    //Delete old attributes reference
                    $deleteAttributes = $this->deleteAttributes($product->id);
                    //Add new attributes
                    $importAttribute = new MpImportAttributes($product->id, $product_row);
                    $process = (int)$importAttribute->process();
                    
                    MpOutput::getInstance()->add(__FUNCTION__, array(
                        'product' => array(
                            'reference' => $product->reference,
                            'del categories' => $deleteCategories,
                            'del attributes' => print_r($deleteAttributes, 1),
                            'import Attributes' => $process,
                            'add default category' => $categoryDefault . ": " . $addDefaultCategory,
                            'add other categories' => $categoryOther . ": " . $addOtherCategories,
                            'record saved' => $save)));
                    
                } else {
                    MpOutput::getInstance()->add(__FUNCTION__, array(
                        'product' => array(
                            'reference' => $reference,
                            'error' => 'NOT FOUND')));
                }
            }   
        }
        return true;
    }
    
    private function addFeatureProduct($id_product, $featureName, $featureValue, $reference)
    {   
        $db = Db::getInstance();
        
        //Get Feature id or create a new feature
        $sqlName = new DbQueryCore();
        $sqlName->select('id_feature')
                ->from('feature_lang')
                ->where('id_lang = ' . $this->lang)
                ->where("name like '%" . pSQL($featureName) . "%'");
        
        $id_feature_name = (int)$db->getValue($sqlName);
        if ($id_feature_name==0) {
            $id_feature_name = (int)$this->addFeature($featureName);
        }
        
        $featureDbName = new FeatureCore($id_feature_name);
        
        $this->output[__FUNCTION__][$reference]['features']['addFeature'][] = "<ul>" 
                . "<li>id_product : <strong>" . $id_product . "</strong></li>"
                . "<li>feature_name : <strong>" . $featureName . "</strong></li>"
                . "<li>feature_value: <strong>" . $featureValue . "</strong></li>"
                . "<li>sql: " . $sqlName->__toString() . "</li>"
                . "<li>id feature: $featureName = " . $id_feature_name . "</li>"
                . "<li>db feature: ($id_feature_name) " . $featureDbName->name[$this->lang] . "</li></ul>";
        
        //Get feature value array
        $results = array();
        $arrValues = explode(",",$featureValue);
        foreach($arrValues as $value)
        {
            $sqlValue = new DbQueryCore();
            $sqlValue->select('id_feature_value')
                    ->from('feature_value_lang')
                    ->where('id_lang = ' . $this->lang)
                    ->where("value like '%" . pSQL($value) . "%'");


            $id_feature_value = (int)$db->getValue($sqlValue);

            if ($id_feature_value==0) {
               $id_feature_value =  (int)$this->addFeatureValue($id_feature_name, $value);
            }
            
            if ($id_feature_name==0 && $id_feature_value==0) {
                $this->output[__FUNCTION__][$reference]['features']['values'][] = 'ERROR: <ul>'
                        . '<li>id_product: <strong>' . $id_product . "</strong></li>"
                        . '<li>id_feature: <strong>' . $id_feature_name  . ", " . $featureName . "</strong></li>"
                        . '<li>id_feature_value: <strong>' . $id_feature_value . ", " . $value ."</strong></li></ul>";
                return;
            }

            try {
                $result = $db->insert(
                    'feature_product',
                    array(
                        'id_feature' => $id_feature_name,
                        'id_product' => $id_product,
                        'id_feature_value' => $id_feature_value,
                        'position' => 1
                    )
                );
                $this->output[__FUNCTION__][$reference]['features']['saved'][] = $result; 
            } catch (Exception $exc) {
                $result = "ERROR: " . $exc->getMessage();
                $this->output[__FUNCTION__][$reference]['features']['saved'][] = "error: " . $result; 
            }

            array_push($results, $result);
        }
        
        return $results;
    }
    
    private function addFeature($name)
    {
        $feature = new FeatureCore();
        $feature->name[$this->lang] = Tools::strtolower($name);
        $feature->id_shop_list = array(1);
        try {
            $result = $feature->save();
        } catch (Exception $exc) {
            $result = $exc->getMessage();
        }
        
        $id_feature = $feature->id;
        $this->output[__FUNCTION__]['new feature'][] = $result;
        
        if($result===true) {
            return $id_feature;
        } else {
            return false;
        }
        
    }
    
    private function addFeatureValue($id_feature, $value)
    {
        $feature = new FeatureValueCore();
        $feature->id_feature = $id_feature;
        $feature->id_shop_list = array(1);
        $feature->value[$this->lang] = Tools::strtoupper($value);
        try {
            $result = $feature->save();
        } catch (Exception $exc) {
            $result = $exc->getMessage();
        }
        
        $id_feature_value = $feature->id;
        $this->output[__FUNCTION__]['new feature value'][] = $result;
        
        if($result===true) {
            return $id_feature_value;
        } else {
            return false;
        }
    }
    
    /**
     * 
     * @param ProductCore $product Product object
     * @param String $categories Categories string comma separated 
     * @return String result message
     */
    private function addCategories($product, $categories)
    {
        if(empty(trim($categories))) {
            return "No categories to associate.";
        } else {
            $db = Db::getInstance();
            
            try {
                $categories_array = explode(",", $categories);
                foreach($categories_array as $id_category)
                {
                    $result = $db->insert('category_product', array(
                        'id_category' => (int)$id_category,
                        'id_product' => (int)$product->id,
                        'position' => 0,
                    ));
                }
                $result = "categories:" . print_r($product->getCategories(), 1);
            } catch (Exception $exc) {
                return "error adding to category: " . $exc->getMessage();
            }
            return $result;
        }
    }
    
    private function deleteCategories($id_product)
    {
        if(empty($id_product)) {
            return "No product id";
        }
        $db = Db::getInstance();
        return $db->delete('category_product', 'id_product = ' . $id_product);
                
    }
    
    private function deleteAttributes($id_product)
    {
        if(empty($id_product)) {
            $this->output[__FUNCTION__]['error'][] = "No id product. ";
            return false;
        }
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        
        $sql->select('id_product_attribute')
                ->from('product_attribute')
                ->where('id_product = ' . $id_product);
        
        $result = $db->executeS($sql);
        
        $ids_array = array();
        
        if(count($result)==0) {
            $this->output[__FUNCTION__]['NO ATTRIBUTES'][] = "No Attribute to delete for product " . $id_product;
            return false;
        }
        
        foreach($result as $id) {
            $ids_array[] = $id['id_product_attribute'];
        }
        $ids = implode(",", $ids_array);
        
        $this->output[__FUNCTION__]['QUERY'][] = $sql->__toString();
        $this->output[__FUNCTION__]['RESULT'][] = print_r($result, 1);
        $this->output[__FUNCTION__]['IDS'][] = $ids;
        $this->output[__FUNCTION__]['DELETE GROUP'][] = "product_attribute_group: " . $db->delete('product_attribute_shop', 'id_product_attribute in (' . $ids . ")");
        $this->output[__FUNCTION__]['DELETE COMBINATION'][] = "product_attribute_combination: " . $db->delete('product_attribute_combination', 'id_product_attribute in (' . $ids . ")");
        $this->output[__FUNCTION__]['DELETE PROD ATTR'][] = "product_attribute: " . $db->delete('product_attribute', 'id_product_attribute in (' . $ids . ")");
        
        //Delete images
        $sql_img = new DbQueryCore();
        $sql_img->select('id_image')
                ->from('product_attribute_image')
                ->where('id_product_attribute in (' . $ids . ")");
        $img_array = $db->executeS($sql_img);
        foreach($img_array as $img)
        {
            $id_img = $img['id_image'];
            $image = new ImageCore($id_img);
            $image->deleteImage();
        }
        $this->output[__FUNCTION__]['DELETE IMAGE'][] = "product_attribute_image: " . $db->delete('product_attribute_image', 'id_product_attribute in (' . $ids . ")");
        
        return true;
    }
    
    private function addAttributes($id_product, $row)
    {
        $this->output[__FUNCTION__]['summary']['id_product'][] = $id_product;
        $this->output[__FUNCTION__]['summary']['row'][] = print_r($row, 1);
        $db = Db::getInstance();
        //Get attribute values
        foreach($row as $key=>$value)
        {
            if($this->contains('attr ', $key)) {
                $attribute = Tools::substr($key, Tools::strlen('attr '));
                $attribute_group_id = $this->getAttributeGroup($attribute);
                $attribute_ids = $this->getAttribute($value);
                
                $this->output[__FUNCTION__]['attributes'][] = "attribute group id $attribute: " . (int)$attribute_group_id 
                            . ", attribute ids $value: " . print_r($attribute_ids,1);
                
                $product = new ProductCore($id_product);
                
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
                    $this->output[__FUNCTION__]['product_attribute'][] = $product->reference . " = " . $id_product_attribute;
                } catch (Exception $exc) {
                    $this->output[__FUNCTION__]['error']['message'][] = $exc->getMessage();
                    return false;
                }
                
                //Add attributes list
                foreach($attribute_ids as $attribute_id) {
                   if($attribute_group_id===false || $attribute_id===false) {
                        $this->output[__FUNCTION__]['error']['message'][] = 'Unable to insert attributes.';
                        $this->output[__FUNCTION__]['error']['attribute'][] = $attribute . ":" . (int)$attribute_group_id;
                        $this->output[__FUNCTION__]['error']['attribute'][] = $value . ": " . (int)$attribute_id;
                    } else {
                        try {
                            $db->insert(
                                    'product_attribute_combination',
                                    array(
                                        'id_attribute' => $attribute_id,
                                        'id_product_attribute' => $id_product_attribute,
                                    )
                            );

                            $db->insert(
                                    'product_attribute_shop',
                                    array(
                                        'id_product_attribute' => $id_product_attribute,
                                        'id_shop' => Context::getContext()->shop->id,
                                        'default_on' => $default_on,
                                        'id_product' => $product->id,
                                    )
                            );


                            $this->output[__FUNCTION__]['combination'][] = 
                                    "combination: $attribute: " . $id_product_attribute 
                                    . "- $value: " . $attribute_id;
                            return true;

                        } catch (Exception $exc) {
                            $this->output[__FUNCTION__]['error']['message'] = $exc->getMessage();
                            return false;
                        }
                    }  
                } 
            }
        }
    }
    
    private function getAttributeGroup($attribute)
    {
        //Get type of attribute
        $type = '';
        $pos = Tools::strpos($attribute, "#");
        if($pos!==false) {
            $attribute = Tools::substr($attribute, 0, $pos-1);
            $type = Tools::strtolower(Tools::substr($attribute, $pos-1));
            if(empty($type)) {
                $type = Tools::strtolower(trim($attribute));
            }
            $this->output[__FUNCTION__]['split attribute #'][] = $attribute . ", " . $type; 
        } else {
          $type = Tools::strtolower(trim($attribute));
          $this->output[__FUNCTION__]['split attribute #']['error'][] = $attribute;
        }
        
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql->select('id_attribute_group')
                ->from('attribute_group_lang')
                ->where('id_lang = ' . $this->lang)
                ->where("name like '" . pSQL($attribute) . "'");
        $value = $db->getValue($sql);
        $this->output[__FUNCTION__]['getAttributeGroupId'][$attribute][] = $value;
        
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
                $this->output[__FUNCTION__]['addAttributeGroup'][] = $objAttributeGroup->id;
                return $objAttributeGroup->id;
            } catch (Exception $exc) {
                $this->output[__FUNCTION__]['error']['addAttributeGroup'] = $exc->getMessage();
                $this->output[__FUNCTION__]['error']['type'] = $attribute;
                return false;
            }
        } else {
            return (int)$value;
        }      
    }
    
    private function getAttribute($attribute)
    {
        //Get color code
        $color = '';
        $pos = Tools::strpos($attribute, "#");
        if($pos!==false) {
            $attribute = Tools::substr($attribute, 0, $pos-1);
            $color = Tools::strtolower(Tools::substr($attribute, $pos-1));
            $this->output[__FUNCTION__]['split attribute #'][] = $attribute . ", " . $color; 
        }
        
        $db = Db::getInstance();
        $values = explode(";", $attribute);
        $output = array();
        
        foreach ($values as $value) {
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
                    $this->output[__FUNCTION__]['addAttribute'][] = $objAttribute->id;
                } catch (Exception $exc) {
                    $this->output[__FUNCTION__]['error'][] = $exc->getMessage();
                    $output[] = false;
                }
            }   else {
                $output[] =  (int)$dbvalue;
            }   
        }
        return $output;
    }
    
    /**
     * Check if a product exists in archive
     * @param string $reference product reference to check
     * @return int count if exists returns 1, 0 otherwise
     */
    private function productExists($reference)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql    ->select("count(*)")
                ->from("product")
                ->where("reference = '" . $reference . "'");
        return (int)$db->getValue($sql);
    }
    
    private function getProductByReference($reference)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $sql    ->select("id_product")
                ->from("product")
                ->where("reference = '" . $reference . "'");
        $id = (int) $db->getValue($sql);
        if ($id>0) {
            return new ProductCore($id);
        } else {
            return null;
        }
    }
    
    private function readUploadedFile($file)
    {
        $inputFileName = $file['tmp_name'];
        $rowData = array();
        
        try {
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);
        } catch(Exception $e) {
            $rowData[] = 'Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage();
        }
        
        //  Get worksheet dimensions
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = $sheet->getHighestRow(); 
        $highestColumn = $sheet->getHighestColumn();

        // Get column title
        $titles = $sheet->rangeToArray('A1:' . $highestColumn . '1',
                                            NULL,
                                            TRUE,
                                            FALSE);
        $this->titles = array();
        
        foreach ($titles[0] as $title)
        {
            $this->titles[] = Tools::strtolower($title);
        }
                
        // Get Sheet Values
        $rowArray = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow,
                                            NULL,
                                            TRUE,
                                            FALSE);
        $cols = count($this->titles);
        
        foreach($rowArray as $row)
        {
            $rowAssociated = array();
            for ($i=0; $i<$cols; $i++)
            {
                $rowAssociated[$this->titles[$i]] = $row[$i];                
            }
            $rowAssociated['category'] = $this->categories[(int)$rowAssociated['category default']];
            $rowAssociated['description'] = $this->makeDescription($rowAssociated);
            $rowAssociated['exists'] = $this->productExists($rowAssociated['reference']);
            $rowData[] = $rowAssociated;
        }
        
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['rowData'] = $rowData;
    }
    
    private function makeDescription($row)
    {
        $description = '';
        foreach($row as $key=>$value)
        {
            if(Tools::strpos(Tools::strtolower($key), 'desc ')!==false) {
                $key = Tools::substr($key, Tools::strlen('DESC '));
                $description .= $this->addField($key, $value);
            }
        }
        return $description;
    }
    
    private function addField($key, $value)
    {
        $output = '';
        if(!empty($value))
        {
            $value = "<strong>" . $value . "</strong>";
            $output = ' - ' . $key . ": " . Tools::strtoupper($value) . '<br>';
            
            return $output;
        }
    }
    
    private function getCategories()
    {
        $categories = CategoryCore::getAllCategoriesName();
        $this->categories[0] = '';
        foreach($categories as $category)
        {
            $this->categories[$category['id_category']] = $category['name'];
        }
    }
    
    private function tableExists($tablename)
    {
        try {
            Db::getInstance()->getValue("select 1 from `$tablename`");
            return true;
        } catch (Exception $exc) {
            return false;
        }
    }
    
    private function object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value)
            {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }
    
    private function importImage($imagePath, $product_id, $legend)
    {
        //import image
        $chunks = explode(".",$imagePath);
        $format = end($chunks); //file extension

        $image = new ImageCore();
        $image->cover=false;
        $image->force_id=false;
        $image->id=0;
        $image->id_image=0;
        $image->id_product = $product_id;
        $image->image_format = $format;
        $image->legend = $legend;
        $image->position=0;
        $image->source_index='';
        $image->add();

        $imageTargetFolder = _PS_PROD_IMG_DIR_ . ImageCore::getImgFolderStatic($image->id);
        if (!file_exists($imageTargetFolder)) {
            mkdir($imageTargetFolder, 0777, true);
        }
        $target = $imageTargetFolder . $image->id . '.' . $image->image_format;
        $copy = copy($imagePath, $target);
    }
    
    /**
     * Check if a string is contained in another string
     * @param string $needle text to find
     * @param string $haystack text container
     * @return bool true if contains, false otherwise
     */
    private function contains($needle, $haystack)
    {
        if(empty($needle) || empty($haystack)) {
            $this->output[__FUNCTION__]['empty'][] = "needle: " . $needle . ", haystack: " . $haystack;
            return false;
        }
        $pos = Tools::strpos($haystack, $needle);
        if($pos!==false) {
            $this->output[__FUNCTION__]['summary'][] = "needle: " . $needle . ", haystack: " . $haystack . " = <strong>true</strong>";
            return true;
        } else {
            $this->output[__FUNCTION__]['summary'][] = "needle: " . $needle . ", haystack: " . $haystack . " = false";
            return false;
        }
    }
}
