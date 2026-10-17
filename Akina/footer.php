<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
	<!-- 请尊重作者！至少保留主题名称及其超链接，谢谢！ -->
<footer id="colophon" class="site-footer" role="contentinfo">
	<div id="footer" class="site-info ak-footer">
		<div class="ak-footer-quote" id="ak-quote">我想要个潇洒的以后。</div>

		<div class="ak-footer-line">Copyright © <?php echo date("Y");?> by <a href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a> All Rights Reserved.</div>

		<div class="ak-footer-line">
			<a href="https://beian.miit.gov.cn/" target="_blank" rel="nofollow noopener"><?php $this->options->ICP(); ?></a>
		</div>

		<?php if (ak_local('show_moe_icp', false)): ?>
		<div class="ak-footer-line ak-footer-moe">
			<?php if (ak_local('moe_icp_id')): ?>
			<a href="<?php echo ak_local('moe_icp_url', 'https://icp.gov.moe/'); ?>" target="_blank" rel="nofollow noopener">
				<img src="https://travel.moe/images/ico64.png" alt=""> 萌ICP备<?php echo ak_local('moe_icp_id'); ?>号
			</a>
			<?php endif; ?>
			<span class="sep">|</span>
			<a href="https://travel.moe/go.html" title="异次元之旅" target="_blank" rel="noopener">
				<img src="https://travel.moe/images/icon/icon64.png" alt=""> 异次元之旅
			</a>
		</div>
		<?php endif; ?>

		<?php if (ak_local('show_uptime', false)): ?>
		<div class="ak-footer-line">已在风雨飘摇中度过 <span id="ak-sitetime">--</span></div>
		<?php endif; ?>

		<div class="ak-footer-line ak-footer-links">
			<a href="/privacy/">隐私权政策</a>
			<span class="sep">|</span>
			<?php if (ak_local('email')): ?>
			<a href="mailto:<?php echo ak_local('email'); ?>"><?php echo ak_local('email'); ?></a>
			<span class="sep">|</span>
			<?php endif; ?>
			<a href="/links/">友情链接</a>/<a href="/apply/">申请友链</a>
		</div>

		<div class="ak-footer-theme">
			<svg class="ak-sakura" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
				<g fill="#f7b8cf">
					<ellipse cx="12" cy="6" rx="3.1" ry="4.6"/>
					<ellipse cx="12" cy="18" rx="3.1" ry="4.6"/>
					<ellipse cx="6" cy="12" rx="4.6" ry="3.1"/>
					<ellipse cx="18" cy="12" rx="4.6" ry="3.1"/>
					<ellipse cx="7.8" cy="7.8" rx="2.6" ry="2.6"/>
					<ellipse cx="16.2" cy="16.2" rx="2.6" ry="2.6"/>
					<ellipse cx="16.2" cy="7.8" rx="2.6" ry="2.6"/>
					<ellipse cx="7.8" cy="16.2" rx="2.6" ry="2.6"/>
				</g>
				<circle cx="12" cy="12" r="2.2" fill="#fff2f6"/>
			</svg>
			<div class="ak-footer-by">
				<a href="https://github.com/Zisbusy/Akina-for-Typecho" target="_blank" rel="noopener">Akina For Typecho</a>
				<span class="sep">·</span>
				<a href="https://github.com/Lingluoluo/Akina-for-Typecho" target="_blank" rel="noopener">Lingluoluo</a>
				<span class="sep">·</span>
				Powered by Typecho
			</div>
		</div>
	</div>
</footer>

