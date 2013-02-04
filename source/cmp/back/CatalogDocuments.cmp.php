<?php
/**
* SBND F&CMS - Framework & CMS for PHP developers
*
* Copyright (C) 1999 - 2013, SBND Technologies Ltd, Sofia, info@sbnd.net, http://sbnd.net
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
* @package cms.cmp.back.catalog
* @version 1.4
*/

BASIC::init()->imported('CatalogItems.cmp', BASIC::init()->package(__FILE__));
/**
 * @author Evgeni Baldzhiyski
 * @version 0.1 
 * @since 29.03.2012
 * @package cms.catalog.back
 */
class CatalogDocuments extends CatalogItems{
	/**
	 * Supported file types
	 * @access public
	 * @var string
	 */
    public $support_file_types = 'txt,pdf,doc,docx,xls,xlsx';
    /**
     * Upload folder
     * @access public
     * @var string
     */
	public $upload_folder 	   = 'upload/documents';
    /**
     * Component db table
     * @access public
     * @var string
     */
	public $base			   = 'documents';
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
	/**
	 * Extends parent method adding mapping for column in list view
	 * @access public
	 * @return string html for list view
	 */
	function ActionList(){ 
		$this->map('title', BASIC_LANGUAGE::init()->get('title'), 'mapFormatter', 'align=left'); 
		$this->map('active', BASIC_LANGUAGE::init()->get('content_pblish_label'), 'mapFormatter', 'align=left'); 
		
		return parent::ActionList();
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
}