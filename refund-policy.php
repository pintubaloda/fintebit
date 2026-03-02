<?php
define('INCLUDED', true);
require_once 'includes/config.php';
$pageTitle = 'Refund Policy';
?>
<?php include 'includes/header.php'; ?>

<section style="padding:3rem 0;">
  <div class="container" style="max-width:980px;">
    <h1 style="font-size:2.2rem;font-weight:800;margin-bottom:0.6rem;">Refund Policy</h1>
    <p style="color:var(--text-muted);margin-bottom:1.5rem;">
      Effective Date: <?= date('F d, Y') ?><br>
      Company: FINTEBIT TECHNOLOGY SERVICES PRIVATE LIMITED
    </p>

    <div class="card" style="padding:1.2rem;">
      <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">No Refund Policy</h2>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">
        All course purchases made on this platform are final. FINTEBIT TECHNOLOGY SERVICES PRIVATE LIMITED does not provide refunds, returns, or cancellations once payment is submitted.
      </p>

      <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Reason for Policy</h2>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">
        Digital course access, downloadable materials, and learning content are delivered immediately or reserved for account access after payment verification. Due to the nature of digital products and educational content, refunds are not supported.
      </p>

      <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Payment Verification Cases</h2>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">
        If a payment is marked pending, access is granted only after verification. A pending status is not a refund case. Users must provide valid proof details where requested.
      </p>

      <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Duplicate or Failed Transactions</h2>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">
        In genuine duplicate debit or technical failure scenarios, users should contact support with complete transaction evidence. Such cases are reviewed for payment reconciliation only, not as a standard refund request.
      </p>

      <h2 style="font-size:1.1rem;margin-bottom:0.5rem;">Policy Acceptance</h2>
      <p style="color:var(--text-muted);line-height:1.8;">
        By purchasing any paid course, you acknowledge and accept this no-refund policy.
      </p>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
