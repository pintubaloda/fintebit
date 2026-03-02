<?php
define('INCLUDED', true);
require_once 'includes/config.php';
$pageTitle = 'About Us';
?>
<?php include 'includes/header.php'; ?>

<section style="padding:3rem 0 1.5rem;">
  <div class="container" style="max-width:980px;">
    <h1 style="font-size:2.4rem;font-weight:800;margin-bottom:0.8rem;">About FINTEBIT TECHNOLOGY SERVICES PRIVATE LIMITED</h1>
    <p style="color:var(--text-muted);font-size:1rem;line-height:1.8;">
      FINTEBIT TECHNOLOGY SERVICES PRIVATE LIMITED is an education technology company focused on practical, job-relevant learning in software, data, AI, and digital skills.
      We design structured courses with lesson-wise content, assessments, and measurable outcomes so learners can move from theory to real execution.
    </p>
  </div>
</section>

<section style="padding:1rem 0 3rem;">
  <div class="container" style="max-width:980px;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;">
    <div class="card" style="padding:1.2rem;">
      <h2 style="font-size:1.1rem;margin-bottom:0.6rem;">Our Mission</h2>
      <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.7;">
        Make high-quality tech education accessible and outcomes-focused for learners, professionals, and teams.
      </p>
    </div>
    <div class="card" style="padding:1.2rem;">
      <h2 style="font-size:1.1rem;margin-bottom:0.6rem;">What We Build</h2>
      <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.7;">
        Course platforms, lesson frameworks, quizzes, and guided learning paths that align with real-world project needs.
      </p>
    </div>
    <div class="card" style="padding:1.2rem;">
      <h2 style="font-size:1.1rem;margin-bottom:0.6rem;">Learner Promise</h2>
      <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.7;">
        Clear curriculum, transparent pricing, and continuous quality improvement in content and learner experience.
      </p>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
