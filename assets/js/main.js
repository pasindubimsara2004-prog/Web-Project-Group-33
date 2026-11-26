

document.addEventListener('DOMContentLoaded', function () {
  
 
  const tooltipElements = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipElements.map(function (el) {
    return new bootstrap.Tooltip(el);
  });

  
  setTimeout(function () {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      if (bsAlert) {
        bsAlert.close();
      }
    });
  }, 5000);

  const imageInputs = document.querySelectorAll('.image-upload-input');
  imageInputs.forEach(function (input) {
    input.addEventListener('change', function (e) {
      const targetSelector = input.dataset.previewTarget;
      const previewContainer = document.querySelector(targetSelector);
      if (!previewContainer) return;

      const file = e.target.files[0];
      if (file) {
        
        if (file.size > 5 * 1024 * 1024) {
          alert('Selected file is too large! Please choose an image smaller than 5MB.');
          input.value = '';
          return;
        }

       
        const reader = new FileReader();
        reader.onload = function (event) {
          previewContainer.innerHTML = `
            <img src="${event.target.result}" class="img-fluid rounded shadow-sm" style="max-height: 280px; object-fit: contain;">
            <p class="text-success small mt-2 fw-semibold mb-0">
              <i class="bi bi-check-circle-fill me-1"></i> ${file.name} ready for upload
            </p>
          `;
        };
        reader.readAsDataURL(file);
      }
    });
  });

 
  const likeButtons = document.querySelectorAll('.btn-artwork-like');
  likeButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      
      const artworkId = btn.dataset.artworkId;
      const countSpan = btn.querySelector('.like-count');
      const heartIcon = btn.querySelector('i');

      fetch(SITE_URL + '/api/like.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'artwork_id=' + encodeURIComponent(artworkId)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
         
          if (countSpan) {
            countSpan.textContent = data.likes_count;
          }
          
         
          if (data.liked) {
            btn.classList.add('btn-danger');
            btn.classList.remove('btn-outline-danger');
            if (heartIcon) {
              heartIcon.classList.remove('bi-heart');
              heartIcon.classList.add('bi-heart-fill');
            }
          } else {
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-outline-danger');
            if (heartIcon) {
              heartIcon.classList.remove('bi-heart-fill');
              heartIcon.classList.add('bi-heart');
            }
          }
        } else if (data.redirect) {
          
          window.location.href = data.redirect;
        } else {
          alert(data.message || 'Could not update like.');
        }
      })
      .catch(error => {
        console.error('AJAX Error toggling like:', error);
      });
    });
  });

  const ratingRadios = document.querySelectorAll('.star-rating-select input');
  ratingRadios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      const artworkId = this.dataset.artworkId;
      const ratingValue = this.value;
      const statusBox = document.getElementById('rating-status-message');

      fetch(SITE_URL + '/api/rate.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'artwork_id=' + encodeURIComponent(artworkId) + '&rating=' + encodeURIComponent(ratingValue)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          if (statusBox) {
            statusBox.innerHTML = `
              <span class="text-success fw-semibold">
                <i class="bi bi-check-circle-fill me-1"></i> You rated this ${ratingValue} stars! (Average: ${data.avg_rating})
              </span>
            `;
          }
          const avgNumberSpan = document.getElementById('average-rating-display');
          if (avgNumberSpan) {
            avgNumberSpan.textContent = data.avg_rating;
          }
        } else if (data.redirect) {
          
          window.location.href = data.redirect;
        } else {
          if (statusBox) {
            statusBox.innerHTML = `<span class="text-danger">${data.message}</span>`;
          }
        }
      })
      .catch(error => {
        console.error('AJAX Error submitting rating:', error);
      });
    });
  });

  
  const followButtons = document.querySelectorAll('.btn-follow-toggle');
  followButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      
      const artistId = btn.dataset.artistId;
      const labelSpan = btn.querySelector('.follow-text');
      const followersSpan = document.querySelector('.followers-count-' + artistId);

      fetch(SITE_URL + '/api/follow.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'artist_id=' + encodeURIComponent(artistId)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          
          if (followersSpan) {
            followersSpan.textContent = data.followers_count;
          }
          
          
          if (data.following) {
            btn.classList.remove('btn-outline-nsbm');
            btn.classList.add('btn-secondary');
            if (labelSpan) labelSpan.textContent = 'Following';
          } else {
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-outline-nsbm');
            if (labelSpan) labelSpan.textContent = 'Follow';
          }
        } else if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          alert(data.message || 'Could not update follow status.');
        }
      })
      .catch(error => {
        console.error('AJAX Error toggling follow:', error);
      });
    });
  });

  
  const formsToValidate = document.querySelectorAll('.needs-validation');
  Array.from(formsToValidate).forEach(form => {
    form.addEventListener('submit', function (event) {
     
      const passwordInput = form.querySelector('input[name="password"]');
      const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');
      
      if (passwordInput && confirmPasswordInput) {
        if (passwordInput.value !== confirmPasswordInput.value) {
          confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else {
          confirmPasswordInput.setCustomValidity('');
        }
      }

    
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

});
