<?php
/**
 * Akina For Typecho移植于WordPress的Akina模板，原作者为 Fuzzz 
 * 
 * @package Akina For Typecho
 * @author 子虚之人
 * @version 4.1.4
 * @link https://zhebk.cn/
 */
 if (!defined('__TYPECHO_ROOT_DIR__')) exit;
 $this->need('header.php');
// 文章置顶
if($this->options->sticky){
	$sticky = $this->options->sticky; //置顶的文章cid，按照排序输入, 请以半角逗号或空格分隔
	if($sticky && $this->is('index') || $this->is('front')){
    $sticky_cids = explode(',', strtr($sticky, ' ', ','));//分割文本 
    $sticky_html = "<span style='color:#ff6d6d;font-weight:600'>[置顶] </span>"; //置顶标题的 html css
    $db = Typecho_Db::get();
    $pageSize = $this->options->pageSize;
    $select1 = $this->select()->where('type = ?', 'post');
    $select2 = $this->select()->where('type = ? AND status = ? AND created < ?', 'post','publish',time());
    //清空原有文章的列队
    $this->row = [];
    $this->stack = [];
    $this->length = 0;
    $order = '';
    foreach($sticky_cids as $i => $cid) {
      if($i == 0) $select1->where('cid = ?', $cid);
      else $select1->orWhere('cid = ?', $cid);
      $order .= " when $cid then $i";
      $select2->where('table.contents.cid != ?', $cid); //避免重复
    }
    if ($order) $select1->order('', "(case cid$order end)"); //置顶文章的顺序 按 $sticky 中 文章ID顺序
    if ($this->_currentPage == 1) foreach($db->fetchAll($select1) as $sticky_post){ //首页第一页才显示
      $sticky_post['sticky'] = $sticky_html;
      $this->push($sticky_post); //压入列队
    }
    $uid = $this->user->uid; //登录时，显示用户各自的私密文章
    if($uid) $select2->orWhere('authorId = ? AND status = ?',$uid,'private');
    $sticky_posts = $db->fetchAll($select2->order('table.contents.created', Typecho_Db::SORT_DESC)->page($this->_currentPage, $this->parameter->pageSize));
    foreach($sticky_posts as $sticky_post) $this->push($sticky_post); //压入列队
    $this->setTotal($this->getTotal()-count($sticky_cids)); //置顶文章不计算在所有文章内
	}
}
?>
<!-- 判断是否搜索 -->
<?php if(!$this->is('index') && !$this->is('front')): ?>
  <!-- 透明导航栏后调整间距 -->
  <?php if (!empty($this->options->menu) && in_array('transparent', $this->options->menu) ): ?>
  <style>
    .site-main {
      padding: 160px 0 0;
    }
    @media (max-width: 860px){
      .site-main {
      padding: 80px 0 0;
    }
    }
  </style>
  <?php endif ?>
	<div class="blank"></div>
	<div class="headertop"></div>
	<div class=""></div>
	<div id="content" class="site-content">
		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">
				<!-- 判断搜索是否有结果-是 -->	
				<?php if ($this->have()): ?>
					<header class="page-header">
						<h1 class="page-title">搜索结果: <span><?php $this->archiveTitle(array('category'=>_t('分类“%s”下的文章'),'search'=>_t('包含关键字“%s”的文章'),'tag' =>_t('标签“%s”下的文章'),'author'=>_t('%s 的主页')), '', ''); ?></span></h1>
						<span><?php echo '找到'.$this->getTotal().'篇';?></span>
					</header>
				<!-- 判断搜索是否有结果-否 -->	
				<?php else: ?>
					<header class="page-header">
						<h1 class="page-title">搜索结果: <span><?php $this->archiveTitle(array('category'=>_t('分类“%s”下暂无文章'),'search'=>_t('暂无包含关键字“%s”的文章'),'tag' =>_t('标签“%s”下暂无文章'),'author'=>_t('%s 的主页')), '', ''); ?></span></h1>
					</header>
					<div class="page-content">
						<div class="sorry">
							<p>抱歉, 没有找到你想要的文章. 看看其他文章吧.</p>
							<ul class="search-no-reasults"><?php getRandomPosts('5');?></ul>
						</div>
					</div>
				<?php endif; ?>
