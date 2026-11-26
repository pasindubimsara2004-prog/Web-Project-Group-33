
CREATE DATABASE IF NOT EXISTS `nsbm_artcraft` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nsbm_artcraft`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `faculty` VARCHAR(100) DEFAULT 'Faculty of Computing',
  `batch` VARCHAR(50) DEFAULT '26.1',
  `bio` TEXT DEFAULT NULL,
  `profile_pic` VARCHAR(255) DEFAULT 'default_avatar.svg',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-palette',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `artworks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `medium` VARCHAR(150) DEFAULT NULL,
  `dimensions` VARCHAR(100) DEFAULT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) DEFAULT NULL,
  `is_for_sale` TINYINT(1) DEFAULT 0,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `is_featured` TINYINT(1) DEFAULT 0,
  `admin_notes` TEXT DEFAULT NULL,
  `views_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_artwork_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_artwork_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `exhibitions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `theme` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `banner_image` VARCHAR(255) NOT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `venue` VARCHAR(255) NOT NULL,
  `status` ENUM('upcoming', 'active', 'completed') NOT NULL DEFAULT 'upcoming',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `exhibition_participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exhibition_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `artwork_id` INT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_participant` (`exhibition_id`, `user_id`, `artwork_id`),
  CONSTRAINT `fk_participant_exhibition` FOREIGN KEY (`exhibition_id`) REFERENCES `exhibitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participant_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `artwork_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `artwork_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_like` (`artwork_id`, `user_id`),
  CONSTRAINT `fk_like_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `artwork_ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `artwork_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_rating` (`artwork_id`, `user_id`),
  CONSTRAINT `fk_rating_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rating_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `artwork_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `artwork_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comment_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `user_follows` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `follower_id` INT NOT NULL,
  `following_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_follow` (`follower_id`, `following_id`),
  CONSTRAINT `fk_follow_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follow_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




INSERT INTO `users` (`id`, `student_id`, `name`, `email`, `password`, `role`, `faculty`, `batch`, `bio`, `profile_pic`) VALUES
(1, 'ADMIN-001', 'System Administrator', 'admin@nsbm.ac.lk', '$2y$10$h0/3OdfTCbgxYG89xTg5HOsHdjO1cMUn1joyKqzl97xPwE6Q5KIPS', 'admin', 'Faculty of Computing', 'Staff', 'NSBM ArtCraft Platform Administrator & Faculty Coordinator.', 'avatar_admin.svg'),
(2, 'NSBM/SOC/26.1/1042', 'Kavindu Perera', 'student1@nsbm.ac.lk', '$2y$10$YxwvZQHw4ZjrHk2LoKbmKuXaVssm7VmGC3IJnWw1cTzxuCaqMNsJi', 'student', 'Faculty of Computing', '26.1', 'Computing student passionate about oil painting, digital landscape art, and nature visuals.', 'avatar1.svg'),
(3, 'NSBM/FOB/26.1/2150', 'Sanduni Jayasinghe', 'student2@nsbm.ac.lk', '$2y$10$YxwvZQHw4ZjrHk2LoKbmKuXaVssm7VmGC3IJnWw1cTzxuCaqMNsJi', 'student', 'Faculty of Business', '26.1', 'Business undergrad specializing in pottery, handmade crafts, macrame and sustainable eco-art.', 'avatar2.svg'),
(4, 'NSBM/FOE/26.1/3310', 'Induwara Samarasinghe', 'student3@nsbm.ac.lk', '$2y$10$YxwvZQHw4ZjrHk2LoKbmKuXaVssm7VmGC3IJnWw1cTzxuCaqMNsJi', 'student', 'Faculty of Engineering', '26.1', 'Engineering student with an eye for architectural photography, graphite sketches, and 3D modeling.', 'avatar3.svg'),
(5, 'NSBM/SOC/26.1/1089', 'Dilini Silva', 'student4@nsbm.ac.lk', '$2y$10$YxwvZQHw4ZjrHk2LoKbmKuXaVssm7VmGC3IJnWw1cTzxuCaqMNsJi', 'student', 'Faculty of Computing', '26.1', 'Digital illustrator, origami enthusiast, and campus graphic design club contributor.', 'avatar4.svg');


INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Painting', 'painting', 'Traditional and contemporary paintings using acrylic, oil, watercolor, and gouache.', 'bi-palette'),
(2, 'Sketching & Pencil Art', 'sketching', 'Charcoal, graphite, ink illustrations, and intricate line sketches.', 'bi-pencil'),
(3, 'Handcraft & Clay Pottery', 'handcraft', 'Handmade ceramic pottery, woven textiles, macrame, and recycled crafts.', 'bi-handbag'),
(4, 'Photography', 'photography', 'Campus life, landscape, macro, architectural, and fine-art photography.', 'bi-camera'),
(5, 'Digital Art & Illustration', 'digital-art', 'Vector graphics, digital concept art, matte paintings, and 3D renders.', 'bi-laptop'),
(6, 'Sculptures & Woodwork', 'sculptures', 'Wood carvings, wire sculptures, clay models, and mixed media installations.', 'bi-hammer'),
(7, 'Origami & Paper Craft', 'paper-craft', '3D paper sculptures, quilling, origami designs, and paper folding arts.', 'bi-file-earmark-diff');

INSERT INTO `exhibitions` (`id`, `title`, `theme`, `description`, `banner_image`, `start_date`, `end_date`, `venue`, `status`) VALUES
(1, 'NSBM Green Horizons Annual Art Expo 2026', 'Sustainability Meets Creative Imagination', 'The flagship arts festival of NSBM Green University showcasing student creations across painting, sculpture, and photography celebrating eco-consciousness.', 'banner_green_horizons.svg', '2026-12-01 09:00:00', '2026-12-05 18:00:00', 'Student Centre Auditorium & Green Courtyard', 'active'),
(2, 'Reflections: Student Photography & Sketch Exhibition', 'Shadows, Lights & Campus Perspectives', 'An exhibition dedicated to black-and-white photography, street photography, and graphite sketches portraying the vibrant spirit of university life.', 'banner_reflections.svg', '2026-12-15 10:00:00', '2026-12-18 17:00:00', 'Faculty of Computing Atrium, 2nd Floor', 'upcoming'),
(3, 'Heritage Crafts & Clay Pottery Fair', 'Sri Lankan Traditional Crafts with Modern Flair', 'Discover handmade pottery, coconut shell creations, handloom textures, and modern handcrafted student designs.', 'banner_crafts.svg', '2027-01-10 09:30:00', '2027-01-12 16:30:00', 'Faculty of Business Exhibition Concourse', 'upcoming');


