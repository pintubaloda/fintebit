<?php
define('INCLUDED', true);
require_once 'includes/config.php';
$pageTitle = 'Contact Us';

$errors = [];
$success = '';
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($subject === '' || strlen($subject) < 3) {
        $errors[] = 'Please enter a subject.';
    }
    if ($message === '' || strlen($message) < 10) {
        $errors[] = 'Please write a detailed message (minimum 10 characters).';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        if ($stmt->execute()) {
            $success = 'Thank you. Your message has been submitted. Our team will contact you soon.';
            $name = $email = $subject = $message = '';
        } else {
            $errors[] = 'Unable to submit right now. Please try again.';
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<section style="padding:3rem 0;">
  <div class="container" style="max-width:980px;">
    <h1 style="font-size:2.2rem;font-weight:800;margin-bottom:0.8rem;">Contact Us</h1>
    <p style="color:var(--text-muted);margin-bottom:1.5rem;">
      Reach out to FINTEBIT TECHNOLOGY SERVICES PRIVATE LIMITED for support, partnerships, course queries, or payment issues.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;margin-bottom:1rem;">
      <div class="card" style="padding:1rem;">
        <h3 style="font-size:1rem;margin-bottom:0.5rem;">Support</h3>
        <p style="color:var(--text-muted);font-size:0.92rem;">Course access, enrollment, learning issues, and account support.</p>
      </div>
      <div class="card" style="padding:1rem;">
        <h3 style="font-size:1rem;margin-bottom:0.5rem;">Business</h3>
        <p style="color:var(--text-muted);font-size:0.92rem;">Training partnerships, enterprise learning, and collaborations.</p>
      </div>
      <div class="card" style="padding:1rem;">
        <h3 style="font-size:1rem;margin-bottom:0.5rem;">Response Time</h3>
        <p style="color:var(--text-muted);font-size:0.92rem;">Most requests are reviewed within 1 to 2 business days.</p>
      </div>
    </div>

    <div class="card" style="padding:1.2rem;">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <?php foreach($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($subject) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="6" required><?= htmlspecialchars($message) ?></textarea>
        </div>
        <button class="btn btn-accent" type="submit"><i class="fas fa-paper-plane"></i> Submit Message</button>
      </form>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
