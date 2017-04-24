{*
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
*}

<div class='panel-info'>
    <table style='width: auto; margin: 0 auto;'>
        <tbody>
            <tr>
                <td>{l s='Page: ' mod='mpimportproducts'}</td>
                <td>
                    <select name='input_select_current_page'>
                        {$opt_pages}
                    </select>
                </td>
                <td style='width: 20px;'></td>
                <td>{l s='Pagination: ' mod='mpimportproducts'}</td>
                <td>
                    <select name='input_select_current_pagination'>
                        {$opt_paginations}
                    </select>
                    <input type='hidden' name='input_hidden_current_pagination' value='{$hidden_curr_pagination}'>
                </td>
                <td style='width: 20px;'></td>
                <td>
                    <button type="submit" value="1" id="submit_pagination" name="submit_pagination" class="btn btn-default pull-right">
                        <i class="icon-arrow-circle-right"></i> 
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
