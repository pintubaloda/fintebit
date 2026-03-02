<?php
define('INCLUDED', true);
require_once 'includes/config.php';
if(isLoggedIn()) redirect('index.php');
$pageTitle = 'Login';
$error = '';

if($_SERVER['REQUEST_METHOD']==='POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $redirect = $_GET['redirect'] ?? ($user['role']==='admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
            redirect($redirect);
        } else { $error = 'Invalid email or password.'; }
    } else { $error = 'Please fill in all fields.'; }
}
?>
<?php include 'includes/header.php'; ?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:3rem 1rem;">
  <div style="width:100%;max-width:440px;">
    <div class="card" style="padding:2.5rem;">
      <div style="text-align:center;margin-bottom:2rem;">
        <div style="font-size:2.5rem;margin-bottom:0.8rem">🎓</div>
        <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:0.3rem">Welcome Back</h1>
        <p style="color:var(--text-muted);font-size:0.9rem">Sign in to continue your learning journey</p>
      </div>
      <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?=isset($_POST['email'])?htmlspecialchars($_POST['email']):''?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-accent w-full" style="justify-content:center;margin-bottom:1rem"><i class="fas fa-sign-in-alt"></i> Sign In</button>
      </form>
      <p style="text-align:center;font-size:0.875rem;color:var(--text-muted)">
        Don't have an account? <a href="register.php" style="color:var(--accent);font-weight:600">Create one free</a>
      </p>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
