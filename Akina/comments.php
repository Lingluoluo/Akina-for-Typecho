<?php
/* 把评论里的表情码还原成图片：{{bili}} / ::tieba:: / @(阿鲁) 全部由 OwO.json 驱动 */
function ak_emoji_render($text) {
    if (!$text) return $text;
    static $map = null;
    if ($map === null) {
        $map = [];
        $jf = __TYPECHO_ROOT_DIR__ . '/usr/themes/akina/images/smilies/OwO.json';
        $raw = @file_get_contents($jf);
        $data = $raw ? json_decode($raw, true) : [];
        if (is_array($data)) {
            foreach ($data as $g) {
                if (!isset($g['type']) || $g['type'] !== 'image' || empty($g['container'])) continue;
                foreach ($g['container'] as $it) {
                    if (!empty($it['text']) && !empty($it['icon'])) {
                        $map[$it['text']] = $it['icon'];
                    }
                }
            }
        }
    }
    if (!$map) return $text;
    $keys = array_keys($map);
    usort($keys, function ($a, $b) { return strlen($b) - strlen($a); });
    foreach ($keys as $k) {
        if (strpos($text, $k) === false) continue;
        $text = str_replace($k, '<img class="ak-emoji" src="' . theurl . $map[$k] . '" alt="' . htmlspecialchars($k) . '">', $text);
    }
    return $text;
}
?>
<?php
/* 评论环境：浏览器/系统 图标+名称+版本 + IP 归属地（不显示 IP 本身） */
function ak_ua_parse($ua) {
    $ua = (string)$ua;
    $os = ['name' => '未知系统', 'icon' => 'unknown'];
    $osMap = [
        ['Windows NT 10', 'Windows 10/11', 'windows'],
        ['Windows NT 6.3', 'Windows 8.1', 'windows'],
        ['Windows NT 6.1', 'Windows 7', 'windows'],
        ['Windows', 'Windows', 'windows'],
        ['iPad', 'iPadOS', 'apple'],
        ['iPhone', 'iOS', 'apple'],
        ['Mac OS X', 'macOS', 'apple'],
        ['Android', 'Android', 'android'],
        ['Linux', 'Linux', 'linux'],
    ];
    foreach ($osMap as $m) {
        if (stripos($ua, $m[0]) !== false) { $os = ['name' => $m[1], 'icon' => $m[2]]; break; }
    }
    $br = ['name' => '未知浏览器', 'ver' => '', 'icon' => 'unknown'];
    $brMap = [
        ['Edg/', 'Edge', 'edge'],
        ['OPR/', 'Opera', 'opera'],
        ['Firefox/', 'Firefox', 'firefox'],
        ['Chrome/', 'Chrome', 'chrome'],
        ['Safari/', 'Safari', 'safari'],
    ];
    foreach ($brMap as $m) {
        if (stripos($ua, $m[0]) !== false) {
            $ver = '';
            if (preg_match('#' . preg_quote($m[0], '#') . '([0-9]+)#', $ua, $mm)) { $ver = $mm[1]; }
            // Safari 的版本号在 Version/ 里
            if ($m[1] === 'Safari' && preg_match('#Version/([0-9]+)#', $ua, $mm)) { $ver = $mm[1]; }
            $br = ['name' => $m[1], 'ver' => $ver, 'icon' => $m[2]];
            break;
        }
    }
    return [$br, $os];
}

function ak_is_cdn_ip($ip) {
    static $cache = [];
    if (isset($cache[$ip])) return $cache[$ip];
    $db = Typecho_Db::get();
    $r = $db->fetchRow($db->select('ip')->from('preview_cdn_ips')->where('ip = ?', $ip));
    $is = !empty($r);
    if (!$is && $ip) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $net = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
            $r2 = $db->fetchRow($db->select('net')->from('preview_cdn_nets')->where('net = ?', $net));
            $is = !empty($r2);
        }
    }
    $cache[$ip] = $is;
    return $is;
}

/**
 * IP 归属地查询（带 preview_geo 缓存）
 * 表里没有时调用 ip-api.com，成功后写回缓存，避免每次渲染都请求
 */
function ak_geo_lookup($ip)
{
    static $memo = [];
    if (isset($memo[$ip])) {
        return $memo[$ip];
    }
    $memo[$ip] = '';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }
    // 跳过内网/保留地址
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return '';
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 3, 'method' => 'GET'],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $raw = @file_get_contents(
        'http://ip-api.com/json/' . urlencode($ip) . '?lang=zh-CN&fields=status,country,regionName,city',
        false,
        $ctx
    );
    if (!$raw) {
        return '';
    }
    $j = json_decode($raw, true);
    if (!is_array($j) || (isset($j['status']) ? $j['status'] : '') !== 'success') {
        return '';
    }
    $parts = array_filter([
        isset($j['country']) ? $j['country'] : '',
        isset($j['regionName']) ? $j['regionName'] : '',
        isset($j['city']) ? $j['city'] : '',
    ]);
    $loc = implode(' ', $parts);
    if ($loc === '') {
        return '';
    }
    // 写回缓存表
    try {
        $db = Typecho_Db::get();
        $db->query($db->insert('preview_geo')->rows(['ip' => $ip, 'loc' => $loc]));
    } catch (\Throwable $e) {
        // 已存在或写失败都不影响展示
    }
    return $memo[$ip] = $loc;
}

