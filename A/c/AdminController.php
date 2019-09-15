<?php

// +----------------------------------------------------------------------
// | JiZhiCMS { 极致CMS，给您极致的建站体验 }  
// +----------------------------------------------------------------------
// | Copyright (c) 2018-2099 http://www.jizhicms.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 留恋风 <2581047041@qq.com>
// +----------------------------------------------------------------------
// | Date：2019/01-2019/02
// +----------------------------------------------------------------------


namespace A\c;

use FrPHP\lib\Controller;
use FrPHP\Extend\Page;

class AdminController extends CommonController
{

	
	
	public function group(){
		$page = new Page('Level_group');
		$sql = ' 1=1 ';
		$data = $page->where($sql)->orderby('id desc')->page($this->frparam('page',0,1))->go();
		$pages = $page->pageList();
		
		$this->pages = $pages;
		$this->lists = $data;
		$this->sum = $page->sum;
		$this->display('group-list');
	}
	function group_del(){
		
		$id = $this->frparam('id');
		if($id){
			//检查是否有管理员
			if(M('level')->getCount(array('gid'=>$id))>0){
				JsonReturn(array('code'=>1,'msg'=>'该角色下存在用户，请先移除用户再删除！'));
			}
			if(M('level_group')->delete(array('id'=>$id))){
				JsonReturn(array('code'=>0,'msg'=>'删除成功！'));
			}else{
				JsonReturn(array('code'=>1,'msg'=>'删除失败，请重试！'));
			}
		}else{
			JsonReturn(array('code'=>1,'msg'=>'非法操作！'));
		}
		
		
	}
	
	function groupedit(){
		$this->fields_biaoshi = 'level_group';
		if($this->frparam('go')==1){
			$data = $this->frparam();
			$data['paction'] = (count($this->frparam('ruler',2))>0)?','.implode(',',$this->frparam('ruler',2)).',':'';
			if(M('level_group')->update(array('id'=>$data['id']),$data)){
				JsonReturn(array('code'=>0,'msg'=>'修改成功！'));
			}else{
				JsonReturn(array('code'=>1,'msg'=>'修改失败，请重新提交！'));
			}
			
			
			
		}
		
		$this->data = M('level_group')->find(['id'=>$this->frparam('id')]);
		$rulers = M('ruler')->findAll(null,'id ASC');
		$ruler_top = array();
		$ruler_children = array();
		foreach($rulers as $v){
			if($v['pid']==0){
				$ruler_top[]=$v;
			}else{
				$ruler_children[$v['pid']][]=$v;
			}
		}
		$this->ruler_top = $ruler_top;
		$this->ruler_children = $ruler_children;
		
		if(!$this->data){
			Error('没有该角色！');
		}
		
		$this->display('group-edit');
	}
	
	function groupadd(){
		$this->fields_biaoshi = 'level_group';
		if($this->frparam('go')==1){
			$data = $this->frparam();
			$data['paction'] = (count($this->frparam('ruler',2))>0)?','.implode(',',$this->frparam('ruler',2)).',':'';
			if(M('level_group')->add($data)){
				JsonReturn(array('code'=>0,'msg'=>'新增成功！'));
			}else{
				JsonReturn(array('code'=>1,'msg'=>'新增失败，请重新提交！'));
			}
			
			
			
		}
		
		
		$rulers = M('ruler')->findAll(null,'id ASC');
		$ruler_top = array();
		$ruler_children = array();
		foreach($rulers as $v){
			if($v['pid']==0){
				$ruler_top[]=$v;
			}else{
				$ruler_children[$v['pid']][]=$v;
			}
		}
		$this->ruler_top = $ruler_top;
		$this->ruler_children = $ruler_children;
		
		
		
		$this->display('group-add');
	}
	public function change_group_status(){
		$id = $this->frparam('id',1);
		if(!$id || ($id==1)){
			JsonReturn(array('code'=>1,'msg'=>'非法操作！'));
		}
		
		$x = M('Level_group')->find('id='.$id);
		if($x['isagree']==1){
			$x['isagree']=0;
		}else{
			$x['isagree']=1;
		}
		M('Level_group')->update(array('id'=>$id),array('isagree'=>$x['isagree']));
	}
	
	
	

	public function adminlist(){
		
     
        $admin = adminInfo($_SESSION['admin']['id']);
		$page = new Page('level');
		$sql = ' 1=1 ';
		
		if($this->frparam('username',1)){
			$sql .= " and name like '%".$this->frparam('username',1)."%' ";
		}
       
		if($this->frparam('start',1)){
			$time = strtotime($this->frparam('start',1));
			
			$sql .= " and regtime >= ".$time;
			
		}
		if($this->frparam('end',1)){
			$end = strtotime($this->frparam('end',1).' 23:59:59');
			$sql .= " and regtime <= ".$end;
		}
		
		$data = $this->frparam();
		$res = molds_search('level',$data);
		$get_sql = ($res['fields_search_check']!='') ? (' and '.$res['fields_search_check']) : '';
		$sql .= $get_sql;
		
		$this->fields_search = $res['fields_search'];
		$this->fields_list = M('Fields')->findAll(array('molds'=>'level','islist'=>1),'orders desc');
		
		
		$this->username = $this->frparam('username',1);
		$this->endtime = $this->frparam('end',1);
		$this->starttime = $this->frparam('start',1);
		//echo $sql;
		$lists = $page->where($sql)->page($this->frparam('page',0,1))->go();
		$pages = $page->pageList();
		$this->lists = $lists;
		$this->page = $pages;
		$this->sum = $page->sum;
		$this->display('admin-list');
	}
	
