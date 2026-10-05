<?php
require_once __DIR__.'/../config/config.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: '.base_url('auth/login.php'));
    exit;
}

$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $allowedStatuses = [
        'appointment' => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
        'enquiry' => ['New', 'Contacted', 'Resolved'],
    ];

    if (!isset($allowedStatuses[$action]) || $id === false || $id === null || $id < 1 || !in_array($status, $allowedStatuses[$action], true)) {
        $error = 'That update is not valid. Please choose a status from the list.';
    } else {
        try {
            $table = $action === 'appointment' ? 'appointments' : 'enquiries';
            $statement = $pdo->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
            $statement->execute([$status, $id]);
            flash(ucfirst($action).' status updated.');
            header('Location: '.base_url('admin/dashboard.php'));
            exit;
        } catch (PDOException $exception) {
            error_log('Admin dashboard status update failed: '.$exception->getMessage());
            $error = 'The update could not be saved. Please try again.';
        }
    }
}

$notice = flash();
$search = trim($_GET['q'] ?? '');
$counts = [
    'clients' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='client' AND status=1")->fetchColumn(),
    'appointments' => (int)$pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn(),
    'waiting' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Pending'")->fetchColumn(),
    'enquiries' => (int)$pdo->query("SELECT COUNT(*) FROM enquiries WHERE status='New'")->fetchColumn(),
];

$appointmentSql = 'SELECT * FROM appointments';
$enquirySql = 'SELECT * FROM enquiries';
$parameters = [];
if ($search !== '') {
    $appointmentSql .= ' WHERE name LIKE ? OR email LIKE ? OR service LIKE ?';
    $enquirySql .= ' WHERE name LIKE ? OR email LIKE ? OR message LIKE ?';
    $term = '%'.$search.'%';
    $parameters = [$term, $term, $term];
}
$appointmentSql .= ' ORDER BY appointment_date DESC, appointment_time DESC LIMIT 50';
$enquirySql .= ' ORDER BY created_at DESC LIMIT 30';
$statement = $pdo->prepare($appointmentSql);
$statement->execute($parameters);
$appointments = $statement->fetchAll();
$statement = $pdo->prepare($enquirySql);
$statement->execute($parameters);
$enquiries = $statement->fetchAll();

