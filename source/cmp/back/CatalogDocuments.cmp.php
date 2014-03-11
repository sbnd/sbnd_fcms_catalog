<?php
/**
* SBND F&CMS - Framework & CMS for PHP developers
*
* Copyright (C) 1999 - 2014, SBND Technologies Ltd, Sofia, info@sbnd.net, http://sbnd.net
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author SBND Techologies Ltd <info@sbnd.net>
* @package cmp.back.catalog
* @version 1.6
*/

BASIC::init()->imported('CatalogGallery.cmp', BASIC::init()->package(__FILE__));

class CatalogDocuments extends CatalogGallery{
	/**
	 * Component db table
	 * @access public
	 * @var string
	 */
	public $base = 'documents';
	/**
	 * Upload folder
	 * @access public
	 * @var string
	 */
	public $upload_folder = 'upload/documents';	
	/**
	 * Supported file types
	 * @access public
	 * @var string
	 */
    public $support_file_types = 'txt,pdf,doc,docx,xls,xlsx';
   	/**
    * Main function - the constructor of the component
    * @access public
    * @return void
    */
	function main(){
		parent::main();
		
		$this->updateField('file', array(
			'text' => BASIC_LANGUAGE::init()->get('file')
		));	
	}
	function map($field, $header, $colback = '', $attribute = '', $sort = true){
		if($field != 'file'){
			parent::map($field, $header, $colback, $attribute, $sort);
		}
	}
	/**
	 * Help method using in column maping for cell formating
	 * @access public
	 * @param string $val
	 * @param string $name
	 * @param array $row
	 * @return mix
	 */
	function mapFormatter($val, $name, $row){
		if($name == 'title'){
	  		return BASIC_GENERATOR::init()->link($val, BASIC::init()->ini_get('root_virtual').$row['file']);
	  	}
	  	return parent::mapFormatter($val, $name, $row);
	}
	/**
	 * Define module settings fields, which values will override value of class properties
	 * @access public
	 * @return hashmap
	 */
	function settingsData(){
		$tmp = parent::settingsData();
		unset($tmp['thumbs']);
	
		return $tmp;
	}
	/**
	 * Module settings fields description
	 * @access public
	 * @return value
	 */
	function settingsUI(){
		$tmp = parent::settingsUI();
		unset($tmp['thumbs']);

		return $tmp;
	}
}