</div><!-- end page-content -->
<footer style="background:#0a0a1a;border-top:1px solid rgba(255,255,255,0.06);padding:3rem 0 1.5rem;margin-top:4rem;">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2.5rem;margin-bottom:2.5rem;">
      <div>
        <div class="nav-logo" style="font-size:1.5rem;margin-bottom:0.8rem">Fin<span style="-webkit-text-fill-color:white;color:white;font-weight:300">tebit</span></div>
        <p style="color:var(--text-muted);font-size:0.875rem;line-height:1.7">Empowering learners worldwide with high-quality tech education. Learn at your own pace.</p>
        <div style="display:flex;gap:0.8rem;margin-top:1rem;">
          <a href="#" style="width:36px;height:36px;background:rgba(255,255,255,0.06);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);transition:all 0.2s" onmouseover="this.style.background='rgba(255,107,53,0.2)';this.style.color='var(--accent)'" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='var(--text-muted)'"><i class="fab fa-twitter"></i></a>
          <a href="#" style="width:36px;height:36px;background:rgba(255,255,255,0.06);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);transition:all 0.2s" onmouseover="this.style.background='rgba(255,107,53,0.2)';this.style.color='var(--accent)'" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='var(--text-muted)'"><i class="fab fa-linkedin"></i></a>
          <a href="#" style="width:36px;height:36px;background:rgba(255,255,255,0.06);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);transition:all 0.2s" onmouseover="this.style.background='rgba(255,107,53,0.2)';this.style.color='var(--accent)'" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='var(--text-muted)'"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
      <div>
        <h4 style="font-size:0.9rem;margin-bottom:1rem;color:var(--text)">Courses</h4>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
          <?php 
          $cats = ['Web Dev','Programming','AI & ML','Data Science','Design'];
          foreach($cats as $c):
          ?>
          <a href="<?= SITE_URL ?>/courses.php?category=<?= urlencode($c) ?>" style="color:var(--text-muted);font-size:0.875rem;transition:color 0.2s" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-muted)'"><?= $c ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h4 style="font-size:0.9rem;margin-bottom:1rem;color:var(--text)">Company</h4>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">About Us</a>
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Careers</a>
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Blog</a>
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Press</a>
        </div>
      </div>
      <div>
        <h4 style="font-size:0.9rem;margin-bottom:1rem;color:var(--text)">Support</h4>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Help Center</a>
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Contact Us</a>
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Privacy Policy</a>
          <a href="#" style="color:var(--text-muted);font-size:0.875rem">Terms of Service</a>
        </div>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
      <p style="color:var(--text-muted);font-size:0.8rem">&copy; <?= date('Y') ?> Fintebit. All rights reserved.</p>
      <p style="color:var(--text-muted);font-size:0.8rem">Made with <span style="color:var(--accent)">♥</span> for learners</p>
    </div>
  </div>
</footer>
</body>
</html>
