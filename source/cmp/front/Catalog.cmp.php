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
* @package cms.cmp.front.catalog
* @version 1.5
*/

BASIC::init()->imported('SearchBar.cmp', 'cms/controlers/front');
/**
 * The paging will set in catalog start page and for first child on current select item.
 * 
 * @author Evgeni Baldzhiyski
 * @version 0.4
 * @since 28.03.2012
 * @package cmp.catalog.front
 */
class Catalog extends CmsBox implements SearchBarInterface{
	/**
	 * Backend component for the catalog
	 * @access public
	 * @var string
	 */
	public $target = '';
	/**
	 * Max element per page
	 * @access public
	 * @var integer
	 */
	public $paging_limit = 10;
	/**
	 * Template for list view
	 * @access public
	 * @var string
	 */
	public $template_list = 'catalog-list.tpl';
	/**
	 * Template for list view
	 * @access public
	 * @var string
	 */
 	public $template_view = 'catalog-view.tpl';
 	/**
 	 * 
 	 * Enter description here ...
 	 * @var unknown_type
 	 */
 	public $manu_template_var = '';
 	/**
 	 * @access private
	 * @var CmsComponent
 	 */
 	protected $baseBuild = null;
 	/**
 	 * @access private
	 * @var CmsComponent
 	 */
 	protected $targetBuild = null;
 	/**
 	 * collection item:
 	 * array(
 	 * 		'title' => ' ... ',
			'link'	=> ' ... ',
			'is_last' => (boolean)
 	 * )
 	 * 
 	 * @var $breadcrumbs collection
 	 */
 	protected $breadcrumbs = array();
 	protected $url = '';
 	/**
 	 * Array with page info
 	 * @access private
 	 * @var array
 	 */
 	protected $currentPage = array();
 	/**
 	 * @access private
 	 * @var integer
 	 */
 	protected $currentPageId = 0;
 	/**
 	 * @access private
 	 * @var boolean
 	 */
 	protected $is_tree = true;
 	protected $chilsBuildList = array();
 	/**
 	 * @access private
 	 * @var BasicComponentPaging
 	 */
 	protected $paging = null;
 	