<style>
.ak-footer{padding:34px 16px 26px;text-align:center;font-size:13px;line-height:2;color:#8a96a3}
.ak-footer a{color:#8a96a3;text-decoration:none;border-bottom:none;transition:color .2s}
.ak-footer a:hover{color:#5fc49a}
.ak-footer-quote{
  font-size:14px;color:#7d8894;font-style:italic;margin:0 0 14px;
  min-height:22px;letter-spacing:.5px;
}
.ak-footer-line{margin:2px 0}
.ak-footer-line .sep{margin:0 8px;color:#d3dae1}
.ak-footer-moe img{width:16px;height:16px;vertical-align:-3px;margin-right:3px}
.ak-footer-moe a:last-child img{width:18px;height:18px}
.ak-footer-theme{margin-top:20px;color:#aab3bd}
.ak-sakura{display:block;margin:0 auto 8px;opacity:.85}
.ak-footer-by{font-size:12px;letter-spacing:.6px;color:#aab3bd}
@media (max-width:768px){.ak-footer{font-size:12px;line-height:1.9}}
</style>

<script>
(function () {
    // 运行时长
    var start = new Date('<?php echo ak_local('start_date', '2019-05-25'); ?>T00:00:00+08:00').getTime();
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function tick() {
        var el = document.getElementById('ak-sitetime');
        if (!el) return;
        var t = Date.now() - start;
        if (t < 0) t = 0;
        var d = Math.floor(t / 86400000);
        var h = Math.floor(t % 86400000 / 3600000);
        var m = Math.floor(t % 3600000 / 60000);
        var s = Math.floor(t % 60000 / 1000);
        el.textContent = d + ' 天 ' + pad(h) + ' 小时 ' + pad(m) + ' 分钟 ' + pad(s) + ' 秒';
    }
    tick(); setInterval(tick, 1000);

    // 一言
    var apis = ['https://v1.hitokoto.cn/?c=d&c=i&c=k&encode=json', 'https://api.nmxc.ltd/yiyan/'];
    var el = document.getElementById('ak-quote');
    function load(i) {
        if (!el || i >= apis.length) return;
        fetch(apis[i], { mode: 'cors' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var txt = d.hitokoto || d.text || (typeof d === 'string' ? d : '');
                var from = d.from || d.source || '';
                if (txt) {
                    el.textContent = txt + (from ? ' ——' + from : '');
                } else {
                    load(i + 1);
                }
            })
            .catch(function () { load(i + 1); });
    }
    load(0);
})();
</script>

<div id="mo-nav">
	<div class="m-avatar">
		<img src="<?php echo theprofile ?>">
	</div>
	<div class="m-search">
		<form class="m-search-form" method="post" action="" role="search">
			<input class="m-search-input" type="search" name="s" placeholder="搜索...">
		</form>
	</div>
	<ul id="nav_menu" class="menu">
		<li class="current-menu-item"><a href="<?php $this->options ->siteUrl(); ?>">首页</a></li>
		<li><a href="#">分类</a>
			<ul class="sub-menu">
				<?php $this->widget('Widget_Metas_Category_List')->parse('<li><a href="{permalink}">{name}</a></li>'); ?>
			</ul>
		</li>
		<li><a href="#">更多</a>
			<ul class="sub-menu">
				<?php $this->widget('Widget_Contents_Page_List')->to($pages); ?>
				<?php while($pages->next()): ?>
					<li><a href="<?php $pages->permalink(); ?>"><?php $pages->title(); ?></a></li>
				<?php endwhile; ?>
			</ul>
		</li>
	</ul>
</div>
<!-- 搜索 -->
<form class="js-search search-form search-form--modal" method="post" action="" role="search">
	<div class="search-form__inner">
		<div class="search-div">
			<h1 class="micromb-search">你想搜索什么...</h1>
			<i class="iconfont">&#xe6f0;</i>
			<input class="submit" type="search" name="s" placeholder="Search...">
		</div>
	</div>
</form>
<!-- 搜索结束 -->
<script type="text/javascript">
	var app = {"pjax":""};
	var theurl = "<?php echo theurl; ?>";
	if (!!window.ActiveXObject || "ActiveXObject" in window) { //is IE?
	  alert('请抛弃万恶的IE系列浏览器吧。');
	}
	<?php
    //判断下拉加载
    if (!empty($this->options->menu) && in_array('xl', $this->options->menu)) {$xl = 1;} else {$xl = 0;}
    // 判断导航栏是否透明
    if (!empty($this->options->menu) && in_array('transparent', $this->options->menu)) {$transparent = 1;} else {$transparent = 0;}
  ?>
  var xl = <?php echo $xl ?>;
  var transparent = <?php echo $transparent ?>;
</script>
<?php echo '
<script type="text/javascript" src="'.theurl.'js/jquery.min.js"></script>
<script type="text/javascript" src="'.theurl.'js/jquery.preloader.js"></script>
<script type="text/javascript" src="'.theurl.'js/jquery.pjax.js"></script>
<script type="text/javascript" src="'.theurl.'js/baguetteBox.min.js"></script>
<script type="text/javascript" src="'.theurl.'js/global.js"></script>
<script type="text/javascript" src="'.theurl.'js/prism.js"></script>
<script type="text/javascript" src="'.theurl.'js/SmoothScroll.js"></script>
';?>
<?php $this->footer(); ?>
</body>
</html>
