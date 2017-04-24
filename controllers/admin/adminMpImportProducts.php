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
    private $products;
    private $display_products;
    private $file;
    private $categories;
    private $titles;
    private $currentPage;
    private $currentPagination;
    
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
        
        $this->status = array();
        $checks = Tools::getValue('checkRow', array());
        foreach ($checks as $key=>$value) {
            $product_row = $this->display_products[(int)$key-1];
            /**
             * @var ProductCore $product
             */
            $product = $this->getProductByReference($product_row['reference']);
            if(!empty($product)) {
                $product->description[$this->lang] = $product_row['description'];
                $product->description_short[$this->lang] = Tools::substr($product_row['description'],0,599);
                $product->id_category_default = $product_row['categoria principale'];
                $this->status[] = "product: " 
                        . $product->id 
                        . " (" . $product->reference . ") "
                        . PHP_EOL . "delete categories: " . $this->deleteCategories($product->id)
                        . PHP_EOL . "add categories: " . $product_row['categorie secondarie'] . "=> " . $this->addCategories($product, $product_row['categorie secondarie'])
                        . PHP_EOL . "saved: " .  $product->save();
                //print "<pre>" .$product_row['reference']  . ", saved: " . $product->save() . "</pre>";
            } else {
                $this->status[] = "product: " 
                        . " (" . $product_row['reference'] . ") "
                        . "saved: NOT FOUND";
            }
        }
        return;
    }
    
    private function addCategories($product, $categories)
    {
        if(empty($categories)) {
            return;
        } else {
            try {
                $result = $product->addToCategories(explode(',',$categories));
            } catch (Exception $exc) {
                return "error: " . $exc->getMessage();
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
            $rowAssociated['reference'] = "ISA" . Tools::strtoupper($rowAssociated['id']);
            $rowAssociated['category'] = $this->categories[(int)$rowAssociated['categoria principale']];
            $rowAssociated['description'] = $this->makeDescription($rowAssociated);
            $rowData[] = $rowAssociated;
        }
        
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['rowData'] = $rowData;
    }
    
    private function makeDescription($row)
    {
        $description = $this->addField($row['descrizione peso tessuto'], 'peso: ') 
                . $this->addField($row['materiali'], 'materiali: ') 
                . $this->addField($row['descrizione manica']) 
                . $this->addField($row['descrizione collo']) 
                . $this->addField($row['descrizione tasche']) 
                . $this->addField($row['descrizione taglie'], '', true) 
                . $this->addField($row['colori'], 'colore: ') 
                . $this->addField($row['rifiniture']) 
                . $this->addField($row['id'], 'codice isacco: ', true, true) 
                . $this->addField($row['descrizione chiusura']) 
                . $this->addField($row['descrizione num. bottoni']) 
                . $this->addField($row['vestibilità']) 
                . $this->addField($row['tessuto'], 'tessuto ') 
                . $this->addField($row['dettagli']) 
                . $this->addField($row['tasche grembiule']) 
                . $this->addField($row['antimacchia'], 'antimacchia: ') 
                . $this->addField($row['no stiro'], 'no stiro: ') 
                . $this->addField($row['anti acido - cloro'], 'anti acido/cloro: ');
        return $description;
    }
    
    private function addField($value, $prefix = '', $upper = false, $bold = false)
    {
        $output = '';
        if(!empty($value))
        {
            if ($bold) {
                $value = "<strong>" . $value . "</strong>";
            }
            if($upper) {
                $output = ' - ' . $prefix . Tools::strtoupper($value) . '<br>';
            } else {
                $output = ' - ' . $prefix . Tools::strtolower($value) . '<br>';
            }
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
    
    private function getFee($id_order)
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        
        $sql    ->select('fees')
                ->select('tax_rate')
                ->from('mp_advpayment_orders')
                ->where('id_order = ' . $id_order);
        
        
        $result = $db->getRow($sql);
        
        $this->context->smarty->assign('sql_fees', $sql);
        $this->context->smarty->assign('result_fees', $result);
        
        return $result;
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
}
