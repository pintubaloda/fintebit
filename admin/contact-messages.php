<?php
define('INCLUDED', true);
require_once '../includes/config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');
$pageTitle = 'Contact Messages';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['status'])) {
    $messageId = (int)($_POST['message_id'] ?? 0);
    $status = $_POST['status'] ?? 'new';
    $allowed = ['new', 'reviewed', 'closed'];
    if ($messageId > 0 && in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE contact_messages SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $messageId);
        $stmt->execute();
    }
    redirect('contact-messages.php');
}

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>
<?php include '../includes/header.php'; ?>
<?php include 'includes/admin_nav.php'; ?>

<div style="padding:2rem 0;">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
      <h1 style="font-size:1.7rem;font-weight:800;">Contact Messages</h1>
      <span class="badge badge-cat">Admin Inbox</span>
    </div>

    <div class="card" style="padding:1rem;">
      <?php if (!$messages || $messages->num_rows === 0): ?>
        <p class="text-muted" style="padding:0.6rem;">No messages yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Status</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($m = $messages->fetch_assoc()): ?>
                <tr>
                  <td>#<?= (int)$m['id'] ?></td>
                  <td><?= htmlspecialchars($m['name']) ?></td>
                  <td><?= htmlspecialchars($m['email']) ?></td>
                  <td><?= htmlspecialchars($m['subject']) ?></td>
                  <td style="max-width:360px;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
                  <td>
                    <form method="post" style="display:flex;gap:0.4rem;align-items:center;">
                      <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
                      <select name="status" class="form-control" style="min-width:120px;padding:0.35rem 0.6rem;">
                        <?php foreach (['new', 'reviewed', 'closed'] as $s): ?>
                          <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn btn-sm btn-ghost">Save</button>
                    </form>
                  </td>
                  <td><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