	public function adminedit(){
		$this->fields_biaoshi = 'level';
		$id = frdecode($this->frparam('id',1));
		if($this->frparam('go')==1){
			$data = $this->frparam();
			$data = get_fields_data($data,'level');
			//防止越权操作
			if(isset($data['gid'])){
				if($this->admin['gid']>$data['gid'] && $this->admin['isadmin']!=1){
					JsonReturn(array('code'=>1,'msg'=>'非法操作！'));	
				}
			}
			
			$data['email'] = $this->frparam('email',1);
			$data['pass'] = $this->frparam('pass',1);
			$data['repass'] = $this->frparam('repass',1);
			
			$data['name'] = $this->frparam('name',1);
			$data['tel'] = $this->frparam('tel',1);
			$data['status'] = $this->frparam('status');
			$data['id'] = $id;
			if($data['id']==0){
				JsonReturn(array('code'=>1,'msg'=>'非法操作！'));
			}
			
			
            
			if($data['pass']!=''){
				if($data['pass']!=$data['repass']){
					JsonReturn(array('code'=>1,'msg'=>'两次密码不同！'));
				}
				$data['pass'] = md5(md5($data['pass']).'YF');
			}else{
				unset($data['pass']);
			}

			
          
           
			
			if($data['tel']!=''){
				if(M('level')->find("tel='".$data['tel']."' and id!=".$data['id'])){
					JsonReturn(array('code'=>1,'msg'=>'手机号已被注册！'));
				}	
			}
			
			if(M('level')->find("name='".$data['name']."' and id!=".$data['id'])){
				JsonReturn(array('code'=>1,'msg'=>'昵称已被使用！'));
			}
			
			if($data['email']!=''){
				if(M('level')->find("email='".$data['email']."' and id!=".$data['id'])){
					JsonReturn(array('code'=>1,'msg'=>'邮箱已被使用！'));
				}
			}
			
			$x = M('level')->update(array('id'=>$data['id']),$data);
			if($x){
				JsonReturn(array('code'=>0,'msg'=>'修改成功！'));
			}else{
				JsonReturn(array('code'=>1,'msg'=>'修改失败！'));
			}
			
		}
		$this->member = M('level')->find('id='.$id);
		if($_SESSION['admin']['isadmin']==1){
			
			$this->isadmin = true;
		}else{
			$this->isadmin = false;
		}
        $this->groups = M('level_group')->findAll();
		
		$this->display('admin-edit');
	}
	
	public function adminadd(){
		
		$this->fields_biaoshi = 'level';
		if($this->frparam('go')==1){
			$data = $this->frparam();
			$data = get_fields_data($data,'level');
			//防止越权操作
			if(isset($data['gid'])){
				if($this->admin['gid']>$data['gid'] && $this->admin['isadmin']!=1){
					JsonReturn(array('code'=>1,'msg'=>'非法操作！'));	
				}
			}
			$data['email'] = $this->frparam('email',1);
			$data['pass'] = $this->frparam('pass',1);
			$data['repass'] = $this->frparam('repass',1);
			
			$data['name'] = $this->frparam('name',1);
			$data['tel'] = $this->frparam('tel',1);
			$data['status'] = $this->frparam('status');
			$data['gid'] = ($this->frparam('gid')==null)?2:($this->frparam('gid'));
			
			$data['regtime'] = time();
			$data['logintime'] = time();
			
            
			if($data['pass']!=$data['repass']){
				JsonReturn(array('code'=>1,'msg'=>'两次密码不同！'));
			}
			$data['pass'] = md5(md5($data['pass']).'YF');
			if($data['tel']!=''){
				if(M('level')->find("tel='".$data['tel']."'")){
					JsonReturn(array('code'=>1,'msg'=>'手机号已被注册！'));
				}
			}
			
			if(M('level')->find("name='".$data['name']."'")){
				JsonReturn(array('code'=>1,'msg'=>'昵称已被使用！'));
			}
			if($data['email']!=''){
				if(M('level')->find("email='".$data['email']."' and id!=".$data['id'])){
					JsonReturn(array('code'=>1,'msg'=>'邮箱已被使用！'));
				}
			}
			$x = M('level')->add($data);
			if($x){
				JsonReturn(array('code'=>0,'msg'=>'新增成功！'));
			}else{
				JsonReturn(array('code'=>1,'msg'=>'新增失败！'));
			}
			
		}
        $this->admin = $_SESSION['admin'];
        $this->groups = M('level_group')->findAll();
		if($_SESSION['admin']['isadmin']==1){
			
			$this->isadmin = true;
		}else{
			$this->isadmin = false;
		}
		$this->display('admin-add');
	
	}
	
	public function change_status(){
		$id = frdecode($this->frparam('id',1));
		if(!$id || ($id==1)){
			JsonReturn(array('code'=>1,'msg'=>'非法操作！'));
		}
		
		$x = M('level')->find('id='.$id);
		if($x['status']==1){
			$x['status']=0;
		}else{
			$x['status']=1;
		}
		M('level')->update(array('id'=>$id),array('status'=>$x['status']));
	}
	public function admindelete(){
    	$id = frdecode($this->frparam('id',1));
        if($id==''){
        	JsonReturn(array('code'=>1,'msg'=>'非法操作！'));
        }
		
		if($id==1){
			JsonReturn(array('code'=>1,'msg'=>'系统管理员不能删除！'));
		}
		
        
        $x = M('level')->delete(array('id'=>$id));
		  if($x){
			JsonReturn(array('code'=>0,'msg'=>'删除成功！'));
		  }else{
			JsonReturn(array('code'=>1,'msg'=>'删除失败！'));
		  }
    }

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	}