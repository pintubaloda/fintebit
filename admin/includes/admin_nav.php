<div style="background:#0a0a1a;border-bottom:1px solid var(--border);padding:0.8rem 0;">
  <div class="container">
    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
      <span style="font-size:0.75rem;color:var(--text-muted);margin-right:0.5rem"><i class="fas fa-shield-alt" style="color:var(--accent)"></i> ADMIN</span>
      <?php $adminLinks=[
        ['Dashboard','dashboard.php','fas fa-tachometer-alt'],
        ['Courses','courses.php','fas fa-graduation-cap'],
        ['Add Course','add_course.php','fas fa-plus'],
        ['Users','users.php','fas fa-users'],
        ['Orders','orders.php','fas fa-receipt'],
        ['Contact Messages','contact-messages.php','fas fa-envelope'],
      ]; foreach($adminLinks as $l): 
        $active = basename($_SERVER['PHP_SELF'])==$l[1]?'active':'';
      ?>
      <a href="<?=SITE_URL?>/admin/<?=$l[1]?>" style="display:flex;align-items:center;gap:0.4rem;padding:0.4rem 0.8rem;border-radius:8px;font-size:0.8rem;font-weight:500;color:<?=$active?'var(--accent)':'var(--text-muted)'?>;background:<?=$active?'rgba(255,107,53,0.1)':'transparent'?>;transition:all 0.2s" onmouseover="if(!this.classList.contains('active')){this.style.color='var(--text)';this.style.background='rgba(255,255,255,0.05)'}" onmouseout="if(!this.classList.contains('active')){this.style.color='var(--text-muted)';this.style.background='transparent'}">
        <i class="<?=$l[2]?>"></i> <?=$l[0]?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
