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

class CatalogItems extends CmsComponent{
	/**
	 * Component db table
	 * @access public
	 * @var string
	 */
	public $base = 'items';
	/**
	 * Max size of uploaded file
	 * @access public
	 * @var string
	 */
	public $max_file_size = '5M';
	/**
     * Upload folder
     * @access public
     * @var string
     */
	public $upload_folder = 'upload/articles';
	/**
	 * Supported file types
	 * @access public
	 * @var string
	 */
    public $support_file_types = 'jpg,jpeg,gif,png';
   /**
    * Main function - the constructor of the component
    * @access public
    * @return void
    */
	function main(){
		parent::main();
	
		$this->setField('name', array(
			'text' => BASIC_LANGUAGE::init()->get('content_name_label'),
			'perm' => '*',
			'messages' => array(
				2  => BASIC_LANGUAGE::init()->get('no_unique_name')
			)
		));
		$this->setField('title', array(
			'text' => BASIC_LANGUAGE::init()->get('content_public_name_label'),
			'lingual' => true
		));
		$this->setField('file', array(
 		    'text' => BASIC_LANGUAGE::init()->get('image'),
   		    'formtype' => 'file',
			'messages' 	=> array(
				2  => BASIC_LANGUAGE::init()->get('upoad_error_2'),
				3  => BASIC_LANGUAGE::init()->get('upoad_error_3'),
				4  => BASIC_LANGUAGE::init()->get('upoad_error_4'),
				10 => BASIC_LANGUAGE::init()->get('upoad_error_10'),
				11 => BASIC_LANGUAGE::init()->get('upoad_error_11'),
				12 => BASIC_LANGUAGE::init()->get('upoad_error_12'),
				13 => BASIC_LANGUAGE::init()->get('upoad_error_13'),
				14 => BASIC_LANGUAGE::init()->get('upoad_error_14'),
				15 => BASIC_LANGUAGE::init()->get('upoad_error_15'),
				16 => BASIC_LANGUAGE::init()->get('upoad_error_16'),
			),   		    
   		    'attributes' => array(
				'max' 	 		=> $this->max_file_size,
				'rand'   		=> 'true', 
				'as' 	 		=> '', 
				'preview' 		=> '200,200',   
				'dir' 	 		=> $this->upload_folder,
				'perm' 	 		=> $this->support_file_types,
				'delete_btn' 	=> array(
					'text' => BASIC_LANGUAGE::init()->get('delete'),
  		 		 )
  		 	)
  		));
  		$this->setField('active', array(
            'text' => BASIC_LANGUAGE::init()->get('content_pblish_label'),
            'formtype' => 'radio',
            'dbtype' => 'int',
            'length' => '1',
            'default' => '1',
  			'lingual' => true,
        	'attributes' => array(
            	'data' => array(
        			BASIC_LANGUAGE::init()->get('no'),
        			BASIC_LANGUAGE::init()->get('yes')
        		)
            )
        ));
        
		$this->ordering(true);
		$this->specialTest = 'beforeSave';
	}
	/**
	 * Extends parent method adding mapping for column in list view
	 * @access public
	 * @return string html for list view
	 */
	function ActionList(){
		$this->map('file', 	BASIC_LANGUAGE::init()->get('image'), 'mapFormatter', 'width=200', 'align=left'); 
		$this->map('title', BASIC_LANGUAGE::init()->get('content_public_name_label'), 'mapFormatter', 'align=left'); 
		$this->map('name', 	BASIC_LANGUAGE::init()->get('content_name_label'), 'mapFormatter', 'align=left'); 
		$this->map('active',BASIC_LANGUAGE::init()->get('content_pblish_label'), 'mapFormatter', 'align=left'); 
		
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
		if($name == 'file'){
    		return BASIC_GENERATOR::init()->image($val, 'width=100|height=100');
	  	}else if($name == 'active'){
			$tmp = '';
			while($l = BASIC_LANGUAGE::init()->listing()){
				if(isset($row['active_'.$l['code']]) && $row['active_'.$l['code']]){
					if($tmp) $tmp .= ", ";
					
					$tmp .= $l['text'];
				}
			}
			return $tmp;
		}
	 	return $val;
	}
	/**
	 * Validator method, set in specialTest property
	 * @access public
	 * @return boolean
	 */
	function beforeSave(){
		$err = false;
		if($this->getDataBuffer('name') && !$this->id && 
			BASIC_SQL::init()->read(" SELECT 1 FROM ".$this->base." WHERE `name` = '".$this->getDataBuffer('name')."' ".
				($this->parent_id ? " AND `_parent_id` = ".$this->parent_id : ''), true)
		){
			$err = $this->setMessage('name', 2);
		}
		return $err;
	}
	/**
	 * Unset field only if it is different from 'name' or 'title' or 'active'
	 * @access public
	 * @return void
	 */
	function unsetField($name){
		if($name != 'name' || $name != 'title' || $name != 'active'){
			parent::unsetField($name);
		}
	}
	/**
	 * Sql query generator
	 * @access public
	 * @param string $criteria
	 * @param boolean $include_all
	 * @return string
	 */
	function select($criteria = '', $include_all = false){
		return parent::select($criteria, true);
	}
	
	/**
	 * Define module settings fields, which values will override value of class properties
	 * @access public
	 * @return hashmap
	 */
	function settingsData(){
		return array(
			'base' 				 => $this->base,
			'upload_folder'		 => $this->upload_folder,
			'support_file_types' => $this->support_file_types,
			'max_file_size' 	 => $this->max_file_size
		);
	}
	/**
	 * Module settings fields description 
	 * @access public
	 * @return value
	 */
	function settingsUI(){
		return array(
			'base' => array(
				'text' => BASIC_LANGUAGE::init()->get('db_table')	
			),
			'upload_folder' => array(
				'text' => BASIC_LANGUAGE::init()->get('upload_folder'),
				'formtype' => 'browser',
				'attributes' => array(
					'resources' => array('upload'),
					'type' => 'folder'
				)
			),			
			'support_file_types' => array(
				'text' => BASIC_LANGUAGE::init()->get('support_file_types')
			),			
			'max_file_size' => array(
				'text' => BASIC_LANGUAGE::init()->get('max_file_size')
			)			
		);
	}
}