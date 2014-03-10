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
class CatalogGallery extends CatalogItems{
	/**
     * Component db table
     * @access public
     * @var string
     */
	public $base = 'gallery';
	/**
     * Upload folder
     * @access public
     * @var string
     */
	public $upload_folder = 'upload/gallery';
	
	public $thumbs = array();
	
	/**
	 * @var string
	 */
	private $ext = '';
	/**
	 * @var number
	 */
	private $size = 0;
	/**
	 * (non-PHPdoc)
	 * @see CatalogItems::main()
	 */
	function main(){
		parent::main();
		
		$this->updateField('file', array(
			'perm' => '*',
			'attributes' => array(
				'onComplete' => array($this, 'sizeAndType'),
				'onDelete' => array($this, 'removeThumbs')				
			)
		));
		$this->setField('size', array(
			'formtype' => 'none'
		));
		$this->setField('extention', array(
			'formtype' => 'none',
			'length' => 10
		));
	}
	/**
	 * @param BasicUpload $obj
	 */
	function sizeAndType($obj){
		$this->ext = $obj->type;
		$this->size = $obj->size;
		
		if(!$this->thumbs) return;
		
		BASIC::init()->imported('media.mod');
		$tmp = new BasicMediaImage($obj->returnName, $this->upload_folder);
		
		foreach($this->thumbs as $v){
			$sizes = explode(",", $v);
			
			$thumb_image_obj = $tmp->copy($sizes[0]."x".$sizes[1].'-'.$obj->returnName, $this->upload_folder);
			$thumb_image_obj->resize($sizes[0], $sizes[1]);
			
			unset($thumb_image_obj);
		}
	}
	/**
	 * @param BasicUpload $obj
	 */
	function removeThumbs($obj){
		if(!$this->thumbs) return;
		
		BASIC::init()->imported('upload.mod');
		
		$name = str_replace($this->upload_folder."/", '', $obj->fullName);
		
		$fl = new BasicUpload(null);
		$fl->upDir = $this->upload_folder;
		
		foreach($this->thumbs as $v){
			$sizes = explode(",", $v);
			
    		$fl->delete($sizes[0].'x'.$sizes[1].'-'.$name);
		}
	}
    /**
	 * Extends parent method adding mapping for column in list view
	 * @access public
	 * @return string html for list view
	 */
	function ActionList(){
		$this->map('file'  , 	BASIC_LANGUAGE::init()->get('image'), 'mapFormatter', 'width=200', 'align=left'); 
		$this->map('title' , 	BASIC_LANGUAGE::init()->get('title'), 'mapFormatter', 'align=left'); 
		
		$this->map('size', 		BASIC_LANGUAGE::init()->get('size') , 'mapFormatter', 'align=left'); 
		$this->map('extention', BASIC_LANGUAGE::init()->get('extention') , 'mapFormatter', 'align=left'); 
		
		$this->map('active', 	BASIC_LANGUAGE::init()->get('content_pblish_label') , 'mapFormatter', 'align=left');

		return parent::ActionList();
	}
	function map($field, $header, $colback = '', $attribute = '', $sort = true){
		if($field != 'name'){
			parent::map($field, $header, $colback, $attribute, $sort);
		}
	}	
	/**
	 * (non-PHPdoc)
	 * @see CmsComponent::ActionSave()
	 */
	function ActionSave($id){
		if($id = parent::ActionSave($id)){
			$file = $this->getDataBuffer('file');
			
			$this->cleanBuffer();
			$this->setDataBuffer('size', $this->size);
			$this->setDataBuffer('extention', $this->ext);
			$id = parent::ActionSave($id);
		}
		return $id;
	}
	/**
	 * (non-PHPdoc)
	 * @see CatalogItems::mapFormatter()
	 */
	function mapFormatter($val, $name, $row){
		if($name == 'size'){
			return BASIC::init()->biteToString($val);
		}
		return parent::mapFormatter($val, $name, $row);
	}
	/**
	 * 
	 * @see CmsComponent::getRecords()
	 */
	function getRecords($ids = array(), $criteria = '', $include_all = false){
		$rdr = parent::getRecords($ids, $criteria, $include_all);
		
		if($this->thumbs){
			while($rdr->read()){
				$tmp = array();
				$name = str_replace($this->upload_folder."/", '', $rdr->item('file'));
				
				foreach ($this->thumbs as $v){
					$sizes = explode(",", $v);
					
					$tmp[$sizes[0]."x".$sizes[1]] = $this->upload_folder."/".$sizes[0]."x".$sizes[1].'-'.$name;
				}
				$rdr->setItem('thimbs', $tmp);
			}
		}
		return $rdr;
	}
	
	/**
	 * Define module settings fields, which values will override value of class properties
	 * @access public
	 * @return hashmap
	 */
	function settingsData(){
		$tmp = parent::settingsData();
		$tmp['thumbs'] = $this->thumbs;
		
		return $tmp;
	}
	/**
	 * Module settings fields description 
	 * @access public
	 * @return value
	 */
	function settingsUI(){
		$tmp = parent::settingsUI();
		$tmp['thumbs'] = array(
			'text' => BASIC_LANGUAGE::init()->get('create_thumbnails'),
			'formtype' => 'selectmanage',
			'attributes' => array(
				'data' => array(
					BASIC_LANGUAGE::init()->get('thumbnail_width'),
					BASIC_LANGUAGE::init()->get('thumbnail_height')
				)
			)
		);
		return $tmp;
	}
}