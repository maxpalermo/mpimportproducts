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

require_once _PS_MODULE_DIR_ . 'mpimportproducts/classes/PHPExcel.php';

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
        $this->status = array();
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
        $this->context->smarty->assign('status', $this->status);
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
        $this->status = array();
        $this->status['product'] = array();
        
        $checks = Tools::getValue('checkRow', array());
        foreach ($checks as $key=>$value) {
            $product_row = $this->display_products[(int)$key-1];
            /**
             * @var ProductCore $product
             */
            $product = $this->getProductByReference($product_row['reference']);
            $reference = Tools::strtoupper(trim($product_row['reference']));
            
            if(!empty($reference)) {
                $this->status['product'][$reference] = array();

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
                            if(Tools::strpos($key, 'caratteristica')!==false) {
                                $featureName = Tools::substr($key, Tools::strlen('caratteristica '));
                                $status = $this->addFeatureProduct($product->id, $featureName, $value, $reference);
                                $this->status['product'][$reference]['status'] = array($status);
                            }
                        }
                    }

                    $deleteCategories = $this->deleteCategories($product->id);
                    $categoryDefault = $product_row['category default'];
                    $categoryOther = $product_row['category other'];
                    $addDefaultCategory = $this->addCategories($product, $categoryDefault);
                    $addOtherCategories = $this->addCategories($product, $categoryOther);

                    $this->status['product'][$reference]['result'] = array("product: " . $product->id 
                            . " (" . $product->reference . ") <ul>"
                            . "<li>delete categories: " . $deleteCategories . "</li>"
                            . "<li>add default category: " . $categoryDefault . "=> " . $addDefaultCategory . "</li>"
                            . "<li>add categories: " . $categoryOther . "=> " . $addOtherCategories . "</li>"
                            . "<li>saved: " .  $save . "</li></ul>");
                } else {
                    $this->status['product'][$reference]['missing'] = array("product: $reference NOT FOUND");
                }
            }   
        }
        return;
    }
    
    private function addFeatureProduct($id_product, $featureName, $featureValue, $reference)
    {   
        $this->status['product'][$reference]['features'] = array("<ul>" 
                . "<li>id_product : <strong>" . $id_product . "</strong></li>"
                . "<li>feature_name : <strong>" . $featureName . "</strong></li>"
                . "<li>feature_value: <strong>" . $featureValue . "</strong></ul>");
        
        $db = Db::getInstance();
        
        //Get Feature id or create a new feature
        $sqlName = new DbQueryCore();
        $sqlName->select('id_feature')
                ->from('feature_lang')
                ->where('id_lang = ' . $this->lang)
                ->where("name like '%" . pSQL($featureName) . "%'");
        $id_feature_name = (int)$db->getValue($sqlName);
        if (!$id_feature_name) {
            $id_feature_name = (int)$this->addFeature($featureName);
        }
        
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

            if (!$id_feature_value) {
               $id_feature_value =  (int)$this->addFeatureValue($id_feature_name, $value);
            }
            
            if ($id_feature_name==0 && $id_feature_value==0) {
                $this->status['product'][$reference]['features']['values'] = array('ERROR: <ul>'
                        . '<li>id_product: <strong>' . $id_product . "</strong></li>"
                        . '<li>id_feature: <strong>' . $id_feature_name  . ", " . $featureName . "</strong></li>"
                        . '<li>id_feature_value: <strong>' . $id_feature_value . ", " . $value ."</strong></li></ul>");
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
                $this->status['product'][$reference]['features']['saved'] = array($result); 
            } catch (Exception $exc) {
                $result = "ERROR: " . $exc->getMessage();
                $this->status['product'][$reference]['features']['saved'] = array("error: " . $result); 
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
        $this->status['product']['new feature'][] = $result;
        
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
        $this->status['product']['new feature value'][] = $result;
        
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
            if(Tools::strpos($key, 'DESC ')!==false) {
                $key = Tools::substr($key, Tools::strlen('DESC  '));
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
}
