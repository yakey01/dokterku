{{-- World-Class Jaspel Detail Component View --}}
<div class="world-class-jaspel-detail">
    {!! $generateWorldClassDetailView($userId ?? 0) !!}
</div>

<script>
    // Enhanced interactivity for world-class experience
    document.addEventListener('DOMContentLoaded', function() {
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        // Observe all stat cards and detail sections
        document.querySelectorAll('.stat-card, .detail-card, .procedure-item').forEach(el => {
            observer.observe(el);
        });

        // Add hover effects for interactive elements
        document.querySelectorAll('.procedure-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
        });
    });
</script>

<style>
    /* Additional world-class styling */
    .world-class-jaspel-detail {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    .stat-card {
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    
    .stat-card:hover::before {
        left: 100%;
    }
    
    @media (prefers-reduced-motion: reduce) {
        .animate-fade-in-up {
            animation: none;
        }
        
        .stat-card::before {
            display: none;
        }
    }
</style>