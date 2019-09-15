<?php

// +----------------------------------------------------------------------
// | FrPHP { a friendly PHP Framework } 
// +----------------------------------------------------------------------
// | Copyright (c) 2018-2099 http://frphp.jizhicms.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 留恋风 <2581047041@qq.com>
// +----------------------------------------------------------------------
// | Date：2018/02
// +----------------------------------------------------------------------


namespace FrPHP\lib;

use FrPHP\Extend\Page;

/**
 * 视图基类
 */
class View
{
    protected $variables = array();
    protected $_controller;
    protected $_action;
    protected $_cachefile;

    function __construct($controller, $action)
    {
        $this->_controller = strtolower($controller);
        $this->_action = strtolower($action);
    }
 
    // 分配变量
    public function assign($name, $value)
    {
        $this->variables[$name] = $value;
    }
 
    // 渲染显示
    public function render($name)
    {
        
		if($name!=null){
			//$name = strtolower($name);
			
			if(strpos($name,'@')!==false){
				$controllerLayout =  str_replace('@','',$name);
			}else{
				$controllerLayout =  APP_HOME . '/'.HOME_VIEW.'/'.Tpl_template.'/' . $name . '.html';
			}
			
		}else{
			$controllerLayout =  APP_HOME .'/'.HOME_VIEW.'/'.Tpl_template.'/' . strtolower($this->_controller) . '/' . $this->_action . '.html';

		}
		//去除可能没有的Tpl_template
        $controllerLayout = str_ireplace(['//','\\'],'/',$controllerLayout);
        //判断视图文件是否存在
        if (file_exists($controllerLayout)) {
			
			$this->template($controllerLayout);
			
			
			// include($cache_file);
			// if(!file_exists($cache_file) && !is_readable($cache_file)){
				// exit('缓存目录cache必须可读可写！请检查目录权限！');
			// }
			
			
			//检查根目录是否存在缓存目录cache
			//检测其是否可读可写
			
			
			
        } else {
           Error_msg('无法找到视图文件'.$controllerLayout);
        }
		
		
		
    }
	