function ak_comment_env($coid) {
    static $cache = [];
    if (isset($cache[$coid])) return $cache[$coid];
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('ip', 'agent')->from('table.comments')->where('coid = ?', $coid));
    $ip = isset($row['ip']) ? $row['ip'] : '';
    $ua = isset($row['agent']) ? $row['agent'] : '';
    list($br, $os) = ak_ua_parse($ua);
    $loc = '未知地区';
    $isCdn = false;
    if ($ip) {
        $g = $db->fetchRow($db->select('loc')->from('preview_geo')->where('ip = ?', $ip));
        if ($g && !empty($g['loc'])) {
            $loc = $g['loc'];
        } else {
            // 缓存未命中 → 在线查询并写回
            $looked = ak_geo_lookup($ip);
            if ($looked !== '') {
                $loc = $looked;
            }
        }
        $isCdn = ak_is_cdn_ip($ip);
    }
    $cache[$coid] = [$br, $os, $loc, $isCdn];
    return $cache[$coid];
}
?>
<?php function threadedComments($comments, $options) {
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
			//如果是文章作者的评论添加 .comment-by-author 样式
            $commentClass .= 'comment-by-author';
        } else {
			//如果是评论作者的添加 .comment-by-user 样式
            $commentClass .= 'comment-by-user';  
        }
    }
	//评论层数大于0为子级，否则是父级
    $commentLevelClass = $comments->_levels > 0 ? ' comment-child' : ' comment-parent';
	$depth = $comments->levels +1;
	//判断博主头像
	if($commentClass=='comment-by-author'){
		$avatar = $avatar2x = theprofile;
	}else{
		$email = $comments->mail;
		//判断邮箱类型选取头像地址
		if(preg_match('|^[1-9]\d{4,10}@qq\.com$|i',$email)){
			$qqnumber = explode("@",$email);
			$avatar = '//q.qlogo.cn/g?b=qq&nk=' . $qqnumber[0]. '&s=100';
			$avatar2x = '//q.qlogo.cn/g?b=qq&nk=' . $qqnumber[0]. '&s=160';
		 }else{
			$avatar = 'https://gravatar.loli.net/avatar/' . md5(strtolower($comments->mail)) . '?s=80&r=X&d=mm';
			$avatar2x = 'https://gravatar.loli.net/avatar/' . md5(strtolower($comments->mail)) . '?s=160&r=X&d=mm';
		}
	}
?>
	<li class="comment <?php $comments->alt('comment-odd', 'comment-even');?> depth-<?php echo $depth ?>" id="li-<?php $comments->theId(); ?>">
		<div id="<?php $comments->theId(); ?>" class="comment_body contents">
			<div class="profile">
				<a href="<?php $comments->url(); ?>"><img alt="<?php $comments->author(false); ?>" src="<?php echo $avatar ?>" srcset="<?php echo $avatar2x ?> 2x" class="avatar avatar-50 photo" height="50" width="50"></a>
			</div>
			<section class="commeta">
				<div class="left">
					<h4 class="author"><a href="<?php $comments->url(); ?>"><img alt="<?php $comments->author(false); ?>" src="<?php echo $avatar ?>" srcset="<?php echo $avatar2x ?> 2x" class="avatar avatar-50 photo" height="50" width="50"/><?php $comments->author(false); ?><span class="isauthor" title="Author"><i class="iconfont">&#xe615;</i></span></a></h4>
				</div>
				<a rel='nofollow' class='comment-reply-link' href='<?php $comments->responseUrl(); ?>' onclick="return TypechoComment.reply('<?php $comments->theId(); ?>', <?php $comments->coid();?>);" aria-label='回复给<?php $comments->author(false); ?>'>回复</a>
				<div class="right">
					<div class="info"><time datetime="<?php $comments->date('Y-m-d'); ?>"><?php $comments->date('Y年m月d日'); ?></time></div>
					<div class="cmt-env"><?php list($__br,$__os,$__loc,$__is_cdn)=ak_comment_env($comments->coid); ?>（<span class="ua"><img class="ua-ico" src="<?php echo theurl; ?>images/ua/<?php echo $__br['icon']; ?>.svg" alt=""><?php echo $__br['name']; ?><?php if($__br['ver']){ echo ' ' . $__br['ver']; } ?></span><span class="ua"><img class="ua-ico" src="<?php echo theurl; ?>images/ua/<?php echo $__os['icon']; ?>.svg" alt=""><?php echo $__os['name']; ?></span>）来自：<?php if ($__is_cdn): ?><span class="cmt-loc cmt-cdn" title="这条评论记录到的是 CDN 回源 IP（<?php echo $__loc; ?> 是节点位置），不是访客真实位置">CDN 节点<?php echo $__loc !== "未知地区" ? '（节点：' . $__loc . '）' : ""; ?><span class="cmt-cdn-tag">访客位置未知</span></span><?php else: ?><span class="cmt-loc"><?php echo $__loc; ?></span><?php endif; ?></div>
				</div>
			</section>
			<div class="body">
				<p>
					<?php get_commentReply_at($comments->coid); ?>                                   <!-- 评论@ -->
					<?php $cos = preg_replace('#</?[p|P][^>]*>#','',$comments->content); echo ak_emoji_render($cos);?> <!-- 评论内容 -->
				</p>
			</div>
		</div>
		<?php if ($comments->children){ ?>
		<!-- 嵌套评论代码 -->
		<div class="children">
			<?php $comments->threadedComments($options); ?>
		</div>
		<?php } ?>
	</li>
