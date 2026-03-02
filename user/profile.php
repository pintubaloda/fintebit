<?php
define('INCLUDED', true);
require_once '../includes/config.php';
if(!isLoggedIn()) redirect('../login.php');
$pageTitle = 'My Profile';
$userId = $_SESSION['user_id'];
$msg = ''; $msgType = '';

if($_SERVER['REQUEST_METHOD']==='POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if($name && $email) {
        if($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt=$conn->prepare("UPDATE users SET name=?,email=?,password=? WHERE id=?");
            $stmt->bind_param("sssi",$name,$email,$hash,$userId);
        } else {
            $stmt=$conn->prepare("UPDATE users SET name=?,email=? WHERE id=?");
            $stmt->bind_param("ssi",$name,$email,$userId);
        }
        if($stmt->execute()) {
            $_SESSION['name']=$name; $_SESSION['email']=$email;
            $msg='Profile updated!'; $msgType='success';
        } else { $msg='Error updating profile.'; $msgType='danger'; }
    }
}

$user=$conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();
?>
<?php include '../includes/header.php'; ?>
<div style="padding:3rem 0;"><div class="container" style="max-width:600px;">
  <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:1.5rem">My Profile</h1>
  <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=$msg?></div><?php endif; ?>
  <div class="card" style="padding:2rem;">
    <div style="display:flex;align-items:center;gap:1.2rem;margin-bottom:2rem;">
      <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800"><?=strtoupper(substr($user['name'],0,1))?></div>
      <div>
        <div style="font-weight:700;font-size:1.1rem"><?=htmlspecialchars($user['name'])?></div>
        <div style="color:var(--text-muted);font-size:0.85rem"><?=htmlspecialchars($user['email'])?></div>
        <span class="badge" style="margin-top:0.3rem;background:rgba(255,107,53,0.1);color:var(--accent);border:1px solid rgba(255,107,53,0.2)"><?=ucfirst($user['role'])?></span>
      </div>
    </div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($user['name'])?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($user['email'])?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span style="color:var(--text-muted)">(leave blank to keep current)</span></label>
        <input type="password" name="password" class="form-control" placeholder="New password...">
      </div>
      <div style="display:flex;gap:0.8rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-accent"><i class="fas fa-save"></i> Save Changes</button>
        <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div></div>
<?php include '../includes/footer.php'; ?>