	//模板解析
	public function template($controllerLayout){
		extract($this->variables);//分配变量到模板中
		//对路径文件换为缓存目录  '/'换为'-'
		$layout = str_ireplace(array("//","/"),'_',$controllerLayout);
		$cache_file = str_ireplace('.html','.php',APP_PATH.'/cache/'.$layout);
		$this->_cachefile = $cache_file;//传入系统中
		
		if(APP_DEBUG===true){
			$fp_tp=@fopen($controllerLayout,"r");
			$fp_txt=@fread($fp_tp,filesize($controllerLayout));
			@fclose($fp_tp);
			$fp_txt=$this->template_html($fp_txt);
			$fpt_tpl=@fopen($cache_file,"w");
			@fwrite($fpt_tpl,$fp_txt);
			@fclose($fpt_tpl);
		}else if(is_readable($cache_file)!==true){
			$fp_tp=@fopen($controllerLayout,"r");
			$fp_txt=@fread($fp_tp,filesize($controllerLayout));
			@fclose($fp_tp);
			$fp_txt=$this->template_html($fp_txt);
			$fpt_tpl=@fopen($cache_file,"w");
			@fwrite($fpt_tpl,$fp_txt);
			@fclose($fpt_tpl);
		}
		
		if(is_readable($cache_file)!==true){
			
			Error_msg('无法找到模板缓存，请刷新后重试，或者检查cache缓存文件夹权限');

		}
		include $cache_file;
		
		
	}
	
	
	//模板分解替换
	public function template_html($content){
		//include标签
		preg_match_all('/\{include=\"(.*?)\"\}/si',$content,$i);
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,$this->template_html_include(strtolower($i[1][$k])),$content);
		}
		//loop标签
		preg_match_all('/\{loop (.*?)\}/si',$content,$i);
		$this->check_template_err(substr_count($content, '{/loop}'),count($i[0]),'loop');
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,$this->template_html_loop(strtolower($i[1][$k])),$content);
		}
		$content=str_ireplace('{/loop}','<?php } ?>',$content);
		//foreach循环
		preg_match_all('/\{foreach(.*?)\}/si',$content,$i);
		$this->check_template_err(substr_count($content, '{/foreach}'),count($i[0]),'foreach');
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php foreach('.$i[1][$k].'){ ?>',$content);
		}
		$content=str_ireplace('{/foreach}','<?php } ?>',$content);
		//screen标签
		preg_match_all('/\{screen (.*?)\}/si',$content,$i);
		$this->check_template_err(substr_count($content, '{/screen}'),count($i[0]),'screen');
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,$this->check_template_screen(strtolower($i[1][$k])),$content);
		}
		$content=str_ireplace('{/screen}','<?php } ?>',$content);
		//for循环
		preg_match_all('/\{for(.*?)\}/si',$content,$i);
		$this->check_template_err(substr_count($content, '{/for}'),count($i[0]),'for');
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php for('.$i[1][$k].'){ ?>',$content);
		}
		$content=str_ireplace('{/for}','<?php } ?>',$content);
		//if判断
		preg_match_all('/\{if(.*?)\}/si',$content,$i);
		$this->check_template_err(substr_count($content, '{/if}'),count($i[0]),'if');
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php if'.$i[1][$k].'{ ?>',$content);
		}	
		$content=str_ireplace('{else}','<?php }else{ ?>',$content);
		//else if判断
		preg_match_all('/\{else if(.*?)\}/si',$content,$i);
		foreach($i[0] as $k=>$v){
		$content=str_ireplace($v,'<?php }else if'.$i[1][$k].'{ ?>',$content);
		}	
		$content=str_ireplace('{/if}','<?php } ?>',$content);
		//PHP函数解析
		preg_match_all('/\{fun (.*?)\}/si',$content,$i);
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php echo '.$i[1][$k].' ?>',$content);
		}
		//PHP常量解析
		preg_match_all('/\{__(.*?)__}/si',$content,$i);
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php echo '.$i[1][$k].' ?>',$content);
		}
		//PHP标签解析
		preg_match_all('/\{php(.*?)\/}/si',$content,$i);
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php  '.$i[1][$k].' ?>',$content);
		}
		//PHP变量解析
		preg_match_all('/\{\$(.*?)\}/si',$content,$i);
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'<?php echo $'.$i[1][$k].' ?>',$content);
		}
		//标签原样输出
		preg_match_all('/\{!--(.*?)\--}/si',$content,$i);
		foreach($i[0] as $k=>$v){
			$content=str_ireplace($v,'{'.$i[1][$k].'}',$content);
		}
		return $content;
	}
	//引入公共模板
	public function template_html_include($filename){
		if(strpos($filename,'.')!==false){
			$prefix = '';
		}else{
			$prefix = '.html';
		}
		if(APP_URL=='/index.php'){
			$includefile = str_replace('//','/',APP_PATH . APP_HOME .'/'.HOME_VIEW.'/'.get_template().'/'.$filename. $prefix);
		}else{
			$includefile = str_replace('//','/',APP_PATH . APP_HOME .'/'.HOME_VIEW.'/'.Tpl_template.'/'. Tpl_common .'/'.$filename. $prefix);
		}
		if(!is_file($includefile)) Error_msg($includefile.'不存在！');
		$content = file_get_contents($includefile);
		$content = $this->template_html($content);
		return $content;
	}
	//检查模板标签是否错误！
	public function check_template_err($a,$b,$msg){
		if($a!=$b) Error_msg($this->_cachefile.'模板中存在不完整'.$msg.'标签，请检查是否遗漏{'.$msg.'}开始或结束符');
	}
	
	//筛选
	/**
		输出参数：筛选列表all(item)，链接url，升降序(id,orders,addtime)
		{screen molds="article" orderby="orders desc" tid="1|2" fields='pingpai,yanse' as="v"}
	**/
	public function check_template_screen($f){
		preg_match_all('/.*?(\s*.*?=.*?[\"|\'].*?[\"|\']\s).*?/si',' '.$f.' ',$aa);
		$a=array();
		foreach($aa[1] as $v){
			$t=explode('=',trim(str_replace(array('"',"'"),'',$v)));
			$a=array_merge($a,array(trim($t[0]) => trim($t[1])));
		}
		if(strpos($a['molds'],'$')!==FALSE){
			$a['molds']='".'.$a['molds'].'."';
		}else{
			$a['molds'] = " '".$a['molds']."' ";
		}
		$molds=$a['molds'];
		if(isset($a['fields'])){$fields="'".$a['fields']."'";}else{$fields='null';}
		if($a['as']!=''){$as=$a['as'];}else{$as='v';}
		if(isset($a['orderby'])){
			$order="'".$a['orderby']."'";
		}else{$order="' id desc '";}
		$tids = '1=1';
		if(isset($a['tid'])){
			$arr_tid = array();
			if(strpos($a['tid'],'|')!==false){
				foreach(explode('|',$a['tid']) as $v){
					$arr_tid[]=" (tids like '%,".$v.",%') ";
				}
			$tids = ' ( '. implode('or',$arr_tid).' ) ';
			}else{
				$tids = " tids like  '%,".$a['tid'].",%'  ";
			}
		}
		$fields = '1=1';
		if(isset($a['fields'])){
			$fields = " field='".$a['fields']."' ";
		}

		$sql=' fieldtype in(7,8) and  isshow=1 and molds='.$molds.'  and '.$tids.' and '.$fields;
		$txt="<?php
		\$table ='fields';
		\$w=\"".$sql."\";
		\$order=$order;";
		$as = trim($as,"'");
		$txt .= "
		$".$as."_data = M(\$table)->findAll(\$w,\$order);";
		

		$txt.='$n=0;foreach($'.$as.'_data as $'.$as.'_key=>  $'.$as.'){
			$n++;
			$vs=array();
			$fieldvalue = explode(",",$'.$as.'["body"]);
			//$rooturl = get_domain()."/screen/index/molds/".$'.$as.'["molds"]."/tid/".$type["id"];
			$rooturl = get_domain()."/screen-".$'.$as.'["molds"]."-".$type["id"];
			foreach($fieldvalue as $kk=>$vv){
				$d=explode("=",$vv);
				$vs[$kk] = array("key"=>$d[1],"value"=>$d[0],"url"=>$rooturl."-".$'.$as.'["field"]."-".$d[1].change_parse_url($filters,$'.$as.'["field"]));
			}
			
			$'.$as.'["list"] = $vs;
			$'.$as.'["url"] = $rooturl."-".$'.$as.'["field"]."-0";
			?>';
		
		return $txt;
	}
	
	
	
	//loop全局标签
	private function template_html_loop($f){
		preg_match_all('/.*?(\s*.*?=.*?[\"|\'].*?[\"|\']\s).*?/si',' '.$f.' ',$aa);
		$a=array();foreach($aa[1] as $v){$t=explode('=',trim(str_replace(array('"'),"'",$v)));$a=array_merge($a,array(trim($t[0]) => trim($t[1])));}
		if(strpos($a['table'],'$')!==FALSE){$a['table']=trim($a['table'],"'");}
		$db=$a['table'];
		if(isset($a['limit'])){$limit=$a['limit'];}else{$limit='null';}
		if(isset($a['notempty'])){$notempty=trim($a['notempty'],"'");}else{$notempty=false;}
		if(isset($a['empty'])){$empty=trim($a['empty'],"'");}else{$empty=false;}
		if(isset($a['fields'])){$fields=$a['fields'];}else{$fields='null';}
		if(isset($a['isall'])){$isall=trim($a['isall'],"'");}else{$isall=false;}
		if(isset($a['as'])){$as=$a['as'];}else{$as='v';}
		if(isset($a['orderby'])){
			$order=$a['orderby'];
			//$order=' '.str_replace('|',' ',$order).' ';
		}else{$order="' id desc '";}
		if(isset($a['like'])){
			// like='title|学习,keywords|学习' => title like '%学习%' and keywords like '%学习%';
			$lk = array();
			if(strpos($a['like'],',')!==false){
				$like = explode(',',trim($a['like'],"'"));
				foreach($like as $v){
					$s = explode('|',$v);
					$lk[]= " ".$s[0]." like \'%".trim($s[1])."%\' ";
				}
				$lk = " and ". implode(" and ",$lk);
			}else{
				if(strpos($a['like'],'$')!==false){
					$like = explode('|',trim($a['like'],"'"));
					$lk = " and ".$like[0]." like \'%'.".trim($like[1]).".'%\' ";
				}else{
					$like = explode('|',trim($a['like'],"'"));
					$lk = " and ".$like[0]." like \'%".trim($like[1])."%\' ";
				}
				
			}
			
		}else{ $lk='';}
		//不在某个参数范围内
		$notin_sql = '';
		if(isset($a['notin'])){
			if(strpos($a['notin'],'|')!==false){
				$notin = explode('|',trim($a['notin'],"'"));
				if(strpos($notin[1],'$')!==false){
					$notin_sql = ' and '.$notin[0].' not in(\'.'.$notin[1].'.\') ';
				}else{
					$notin_sql = ' and '.$notin[0].' not in('.$notin[1].') ';
				}
				
			}
		}
		
		unset($a['table']);unset($a['orderby']);unset($a['limit']);unset($a['as']);unset($a['like']);unset($a['fields']);unset($a['isall']);unset($a['notin']);unset($a['notempty']);unset($a['empty']);
		$pages='';
		$w = ' 1=1 ';
		$ispage=false;
		
		foreach($a as $k=>$v){
			if(strpos($v,'$')===FALSE){
				//$v = str_ireplace("'",'',$v);
				$v = trim($v,"'");
			}
			
			if($k=='ispage'){
				$ispage=true;
			}else if($k=='tid'){
				
				if(strpos($a['tid'],',')!==false){
					
					if($isall){
						$a['tid'] = trim($a['tid'],"'");
						$tids=explode(',',$a['tid']);
						$ss = [];
						foreach($tids as $s){
							$ss[] = '  tid in(\'.implode(",",$classtypedata['.$s.']["children"]["ids"]).\') ';
						}
						$w.=' and ('.implode(' or ',$ss).' ) ';
					}else{
						$w.=' and tid in('.trim($a['tid'],"'").') ';
					}
					
					
				}else{
					
					if(strpos($a['tid'],'$')!==false){
						if($isall){
							
							$w.= ' and  tid in(\'.implode(",",$classtypedata["'.trim($v,"'").'"]["children"]["ids"]).\') ';
						}else{
							$w.="and tid='.".trim($v,"'").".' ";
						}
						
						
					}else{
						
						if($isall){
							$w.= ' and  tid in(\'.implode(",",$classtypedata['.trim($v,"'").']["children"]["ids"]).\') ';
						}else{
							$w.="and tid=".$v." ";
						}
						
						
					}
				}
				
				// if($isall && isset($a['tid'])){
					// $w.='or tid in(\'.implode(",",$classtypedata['.$a["tid"].']["children"]["ids"]).\') ';
				// }
			}else{
				if(strpos($v,'$')!==FALSE){
					$w.="and ".$k."='.".trim($v,"'").".' ";
				}else{
					$w.="and ".$k."=\'".$v."\' ";
				}
				
			}
			
			
			
		}
		
		if($notempty){
			//多个字段
			if(strpos($notempty,'|')!==false){
				$notempty = explode('|',$notempty);
				foreach($notempty as $v){
					$w.=' and trim('.$v.') !=""  ';
				}
				
			}else{
				$w.=' and trim('.$notempty.') !=""  ';
			}
			
		}
		if($empty){
			//多个字段
			if(strpos($empty,'|')!==false){
				$empty = explode('|',$empty);
				foreach($empty as $v){
					$w.=' and trim('.$v.') =""  ';
				}
				
			}else{
				$w.=' and trim('.$empty.') =""  ';
			}
			
		}
		
		$w .= $notin_sql;
		$w.= $lk;
		$txt="<?php
		\$table =$db;
		\$w='".$w."';
		\$order=$order;
		\$fields=$fields;
		\$limit=$limit;";
		$as = trim($as,"'");
		if($ispage){
			$txt .="
			\$page = new FrPHP\Extend\Page(\$table);
			\$page->typeurl = 'tpl';
			$".$as."_data = \$page->where(\$w)->fields(\$fields)->orderby(\$order)->limit(\$limit)->page(\$frpage)->go();
			$".$as."_pages = \$page->pageList(3,'?page=');
			$".$as."_sum = \$page->sum;
			$".$as."_listpage = \$page->listpage;
			$".$as."_prevpage = \$page->prevpage;
			$".$as."_nextpage = \$page->nextpage;
			$".$as."_allpage = \$page->allpage;";
			
		}else{
			
			$txt .= "
			$".$as."_data = M(\$table)->findAll(\$w,\$order,\$fields,\$limit);";
			
		}
		$txt.='$n=0;foreach($'.$as.'_data as $'.$as.'_key=> $'.$as.'){
			$n++;
			if(isset($'.$as.'[\'htmlurl\']) && !isset($'.$as.'[\'url\'])){
				if($table==\'classtype\'){
					$'.$as.'[\'url\'] = get_domain().\'/\'.$'.$as.'[\'htmlurl\'].\'.html\';
				}else{
					$'.$as.'[\'url\'] = gourl($'.$as.'[\'id\'],$'.$as.'[\'htmlurl\']);
				}
				
			}
			?>';
		
		return $txt;
		
	}
	
	
}