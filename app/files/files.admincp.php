<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
*/
class filesAdmincp{
    public function __construct() {
	    $this->from		= iSecurity::escapeStr($_GET['from']);
	    $this->callback	= iSecurity::escapeStr($_GET['callback']);
		$this->click	= iSecurity::escapeStr($_GET['click']);
        $this->target   = iSecurity::escapeStr($_GET['target']);
        $this->format   = iSecurity::escapeStr($_GET['format']);
    	$this->id		= (int)$_GET['id'];
	    $this->callback OR $this->callback	= 'icms';
        $this->upload_max_filesize = get_cfg_var("upload_max_filesize");
    }
    /**
     * [上传文件页]
     * @return [type] [description]
     */
	public function do_add(){
		$this->id && $rs = iFS::get_filedata('id',$this->id);
		include admincp::view("files.add");
	}
    /**
     * [批量上传]
     * @return [type] [description]
     */
	public function do_multi(){
		$file_upload_limit	= $_GET['UN']?$_GET['UN']:100;
		$file_queue_limit	= $_GET['QN']?$_GET['QN']:10;
		$file_size_limit	= (int)$this->upload_max_filesize;
        $file_size_limit OR iUI::alert("检测到系统环境脚本上传文件大小限制为{$this->upload_max_filesize},请联系管理员");
        stristr($this->upload_max_filesize,'m') && $file_size_limit    = $file_size_limit*1024;
		include admincp::view("files.multi");
	}
	public function do_iCMS(){
    	$sql='WHERE 1=1 ';
        if($_GET['keywords']) {
            if($_GET['st']=="filename") {
                $sql.=" AND `filename` REGEXP '{$_GET['keywords']}'";
            }else if($_GET['st']=="indexid") {
                $sql.=" AND `indexid`='{$_GET['keywords']}'";
            }else if($_GET['st']=="userid") {
                $sql.=" AND `userid` = '{$_GET['keywords']}'";
            }else if($_GET['st']=="ofilename") {
                $sql.=" AND `ofilename` REGEXP '{$_GET['keywords']}'";
            }else if($_GET['st']=="size") {
                $sql.=" AND `size` REGEXP '{$_GET['keywords']}'";
            }
        }
		$_GET['indexid'] 	&& $sql.=" AND `indexid`='{$_GET['indexid']}'";
        $_GET['starttime'] 	&& $sql.=" and `time`>=UNIX_TIMESTAMP('".$_GET['starttime']." 00:00:00')";
        $_GET['endtime'] 	&& $sql.=" and `time`<=UNIX_TIMESTAMP('".$_GET['endtime']." 23:59:59')";

        isset($_GET['userid']) 	&& $uri.='&userid='.(int)$_GET['userid'];

        $orderby	= $_GET['orderby']?iSecurity::escapeStr($_GET['orderby']):"id DESC";
        $maxperpage = $_GET['perpage']>0?(int)$_GET['perpage']:50;
		$total		= iCMS::page_total_cache("SELECT count(*) FROM `#iCMS@__files` {$sql}","G");
        iUI::pagenav($total,$maxperpage,"个文件");
        $rs     = iDB::all("SELECT * FROM `#iCMS@__files` {$sql} order by {$orderby} LIMIT ".iUI::$offset." , {$maxperpage}");
        $_count = count($rs);
        $widget = array('search'=>1,'id'=>1,'uid'=>1,'index'=>1);
    	include admincp::view("files.manage");
    }
    /**
     * [流数据上传]
     * @return [type] [description]
     */
    public function do_IO(){
        $udir      = iSecurity::escapeStr($_GET['udir']);
        $name      = iSecurity::escapeStr($_GET['name']);
        $ext       = iSecurity::escapeStr($_GET['ext']);
        iFS::check_ext($ext,0) OR iUI::json(array('state'=>'ERROR','msg'=>'不允许的文件类型'));
        iFS::$ERROR_TYPE = true;
        $_GET['watermark'] OR iFile::$watermark = false;
        $F = iFS::IO($name,$udir,$ext);
        $F ===false && iUI::json(iFS::$ERROR);
        iUI::json(array(
            "value"    => $F["path"],
            "url"      => iFS::fp($F['path'],'+http'),
            "fid"      => $F["fid"],
            "fileType" => $F["ext"],
            "image"    => in_array($F["ext"],array('gif','jpg','jpeg','png'))?1:0,
            "original" => $F["oname"],
            "state"    => ($F['code']?'SUCCESS':$F['state'])
        ));
    }
    /**
     * [上传文件]
     * @return [type] [description]
     */
    public function do_upload(){
//iFile::$check_data = true;
    	$_POST['watermark'] OR iFile::$watermark = false;
        iFS::$ERROR_TYPE = true;
    	if($this->id){
            iFS::$data = iFile::get('id',$this->id);
            $F = iFS::upload('upfile');
            if($F && $F['size']!=iFS::$data->size){
                iDB::query("update `#iCMS@__files` SET `size`='".$F['size']."' WHERE `id` = '$this->id'");
            }
    	}else{
            $udir = ltrim($_POST['udir'],'/');
            $F    = iFS::upload('upfile',$udir);
    	}
        $array = ($F===false)?iFS::$ERROR:array(
            "value"    => $F["path"],
            "url"      => iFS::fp($F['path'],'+http'),
            "fid"      => $F["fid"],
            "fileType" => $F["ext"],
            "image"    => in_array($F["ext"],array('gif','jpg','jpeg','png'))?1:0,
            "original" => $F["oname"],
            "state"    => ($F['code']?'SUCCESS':$F['state'])
        );
		if($this->format=='json'){
	    	iUI::json($array);
		}else{
			iUI::js_callback($array);
		}
    }
    /**
     * [下载远程图片]
     * @return [type] [description]
     */
    public function do_download(){
        iFile::$userid   = false;
        $rs            = iFS::get_filedata('id',$this->id);
        $FileRootPath  = iFS::fp($rs->filepath,"+iPATH");
        iFS::check_ext($rs->filepath,true) OR iUI::alert('文件类型不合法!');
        iFile::$userid = members::$userid;
        $fileresults   = iHttp::remote($rs->ofilename);
    	if($fileresults){
    		iFS::mkdir(dirname($FileRootPath));
    		iFS::write($FileRootPath,$fileresults);
            iFile::$watermark = !isset($_GET['unwatermark']);
            iFS::hook('write',array($FileRootPath,$rs->ext));

    		$_FileSize	= strlen($fileresults);
    		if($_FileSize!=$rs->size){
	    		iDB::query("update `#iCMS@__files` SET `size`='$_FileSize' WHERE `id` = '$this->id'");
    		}
    		iUI::success("{$rs->ofilename} <br />重新下载到<br /> {$rs->filepath} <br />完成",'js:1',3);
    	}else{
    		iUI::alert("下载远程文件失败!",'js:1',3);
    	}
    }
    public function do_batch(){
        $idArray = (array)$_POST['id'];
        $idArray OR iUI::alert("请选择要删除的文件");
        $ids     = implode(',',$idArray);
        $batch   = $_POST['batch'];
    	switch($batch){
    		case 'dels':
				iUI::$break	= false;
	    		foreach($idArray AS $id){
	    			$this->do_del($id);
	    		}
	    		iUI::$break	= true;
				iUI::success('文件全部删除完成!','js:1');
    		break;
		}
	}
    public function do_del($id = null){
        $id ===null && $id = $this->id;
        $id OR iUI::alert("请选择要删除的文件");
        $indexid = (int)$_GET['indexid'];
        $sql     = isset($_GET['indexid'])?"AND `indexid`='$indexid'":"";
        $rs      = iDB::row("SELECT * FROM `#iCMS@__files` WHERE `id` = '$id' {$sql} LIMIT 1;");
    	if($rs){
            $rs->filepath = rtrim($rs->path,'/').'/'.$rs->filename.'.'.$rs->ext;
            $FileRootPath = iFS::fp($rs->filepath,"+iPATH");
            iDB::query("DELETE FROM `#iCMS@__files` WHERE `id` = '$id' {$sql};");
	    	if(iFS::del($FileRootPath)){
                $msg = 'success:#:check:#:文件删除完成!';
	    		$_GET['ajax'] && iUI::json(array('code'=>1,'msg'=>$msg));
	    	}else{
	    		$msg	= 'warning:#:warning:#:找不到相关文件,文件删除失败!<hr/>文件相关数据已清除';
	    		$_GET['ajax'] && iUI::json(array('code'=>0,'msg'=>$msg));
	    	}
			iUI::dialog($msg,'js:parent.$("#tr'.$id.'").remove();');
    	}
    	$msg	= '文件删除失败!';
    	$_GET['ajax'] && iUI::json(array('code'=>0,'msg'=>$msg));
    	iUI::alert($msg);
    }
    /**
     * [创建目录]
     * @return [type] [description]
     */
    public function do_mkdir(){
    	$name	= $_POST['name'];
        strstr($name,'.')!==false	&& iUI::json(array('code'=>0,'msg'=>'您输入的目录名称有问题!'));
        strstr($name,'..')!==false	&& iUI::json(array('code'=>0,'msg'=>'您输入的目录名称有问题!'));
    	$pwd	= trim($_POST['pwd'],'/');
    	$dir	= iFS::path_join(iPATH,iCMS::$config['FS']['dir']);
    	$dir	= iFS::path_join($dir,$pwd);
    	$dir	= iFS::path_join($dir,$name);
    	file_exists($dir) && iUI::json(array('code'=>0,'msg'=>'您输入的目录名称已存在,请重新输入!'));
    	if(iFS::mkdir($dir)){
    		iUI::json(array('code'=>1,'msg'=>'创建成功!'));
    	}
		iUI::json(array('code'=>0,'msg'=>'创建失败,请检查目录权限!!'));
    }
    /**
     * [选择模板文件页]
     * @return [type] [description]
     */
    public function do_seltpl(){
    	$this->explorer('template');
    }
    /**
     * [浏览文件]
     * @return [type] [description]
     */
    public function do_browse(){
    	$this->explorer(iCMS::$config['FS']['dir']);
    }
    /**
     * [浏览图片]
     * @return [type] [description]
     */
    public function do_picture(){
    	$this->explorer(iCMS::$config['FS']['dir'],array('jpg','png','gif','jpeg'));
    }
    /**
     * [图片编辑器]
     * @return [type] [description]
     */
    public function do_editpic(){
        $pic = iSecurity::escapeStr($_GET['pic']);
        //$pic OR iUI::alert("请选择图片!");
        if($pic){
            $src       = iFS::fp($pic,'+http')."?".time();
            $srcPath   = iFS::fp($pic,'+iPATH');
            $fsInfo    = iFS::info($pic);
            $file_name = $fsInfo->filename;
            $file_path = $fsInfo->dirname;
            $file_ext  = $fsInfo->extension;
            $file_id   = 0;
            $rs        = iFS::get_filedata('filename',$file_name);
            if($rs){
                $file_path = $rs->path;
                $file_id   = $rs->id;
                $file_ext  = $rs->ext;
            }
        }else{
            $file_name= md5(uniqid());
            $src      = false;
            $file_ext = 'jpg';
        }
        if($_GET['indexid']){
            $rs = iDB::all("SELECT * FROM `#iCMS@__files` where `indexid`='{$_GET['indexid']}' order by `id` ASC LIMIT 100");
            foreach ((array)$rs as $key => $value) {
                $filepath = $value['path'] . $value['filename'] . '.' . $value['ext'];
                $src[] = iFS::fp($filepath,'+http')."?".time();
            }
        }
        if($_GET['pics']){
            $src = explode(',', $_GET['pics']);
            if(count($src)==1){
                $src = $_GET['pics'];
            }
        }
        $max_size  = (int)$this->upload_max_filesize;
        stristr($this->upload_max_filesize,'m') && $max_size = $max_size*1024*1024;
        include admincp::view("files.editpic");
    }
    /**
     * [预览]
     * @return [type] [description]
     */
    public function do_preview(){
        $_GET['pic'] && $src = iFS::fp($_GET['pic'],'+http');
        include admincp::view("files.preview");
    }
    /**
     * [删除目录]
     * @return [type] [description]
     */
    public function do_deldir(){
        $_GET['path'] OR iUI::alert("请选择要删除的目录");
        strpos($_GET['path'], '..') !== false && iUI::alert("目录路径中带有..");

        $hash         = md5($_GET['path']);
        $dirRootPath = iFS::fp($_GET['path'],'+iPATH');

        if(iFS::rmdir($dirRootPath)){
            $msg    = 'success:#:check:#:目录删除完成!';
            $_GET['ajax'] && iUI::json(array('code'=>1,'msg'=>$msg));
        }else{
            $msg    = 'warning:#:warning:#:找不到相关目录,目录删除失败!';
            $_GET['ajax'] && iUI::json(array('code'=>0,'msg'=>$msg));
        }
        iUI::dialog($msg,'js:parent.$("#'.$hash.'").remove();');
    }
    /**
     * [删除文件]
     * @return [type] [description]
     */
    public function do_delfile(){
        $_GET['path'] OR iUI::alert("请选择要删除的文件");
        strpos($_GET['path'], '..') !== false && iUI::alert("文件路径中带有..");

        $hash         = md5($_GET['path']);
        $FileRootPath = iFS::fp($_GET['path'],'+iPATH');
        if(iFS::del($FileRootPath)){
            $msg    = 'success:#:check:#:文件删除完成!';
            $_GET['ajax'] && iUI::json(array('code'=>1,'msg'=>$msg));
        }else{
            $msg    = 'warning:#:warning:#:找不到相关文件,文件删除失败!';
            $_GET['ajax'] && iUI::json(array('code'=>0,'msg'=>$msg));
        }
        iUI::dialog($msg,'js:parent.$("#'.$hash.'").remove();');
    }
    public function explorer($dir=NULL,$type=NULL){
        $res    = iFS::folder($dir,$type);
        $dirRs  = $res['DirArray'];
        $fileRs = $res['FileArray'];
        $pwd    = $res['pwd'];
        $parent = $res['parent'];
        $URI    = $res['URI'];
        $navbar = false;
        include admincp::view("files.explorer");
    }
    public static function modal_btn($title='',$click='file',$target='template_index',$callback='',$do='seltpl',$from='modal'){
        $href = __ADMINCP__."=files&do={$do}&from={$from}&click={$click}&target={$target}&callback={$callback}";
        $_title=$title.'文件';
        $click=='dir' && $_title=$title.'目录';
        return '<a href="'.$href.'" class="btn files_modal" data-toggle="modal" title="选择'.$_title.'"><i class="fa fa-search"></i> 选择</a>';
    }
    public static function pic_btn($callback, $indexid = 0, $title="图片",$ret=false) {
        $ret && ob_start();
        include admincp::view("files.picbtn","files");
        if ($ret) {
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
    }
}