$title = 'Admin Dashboard';
include __DIR__.'/../partials_header.php';
?>
<style>
.admin-page{--admin-ink:#20183b;--admin-muted:#79738d;--admin-line:#ece8f5;--admin-soft:#f8f6fc;padding:42px 0 80px;background:linear-gradient(180deg,#faf8ff 0,#fff 430px)}
.admin-wrap{max-width:1200px;margin:auto}
.admin-welcome{position:relative;overflow:hidden;padding:36px 40px;border-radius:28px;color:#fff;background:linear-gradient(120deg,#281451,#5426a2 58%,#8956e8);box-shadow:0 22px 50px rgba(65,35,125,.2)}
.admin-welcome:after{content:"";position:absolute;width:310px;height:310px;right:7%;top:-190px;border:1px solid rgba(255,255,255,.2);border-radius:50%;box-shadow:0 0 0 34px rgba(255,255,255,.04),0 0 0 70px rgba(255,255,255,.035)}
.admin-welcome-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:24px}
.admin-welcome .eyebrow{color:#dfd1ff}.admin-welcome h1{margin:10px 0;font-size:clamp(30px,4vw,44px);letter-spacing:-1.5px}.admin-welcome p{margin:0;color:#e5dcf7;max-width:620px;line-height:1.7}
.admin-welcome-actions{display:flex;gap:10px;flex-wrap:wrap}.admin-welcome-actions .btn{white-space:nowrap}.admin-welcome-actions .btn-light{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.42);color:#fff}.admin-welcome-actions .btn-light:hover{background:rgba(255,255,255,.24);box-shadow:0 8px 20px rgba(24,12,55,.16);transform:translateY(-2px)}
.admin-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:24px 0}
.admin-metric{display:flex;align-items:center;gap:15px;padding:20px;border:1px solid var(--admin-line);border-radius:20px;background:#fff;box-shadow:0 8px 24px rgba(43,29,82,.045)}
.admin-metric-icon{display:grid;place-items:center;flex:0 0 48px;height:48px;border-radius:16px;background:#f2edff;color:#6837c4;font-size:21px}
.admin-metric:nth-child(2) .admin-metric-icon{background:#e9f4ff;color:#2471b8}.admin-metric:nth-child(3) .admin-metric-icon{background:#fff4df;color:#b57511}.admin-metric:nth-child(4) .admin-metric-icon{background:#e9f8ef;color:#23814a}
.admin-metric strong{display:block;color:var(--admin-ink);font-size:27px;line-height:1.1}.admin-metric span:last-child{display:block;margin-top:5px;color:var(--admin-muted);font-size:13px}
.admin-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:30px 0 16px}
.admin-toolbar h2{margin:0;color:var(--admin-ink);font-size:23px;letter-spacing:-.5px}.admin-toolbar p{margin:5px 0 0;color:var(--admin-muted);font-size:14px}
.admin-search{display:flex;width:min(100%,390px);gap:8px}.admin-search input{min-width:0;flex:1;padding:12px 14px;border:1px solid var(--admin-line);border-radius:12px;background:#fff;font:inherit}.admin-search .btn{padding:11px 17px}
.admin-panel{overflow:hidden;border:1px solid var(--admin-line);border-radius:20px;background:#fff;box-shadow:0 10px 30px rgba(43,29,82,.045)}
.admin-panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:20px 22px;border-bottom:1px solid var(--admin-line)}
.admin-panel-head h3{margin:0;color:var(--admin-ink);font-size:17px}.admin-panel-head span{color:var(--admin-muted);font-size:13px}
.admin-table-wrap{overflow-x:auto}.admin-table{width:100%;border-collapse:collapse;text-align:left;min-width:780px}.admin-table th{padding:12px 18px;background:#fbfaff;color:#817a95;font-size:11px;letter-spacing:.08em;text-transform:uppercase}.admin-table td{padding:15px 18px;border-top:1px solid #f0edf5;color:#37314b;font-size:13px;vertical-align:middle}.admin-table tbody tr:hover{background:#fdfcff}.admin-person{font-weight:700;color:var(--admin-ink)}.admin-sub{display:block;margin-top:4px;color:var(--admin-muted);font-size:12px}.admin-status{display:inline-flex;padding:6px 10px;border-radius:999px;background:#f2edff;color:#6939bb;font-size:11px;font-weight:700}.admin-status.confirmed,.admin-status.resolved,.admin-status.completed{background:#eaf7ef;color:#267344}.admin-status.cancelled{background:#fff0f0;color:#a23b3b}.admin-status.contacted{background:#eaf4ff;color:#286eae}.admin-update{display:flex;gap:7px;align-items:center}.admin-update select{max-width:125px;padding:8px 9px;border:1px solid var(--admin-line);border-radius:9px;background:#fff;color:var(--admin-ink);font:inherit;font-size:12px}.admin-update button{padding:8px 10px;border:0;border-radius:9px;background:#eee8fb;color:#6034ad;font-weight:700;cursor:pointer}.admin-update button:hover{background:#e2d8f7}
.admin-empty{padding:34px 20px;text-align:center;color:var(--admin-muted)}.admin-empty strong{display:block;margin-bottom:5px;color:var(--admin-ink)}
.admin-alert{margin:16px 0;padding:13px 16px;border-radius:12px;background:#eff9f1;color:#246b3c}.admin-alert.error{background:#fff0f0;color:#963d3d}
.admin-secondary{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:28px}
.admin-secondary .admin-panel{align-self:start}.admin-enquiry{padding:18px 20px;border-top:1px solid #f0edf5}.admin-enquiry:first-of-type{border-top:0}.admin-enquiry-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.admin-enquiry p{margin:10px 0;color:#514b62;line-height:1.6;white-space:pre-wrap;overflow-wrap:anywhere}.admin-enquiry time{color:var(--admin-muted);font-size:12px}
@media(max-width:800px){.admin-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-secondary{grid-template-columns:1fr}.admin-welcome{padding:30px}.admin-welcome-inner{align-items:flex-start;flex-direction:column}}
@media(max-width:560px){.admin-page{padding-top:24px}.admin-metrics{gap:10px}.admin-metric{gap:10px;padding:14px}.admin-metric-icon{flex-basis:40px;height:40px;border-radius:13px}.admin-metric strong{font-size:23px}.admin-toolbar{align-items:stretch;flex-direction:column}.admin-search{width:100%}.admin-welcome{padding:25px;border-radius:22px}}
</style>
<section class="admin-page">
<div class="container admin-wrap">
  <section class="admin-welcome reveal">
    <div class="admin-welcome-inner">
      <div><span class="eyebrow">SAM &amp; ASSOCIATES · ADMIN</span><h1>Good to see you, <?=e($_SESSION['user']['name'])?>.</h1><p>Your team overview, appointment queue, and client enquiries are all in one place.</p></div>
      <div class="admin-welcome-actions"><a class="btn btn-light" href="<?=e(base_url('appointment.php'))?>">＋ New appointment</a><a class="btn btn-light" href="<?=e(base_url('auth/logout.php'))?>">Sign out</a></div>
    </div>
  </section>

  <?php if ($notice): ?><div class="admin-alert"><?=e($notice)?></div><?php endif; ?>
  <?php if ($error): ?><div class="admin-alert error"><?=e($error)?></div><?php endif; ?>

  <section class="admin-metrics reveal" aria-label="Business overview">
    <article class="admin-metric"><span class="admin-metric-icon" aria-hidden="true">♙</span><div><strong><?=$counts['clients']?></strong><span>Active clients</span></div></article>
    <article class="admin-metric"><span class="admin-metric-icon" aria-hidden="true">▦</span><div><strong><?=$counts['appointments']?></strong><span>Total appointments</span></div></article>
    <article class="admin-metric"><span class="admin-metric-icon" aria-hidden="true">◷</span><div><strong><?=$counts['waiting']?></strong><span>Awaiting confirmation</span></div></article>
    <article class="admin-metric"><span class="admin-metric-icon" aria-hidden="true">✉</span><div><strong><?=$counts['enquiries']?></strong><span>New enquiries</span></div></article>
  </section>

  <div class="admin-toolbar reveal">
    <div><h2>Appointment pipeline</h2><p>Review requests and keep each client up to date.</p></div>
    <form class="admin-search" method="get" action="<?=e(base_url('admin/dashboard.php'))?>">
      <input type="search" name="q" value="<?=e($search)?>" placeholder="Search name, email, or service" aria-label="Search appointments and enquiries">
      <button class="btn btn-primary" type="submit">Search</button>
      <?php if ($search !== ''): ?><a class="btn btn-light" href="<?=e(base_url('admin/dashboard.php'))?>">Clear</a><?php endif; ?>
    </form>
  </div>

  <section class="admin-panel reveal" aria-label="Appointments">
    <div class="admin-panel-head"><h3>Appointments</h3><span>Showing up to 50 most recent<?=$search !== '' ? ' matching “'.e($search).'”' : ''?></span></div>
    <?php if (!$appointments): ?><div class="admin-empty"><strong>No appointments found</strong>New bookings will appear here.</div>
    <?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table">
      <thead><tr><th>Client</th><th>Service</th><th>Requested time</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
      <?php foreach ($appointments as $appointment): ?>
        <tr>
          <td><span class="admin-person"><?=e($appointment['name'])?></span><span class="admin-sub"><?=e($appointment['email'])?> · <?=e($appointment['phone'])?></span></td>
          <td><?=e($appointment['service'])?></td>
          <td><?=e($appointment['appointment_date'])?><span class="admin-sub"><?=e(substr((string)$appointment['appointment_time'], 0, 5))?></span></td>
          <td><span class="admin-status <?=e(strtolower(preg_replace('/[^a-z0-9-]/', '-', (string)$appointment['status'])))?>"><?=e($appointment['status'])?></span></td>
          <td><form class="admin-update" method="post">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="appointment"><input type="hidden" name="id" value="<?=$appointment['id']?>">
            <select name="status" aria-label="Appointment status">
              <?php foreach (['Pending', 'Confirmed', 'Completed', 'Cancelled'] as $status): ?><option value="<?=e($status)?>" <?=$appointment['status'] === $status ? 'selected' : ''?>><?=e($status)?></option><?php endforeach; ?>
            </select><button type="submit">Save</button>
          </form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>

  <div class="admin-secondary">
    <section class="admin-panel reveal" aria-label="Client enquiries">
      <div class="admin-panel-head"><h3>Client enquiries</h3><span>Latest 30</span></div>
      <?php if (!$enquiries): ?><div class="admin-empty"><strong>Inbox is clear</strong>New contact messages will show up here.</div>
      <?php else: foreach ($enquiries as $enquiry): ?>
        <article class="admin-enquiry">
          <div class="admin-enquiry-top"><div><span class="admin-person"><?=e($enquiry['name'])?></span><span class="admin-sub"><a href="mailto:<?=e($enquiry['email'])?>"><?=e($enquiry['email'])?></a> · <?=e($enquiry['phone'])?></span></div><time><?=e($enquiry['created_at'])?></time></div>
          <p><?=e($enquiry['message'])?></p>
          <form class="admin-update" method="post">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="enquiry"><input type="hidden" name="id" value="<?=$enquiry['id']?>">
            <select name="status" aria-label="Enquiry status">
              <?php foreach (['New', 'Contacted', 'Resolved'] as $status): ?><option value="<?=e($status)?>" <?=$enquiry['status'] === $status ? 'selected' : ''?>><?=e($status)?></option><?php endforeach; ?>
            </select><button type="submit">Save</button>
          </form>
        </article>
      <?php endforeach; endif; ?>
    </section>
    <section class="admin-panel reveal" aria-label="Admin shortcuts">
      <div class="admin-panel-head"><h3>Quick links</h3><span>Shortcuts</span></div>
      <div class="admin-enquiry"><span class="admin-person">Website enquiries</span><span class="admin-sub">Review new client messages in the inbox.</span><p><a class="btn btn-light" href="<?=e(base_url('contact.php'))?>">Open contact page</a></p></div>
      <div class="admin-enquiry"><span class="admin-person">Client appointments</span><span class="admin-sub">Share the booking form with a client or create a request.</span><p><a class="btn btn-primary" href="<?=e(base_url('appointment.php'))?>">Open booking form</a></p></div>
      <div class="admin-enquiry"><span class="admin-person">Account</span><span class="admin-sub">Signed in as <?=e($_SESSION['user']['email'])?></span><p><a class="text-link" href="<?=e(base_url('auth/logout.php'))?>">Sign out securely →</a></p></div>
    </section>
  </div>
</div>
</section>
<?php include __DIR__.'/../partials_footer.php'; ?>