<?php } ?>

<section id="comments" class="comments">
	<!-- 隐藏评论 -->
	<div class="commentwrap">
		<div class="notification"><i class="iconfont">&#xe610;</i><?php $this->commentsNum(_t('添加评论'), _t('查看沙发'), _t('查看评论')); ?></div>
	</div>
		<!-- 输出评论信息 -->
	<div class="comments-main">
		<h3 id="comments-list-title">Comments | <a ><?php $this->commentsNum(_t('NOTHING'), _t('<span class="noticom">1</span>条评论'), _t('<span class="noticom">%d</span>条评论')); ?></a></h3> 
		<div id="loading-comments"><span></span></div>
		<!-- 评论内容 -->
	<div id="comments-ajax">
	<?php $this->comments()->to($comments); ?>
    <?php $comments->listComments(); ?>
	</div>
	<?php if ($comments->have()): ?>
		<!-- 评论翻页 -->
		<nav id="comments-navi">
			<?php $comments->pageNav('←','→','1','...'); ?>
		</nav>
	<?php endif; ?>
		<!--评论框-->
		<!-- 判断设置是否允许对当前文章进行评论 -->
		<?php if($this->allow('comment')): ?>
		<div id="respond_box">
			<div id="<?php $this->respondId(); ?>" class="comment-respond">
				<div class="cancel-comment-reply">
					<?php $comments->cancelReply(); ?>
				</div>
				<!-- 输入表单开始 -->
				<form action="<?php $this->commentUrl() ?>" method="post" id="commentform">
					<!-- 如果当前用户已经登录 -->
					<?php if($this->user->hasLogin()): ?>
					<p>登录者： <a href="<?php $this->options->adminUrl(); ?>"><?php $this->user->screenName(); ?></a>&nbsp;&nbsp;<a href="<?php $this->options->logoutUrl(); ?>"title="退出">[ 退出 ] →</a></p>
					<!-- 若当前用户未登录 -->
					<?php else: ?>
					<div class="author-updown">Welcome back ,&nbsp;<?php $this->remember('author'); ?>&nbsp;<a id="toggle-comment-info">[ 修改 ] ↓</a></div>
					<div id="comment-author-info">
						<input type="text" name="author" id="author" class="commenttext" placeholder="Name"  value="<?php $this->remember('author'); ?>" size="22" tabindex="1" placeholder="Name" />
						<label for="author"></label>
					
						<input type="text" name="mail" id="mail" class="commenttext" value="<?php $this->remember('mail'); ?>" size="22" placeholder="Email" tabindex="2" />
						<label for="mail"></label>
					
						<input type="text" name="url" id="url" class="commenttext" value="<?php $this->remember('url'); ?>" size="22"placeholder="http://"  tabindex="3" />
						<label for="url"></label>
					</div>
					<div class="clear"></div>
					<?php endif; ?>
					<p><textarea name="text" id="comment" class="OwO-textarea" placeholder="come on baby !" tabindex="4" cols="50" rows="5"></textarea></p>
					<?php if (!$this->user->hasLogin() && function_exists('ak_comment_captcha_fields')): ?>
					<?php if (isset($_GET['ccap']) && $_GET['ccap'] === 'fail'): ?>
					<p class="ak-ccap-msg">验证码错误，请重试</p>
					<?php endif; ?>
					<span id="ak-ccap-box"><?php echo ak_comment_captcha_fields(); ?></span>
					<?php endif; ?>
					<div class="com-footer">
						<input class="submit" name="submit" type="submit" id="submit" tabindex="5" value="发表评论" />
						<input type='hidden' name='comment_post_ID' value='58' id='comment_post_ID' />
						<input type='hidden' name='comment_parent' id='comment_parent' value='0' />
						<input type="hidden" name="_" value="<?php echo $this->security->getToken($this->request->getRequestUrl()); ?>" />
							<!--表情-->
						<div class="OwO"></div>
						<script type="text/javascript" src="<?php echo theurl; ?>js/OwO.js" defer="defer"></script>
					</div>
				</form>
			<?php else: ?>
				<style>
					.comment-reply-link {
					    display: none !important;
					}
				</style>
				<section class="author-profile">
					<p><i class="iconfont">&#xe61a;</i>该文章已经关闭评论</p>
				</section>
			<?php endif; ?>
			<div class="clear"></div>
			</div>
		</div>
	</div>
</section>
