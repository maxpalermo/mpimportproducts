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

<style>
    .table-invoice th
    {
        text-align: center;
        background-color: #E1E2E2;
        color: #555555;
        text-shadow: 1px 1px 1px #999999;
        padding-top: 10px;
        padding-bottom: 10px;
        border: 1px solid #eeeeee;
        text-transform: uppercase;
    }
    .table-invoice tbody tr:hover td
    {
        background-color: #F6F6CC !important;
        cursor: pointer !important;
    }
    .table-invoice td
    {
        padding: 5px;
        border: 1px solid #eeeeee;
    }
</style>

<table class='table-bordered  table-invoice table-striped' style='width: 100%;'>
    <thead>
        <tr>
            <th><input type="checkbox" name='checkAll' id='checkAll'></th>
            <th>{l s='reference' mod='mpexportinvoices'}</th>
            <th>{l s='name' mod='mpexportinvoices'}</th>
            <th>{l s='description' mod='mpexportinvoices'}</th>
            <th>{l s='category' mod='mpexportinvoices'}</th>
            <th>{l s='size' mod='mpexportinvoices'}</th>
            <th>{l s='color' mod='mpexportinvoices'}</th>
            <th>{l s='type' mod='mpexportinvoices'}</th>
            <th>{l s='thumb' mod='mpexportinvoices'}</th>
        </tr>
    </thead>
    <tbody>
        {assign var=i value=1}
        {foreach $rows as $row}
            <tr>
                <td style='text-align: right;'>
                    <span>
                        {$i++|escape:'htmlall':'UTF-8'} 
                        <input type='checkbox' name='checkRow[{$i-1|escape:'htmlall':'UTF-8'}]' {if !empty($checkRow[$i-1])}checked='checked'{/if}>
                    </span>
                </td>
                <td style='text-align: left;'>
                    {if $row['exists']>0}
                        {$row['reference']|escape:'htmlall':'UTF-8'}
                    {else}
                        <span style='color: #a11; font-weight: bold;'>
                            {$row['reference']|escape:'htmlall':'UTF-8'}
                        </span>
                    {/if}
                </td>
                <td style='text-align: left;'>{$row['name']|escape:'htmlall':'UTF-8'}</td>
                <td style='text-align: left;'>{$row['description']}</td>
                <td style='text-align: left;'>{$row['category']|escape:'htmlall':'UTF-8'}</td>
                <td style='text-align: left;'>{$row['desc taglie']|escape:'htmlall':'UTF-8'}</td>
                <td style='text-align: left;'>{$row['desc colore']|escape:'htmlall':'UTF-8'}</td>
                <td style='text-align: left;' >{{$row['feat tipo prodotto']|strtoupper}|escape:'htmlall':'UTF-8'}</td>
                <td style='text-align: center;'><img src="{$row['thumb']|escape:'htmlall':'UTF-8'}" style='max-height: 128px;'></td>
            </tr>
        {/foreach}
    </tbody>
</table>
