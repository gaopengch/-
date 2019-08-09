<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<title><?php echo C('title');?></title>
<link rel="stylesheet" href="/Public/Install/css/css.css"/>
</head>
<body>
<div class="wrap">
  <div class="width top">
	<div class="logo left"><img src="/Public/Install/img/logo.png" width="324" height="41"></div>
  <div class="Can-finish right clear center size16">只需3步，即可完成安装</div>
</div>
  
  
	 
  <div class="content width">

  <section class="section">
    <div class="step">
      <ul>
        <li class="on"><em>1</em>检测环境</li>
        <li class="on"><em>2</em>创建数据</li>
        <li class="current"><em>3</em>完成安装</li>
      </ul>
    </div>
    <div class="install" id="log">
    <div style="overflow-x: auto; overflow-y: auto; height: 400px; background-color:#FFF; ">
      <ul id="loginner">
      </ul>
      </div>
    </div>
    <div class="bottom tac"> <a href="javascript:;" class="btn_old"><img src="/Public/Install/images/install/loading.gif" align="absmiddle" />&nbsp;正在安装...</a>
        <div style="color: red">如发现大批数据表创建失败，请将mysql引擎将Myisam 改为Innodb，在install目录删除install.lock,重新安装即可。
        </div>
    </div>

  </section>
  <script src="/Public/Common/js/jquery-1.11.3.min.js"></script>
  <script>
  var data = <?php echo json_encode($_POST);?>;
  var INSTALL_DB = "<?php echo U('installDb', null, null);?>";
  var WHAT_URD	 = "<?php echo U('stepFive');?>";
  </script>
  <script src="/Public/Install/js/install.js?a=6"></script>
  </div>
  
  
  

  
  <script type="text/javascript" src="/Public/Install/js/index.js"></script>
 <div class="bottom  width center">
  <p>ShopsN开源商城官网     ShopsN开源商城论坛</p>
  <p> Powered by ShopsN开源商城 B2C单商户版   www.shopsn.net </p>
</div>
</div>
</body>
</html>