<?php
define('INCLUDED', true);
require_once 'includes/config.php';
if(isLoggedIn()) redirect('index.php');
$pageTitle = 'Register';
$error = ''; $success = '';

if($_SERVER['REQUEST_METHOD']==='POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if(!$name || !$email || !$password) { $error = 'Please fill in all fields.'; }
    elseif(strlen($password)<6) { $error = 'Password must be at least 6 characters.'; }
    elseif($password !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,password) VALUES(?,?,?)");
        $stmt->bind_param("sss",$name,$email,$hash);
        if($stmt->execute()) {
            $success = 'Account created! You can now login.';
        } else {
            $error = 'Email already exists. Try logging in.';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:3rem 1rem;">
  <div style="width:100%;max-width:440px;">
    <div class="card" style="padding:2.5rem;">
      <div style="text-align:center;margin-bottom:2rem;">
        <div style="font-size:2.5rem;margin-bottom:0.8rem">🚀</div>
        <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:0.3rem">Create Account</h1>
        <p style="color:var(--text-muted);font-size:0.9rem">Join thousands of learners on Fintebit</p>
      </div>
      <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
      <?php if($success): ?><div class="alert alert-success"><?=$success?> <a href="login.php" style="color:var(--success);font-weight:600">Login now</a></div><?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" placeholder="John Doe" required value="<?=isset($_POST['name'])?htmlspecialchars($_POST['name']):''?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?=isset($_POST['email'])?htmlspecialchars($_POST['email']):''?>">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm" class="form-control" placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn btn-accent w-full" style="justify-content:center"><i class="fas fa-user-plus"></i> Create Account</button>
      </form>
      <p style="text-align:center;font-size:0.875rem;color:var(--text-muted);margin-top:1.2rem">
        Already have an account? <a href="login.php" style="color:var(--accent);font-weight:600">Sign in</a>
      </p>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
