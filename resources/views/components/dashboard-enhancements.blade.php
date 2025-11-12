<style>
/* Dashboard Enhancements - Elegan, Informatif, Interaktif, User-Friendly */

/* Smooth Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

/* Card Hover Effects */
[data-resident-card] {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

[data-resident-card]:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Stat Cards Enhancement */
[data-motion-card] {
    animation: fadeInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

[data-motion-card]::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s;
}

[data-motion-card]:hover::before {
    left: 100%;
}

/* Interactive Stat Numbers */
[data-motion-card] p.text-3xl {
    transition: all 0.3s ease;
}

[data-motion-card]:hover p.text-3xl {
    transform: scale(1.05);
}

/* Bill Cards Enhancement */
article.group {
    position: relative;
    overflow: hidden;
}

article.group::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: linear-gradient(to bottom, #0284C7, #0EA5E9);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

article.group:hover::after {
    transform: scaleY(1);
}

/* Chip Animations */
[data-resident-chip] {
    transition: all 0.2s ease;
}

[data-resident-chip]:hover {
    transform: scale(1.05);
}

/* Button Enhancements */
button, a[class*="rounded-full"] {
    position: relative;
    overflow: hidden;
}

button::before, a[class*="rounded-full"]::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

button:active::before, a[class*="rounded-full"]:active::before {
    width: 300px;
    height: 300px;
}

button .ripple,
a[class*="rounded-full"] .ripple {
    position: absolute;
    display: block;
    border-radius: 50%;
    transform: scale(0);
    transform-origin: center;
    background: rgba(255, 255, 255, 0.28);
    opacity: 0.75;
    pointer-events: none;
    animation: ripple-effect 0.6s ease-out;
}

@keyframes ripple-effect {
    to {
        transform: scale(1);
        opacity: 0;
    }
}

/* Loading State */
[wire\\:loading] {
    opacity: 0.6;
    pointer-events: none;
}

/* Skeleton Loading */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 1000px 100%;
    animation: shimmer 2s infinite;
}

/* Tooltip Enhancement */
[title] {
    position: relative;
}

/* Progress Indicator */
.progress-ring {
    transition: stroke-dashoffset 0.35s;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

/* Responsive Enhancements */
@media (max-width: 768px) {
    [data-motion-card] {
        animation-duration: 0.4s;
    }
}

/* Dark Mode Transitions */
.dark * {
    transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}

/* Focus States for Accessibility */
button:focus-visible, a:focus-visible {
    outline: 2px solid #0284C7;
    outline-offset: 2px;
}

/* Smooth Scroll */
html {
    scroll-behavior: smooth;
}

/* Interactive Insights */
[data-resident-insight] {
    transition: all 0.3s ease;
    cursor: pointer;
}

[data-resident-insight]:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
}

/* Badge Pulse for New Items */
.badge-new {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Gradient Text */
.gradient-text {
    background: linear-gradient(135deg, #0284C7 0%, #0EA5E9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Card Shine Effect */
.card-shine {
    position: relative;
    overflow: hidden;
}

.card-shine::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    bottom: -50%;
    left: -50%;
    background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,0.1) 50%, rgba(255,255,255,0));
    transform: rotateZ(60deg) translate(-5em, 7.5em);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: rotateZ(60deg) translate(-5em, 7.5em); }
    100% { transform: rotateZ(60deg) translate(20em, -20em); }
}

/* Interactive Number Counter */
.counter {
    font-variant-numeric: tabular-nums;
}
</style>

<script>
// Dashboard Interactive Enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Animate numbers on scroll
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    document.querySelectorAll('[data-motion-card]').forEach(el => {
        observer.observe(el);
    });

    // Add ripple effect to buttons
    document.querySelectorAll('button, a[class*="rounded-full"]').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Smooth scroll to sections
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Add loading state feedback
    document.addEventListener('livewire:init', () => {
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                // Add success feedback
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-in';
                toast.textContent = '✓ Updated';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            });
        });
    });
});
</script>