INSERT INTO `artworks` (`id`, `user_id`, `category_id`, `title`, `description`, `medium`, `dimensions`, `image_path`, `price`, `is_for_sale`, `status`, `is_featured`, `admin_notes`, `views_count`) VALUES
(1, 2, 1, 'Serenity of Pitipana Dusk', 'A tranquil oil painting capturing the golden sunset reflecting over the serene lake near the NSBM Green University campus.', 'Oil on Stretched Canvas', '24 x 36 inches', 'artwork1.jpg', 8500.00, 1, 'approved', 1, 'Exceptional color blending and depth. Approved for homepage feature.', 142),
(2, 2, 5, 'Cybernetic Green Metropolis', 'A vibrant digital concept painting illustrating a futuristic eco-friendly university city powered by renewable green energy.', 'Digital Art (Photoshop & Procreate)', '4000 x 2500 px (300 DPI)', 'artwork2.jpg', NULL, 0, 'approved', 1, 'Fabulous concept aligning with NSBM green campus ethos.', 210),
(3, 3, 3, 'Handcrafted Terracotta Lotus Vase', 'Carefully wheel-thrown and hand-carved terracotta vase inspired by Sri Lankan water lilies, glazed with natural matte pigments.', 'Terracotta Clay & Natural Glaze', '12 x 8 inches', 'artwork3.jpg', 4200.00, 1, 'approved', 1, 'Beautiful craftsmanship. Approved for annual exhibition.', 98),
(4, 4, 4, 'Echoes in Concrete: Computing Atrium', 'A high-contrast architectural photograph highlighting the geometric symmetry and natural daylight of the NSBM Computing building.', 'Fine Art Digital Photography', 'A2 Print on Archival Paper', 'artwork4.jpg', 3500.00, 1, 'approved', 0, 'Clean architectural composition and great lighting.', 115),
(5, 4, 2, 'Charcoal Silhouette: Old Banyan Tree', 'Detailed graphite and charcoal sketch representing resilience and deep roots, drawn during the university field study camp.', 'Charcoal & 6B Graphite on Paper', '18 x 24 inches', 'artwork5.jpg', 2500.00, 1, 'approved', 0, 'Superb textural shading and expressive strokes.', 76),
(6, 5, 7, 'Geometric Paper Hummingbird', 'A 3D polygonal paper craft sculpture with 120 interlocking hand-cut folds in gradient emerald and teal tones.', 'Heavyweight Color Cardstock', '15 x 15 x 10 cm', 'artwork6.jpg', 1800.00, 1, 'approved', 1, 'Intricate and patient work! Recommended for exhibition display.', 164),
(7, 3, 6, 'Upcycled Teak Wood Abstract Bloom', 'Sculpted entirely from reclaimed timber offcuts collected from campus construction woodshop workshops.', 'Reclaimed Teak & Beeswax Finish', '20 x 10 x 8 inches', 'artwork7.svg', 6000.00, 1, 'approved', 0, 'Great use of upcycled materials.', 88),
(8, 2, 1, 'Rain over the Green Pavilion', 'Atmospheric watercolor study of students walking under umbrellas in the rain at the campus fountain pavilion.', 'Cold Pressed Watercolor Paper (300gsm)', '16 x 20 inches', 'artwork8.svg', 3000.00, 1, 'approved', 0, 'Fluid watercolor technique with natural mood.', 65),
(9, 5, 5, 'Spirit of Innovation', 'Modern 3D and 2D mixed media graphic symbolizing algorithms meeting creativity in computing students.', 'Blender & Illustrator', '3840 x 2160 px', 'artwork9.svg', NULL, 0, 'pending', 0, 'Pending review by Art Admin.', 12),
(10, 4, 4, 'Morning Dew on Botanical Walk', 'Macro photograph of water droplets resting on lotus foliage in the campus ecological pond.', 'Macro Lens 100mm, f/2.8', 'Digital High-Resolution', 'artwork10.svg', 1500.00, 1, 'pending', 0, 'Pending review by Art Admin.', 9);


INSERT INTO `exhibition_participants` (`id`, `exhibition_id`, `user_id`, `artwork_id`, `notes`) VALUES
(1, 1, 2, 1, 'Registered for the Main Hall Gallery Section'),
(2, 1, 3, 3, 'Registered for Handcraft Stall #4'),
(3, 1, 5, 6, 'Registered for Paper Sculptures showcase'),
(4, 2, 4, 4, 'Registered for Computing Faculty Atrium Wall display');


INSERT INTO `artwork_likes` (`artwork_id`, `user_id`) VALUES
(1, 3), (1, 4), (1, 5),
(2, 3), (2, 4), (2, 5), (2, 2),
(3, 2), (3, 4),
(4, 2), (4, 3), (4, 5),
(5, 2), (5, 3),
(6, 2), (6, 3), (6, 4);


INSERT INTO `artwork_ratings` (`artwork_id`, `user_id`, `rating`) VALUES
(1, 3, 5), (1, 4, 5), (1, 5, 4),
(2, 3, 5), (2, 4, 5), (2, 5, 5),
(3, 2, 4), (3, 4, 5),
(4, 2, 4), (4, 3, 4), (4, 5, 5),
(5, 2, 5), (5, 3, 4),
(6, 2, 5), (6, 3, 5), (6, 4, 4);


INSERT INTO `artwork_comments` (`artwork_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 3, 'The sunset reflection on the water is absolutely mesmerizing! Inspiring work, Kavindu!', '2026-11-10 14:22:00'),
(1, 4, 'Love the warm color temperature and serene mood. Perfect representation of Pitipana.', '2026-11-11 09:15:00'),
(2, 5, 'This futuristic campus concept is stunning! The neon green accents look brilliant.', '2026-11-12 11:40:00'),
(3, 2, 'The floral carvings on terracotta are so delicate and traditional. Great job Sanduni!', '2026-11-13 16:05:00'),
(6, 4, 'Unbelievable patience required for 120 geometric folds. Looks like museum-grade origami!', '2026-11-14 18:30:00');


INSERT INTO `user_follows` (`follower_id`, `following_id`) VALUES
(2, 3), (2, 4), (2, 5),
(3, 2), (3, 4),
(4, 2), (4, 3),
(5, 2), (5, 3);