<!-- 不是搜索显示主页 -->
<?php else: ?>
	<div class="blank"></div>
		<div class="headertop">
			<!-- 首页大图 -->
			<div id="centerbg" style="background-image: url(<?php echo authorProfile($this->options->headimg,theurl);?>);">
				<!-- 左右倾斜 -->
				<div class="slant-left"></div>
				<div class="slant-right"></div>
				<!-- 博主信息 -->
				<div class="focusinfo">
					<!-- 头像 -->
					<div class="header-tou" >
					<a href="<?php $this->options ->siteUrl(); ?>"><img src="<?php echo theprofile ?>"></a>
					</div>
					<?php
                        // 标语、社交、邮箱、小红书全都为空时，整个卡片不渲染
                        $__slogan = trim((string)$this->options->headerinfo);
                        $__hasSocial = false;
                        foreach (array('SINA','Wechat','QQnum','coolapk','Qzone','Github','Bilibili','Music') as $__k) {
                            if ($this->options->{$__k}) { $__hasSocial = true; break; }
                        }
                        if (ak_local('xiaohongshu') || ak_local('email')) { $__hasSocial = true; }
                        $__hasCard = ($__slogan !== '' || $__hasSocial);
                    ?>
                    <?php if ($__hasCard): ?>
                    <div class="ak-focus-card">
					<?php if ($__slogan !== ''): ?><p class="ak-slogan"><?php $this->options->headerinfo() ?></p><?php endif; ?>
					<ul class="top-social">
						<?php
							//微博
							if ($this->options->SINA){
								echo '<li><a href="'.$this->options->SINA.'" target="_blank" rel="nofollow" class="social-sina"><img src="'.theurl.'images/sina.png"/></a></li>';
							}
							//微信
							if ($this->options->Wechat){
								echo '<li class="qq"><img style="cursor: pointer" src="'.theurl.'images/wechat.png"/></a><div class="qqInner">'.$this->options->Wechat.'</div></li>';
							}
							//QQ
							if ($this->options->QQnum){
								echo '<li class="qq"><a href="http://wpa.qq.com/msgrd?v=3&uin='.$this->options->QQnum.'&site=qq&menu=yes" target="_blank" rel="nofollow" ><img src="'.theurl.'images/qq.png"/></a>
										<div class="qqInner">'.$this->options->QQnum.'</div>
									</li>';
							}
							//酷安
							if ($this->options->coolapk){
								echo '<li class="qq"><a href="'.$this->options->coolapkLink.'" target="_blank" rel="nofollow" ><img src="'.theurl.'images/coolapk.png"/></a>
										<div class="qqInner">'.$this->options->coolapk.'</div>
									</li>';
							}
							//QQ空间
							if ($this->options->Qzone){
								echo '<li><a href="'.$this->options->Qzone.'" target="_blank" rel="nofollow" class="social-qzone"><img src="'.theurl.'images/qzone.png"/></a></li>';
							}
							//Github
							if ($this->options->Github){
								echo '<li><a href="'.$this->options->Github.'" target="_blank" rel="nofollow" class="social-github"><img src="'.theurl.'images/github.png"/></a></li>';
							}
							//Bilibili
							if ($this->options->Bilibili){
								echo '<li><a href="'.$this->options->Bilibili.'" target="_blank" rel="nofollow" class="social-bilibili"><img src="'.theurl.'images/bilibili.png"/></a></li>';
							}
							//网易云音乐
							if ($this->options->Music){
								echo '<li><a href="https://music.163.com/#/user/home?id='.$this->options->Music.'" target="_blank" rel="nofollow" class="social-bilibili"><img src="'.theurl.'images/music.png"/></a></li>';
							}
						?>
						<?php if (ak_local('xiaohongshu')): ?><li><a href="<?php echo ak_local('xiaohongshu'); ?>" target="_blank" rel="nofollow noopener" class="social-xiaohongshu" title="小红书"><img src="<?php echo theurl; ?>images/xiaohongshu.svg" alt="小红书"></a></li><?php endif; ?>
						<?php if (ak_local('email')): ?><li><a href="mailto:<?php echo ak_local('email'); ?>" class="social-mail" title="<?php echo ak_local('email'); ?>"><img src="<?php echo theurl; ?>images/mail.svg" alt="邮箱"></a></li><?php endif; ?>
					</ul>
                    <?php endif; ?>
				</div>
			</div>
			<!-- 首页大图结束 -->
		</div>
	<div class=""></div>
	<div id="content" class="site-content">
	<div id="primary" class="content-area">
		<main id="main" class="site-main indexMain" role="main">
		<h1 class="main-title">文章</h1>