 	protected $is_selected = false;
 	protected $selected_level = 0;
 	/**
 	 * Return fenerated html for catalog component, that will be placed after page info
 	 * @access public
 	 * @return string html
 	 */
	function startPanel(){
		if(!$this->baseBuild = Builder::init()->build($this->target)){
			BASIC_ERROR::init()->setError(BASIC_LANGUAGE::init()->get('access_denied'));
			return '';
		}
		
		$this->targetBuild = $this->baseBuild;
		$this->paging = new BasicComponentPaging($this->prefix);
		
		$this->is_tree = ($this->targetBuild instanceof Tree);
		
		// search for exist page
		$this->decodeUrl();
		
		$data = $this->buildData();
		
		$tpl = ($this->is_tree || (!$this->is_tree && !$this->currentPageId) ? $this->template_list : $this->template_view);
		
		$this->paging->script = BASIC::init()->scriptName()."/".$this->url;

		if($this->manu_template_var){
			BASIC_TEMPLATE2::init()->set($this->manu_template_var , $this->getMenu());
		}
		if($this->currentPageId){
			if(BASIC::init()->ini_get('rewrite')){
				$serializeUrl = str_replace(BASIC::init()->virtual()."/", '', BASIC::init()->ini_get('rewrite')->encoder('', array()));
			}else{
				$serializeUrl = BASIC_URL::init()->serialize();
			}
			foreach ($this->breadcrumbs as $k => $v){
				$this->breadcrumbs[$k]['href'] .= $serializeUrl;
				
				Builder::init()->META_NAMES($this->breadcrumbs[$k]['title']);
				Builder::init()->breadcrumb($this->breadcrumbs[$k]);
			}
		}

		return BASIC_TEMPLATE2::init()->set(array(
			'data' => $data,
			'paging_bar' => $this->paging->getBar()
		), $tpl)->parse($tpl);
	}
	/**
	 * @access private
	 * @return hashmap
	 */
	protected function buildData(){
		if($this->currentPageId){
			$res = $this->targetBuild->getRecord($this->currentPageId);
			$res['_current_'] = true;
			$res['href'] = $this->buildUrl('');
			$res['_subs_'] = $this->buildSubs($this->currentPageId);
			$res['_childs_'] = $this->buildChilds($this->currentPageId, true);
		}else{
			$res = array();
			$url = $this->url;
			
			$criteria = " AND `active` = 1 ".($this->is_tree ? " AND `_parent_self` = 0 " : '');
			
			$rdr = $this->targetBuild->read($criteria);
			if($rdr->num_rows() > $this->paging_limit){
				$this->paging->init($rdr->num_rows(), $this->paging_limit);
				$rdr = $this->targetBuild->read($criteria.$this->paging->getSql());
			}
			while($rdr->read()){				
				$rdr->setItem('href', $this->buildUrl($rdr->item('name')));
				$rdr->setItem('_subs_', $this->buildSubs($rdr->item('id')));
				$rdr->setItem('_childs_', $this->buildChilds($rdr->item('id')));
				
				$res[] = $rdr->getItems();
				
				$this->url = $url;
			}
		}
		return $res;
	}	
	/**
	 * Find in child components.
	 * 
	 * @access private
	 * @param string $name
	 * @return boolean
	 */
	protected function getChildPage($name){
		if(!$this->currentPageId) return false;
		
		$this->buildChildsList();
		
		foreach ($this->chilsBuildList as $k => $child){
			$child->parent_id = $this->currentPageId;
			
			if($this->currentPage = $child->read(" AND `name` = '".cleanURLInjection($name)."' AND `_parent_id` = ".$this->currentPageId." ")->read()){
				
				$this->setBreadcrumbsData($this->currentPage);
				$this->buildUrl($this->currentPage['name']);
				$this->targetBuild = $child;
				$this->currentPageId = $this->currentPage['id'];
				
				$this->is_tree = ($this->targetBuild instanceof Tree);
				$this->chilsBuildList = array();
				
				return true;
			}
		}
		return false;
	}
	/**
	 * parse request variables 
	 * @access private
	 * @return void
	 */
	protected function decodeUrl(){
		if($_GET){
			$tmp = array(); foreach($_GET as $k => $v){
				if($k){
					$tmp[] = $k;
				}
				if($v){
					$tmp[] = $v;
				}
			}
			$_GET = array();
			
			foreach ($tmp as $k => $v){
				if($this->getPageByName($v)){
					unset($tmp[$k]);
				}else{
					break;
				}
			}
			$t = ''; $i = 0; foreach($tmp as $v){
	            if(!($i % 2)){
	            	$t = $v;
	                $_GET[$t] = '';
	            }else{
	                $_GET[$t] = $v;
	                $t = '';
	            }
	            $i++;
			}
		}
	}
	/**
	 * Find rows in top component
	 * 
	 * @access private
	 * @param string $name
	 * @return boolean
	 */
	protected function getPageByName($name){
		if(!$this->currentPage = $this->targetBuild->read(" AND `name` = '".cleanURLInjection($name)."' ".
			($this->is_tree ? "AND `_parent_self` = ".(int)$this->currentPageId." " : ""))->read()
		){
			return $this->getChildPage($name);
		}
		
		$this->setBreadcrumbsData($this->currentPage);
		$this->buildUrl($this->currentPage['name']);
		$this->currentPageId = $this->currentPage['id'];
		
		return true;
	}
	/**
	 * 
	 * @param int $id
	 * @return hashmap
	 */
	protected function buildSubs($id){
		if($this->is_tree){
			$subs = array();
			$rdr = $this->targetBuild->read(" AND `active` = 1 AND `_parent_self` = ".$id." ");
			while($rdr->read()){
				$rdr->setItem('href', $this->buildUrl($rdr->item('name'), false));
				
				$subs[] = $rdr->getItems();	
			}
			return $subs;
		}
		return array();
	}
	/**
	 * 
	 * @param int $id
	 * @return hashmap
	 */
	protected function buildChilds($id, $usePaging = false){
		$this->buildChildsList();
		
		$childs = array();
		
		$i = 0; foreach ($this->chilsBuildList as $name => $child){
			$child->parent_id = $id;
			
			$criteria = " AND `active` = 1 ";
			
			$rdr = $child->read($criteria);
			if($usePaging && !$i && $rdr->num_rows() > $this->paging_limit){
				$this->paging->init($rdr->num_rows(), $this->paging_limit);
				$rdr = $child->read($criteria.$this->paging->getSql());
			}
			$tmp = array(); while ($rdr->read()){
				$rdr->setItem('href', $this->buildUrl($rdr->item('name'), false));
				
				$tmp[] = $rdr->getItems();
			}
			$childs[$name] = $tmp;
			
			$i++;
		}
		return $childs;
	}
	protected function buildChildsList(){
		if(!$this->chilsBuildList){
			foreach($this->targetBuild->child as $obj){
				$this->chilsBuildList[$obj->system_name] = Builder::init()->build($obj->system_name);
			}
		}
	}
	/**
	 * 
	 * @param string $url
	 * @param boolean $saveChange
	 */
	protected function buildUrl($url, $saveChange = true){
		$url = $this->url.($url ? $url."/" : '');
		
		if($saveChange){
			$this->url = $url;
		}
		return BASIC_URL::init()->link(BASIC::init()->scriptName()."/".$url, BASIC_URL::init()->serialize());
	}
	/**
	 * @access private
	 * @param hashmap $item
	 */
	protected function setBreadcrumbsData($item){
		if($this->breadcrumbs){
			$this->breadcrumbs[count($this->breadcrumbs) - 1]['is_last'] = false;
		}
		$this->breadcrumbs[] = array(
			'id' => $item['id'],
			'title' => $item['title'],
			'href'	=> $this->buildUrl($item['name'], false),
			'is_last' => true
		);
	}
	
