<?php
include 'connect.php';
if (!isLoggedIn()) { header("Location: index.php"); exit(); }
if (!isset($_SESSION['cart']))  $_SESSION['cart'] = [];
if (!isset($_SESSION['lang']))  $_SESSION['lang'] = 'ku';

if (isset($_GET['lang']))   { $_SESSION['lang']=$_GET['lang']; header("Location: cart.php"); exit(); }
if (isset($_GET['remove'])) {
    $k=array_search((int)$_GET['remove'],$_SESSION['cart']);
    if($k!==false){unset($_SESSION['cart'][$k]);$_SESSION['cart']=array_values($_SESSION['cart']);}
    header("Location: cart.php"); exit();
}
if (isset($_GET['clear']))  { $_SESSION['cart']=[]; header("Location: cart.php"); exit(); }

$lang=$_SESSION['lang']; $isKu=$lang=='ku';
$t=$isKu?[
    'title'=>'سەبەتەی من','empty'=>'سەبەتەکەت بەتاڵە',
    'continue'=>'بەردەوامی کڕین','total'=>'کۆی گشتی',
    'clear'=>'سڕینەوەی هەموو','remove'=>'سڕینەوە',
    'checkout'=>'پارەدان','home'=>'سەرەکی','items'=>'بەرهەم',
    'pay_fib'=>'پارەدان بە FIB','pay_place'=>'پارەدان بەشوێن',
    'address'=>'ئادرەسی تۆ','order'=>'ناردنی داواکاری',
    'success'=>'داواکارییەکەت نێردرا!','fib_hint'=>'ئاپی FIB بکەرەوە و QR بکەوێنە',
    'payment'=>'ڕێگای پارەدان'
]:[
    'title'=>'My Cart','empty'=>'Your cart is empty',
    'continue'=>'Continue Shopping','total'=>'Total',
    'clear'=>'Clear All','remove'=>'Remove',
    'checkout'=>'Checkout','home'=>'Home','items'=>'items',
    'pay_fib'=>'Pay with FIB','pay_place'=>'Pay at Location',
    'address'=>'Your Address','order'=>'Place Order',
    'success'=>'Order placed successfully!','fib_hint'=>'Open FIB app and scan QR',
    'payment'=>'Payment Method'
];

