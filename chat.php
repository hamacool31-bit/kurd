<?php
include 'connect.php';
if (!isLoggedIn()) { header("Location: index.php"); exit(); }
$uid = (int)$_SESSION['user_id'];
$pid = (int)($_GET['product'] ?? 0);
if (!$pid) { header("Location: mobile.php"); exit(); }

$prod = $conn->query("SELECT p.*, u.FirstName, u.LastName, u.id AS seller_id FROM products p LEFT JOIN users u ON u.id=p.added_by WHERE p.id=$pid")->fetch_assoc();
if (!$prod) { header("Location: mobile.php"); exit(); }

$seller_id = (int)$prod['seller_id'];
$is_seller = ($uid === $seller_id);

if ($is_seller) {
    $other_id = (int)($_GET['with'] ?? 0);
    if (!$other_id) {
        $buyers = $conn->query("SELECT DISTINCT u.id, u.FirstName, u.LastName,
            (SELECT message FROM chats WHERE product_id=$pid AND (sender_id=u.id OR receiver_id=u.id) ORDER BY created_at DESC LIMIT 1) as last_msg,
            (SELECT COUNT(*) FROM chats WHERE product_id=$pid AND sender_id=u.id AND receiver_id=$uid AND is_read=0) as unread
            FROM chats c JOIN users u ON u.id = IF(c.sender_id=$uid, c.receiver_id, c.sender_id)
            WHERE c.product_id=$pid AND (c.sender_id=$uid OR c.receiver_id=$uid)
            GROUP BY u.id");
?>
<!DOCTYPE html><html lang="ku" dir="rtl">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>چاتەکان</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Noto Sans Arabic',sans-serif}
:root{--bg:#070912;--s:#0e1018;--bd:rgba(255,255,255,.08);--ac:#7c6fff;--text:#f0f0ff;--muted:rgba(255,255,255,.4)}
body{background:var(--bg);color:var(--text);min-height:100vh}
.topbar{background:rgba(14,16,24,.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--bd);padding:13px 18px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10}
.back{color:var(--ac);text-decoration:none;font-size:1.1rem;font-weight:800}
.title{font-weight:800;font-size:.95rem}
.list{padding:14px}
.item{background:var(--s);border:1px solid var(--bd);border-radius:14px;padding:14px;margin-bottom:10px;display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--text);transition:.2s}
.item:hover{border-color:var(--ac)}
.av{width:44px;height:44px;border-radius:50%;background:var(--bd);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.n{font-weight:800;font-size:.9rem}
.lm{font-size:.75rem;color:var(--muted);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px}
.unb{background:var(--ac);color:#fff;font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;margin-right:auto}
.empty{text-align:center;padding:60px 20px;color:var(--muted)}
</style></head><body>
<div class="topbar">
  <a href="mobile.php" class="back">←</a>
  <div class="title">💬 چاتەکانی <?=htmlspecialchars($prod['brand'].' '.$prod['name'])?></div>
</div>
<div class="list">
  <?php if(!$buyers||!$buyers->num_rows): ?>
    <div class="empty">💬 هیچ کریارێک پەیامی نەنێردووە</div>
  <?php else: while($b=$buyers->fetch_assoc()): ?>
    <a href="chat.php?product=<?=$pid?>&with=<?=$b['id']?>" class="item">
      <div class="av">👤</div>
      <div style="flex:1;min-width:0">
        <div class="n"><?=htmlspecialchars($b['FirstName'].' '.$b['LastName'])?></div>
        <div class="lm"><?=htmlspecialchars($b['last_msg']??'پەیام نییە')?></div>
      </div>
      <?php if($b['unread']>0):?><span class="unb"><?=$b['unread']?></span><?php endif;?>
    </a>
  <?php endwhile; endif;?>
</div></body></html>
<?php exit(); }
    $other=$conn->query("SELECT * FROM users WHERE id=$other_id")->fetch_assoc();
} else {
    $other_id=$seller_id;
    $other=['id'=>$seller_id,'FirstName'=>$prod['FirstName'],'LastName'=>$prod['LastName']];
}

if ($_SERVER['REQUEST_METHOD']==='POST'&&!empty($_POST['msg'])) {
    $msg=$conn->real_escape_string(trim($_POST['msg']));
    $conn->query("INSERT INTO chats (product_id, sender_id, receiver_id, message) VALUES ($pid, $uid, $other_id, '$msg')");
    $sname=$conn->real_escape_string($_SESSION['name']??'کەسێک');
    $pname=$conn->real_escape_string($prod['brand'].' '.$prod['name']);
    $conn->query("INSERT INTO notifications (user_id, type, title, message, product_id) VALUES ($other_id, 'chat', 'پەیامی نوێ 💬', '$sname پەیامت نێردووە لەبارەی $pname', $pid)");
    header("Location: chat.php?product=$pid".($is_seller?"&with=$other_id":"")); exit();
}
$conn->query("UPDATE chats SET is_read=1 WHERE product_id=$pid AND sender_id=$other_id AND receiver_id=$uid");
$msgs=$conn->query("SELECT c.*, u.FirstName FROM chats c JOIN users u ON u.id=c.sender_id WHERE c.product_id=$pid AND ((c.sender_id=$uid AND c.receiver_id=$other_id) OR (c.sender_id=$other_id AND c.receiver_id=$uid)) ORDER BY c.created_at ASC");
?>
<!DOCTYPE html><html lang="ku" dir="rtl">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>چات — <?=htmlspecialchars($prod['brand'].' '.$prod['name'])?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Noto Sans Arabic',sans-serif}
:root{--bg:#070912;--s:#0e1018;--s2:#141520;--bd:rgba(255,255,255,.08);--ac:#7c6fff;--text:#f0f0ff;--muted:rgba(255,255,255,.4)}
body{background:var(--bg);color:var(--text);height:100vh;display:flex;flex-direction:column}
.topbar{background:rgba(14,16,24,.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--bd);padding:12px 16px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10}
.back{color:var(--ac);text-decoration:none;font-size:1.1rem;font-weight:800}
.prod-img{width:44px;height:44px;border-radius:10px;object-fit:cover;background:var(--s2);flex-shrink:0}
.prod-name{font-weight:800;font-size:.9rem}
.prod-sub{font-size:.72rem;color:var(--muted)}
.prod-price{color:#00d4aa;font-weight:800;font-size:.9rem;margin-right:auto}
.msgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px}
.bubble{max-width:72%;padding:10px 14px;border-radius:16px;font-size:.88rem;line-height:1.55;word-break:break-word}
.mine{align-self:flex-start;background:var(--ac);color:#fff;border-bottom-right-radius:4px}
.theirs{align-self:flex-end;background:var(--s2);color:#ccc;border-bottom-left-radius:4px}
.time{font-size:.65rem;opacity:.5;margin-top:4px}
.mine .time{text-align:right}.theirs .time{text-align:left}
.form{background:var(--s);border-top:1px solid var(--bd);padding:10px 12px;display:flex;gap:8px}
.form input{flex:1;background:var(--s2);border:1px solid var(--bd);color:var(--text);border-radius:50px;padding:10px 16px;font-family:inherit;font-size:.88rem;outline:none;transition:.2s}
.form input:focus{border-color:var(--ac)}
.form button{background:var(--ac);border:none;color:#fff;width:42px;height:42px;border-radius:50%;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;flex-shrink:0}
.form button:hover{opacity:.85}
.empty{text-align:center;color:var(--muted);padding:40px;margin:auto}
</style></head>
<body>
<div class="topbar">
  <a href="<?=$is_seller?"chat.php?product=$pid":"mobile.php"?>" class="back">←</a>
  <img src="<?=(!empty($prod['image'])&&file_exists('uploads/'.$prod['image']))?'uploads/'.$prod['image']:'https://via.placeholder.com/44/141520/7c6fff?text=📱'?>" class="prod-img">
  <div style="flex:1">
    <div class="prod-name"><?=htmlspecialchars($prod['brand'].' '.$prod['name'])?></div>
    <div class="prod-sub">چات لەگەڵ: <?=htmlspecialchars($other['FirstName'].' '.$other['LastName'])?></div>
  </div>
  <span class="prod-price">$<?=number_format($prod['price'],2)?></span>
</div>
<div class="msgs" id="msgBox">
  <?php if(!$msgs->num_rows): ?>
    <div class="empty">💬 هیچ پەیامێک نییە — دەستپێ بکە!</div>
  <?php else: while($m=$msgs->fetch_assoc()): $is_mine=($m['sender_id']==$uid); ?>
    <div style="display:flex;flex-direction:column;align-items:<?=$is_mine?'flex-start':'flex-end'?>">
      <div class="bubble <?=$is_mine?'mine':'theirs'?>">
        <?=nl2br(htmlspecialchars($m['message']))?>
        <div class="time"><?=date('H:i',strtotime($m['created_at']))?></div>
      </div>
    </div>
  <?php endwhile; endif; ?>
</div>
<form class="form" method="POST">
  <input type="text" name="msg" placeholder="پەیامێک بنووسە..." autocomplete="off" autofocus>
  <button type="submit">↑</button>
</form>
<script>const mb=document.getElementById('msgBox');mb.scrollTop=mb.scrollHeight;</script>
</body></html>
