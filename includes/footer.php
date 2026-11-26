</main>

<footer class="py-5 mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center mb-3">
          <img src="<?php echo SITE_URL; ?>/assets/images/nsbm_logo.png" alt="NSBM Logo" width="36" height="38" class="me-2 bg-white rounded p-1" style="object-fit: contain;">
          <span class="fs-5 fw-bold text-white">NSBM ArtCraft</span>
        </div>
        <p class="small text-secondary mb-3">
          A dedicated creative showcase platform for students of NSBM Green University. Discover paintings, photography, handicrafts, digital art, and sculptures crafted by future innovators.
        </p>
       </div>

      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="text-white fw-bold mb-3">Explore</h6>
        <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
          <li><a href="<?php echo SITE_URL; ?>/gallery.php">Art Gallery</a></li>
          <li><a href="<?php echo SITE_URL; ?>/artists.php">Student Artists</a></li>
          <li><a href="<?php echo SITE_URL; ?>/exhibitions.php">Exhibitions</a></li>
          <li><a href="<?php echo SITE_URL; ?>/gallery.php?filter=featured">Featured Works</a></li>
        </ul>
      </div>

      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="text-white fw-bold mb-3">Categories</h6>
        <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
          <li><a href="<?php echo SITE_URL; ?>/gallery.php?category=1">Painting</a></li>
          <li><a href="<?php echo SITE_URL; ?>/gallery.php?category=3">Handcraft & Pottery</a></li>
          <li><a href="<?php echo SITE_URL; ?>/gallery.php?category=4">Photography</a></li>
          <li><a href="<?php echo SITE_URL; ?>/gallery.php?category=5">Digital Art</a></li>
        </ul>
      </div>

      <div class="col-lg-4 col-md-12">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <a href="<?php echo SITE_URL; ?>/about.php" class="btn btn-outline-secondary btn-sm">About Us</a>
          <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-secondary btn-sm">Contact</a>
          <a href="<?php echo SITE_URL; ?>/privacy.php" class="btn btn-outline-secondary btn-sm">Privacy Policy</a>
          <a href="<?php echo SITE_URL; ?>/terms.php" class="btn btn-outline-secondary btn-sm">Terms of Service</a>
        </div>
        <div class="d-flex flex-wrap gap-2">
         <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-success btn-sm">
            <i class="bi bi-shield-lock me-1"></i> Staff & Admin Login
          </a>
        </div>
      </div>
    </div>

    <hr class="border-secondary my-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
      <div>
        &copy; <?php echo date('Y'); ?> NSBM Green University. All rights reserved.
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

</body>
</html>
