<?php



$pageTitle = 'Home';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pdo = getDBConnection();


$featuredStmt = $pdo->query("
    SELECT a.*, u.name AS artist_name, u.faculty AS artist_faculty, u.profile_pic AS artist_avatar,
           c.name AS category_name, c.slug AS category_slug,
           (SELECT COUNT(*) FROM artwork_likes WHERE artwork_id = a.id) AS likes_count,
           (SELECT IFNULL(AVG(rating), 0) FROM artwork_ratings WHERE artwork_id = a.id) AS avg_rating,
           (SELECT COUNT(*) FROM artwork_ratings WHERE artwork_id = a.id) AS ratings_count
    FROM artworks a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'approved' AND a.is_featured = 1
    ORDER BY a.created_at DESC
    LIMIT 6
");
$featuredArtworks = $featuredStmt->fetchAll();


$categoriesStmt = $pdo->query("
    SELECT c.*, COUNT(a.id) AS total_artworks
    FROM categories c
    LEFT JOIN artworks a ON c.id = a.category_id AND a.status = 'approved'
    GROUP BY c.id
    ORDER BY total_artworks DESC
");
$categories = $categoriesStmt->fetchAll();


$exhibitionsStmt = $pdo->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM exhibition_participants WHERE exhibition_id = e.id) AS total_participants
    FROM exhibitions e
    WHERE e.status IN ('active', 'upcoming')
    ORDER BY e.start_date ASC
    LIMIT 2
");
$exhibitions = $exhibitionsStmt->fetchAll();


$creatorsStmt = $pdo->query("
    SELECT u.id, u.name, u.faculty, u.batch, u.profile_pic,
           COUNT(DISTINCT a.id) AS artworks_count,
           COUNT(DISTINCT l.id) AS total_likes,
           (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) AS followers_count
    FROM users u
    JOIN artworks a ON u.id = a.user_id AND a.status = 'approved'
    LEFT JOIN artwork_likes l ON a.id = l.artwork_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY total_likes DESC, artworks_count DESC
    LIMIT 4
");
$topCreators = $creatorsStmt->fetchAll();


$statsStmt = $pdo->query("
    SELECT 
      (SELECT COUNT(*) FROM artworks WHERE status = 'approved') AS total_approved_art,
      (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_artists,
      (SELECT COUNT(*) FROM exhibitions) AS total_exhibitions,
      (SELECT COUNT(*) FROM artwork_likes) AS total_applauds
");
$stats = $statsStmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>


<section class="hero-banner mb-5">
  <div class="container position-relative py-lg-4">
    <div class="row align-items-center justify-content-between g-4">
      <div class="col-lg-7 col-xl-6">
        <!-- Accessible headers for screen-readers & SEO -->
        <h1 class="visually-hidden">NSBM ArtCraft - Unleash Creativity - Student Arts and Handcraft Showcasing System</h1>
        <p class="visually-hidden">A dedicated web platform for NSBM students to showcase creative projects, paintings, handicrafts, and photography, and participate in campus exhibitions.</p>
        
      
        <div class="d-lg-none mb-3">
          <span class="badge bg-white text-nsbm px-3 py-2 rounded-pill fw-bold mb-2 shadow-sm">
            <i class="bi bi-mortarboard-fill me-1"></i> NSBM Green University
          </span>
          <h2 class="h3 fw-bold text-white mb-2">Student Arts & Handcraft Showcasing System</h2>
          <p class="small text-white-50 mb-3">Showcase student artwork, explore creative talent, and be part of a vibrant art community.</p>
        </div>

       
        <div class="hero-actions-container pt-md-4 pt-2">
          <div style="height: 180px;" class="d-none d-lg-block"></div>
          
          <div class="d-flex flex-wrap gap-3 mt-4 pt-lg-2">
            <a href="<?php echo SITE_URL; ?>/gallery.php" class="btn btn-warning btn-lg px-4 fw-bold shadow">
              <i class="bi bi-compass me-2"></i>Explore Gallery
            </a>
            <?php if (is_student()): ?>
              <a href="<?php echo SITE_URL; ?>/student/upload_artwork.php" class="btn btn-outline-light btn-lg px-4 fw-semibold shadow-sm">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload Artwork
              </a>
            <?php else: ?>
              <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-outline-light btn-lg px-4 fw-semibold shadow-sm">
                <i class="bi bi-person-plus-fill me-2"></i>Register as Student
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

    
      <div class="col-lg-5 col-xl-5 col-xxl-4 d-none d-lg-block">
        <div class="card border-0 p-3 rounded-4 shadow-lg text-white" style="background: rgba(0, 45, 20, 0.75); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255, 255, 255, 0.2) !important;">
          <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 fw-bold">Platform Overview</h6>
          </div>
          <div class="row g-2 text-center">
            <div class="col-6">
              <div class="bg-dark bg-opacity-25 p-3 rounded-3" style="border: 1px solid rgba(255, 255, 255, 0.08);">
                <h3 class="fw-bold mb-0 text-white"><?php echo $stats['total_approved_art']; ?>+</h3>
                <span class="small text-white-50">Artworks</span>
              </div>
            </div>
            <div class="col-6">
              <div class="bg-dark bg-opacity-25 p-3 rounded-3" style="border: 1px solid rgba(255, 255, 255, 0.08);">
                <h3 class="fw-bold mb-0 text-white"><?php echo $stats['total_artists']; ?></h3>
                <span class="small text-white-50">Students</span>
              </div>
            </div>
            <div class="col-6">
              <div class="bg-dark bg-opacity-25 p-3 rounded-3" style="border: 1px solid rgba(255, 255, 255, 0.08);">
                <h3 class="fw-bold mb-0 text-white"><?php echo $stats['total_exhibitions']; ?></h3>
                <span class="small text-white-50">Exhibitions</span>
              </div>
            </div>
            <div class="col-6">
              <div class="bg-dark bg-opacity-25 p-3 rounded-3" style="border: 1px solid rgba(255, 255, 255, 0.08);">
                <h3 class="fw-bold mb-0 text-white"><?php echo $stats['total_applauds']; ?>+</h3>
                <span class="small text-white-50">Likes</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="container mb-5">
  <!-- Search and Category Filters -->
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
    <form action="<?php echo SITE_URL; ?>/gallery.php" method="GET" class="row g-3 align-items-center">
      <div class="col-lg-5">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" placeholder="Search artwork title, medium, artist name...">
        </div>
      </div>
      <div class="col-lg-4">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>">
              <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['total_artworks']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-3">
        <button type="submit" class="btn btn-nsbm w-100">
          <i class="bi bi-search me-1"></i> Search Gallery
        </button>
      </div>
    </form>
    
    <div class="mt-4 pt-3 border-top">
      <div class="d-flex flex-wrap align-items-center">
        <span class="small fw-bold text-muted me-3 mb-2"><i class="bi bi-tags-fill me-1"></i> Popular Categories:</span>
        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
          <a href="<?php echo SITE_URL; ?>/gallery.php?category=<?php echo $cat['id']; ?>" class="filter-chip">
            <i class="bi <?php echo htmlspecialchars($cat['icon']); ?> me-1 text-nsbm"></i>
            <?php echo htmlspecialchars($cat['name']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>


  <div class="d-flex justify-content-between align-items-end mb-4">
    <div>
      <span class="text-nsbm fw-bold text-uppercase small tracking-wider">Showcase</span>
      <h2 class="fw-bold mb-0">Featured Student Creations</h2>
    </div>
    <a href="<?php echo SITE_URL; ?>/gallery.php?filter=featured" class="btn btn-outline-nsbm btn-sm">
      View All Featured <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>

  <div class="row g-4 mb-5">
    <?php if (empty($featuredArtworks)): ?>
      <div class="col-12 text-center py-5">
        <p class="text-muted">No featured artworks yet.</p>
      </div>
    <?php else: ?>
      <?php foreach ($featuredArtworks as $art): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card card-art h-100">
            <div class="artwork-img-container">
              <span class="badge-art-category">
                <?php echo htmlspecialchars($art['category_name']); ?>
              </span>
              <span class="badge-featured">
                <i class="bi bi-star-fill me-1"></i> Featured
              </span>
              <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>">
                <img src="<?php echo artwork_image_url($art['image_path']); ?>" alt="<?php echo htmlspecialchars($art['title']); ?>" loading="lazy">
              </a>
            </div>
            
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title fw-bold mb-0">
                  <a href="<?php echo SITE_URL; ?>/artwork_detail.php?id=<?php echo $art['id']; ?>" class="text-dark text-decoration-none text-truncate d-block" style="max-width: 210px;">
                    <?php echo htmlspecialchars($art['title']); ?>
                  </a>
                </h5>
                <span class="badge bg-light text-secondary border">
                  <?php echo format_lkr($art['price']); ?>
                </span>
              </div>
              
              <p class="small text-muted mb-3 flex-grow-1 text-truncate-2">
                <?php echo htmlspecialchars($art['description']); ?>
              </p>

              <div class="mb-3">
                <?php echo render_star_rating($art['avg_rating'], true, $art['ratings_count']); ?>
              </div>

              <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $art['user_id']; ?>" class="d-flex align-items-center text-decoration-none">
                  <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($art['artist_avatar']); ?>" class="rounded-circle me-2" width="28" height="28" style="object-fit:cover;">
                  <span class="small text-muted fw-semibold"><?php echo htmlspecialchars($art['artist_name']); ?></span>
                </a>
                
                <button type="button" class="btn btn-sm btn-outline-danger btn-artwork-like" data-artwork-id="<?php echo $art['id']; ?>">
                  <i class="bi bi-heart me-1"></i> <span class="like-count"><?php echo $art['likes_count']; ?></span>
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>


  <div class="card border-0 rounded-4 p-4 p-md-5 mb-5 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="row align-items-center mb-4">
      <div class="col-md-8">
        <span class="badge bg-warning text-dark fw-bold mb-2">Campus Events</span>
        <h2 class="fw-bold mb-1">Upcoming Exhibitions & Showcases</h2>
        <p class="text-white-50 mb-0">Join campus art exhibitions, reserve display stalls, and show your work to visitors.</p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="<?php echo SITE_URL; ?>/exhibitions.php" class="btn btn-outline-light">
          Browse All Events <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>

    <div class="row g-4">
      <?php foreach ($exhibitions as $expo): ?>
        <div class="col-lg-6">
          <div class="card bg-dark bg-opacity-50 border-secondary rounded-4 overflow-hidden h-100 text-white">
            <div style="height: 180px; overflow:hidden;">
              <img src="<?php echo SITE_URL . '/uploads/exhibitions/' . htmlspecialchars($expo['banner_image']); ?>" class="w-100 h-100" style="object-fit:cover;" alt="<?php echo htmlspecialchars($expo['title']); ?>">
            </div>
            <div class="p-4 d-flex flex-column flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge <?php echo ($expo['status'] === 'active') ? 'bg-success' : 'bg-primary'; ?> text-uppercase small">
                  <?php echo $expo['status']; ?>
                </span>
                <span class="small text-white-50">
                  <i class="bi bi-people-fill me-1"></i> <?php echo $expo['total_participants']; ?> Registered Students
                </span>
              </div>
              <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($expo['title']); ?></h5>
              <p class="text-warning small mb-2"><i class="bi bi-palette me-1"></i> Theme: <?php echo htmlspecialchars($expo['theme']); ?></p>
              <p class="small text-white-50 mb-3"><?php echo htmlspecialchars($expo['description']); ?></p>
              
              <div class="mt-auto pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
                <span class="small text-white-50">
                  <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo htmlspecialchars($expo['venue']); ?>
                </span>
                <a href="<?php echo SITE_URL; ?>/exhibition_detail.php?id=<?php echo $expo['id']; ?>" class="btn btn-warning btn-sm fw-bold">
                  Event Details & Register
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

 
  <div class="d-flex justify-content-between align-items-end mb-4">
    <di
      <span class="text-nsbm fw-bold text-uppercase small tracking-wider">Student Artists</span>
      <h2 class="fw-bold mb-0">Meet Our Creators</h2>
    </div>
    <a href="<?php echo SITE_URL; ?>/artists.php" class="btn btn-outline-nsbm btn-sm">
      View All Artists <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>

  <div class="row g-4 mb-5">
    <?php foreach ($topCreators as $creator): ?>
      <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100">
          <div class="position-relative mx-auto mb-3">
            <img src="<?php echo SITE_URL . '/uploads/avatars/' . htmlspecialchars($creator['profile_pic']); ?>" alt="<?php echo htmlspecialchars($creator['name']); ?>" class="rounded-circle shadow-sm" width="80" height="80" style="object-fit:cover;">
          </div>
          <h5 class="fw-bold mb-1">
            <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $creator['id']; ?>" class="text-dark text-decoration-none">
              <?php echo htmlspecialchars($creator['name']); ?>
            </a>
          </h5>
          <span class="badge bg-light text-secondary border mb-3 small"><?php echo htmlspecialchars($creator['faculty']); ?></span>
          
          <div class="d-flex justify-content-center gap-3 small text-muted mb-3 py-2 border-top border-bottom">
            <div>
              <span class="d-block fw-bold text-dark"><?php echo $creator['artworks_count']; ?></span>
              <span>Works</span>
            </div>
            <div>
              <span class="d-block fw-bold text-dark"><?php echo $creator['total_likes']; ?></span>
              <span>Likes</span>
            </div>
            <div>
              <span class="d-block fw-bold text-dark followers-count-<?php echo $creator['id']; ?>"><?php echo $creator['followers_count']; ?></span>
              <span>Followers</span>
            </div>
          </div>

          <a href="<?php echo SITE_URL; ?>/artist_profile.php?id=<?php echo $creator['id']; ?>" class="btn btn-outline-nsbm btn-sm w-100">
            View Portfolio
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
