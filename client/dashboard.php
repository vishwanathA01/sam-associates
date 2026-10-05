<?php
require_once __DIR__.'/../config/config.php';
if(empty($_SESSION['user'])||$_SESSION['user']['role']!=='client'){header('Location: '.base_url('auth/login.php'));exit;}
$pdo=db(); $u=$_SESSION['user']; $message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  try{
    if(($_POST['action']??'')==='profile'){
      $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??'');
      if($name==='') throw new RuntimeException('Name is required.');
      $st=$pdo->prepare('UPDATE users SET name=?, phone=? WHERE id=?'); $st->execute([$name,$phone,$u['id']]);
      $_SESSION['user']['name']=$name; $_SESSION['user']['phone']=$phone; $u=$_SESSION['user']; $message='Profile updated successfully.';
    } elseif(($_POST['action']??'')==='support'){
      $msg=trim($_POST['message']??''); if($msg==='') throw new RuntimeException('Please enter your message.');
      $st=$pdo->prepare('INSERT INTO enquiries(name,email,phone,message) VALUES(?,?,?,?)'); $st->execute([$u['name'],$u['email'],$u['phone']??'', $msg]); $message='Support request sent. Our team can follow up from the admin inbox.';
    }
  }catch(Throwable $e){$error=$e->getMessage();}
}
$st=$pdo->prepare('SELECT * FROM appointments WHERE user_id=? ORDER BY appointment_date DESC, appointment_time DESC LIMIT 8');$st->execute([$u['id']]);$apps=$st->fetchAll();
$st=$pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 6');$st->execute([$u['id']]);$notifications=$st->fetchAll();
$counts=[]; foreach(['appointments','notifications'] as $t){$where=$t==='appointments'?'user_id':'user_id';$st=$pdo->prepare("SELECT COUNT(*) FROM {$t} WHERE {$where}=?");$st->execute([$u['id']]);$counts[$t]=(int)$st->fetchColumn();}
$st=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');$st->execute([$u['id']]);$unread=(int)$st->fetchColumn();
$extra=['invoices'=>0,'documents'=>0];
foreach($extra as $table=>$dummy){try{$st=$pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=?");$st->execute([$u['id']]);$extra[$table]=(int)$st->fetchColumn();}catch(Throwable $e){}}
$title='Client Dashboard'; include __DIR__.'/../partials_header.php';
?>
<section class="dashboard-hero"><div class="container dashboard-hero-inner"><div class="reveal"><span class="eyebrow">Secure client portal</span><h1>Welcome back, <?=e($u['name'])?>.</h1><p>Everything important about your SAM & Associates account, organized in one place.</p></div><a class="btn btn-light reveal delay-1" href="<?=e(base_url('appointment.php'))?>">+ Book consultation</a></div></section>
<section class="section portal-section"><div class="container">
<?php if($message):?><div class="notice success reveal">✓ <?=e($message)?></div><?php endif;?><?php if($error):?><div class="notice error reveal">! <?=e($error)?></div><?php endif;?>
<div class="client-stats reveal"><div><span>Appointments</span><strong><?=$counts['appointments']?></strong><small>Booked with us</small></div><div><span>Notifications</span><strong><?=$counts['notifications']?></strong><small><?=$unread?> unread</small></div><div><span>Documents</span><strong><?=$extra['documents']?></strong><small>Files in your vault</small></div><div><span>Invoices</span><strong><?=$extra['invoices']?></strong><small>Financial records</small></div></div>
<div class="dashboard-grid">
<div class="dashboard-main">
<div class="dashboard-card reveal"><div class="card-head"><div><span class="eyebrow">Upcoming & recent</span><h2>Your appointments</h2></div><a class="text-link" href="<?=e(base_url('appointment.php'))?>">Book new →</a></div>
<?php if(!$apps):?><div class="empty-state"><div class="empty-icon">⌁</div><h3>No appointments yet</h3><p>Schedule your first consultation and we'll take it from there.</p><a class="btn btn-primary" href="<?=e(base_url('appointment.php'))?>">Schedule now</a></div><?php else: foreach($apps as $a): ?><div class="appointment-row"><div class="date-box"><b><?=date('d',strtotime($a['appointment_date']))?></b><small><?=date('M',strtotime($a['appointment_date']))?></small></div><div class="appt-info"><b><?=e($a['service'])?></b><span><?=e(substr($a['appointment_time'],0,5))?> · <?=e($a['notes']?:'Consultation request')?></span></div><span class="status <?=strtolower(e($a['status']))?>"><?=e($a['status'])?></span></div><?php endforeach; endif; ?></div>
<div class="dashboard-card reveal"><div class="card-head"><div><span class="eyebrow">Account tools</span><h2>Quick actions</h2></div></div><div class="quick-actions"><a href="<?=e(base_url('appointment.php'))?>"><b>+</b><span>Book appointment</span><small>Choose a date & time</small></a><a href="#profile"><b>◎</b><span>Update profile</span><small>Keep details current</small></a><a href="#support"><b>?</b><span>Ask for support</span><small>Send a private enquiry</small></a><a href="<?=e(base_url('services.php'))?>"><b>✦</b><span>Explore services</span><small>See what we can help with</small></a></div></div>
</div>
<aside class="dashboard-side">
<div class="dashboard-card reveal delay-1"><div class="card-head"><div><span class="eyebrow">Updates</span><h2>Notifications</h2></div><span class="notif-count"><?=$unread?></span></div><?php if(!$notifications):?><p class="muted">No notifications yet.</p><?php else: foreach($notifications as $n):?><div class="notification-row <?=empty($n['is_read'])?'unread':''?>"><span class="notif-dot"></span><div><b><?=e($n['title'])?></b><p><?=e($n['message'])?></p><small><?=date('d M Y',strtotime($n['created_at']))?></small></div></div><?php endforeach; endif;?></div>
<div class="dashboard-card profile-card reveal delay-2" id="profile"><div class="card-head"><div><span class="eyebrow">Your details</span><h2>Profile</h2></div></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="profile"><div class="field"><label>Name</label><input name="name" value="<?=e($u['name'])?>" required></div><div class="field"><label>Email</label><input value="<?=e($u['email'])?>" disabled></div><div class="field"><label>Phone</label><input name="phone" value="<?=e($u['phone']??'')?>"></div><button class="btn btn-primary">Save profile</button></form></div>
<div class="dashboard-card reveal delay-3"><span class="eyebrow">Need help?</span><h2>Talk to our team.</h2><p class="muted">Send a support request or reach us directly.</p><div class="actions compact"><a class="btn btn-primary" href="tel:+916202426418">Call</a><a class="btn btn-light" href="https://wa.me/916202426418" target="_blank" rel="noopener">WhatsApp</a></div></div>
</aside></div>
<div class="vault-strip reveal"><div><span class="eyebrow">Digital workspace</span><h2>Documents & invoices</h2><p>Keep your account organized with a dedicated space for financial records and important files.</p></div><div class="vault-metrics"><div><b><?=$extra['documents']?></b><span>Documents</span></div><div><b><?=$extra['invoices']?></b><span>Invoices</span></div><div><b>✓</b><span>Secure access</span></div></div></div>
<div class="support-panel reveal" id="support"><div><span class="eyebrow">Private support request</span><h2>Need something from the team?</h2><p>Send a message from your account and the admin team can follow up.</p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="support"><textarea name="message" rows="4" placeholder="Tell us what you need help with..." required></textarea><button class="btn btn-primary">Send support request →</button></form></div>
<div class="portal-footer-actions reveal"><a href="<?=e(base_url('auth/logout.php'))?>">Sign out</a><a href="<?=e(base_url('contact.php'))?>">Contact office</a><a href="<?=e(base_url('services.php'))?>">View services</a></div>
</div></section>
<?php include __DIR__.'/../partials_footer.php'; ?>
