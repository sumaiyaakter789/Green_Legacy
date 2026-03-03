<?php
// preloader.php
?>
<div id="preloader" class="fixed inset-0 z-50 flex items-center justify-center bg-white transition-opacity duration-300">
  <div class="relative">
    <!-- Animated logo container -->
    <div class="ml-7 relative w-28 h-28">
      <!-- Outer circle (animated) -->
      <div class="absolute inset-0 rounded-full border-4 border-green-200 border-t-green-500 animate-spin"></div>

      <!-- Company logo -->
      <div class="absolute inset-4 flex items-center justify-center">
        <img src="lg-logo.png" alt="Green Legacy Logo" class="w-full h-full object-contain animate-pulse">
      </div>
    </div>

    <!-- Loading text with animated dots -->
    <div class="mt-5 text-center">
      <p class="text-green-700 font-semibold text-base">
        Loading
        <span class="loading-dots">
          <span class="opacity-0">.</span>
          <span class="opacity-0">.</span>
          <span class="opacity-0">.</span>
        </span>
      </p>
    </div>

    <!-- Progress bar -->
    <div class="mt-3 w-40 h-1.5 bg-green-100 rounded-full overflow-hidden mx-auto">
      <div class="h-full bg-green-500 rounded-full progress-bar" style="width: 0%"></div>
    </div>
  </div>
</div>

<style>
  /* Preloader animations */
  @keyframes dot-pulse {
    0%, 100% { opacity: 0; }
    50% { opacity: 1; }
  }

  .loading-dots span {
    animation: dot-pulse 1s infinite;
  }

  .loading-dots span:nth-child(1) { animation-delay: 0.1s; }
  .loading-dots span:nth-child(2) { animation-delay: 0.25s; }
  .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

  .progress-bar {
    transition: width 0.2s ease-in-out;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const preloader = document.getElementById('preloader');
    const progressBar = document.querySelector('.progress-bar');
    let progress = 0;

    const interval = setInterval(() => {
      progress += Math.random() * 25; // Faster increment
      if (progress > 100) progress = 100;
      progressBar.style.width = progress + '%';

      if (progress >= 100) {
        clearInterval(interval);
        setTimeout(() => {
          preloader.style.opacity = '0';
          setTimeout(() => {
            preloader.style.display = 'none';
          }, 300);
        }, 200);
      }
    }, 100); // Faster interval

    // Fallback in case JS fails
    setTimeout(() => {
      preloader.style.opacity = '0';
      setTimeout(() => {
        preloader.style.display = 'none';
      }, 300);
    }, 2500); // Faster fallback
  });
</script>