$products=[]; $total=0;
if (!empty($_SESSION['cart'])) {
    $ids=implode(',',array_map('intval',$_SESSION['cart']));
    $r=$conn->query("SELECT * FROM products WHERE id IN ($ids)");
    if($r) while($row=$r->fetch_assoc()){ $products[]=$row; $total+=$row['price']; }
}
$cart_count=count($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="<?=$lang?>" dir="<?=$isKu?'rtl':'ltr'?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=$t['title']?> — MobileShop</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700;800&family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#070912;--s:#0e1018;--s2:#141520;--bd:rgba(255,255,255,.08);--ac:#7c6fff;--ac2:#ff6b9d;--green:#00d4aa;--cyan:#00d4ff;--text:#f0f0ff;--muted:rgba(255,255,255,.4)}
body{background:var(--bg);color:var(--text);min-height:100vh;font-family:'Noto Sans Arabic','Inter',sans-serif}
nav{background:rgba(7,9,18,.88);backdrop-filter:blur(20px);border-bottom:1px solid var(--bd);padding:0 24px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.brand{font-size:1.1rem;font-weight:800;text-decoration:none;background:linear-gradient(135deg,var(--ac),var(--ac2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-r{display:flex;gap:8px}
.nb{background:var(--s2);border:1px solid var(--bd);color:var(--muted);padding:7px 14px;border-radius:50px;text-decoration:none;font-size:.8rem;font-weight:700;transition:.2s}
.nb:hover{border-color:var(--ac);color:var(--ac)}
main{max-width:1160px;margin:0 auto;padding:40px 24px}
.ph{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:32px}
.ph h1{font-size:1.6rem;font-weight:800;display:flex;align-items:center;gap:12px}
.ph-icon{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--ac),var(--ac2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem;flex-shrink:0}
.btn-clear{background:transparent;border:1px solid rgba(255,107,157,.3);color:var(--ac2);padding:8px 16px;border-radius:50px;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:.2s}
.btn-clear:hover{background:var(--ac2);color:#fff;border-color:var(--ac2)}
.grid{display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.list{display:flex;flex-direction:column;gap:10px}
.pcard{background:var(--s);border:1px solid var(--bd);border-radius:16px;padding:14px;display:flex;align-items:center;gap:14px;transition:.25s;animation:fi .4s both ease-out}
@keyframes fi{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.pcard:hover{border-color:rgba(124,111,255,.3);transform:translateY(-2px);box-shadow:0 6px 24px rgba(0,0,0,.3)}
.pimg{width:72px;height:72px;min-width:72px;border-radius:12px;overflow:hidden;background:var(--s2);flex-shrink:0}
.pimg img{width:100%;height:100%;object-fit:cover}
.pinfo{flex:1;min-width:0}
.pname{font-size:.95rem;font-weight:800;margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pmeta{display:flex;flex-wrap:wrap;gap:5px}
.pill{background:var(--s2);border:1px solid var(--bd);color:var(--muted);font-size:.72rem;font-weight:600;padding:3px 9px;border-radius:50px;display:flex;align-items:center;gap:4px}
.pprice{font-size:1.05rem;font-weight:800;color:var(--green);white-space:nowrap;margin:0 8px}
.btn-rm{width:32px;height:32px;border-radius:50%;background:rgba(255,107,157,.1);border:1px solid rgba(255,107,157,.25);color:var(--ac2);display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:.85rem;transition:.2s;flex-shrink:0}
.btn-rm:hover{background:var(--ac2);color:#fff}
/* سمری */
.summary{background:var(--s);border:1px solid var(--bd);border-radius:18px;padding:22px}
.sum-title{font-size:1rem;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.sum-row{display:flex;justify-content:space-between;font-size:.83rem;padding:6px 0;color:var(--muted);border-bottom:1px solid var(--bd)}
.sum-row:last-of-type{border:none}
.sum-total{display:flex;justify-content:space-between;font-size:1rem;font-weight:800;padding:14px 0;border-top:1px solid var(--bd);margin-top:4px}
.sum-total .amt{color:var(--green);font-size:1.2rem}
.pay-label{font-size:.75rem;color:var(--muted);font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin:16px 0 8px}
.pay-btns{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px}
.pb{padding:11px 8px;background:var(--s2);border:1.5px solid var(--bd);color:var(--muted);border-radius:11px;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px}
.pb:hover,.pb.active{border-color:var(--ac);color:var(--ac);background:rgba(124,111,255,.1)}
.panel{display:none;animation:fadeIn .3s}
.panel.show{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.fib-logo{background:linear-gradient(135deg,#009a4e,#00c861);color:#fff;border-radius:10px;padding:9px 16px;font-weight:800;font-size:1.1rem;letter-spacing:3px;text-align:center;margin-bottom:12px}
.qr-wrap{text-align:center;margin-bottom:10px}
.qr-wrap img{border-radius:12px;border:3px solid var(--s2)}
.hint{font-size:.75rem;color:var(--muted);text-align:center;margin-bottom:12px;line-height:1.5}
.addr-label{font-size:.75rem;color:var(--muted);margin-bottom:6px;font-weight:700}
textarea{width:100%;background:var(--s2);border:1.5px solid var(--bd);color:var(--text);border-radius:10px;padding:10px;font-family:inherit;font-size:.85rem;resize:vertical;min-height:72px;outline:none;transition:.2s;margin-bottom:12px}
textarea:focus{border-color:var(--ac)}
.btn-order{width:100%;padding:14px;border:none;border-radius:12px;font-family:inherit;font-size:.95rem;font-weight:800;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px}
.bo-fib{background:linear-gradient(135deg,#009a4e,#00c861);color:#fff}
.bo-place{background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));color:#fff}
.btn-order:hover{opacity:.88;transform:translateY(-1px)}
.btn-cont{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:.83rem;margin-top:12px;justify-content:center;transition:.2s}
.btn-cont:hover{color:var(--ac)}
/* بەتاڵ */
.empty-page{text-align:center;padding:80px 20px}
.empty-icon-wrap{width:90px;height:90px;border-radius:50%;background:var(--s);border:1px solid var(--bd);display:flex;align-items:center;justify-content:center;font-size:2.2rem;color:var(--muted);margin:0 auto 20px}
.empty-page h2{font-size:1.4rem;font-weight:800;margin-bottom:8px}
.empty-page p{color:var(--muted);margin-bottom:28px;font-size:.9rem}
.btn-shop{background:linear-gradient(135deg,var(--ac),rgba(124,111,255,.7));color:#fff;padding:13px 28px;border-radius:50px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;box-shadow:0 6px 20px rgba(124,111,255,.3)}
/* تۆست */
#toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--s);border:1px solid rgba(0,212,170,.4);color:var(--green);padding:12px 24px;border-radius:50px;font-weight:700;font-size:.88rem;z-index:999;opacity:0;transition:.4s cubic-bezier(.34,1.56,.64,1);display:flex;align-items:center;gap:8px;pointer-events:none;box-shadow:0 8px 30px rgba(0,0,0,.4)}
#toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
</style>
</head>
<body>
<nav>
  <a href="mobile.php" class="brand">📱 MobileShop</a>
  <div class="nav-r">
    <a href="mobile.php" class="nb"><i class="fas fa-home"></i> <?=$t['home']?></a>
    <a href="?lang=<?=$isKu?'en':'ku'?>" class="nb"><?=$isKu?'EN':'کو'?></a>
  </div>
</nav>
<main>
  <?php if(empty($products)): ?>
    <div class="empty-page">
      <div class="empty-icon-wrap"><i class="fas fa-shopping-cart"></i></div>
      <h2><?=$t['empty']?></h2>
      <p><?=$isKu?'هێشتا هیچ بەرهەمێکت زیاد نەکردووە':'No items added yet'?></p>
      <a href="mobile.php" class="btn-shop"><i class="fas fa-store"></i> <?=$t['continue']?></a>
    </div>
  <?php else: ?>
    <div class="ph">
      <div>
        <h1><span class="ph-icon"><i class="fas fa-shopping-cart"></i></span><?=$t['title']?></h1>
        <p style="color:var(--muted);font-size:.85rem;margin-top:5px"><?=$cart_count.' '.$t['items']?></p>
      </div>
      <a href="?clear=1" class="btn-clear" onclick="return confirm('<?=$isKu?'دڵنیایت؟':'Sure?'?>')">
        <i class="fas fa-trash-alt"></i> <?=$t['clear']?>
      </a>
    </div>
    <div class="grid">
      <div class="list">
        <?php foreach($products as $i=>$p):
          $img="uploads/".$p['image'];
          if(!file_exists($img)||empty($p['image'])) $img="https://via.placeholder.com/72/141520/7c6fff?text=📱";
        ?>
          <div class="pcard" style="animation-delay:<?=$i*.06?>s">
            <div class="pimg"><img src="<?=$img?>" alt="<?=htmlspecialchars($p['name'])?>"></div>
            <div class="pinfo">
              <div class="pname"><?=htmlspecialchars($p['name'])?></div>
              <div class="pmeta">
                <span class="pill"><i class="fas fa-tag" style="font-size:.6rem"></i><?=htmlspecialchars($p['brand'])?></span>
                <span class="pill"><i class="fas fa-hdd" style="font-size:.6rem"></i><?=htmlspecialchars($p['storage'])?></span>
                <span class="pill"><?=htmlspecialchars($p['color'])?></span>
              </div>
            </div>
            <div class="pprice">$<?=number_format($p['price'],2)?></div>
            <a href="?remove=<?=$p['id']?>" class="btn-rm" onclick="return confirm('<?=$isKu?'دڵنیایت؟':'Sure?'?>')">
              <i class="fas fa-times"></i>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- سمری -->
      <div class="summary">
        <div class="sum-title"><i class="fas fa-receipt" style="color:var(--ac)"></i><?=$t['total']?></div>
        <?php foreach($products as $p): ?>
          <div class="sum-row">
            <span><?=htmlspecialchars($p['name'])?></span>
            <span>$<?=number_format($p['price'],2)?></span>
          </div>
        <?php endforeach; ?>
        <div class="sum-total">
          <span><?=$t['total']?></span>
          <span class="amt">$<?=number_format($total,2)?></span>
        </div>

        <div class="pay-label"><?=$t['payment']?></div>
        <div class="pay-btns">
          <button class="pb" id="bFib" onclick="selPay('fib')"><i class="fas fa-mobile-alt"></i> FIB</button>
          <button class="pb" id="bPlace" onclick="selPay('place')"><i class="fas fa-map-marker-alt"></i> <?=$isKu?'شوێن':'Location'?></button>
        </div>

        <div class="panel" id="pFib">
          <div class="fib-logo">FIB</div>
          <div class="qr-wrap"><img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=fib://pay?amount=<?=$total?>&merchant=mobileshop" alt="QR"></div>
          <p class="hint"><?=$t['fib_hint']?></p>
          <button class="btn-order bo-fib" onclick="placeOrder()"><i class="fas fa-check-circle"></i> <?=$t['pay_fib']?> — $<?=number_format($total,2)?></button>
        </div>
        <div class="panel" id="pPlace">
          <div class="addr-label"><?=$t['address']?></div>
          <textarea id="addr" placeholder="<?=$isKu?'ناوچە، شەقام، ژمارەی خانوو...':'Area, street, house number...'?>"></textarea>
          <button class="btn-order bo-place" onclick="placeOrder()"><i class="fas fa-paper-plane"></i> <?=$t['order']?> — $<?=number_format($total,2)?></button>
        </div>
        <a href="mobile.php" class="btn-cont"><i class="fas fa-arrow-<?=$isKu?'right':'left'?>"></i> <?=$t['continue']?></a>
      </div>
    </div>
  <?php endif; ?>
</main>
<div id="toast"><i class="fas fa-check-circle"></i> <?=$t['success']?></div>
<script>
function selPay(t){
  ['pFib','pPlace'].forEach(id=>document.getElementById(id).classList.remove('show'));
  ['bFib','bPlace'].forEach(id=>document.getElementById(id).classList.remove('active'));
  if(t==='fib'){document.getElementById('pFib').classList.add('show');document.getElementById('bFib').classList.add('active');}
  else{document.getElementById('pPlace').classList.add('show');document.getElementById('bPlace').classList.add('active');}
  window._payMethod=t;
}
async function placeOrder(){
  const method=window._payMethod||'place';
  const addr=document.getElementById('addr')?document.getElementById('addr').value:'';
  const ids=<?=json_encode(array_map('intval',$_SESSION['cart']))?>;
  const fd=new FormData();
  ids.forEach(id=>fd.append('product_ids[]',id));
  fd.append('payment',method);fd.append('address',addr);
  try{
    const r=await fetch('order.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){
      const t=document.getElementById('toast');t.classList.add('show');
      setTimeout(()=>{window.location.href='seller_dashboard.php?tab=orders';},2500);
    }
  }catch(e){alert('هەڵەیەک ڕووی دا')}
}
</script>
</body>
</html>