<!-- 结束搜索判断 -->
<?php endif; ?>
		<!-- 开始文章循环输出 -->
    <?php $deuIndex = 1; ?>
		<?php while($this->next()): ?>
		<article class="post post-list<?php echo ($this->fields->dtMode || $this->type == 'dynamic') ? ' ak-moment' : ' ak-card'; ?>" itemscope="" itemtype="http://schema.org/BlogPosting">
<?php if ($this->fields->dtMode || $this->type == 'dynamic'): ?>
			<?php
				$__cmt = (int)$this->commentsNum;
			?>
			<div class="ak-moment-head">
				<div class="ak-moment-ava">
					<a href="<?php $this->permalink() ?>"><img src="<?php echo theprofile; ?>" alt="avatar"></a>
					<span class="ak-lv">LV<?php echo max(1, min(6, (int)ceil($__cmt / 5) + 1)); ?></span>
				</div>
				<div class="ak-moment-who">
					<span class="ak-moment-name"><?php $this->author(); ?></span>
					<span class="ak-moment-sub"><?php $this->title(); ?></span>
				</div>
				<div class="ak-moment-right">
					<span class="m"><i class="iconfont">&#xe610;</i><?php echo $__cmt; ?></span>
					<span class="m"><i class="iconfont">&#xe604;</i><?php $this->date('Y-m-d H:i'); ?></span>
				</div>
			</div>
			<div class="ak-moment-body">
				<?php $this->content(); ?>
			</div>
			<div class="ak-moment-foot">
				<a href="<?php $this->permalink() ?>"><?php $this->title(); ?></a>
				<span class="sep">|</span>
				<?php $this->author(); ?>
				<span class="sep">|</span>
				<?php $this->category(' / '); ?>
			</div>
<?php else: ?>
			<?php
				$__words  = mb_strlen(strip_tags($this->content), 'UTF-8');
				$__mins   = max(1, (int)ceil($__words / 300));
				$__excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags($this->content)));
				$__excerpt = mb_substr($__excerpt, 0, 120, 'UTF-8');
				$__cover = '';
				if ($this->fields->icon) { $__cover = $this->fields->icon; }
				if (!$__cover && $this->fields->thumbnail) { $__cover = $this->fields->thumbnail; }
				if (!$__cover) { $__cover = theurl . 'images/postbg/' . (($this->cid % 3) + 1) . '.jpg'; }
			?>
			<div class="ak-card-meta">
				<span class="m"><i class="iconfont">&#xe604;</i>发布于 <?php $this->date('Y-m-d'); ?></span>
				<span class="m"><i class="iconfont">&#xe614;</i><?php echo Postviews($this); ?> 热度</span>
				<span class="m"><?php $this->category(' / '); ?></span>
				<span class="m"><?php echo $__words; ?> 字</span>
				<span class="m"><?php echo $__mins; ?> 分钟</span>
			</div>
			<div class="ak-card-body">
				<div class="ak-card-summary">
					<h4>摘要</h4>
					<p><?php echo htmlspecialchars($__excerpt); ?>&hellip;</p>
				</div>
				<a class="ak-card-cover" href="<?php $this->permalink() ?>">
					<img src="<?php echo $__cover; ?>" alt="<?php $this->title() ?>" loading="lazy">
					<div class="ak-card-shade"></div>
					<h2 class="ak-card-title"><?php $this->sticky(); ?><?php $this->title() ?></h2>
				</a>
			</div>
<?php endif; ?>
		</article>
		<?php endwhile; ?>
		<!-- 结束文章循环输出 -->
		<!-- 翻页按钮 -->
		<nav class="navigator">
			<?php $this->pageLink('<i class="iconfont">&#xe611;</i>'); ?>	
			<i class="navnumber"><?php if($this->_currentPage>1) echo $this->_currentPage;  else echo 1;?> / <?php echo ceil($this->getTotal() / $this->parameter->pageSize); ?></i>
			<?php $this->pageLink('<i class="iconfont">&#xe60f;</i>','next'); ?>
		</nav>
	</main>
	<div id="pagination"><?php $this->pageLink('加载更多','next'); ?></div>
</div>
</div>
<!-- 结束主页内容 -->
</div>
</section>
<!-- 页底信息 -->
<?php $this->need('footer.php'); ?>