	/**
	 * Generate menu
	 * @access public
	 * @return array
	 */
	function getMenu(){
		$this->url = '';
		return $this->_getMenu(0, 0, 0);
	}
	/**
	 * Generate menu help method
	 * @access private
	 * @return array
	 */
	protected function _getMenu($parent_self = 0, $level = 0, $max_depth = 0){
		$res = array();
		
		if(!$max_depth || ($max_depth && $level < $max_depth)){
			$url = $this->url;
			
			$selected_flag = true;
			$criteria = " AND `active` = 1 ".(($this->baseBuild instanceof Tree) ? " AND `_parent_self` = ".(int)$parent_self." " : '');
			$rdr = $this->baseBuild->read($criteria);
			
			while($rdr->read()){				
				$rdr->setItem('href', $this->buildUrl($rdr->item('name')));
				
				$childs = $this->_getMenu($rdr->item('id'), $level+1, $max_depth);
				
				if($this->currentPageId == $rdr->item('id')){
					$this->is_selected = true;
				}
					
				if($this->is_selected && $selected_flag){
					$rdr->setItem('current', true);
					$selected_flag = false;
				}
		
				$res[] = array(
					'data' => $rdr->getItems(),
					'childs' => $childs
				);
				
				$this->url = $url;
			}
		}
		return $res;
	}

	// Interfaces //
	
	/**
	 * @todo need to be  implemented
	 */
	public function getMatchData($criteria){
		if(!$this->baseBuild = Builder::init()->build($this->target)){
			return array();
		}	
		return $this->_getMatchData($criteria, $this->baseBuild);
	}
	protected function _getMatchData($criteria, $el){
		$res = array();
		$tree = false;
		
		$search_criteria = array('name', 'title');
		$advanse_search_criteria = array('name', 'title', 'short_desc', 'desc');
		
		$scriteria = $search_criteria;
		if($el instanceof Tree){
			$tree = true;
			$scriteria = $advanse_search_criteria;
		}
		if($el instanceof CatalogArticles){
			$scriteria = $advanse_search_criteria;
		}
		
		
		$rdr = $el->read(" AND (".SearchBar::buildSqlCriteria($scriteria, array($criteria[0])).") ");
		if(!$rdr->num_rows()){
			unset($criteria[0]);
			$rdr = $el->read(" AND (".SearchBar::buildSqlCriteria($scriteria, $criteria).") ");
		}
		while($rdr->read()){
			$rdr->setItem('href', BASIC_URL::init()->link(Builder::init()->pagesControler->getPageTreeByComponent($this->model->system_name).
				$this->searchBuildNavigation($el, $rdr->item('id'))
			));
		
			$res[] = $rdr->getItems();
		}
		
		foreach ($el->model->child as $child){
			foreach ($this->_getMatchData($criteria, $el->buildChild($child->system_name)) as $v){
				$res[] = $v;
			}
		}
		return $res;
	}
	protected function searchBuildNavigation($target, $id){
		$rtn = '';
		if($target instanceof Tree){
			
		}else{
			if($res = $target->getRecord($id)){
				if($target->model->param){
					$rtn .= $this->searchBuildNavigation($target->buildParent(), $res['_parent_id']);
				}
				$rtn .= $res['name']."/";
			}
			$rtn = '';
		}
		return $rtn;
	}
	

	
	function isRequireSettings(){
		return true;
	}	
	/**
	 * Define module settings fields, which values will override value of class properties
	 * @access public
	 * @return hashmap
	 */
	function settingsData(){
		return array(
			'target' 			=> $this->target ,
			'template_list' 	=> $this->template_list,
			'template_view' 	=> $this->template_view,
			'manu_template_var' => $this->manu_template_var,
			'paging_limit' 		=> $this->paging_limit
		);
	}
	/**
	 * Module settings fields description 
	 * @access public
	 * @return value
	 */
	function settingsUI(){
		BASIC::init()->imported("*", BASIC::init()->package(__FILE__).'../back');
		
		$cmps = array();
		foreach(Builder::init()->build('modules', false)->genesateAssignList(array('' => ' ')) as $k => $v){
			if(!$k){
				$cmps[$k] = $v;
			}else{
				$cmp = Builder::init()->build($k, false);
				if(($cmp instanceof CatalogPackages) || ($cmp instanceof CatalogItems)){
					$cmps[$k] = $v;
				}
			}
		}
		
		return array(
			'target' => array(
				'text' => BASIC_LANGUAGE::init()->get('target_modul'),
				'formtype' => 'select',
				'perm' => '*',
				'attributes' => array(
					'data' => $cmps
				 )
			),
			'template_list' => array(
				'text' => BASIC_LANGUAGE::init()->get('template_list')	
			),			
			'template_view' => array(
				'text' => BASIC_LANGUAGE::init()->get('template_view')	
			),
			'manu_template_var' => array(
				'text' => BASIC_LANGUAGE::init()->get('manu_template_var')	
			),
			'paging_limit' => array(
				'text' => BASIC_LANGUAGE::init()->get('paging_limit')
			)			
		);
	}	
}