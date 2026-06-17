/**
 * Scoped JS for Custom Product Gallery
 * Slug: egnitech-one/product-gallery
 */

document.addEventListener('DOMContentLoaded', function() {
	// Gate on editor iframe to prevent running inside Gutenberg admin panel
	if (window.frameElement) {
		return;
	}

	var galleryEl = document.getElementById('egnitech-product-gallery');
	if (!galleryEl) {
		return;
	}

	// Parse images json
	var imagesData = galleryEl.getAttribute('data-images');
	var images = [];
	try {
		images = JSON.parse(imagesData);
	} catch (e) {
		console.error('Failed to parse gallery images JSON', e);
		return;
	}

	if (!images || images.length === 0) {
		return;
	}

	// Preload large and full images for instant transitions after the page has fully loaded
	function preloadImages() {
		images.forEach(function(img) {
			if (img.large) {
				var lImg = new Image();
				lImg.src = img.large;
			}
			if (img.full) {
				var fImg = new Image();
				fImg.src = img.full;
			}
		});
	}

	if (document.readyState === 'complete') {
		preloadImages();
	} else {
		window.addEventListener('load', preloadImages);
	}

	var currentIndex = 0;
	var isScrollingProgrammatically = false;
	var scrollTimeout;
	
	// Cache elements
	var slider = galleryEl.querySelector('.egnitech-gallery-main');
	var thumbBtns = galleryEl.querySelectorAll('.egnitech-gallery-thumb-btn');
	var arrowPrev = galleryEl.querySelector('.egnitech-gallery-arrow--prev');
	var arrowNext = galleryEl.querySelector('.egnitech-gallery-arrow--next');
	
	// Cache mobile pagination elements
	var mobileArrowPrev = galleryEl.querySelector('.egnitech-gallery-mobile-arrow--prev');
	var mobileArrowNext = galleryEl.querySelector('.egnitech-gallery-mobile-arrow--next');
	var mobileCurrentEl = galleryEl.querySelector('.egnitech-gallery-mobile-current');
	
	// Cache lightbox elements
	var lightbox = document.getElementById('egnitech-gallery-lightbox');
	var lightboxImg = document.getElementById('egnitech-lightbox-active-image');
	var lightboxCaption = document.getElementById('egnitech-lightbox-caption');
	var lightboxClose = lightbox ? lightbox.querySelector('.egnitech-lightbox-close') : null;
	var lightboxBackdrop = lightbox ? lightbox.querySelector('.egnitech-lightbox-backdrop') : null;
	var lightboxPrev = lightbox ? lightbox.querySelector('.egnitech-lightbox-arrow--prev') : null;
	var lightboxNext = lightbox ? lightbox.querySelector('.egnitech-lightbox-arrow--next') : null;

	// Helper function to switch images in the gallery
	function showImage(index) {
		if (index < 0) {
			index = images.length - 1;
		} else if (index >= images.length) {
			index = 0;
		}

		currentIndex = index;

		// Programmatically scroll to active slide
		if (slider) {
			var slide = slider.querySelector('.egnitech-gallery-slide');
			if (slide) {
				var slideWidth = slide.offsetWidth;
				var gap = parseInt(window.getComputedStyle(slider).gap) || 0;
				isScrollingProgrammatically = true;

				if (scrollTimeout) {
					clearTimeout(scrollTimeout);
				}

				slider.scrollTo({
					left: currentIndex * (slideWidth + gap),
					behavior: 'smooth'
				});

				scrollTimeout = setTimeout(function() {
					isScrollingProgrammatically = false;
				}, 400);
			}
		}

		updateUI();
	}

	// Update the UI state (thumbnails, fraction count, lightbox)
	function updateUI() {
		// Update thumbnails active state
		thumbBtns.forEach(function(btn, i) {
			if (i === currentIndex) {
				btn.classList.add('is-active');
				btn.setAttribute('aria-selected', 'true');
				// Smooth scroll active thumbnail into view
				btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
			} else {
				btn.classList.remove('is-active');
				btn.setAttribute('aria-selected', 'false');
			}
		});

		// Update mobile fraction index
		if (mobileCurrentEl) {
			mobileCurrentEl.textContent = currentIndex + 1;
		}

		// If lightbox is open, update lightbox image too
		if (lightbox && lightbox.classList.contains('is-open')) {
			updateLightboxImage();
		}
	}

	// Detect the index based on scroll position (for native swiping on mobile)
	if (slider) {
		var scrollDebounceTimeout;
		slider.addEventListener('scroll', function() {
			if (isScrollingProgrammatically) {
				return;
			}

			if (scrollDebounceTimeout) {
				window.cancelAnimationFrame(scrollDebounceTimeout);
			}

			scrollDebounceTimeout = window.requestAnimationFrame(function() {
				var scrollLeft = slider.scrollLeft;
				var slide = slider.querySelector('.egnitech-gallery-slide');
				if (!slide) return;

				var slideWidth = slide.offsetWidth;
				var gap = parseInt(window.getComputedStyle(slider).gap) || 0;
				var totalWidth = slideWidth + gap;

				var newIndex = Math.round(scrollLeft / totalWidth);
				if (newIndex >= 0 && newIndex < images.length && newIndex !== currentIndex) {
					currentIndex = newIndex;
					updateUI();
				}
			});
		}, { passive: true });
	}

	// Update the image inside the lightbox
	function updateLightboxImage() {
		if (!lightboxImg || !images[currentIndex]) return;
		
		lightboxImg.style.opacity = '0';
		setTimeout(function() {
			lightboxImg.src = images[currentIndex].full;
			lightboxImg.alt = images[currentIndex].alt;
			if (lightboxCaption) {
				lightboxCaption.textContent = images[currentIndex].alt;
			}
			lightboxImg.style.opacity = '1';
		}, 150);
	}

	// Thumbnail click event
	thumbBtns.forEach(function(btn) {
		btn.addEventListener('click', function() {
			var index = parseInt(btn.getAttribute('data-index'), 10);
			if (!isNaN(index)) {
				showImage(index);
			}
		});
	});

	// Arrow clicks on main gallery (Desktop)
	if (arrowPrev) {
		arrowPrev.addEventListener('click', function(e) {
			e.stopPropagation();
			showImage(currentIndex - 1);
		});
	}

	if (arrowNext) {
		arrowNext.addEventListener('click', function(e) {
			e.stopPropagation();
			showImage(currentIndex + 1);
		});
	}

	// Arrow clicks on mobile pagination
	if (mobileArrowPrev) {
		mobileArrowPrev.addEventListener('click', function(e) {
			e.stopPropagation();
			showImage(currentIndex - 1);
		});
	}

	if (mobileArrowNext) {
		mobileArrowNext.addEventListener('click', function(e) {
			e.stopPropagation();
			showImage(currentIndex + 1);
		});
	}

	// Open Lightbox from any slide
	var triggers = galleryEl.querySelectorAll('.egnitech-gallery-trigger');
	triggers.forEach(function(trigger) {
		trigger.addEventListener('click', function() {
			var index = parseInt(trigger.getAttribute('data-index'), 10);
			if (!isNaN(index)) {
				currentIndex = index;
			}
			if (lightbox) {
				lightbox.classList.add('is-open');
				document.body.style.overflow = 'hidden'; // Lock scroll
				updateLightboxImage();
				lightbox.focus();
			}
		});
	});

	// Close Lightbox
	function closeLightbox() {
		if (lightbox) {
			lightbox.classList.remove('is-open');
			document.body.style.overflow = ''; // Unlock scroll
		}
	}

	if (lightboxClose) {
		lightboxClose.addEventListener('click', closeLightbox);
	}
	if (lightboxBackdrop) {
		lightboxBackdrop.addEventListener('click', closeLightbox);
	}

	// Lightbox navigation clicks
	if (lightboxPrev) {
		lightboxPrev.addEventListener('click', function() {
			showImage(currentIndex - 1);
		});
	}
	if (lightboxNext) {
		lightboxNext.addEventListener('click', function() {
			showImage(currentIndex + 1);
		});
	}

	// Keyboard Controls
	document.addEventListener('keydown', function(e) {
		if (!lightbox || !lightbox.classList.contains('is-open')) {
			return;
		}

		if (e.key === 'Escape') {
			closeLightbox();
		} else if (e.key === 'ArrowLeft') {
			showImage(currentIndex - 1);
		} else if (e.key === 'ArrowRight') {
			showImage(currentIndex + 1);
		}
	});

	// Touch/Swipe gestures for lightbox modal
	var touchstartX = 0;
	var touchendX = 0;
	var touchstartY = 0;
	var touchendY = 0;

	if (lightbox) {
		lightbox.addEventListener('touchstart', function(e) {
			touchstartX = e.changedTouches[0].screenX;
			touchstartY = e.changedTouches[0].screenY;
		}, { passive: true });

		lightbox.addEventListener('touchend', function(e) {
			touchendX = e.changedTouches[0].screenX;
			touchendY = e.changedTouches[0].screenY;
			handleSwipe(true);
		}, { passive: true });
	}

	function handleSwipe(isLightbox) {
		var diffX = touchendX - touchstartX;
		var diffY = touchendY - touchstartY;

		// Verify swipe is horizontal rather than vertical scroll
		if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
			if (diffX > 0) {
				// Swipe Right -> Previous image
				showImage(currentIndex - 1);
			} else {
				// Swipe Left -> Next image
				showImage(currentIndex + 1);
			}
		}
	}

	// Cache thumbnails scrollbar and arrows for Bottom Horizontal Carousel (Desktop layout)
	var thumbsContainer = galleryEl.querySelector('.egnitech-gallery-thumbs');
	var thumbsPrev = galleryEl.querySelector('.egnitech-thumbs-arrow--prev');
	var thumbsNext = galleryEl.querySelector('.egnitech-thumbs-arrow--next');
	var thumbsScrollbar = galleryEl.querySelector('.egnitech-gallery-thumbs-scrollbar-bar');

	if (thumbsContainer) {
		// Update scrollbar width & visibility of arrows
		function updateThumbsScrollState() {
			if (thumbsScrollbar) {
				var maxScroll = thumbsContainer.scrollWidth - thumbsContainer.clientWidth;
				var scrollPct = maxScroll > 0 ? (thumbsContainer.scrollLeft / maxScroll) * 100 : 0;
				thumbsScrollbar.style.width = scrollPct + '%';
			}

			// Toggle arrows disabled state based on scroll bounds
			if (thumbsPrev) {
				thumbsPrev.disabled = thumbsContainer.scrollLeft <= 5;
			}
			if (thumbsNext) {
				thumbsNext.disabled = thumbsContainer.scrollLeft >= (thumbsContainer.scrollWidth - thumbsContainer.clientWidth - 5);
			}
		}

		// Initial check
		setTimeout(updateThumbsScrollState, 100);

		// Event listener
		thumbsContainer.addEventListener('scroll', updateThumbsScrollState);
		window.addEventListener('resize', updateThumbsScrollState);

		// Thumbs Chevron Clicks
		if (thumbsPrev) {
			thumbsPrev.addEventListener('click', function(e) {
				e.stopPropagation();
				thumbsContainer.scrollBy({ left: -160, behavior: 'smooth' });
			});
		}
		if (thumbsNext) {
			thumbsNext.addEventListener('click', function(e) {
				e.stopPropagation();
				thumbsContainer.scrollBy({ left: 160, behavior: 'smooth' });
			});
		}
	}
});
