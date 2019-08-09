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
  
  

<div class="content width" >
<div style="overflow-x: auto; overflow-y: auto; height: 400px; background-color:#FFF; ">
<table width="96%" border="0" align="center" cellpadding="0" cellspacing="0" style="line-height:30px; margin-top:50px;">
    <tr>
      <td width="14%" height="30"  align="right" ><img src="/Public/Install/img/complete2.jpg" width="71" height="71"></td>
      <td align="left" class="Orange">安装完成，进入后台管理</td>
      </tr>
    <tr>
      <td height="30"  align="right" >&nbsp;</td>
      <td class="gray">请在/install/目录下保留或创建install.lock文件防止重复安装。<br>
        </td>
      </tr>
    <tr>
      <td height="30"  align="right" >&nbsp;</td>
      <td align="center">
      <a href="/index.php" class="Reception left" target="_blank">进入前台</a>
      <a href="/adminprov.php/" class="Backstage left J_install_btn">进入后台</a>
      </td>
      </tr>
  </table></div>
</div>


  
  <script type="text/javascript" src="/Public/Install/js/index.js"></script>
 <div class="bottom  width center">
  <p>ShopsN开源商城官网     ShopsN开源商城论坛</p>
  <p> Powered by ShopsN开源商城 B2C单商户版   www.shopsn.net </p>
</div>
</div>
</body>
</html>