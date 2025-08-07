// LoginSuccessAnimation class - separated for clean module structure
class LoginSuccessAnimation {
  private isAnimating: boolean = false;
  private particles: any[] = [];
  private canvas: HTMLCanvasElement | null = null;
  private ctx: CanvasRenderingContext2D | null = null;

  constructor() {
    this.isAnimating = false;
    this.particles = [];
    this.canvas = null;
    this.ctx = null;
  }

  initCanvas() {
    this.canvas = document.createElement('canvas');
    this.canvas.style.position = 'fixed';
    this.canvas.style.top = '0';
    this.canvas.style.left = '0';
    this.canvas.style.width = '100%';
    this.canvas.style.height = '100%';
    this.canvas.style.pointerEvents = 'none';
    this.canvas.style.zIndex = '9999';
    
    this.canvas.width = window.innerWidth;
    this.canvas.height = window.innerHeight;
    
    this.ctx = this.canvas.getContext('2d');
    document.body.appendChild(this.canvas);
    
    return this.canvas;
  }

  createParticles(centerX: number, centerY: number, count = 50) {
    this.particles = [];
    
    for (let i = 0; i < count; i++) {
      this.particles.push({
        x: centerX,
        y: centerY,
        vx: (Math.random() - 0.5) * 15,
        vy: (Math.random() - 0.5) * 15,
        life: 1,
        decay: Math.random() * 0.015 + 0.01,
        size: Math.random() * 4 + 2,
        color: this.getRandomColor(),
        rotation: Math.random() * Math.PI * 2,
        rotationSpeed: (Math.random() - 0.5) * 0.2
      });
    }
  }

  getRandomColor() {
    const colors = ['#00f5ff', '#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#ef4444'];
    return colors[Math.floor(Math.random() * colors.length)];
  }

  animateParticles() {
    if (!this.ctx || this.particles.length === 0) return;

    this.ctx.clearRect(0, 0, this.canvas!.width, this.canvas!.height);

    for (let i = this.particles.length - 1; i >= 0; i--) {
      const particle = this.particles[i];
      
      particle.x += particle.vx;
      particle.y += particle.vy;
      particle.vy += 0.3;
      particle.life -= particle.decay;
      particle.rotation += particle.rotationSpeed;

      if (particle.life <= 0) {
        this.particles.splice(i, 1);
        continue;
      }

      this.ctx.save();
      this.ctx.globalAlpha = particle.life;
      this.ctx.translate(particle.x, particle.y);
      this.ctx.rotate(particle.rotation);
      
      this.drawStar(particle.size, particle.color);
      
      this.ctx.restore();
    }

    if (this.particles.length > 0) {
      requestAnimationFrame(() => this.animateParticles());
    } else {
      if (this.canvas && document.body.contains(this.canvas)) {
        document.body.removeChild(this.canvas);
      }
    }
  }

  drawStar(size: number, color: string) {
    if (!this.ctx) return;
    this.ctx.fillStyle = color;
    this.ctx.beginPath();
    
    for (let i = 0; i < 5; i++) {
      const angle = (i * Math.PI * 2) / 5;
      const x = Math.cos(angle) * size;
      const y = Math.sin(angle) * size;
      
      if (i === 0) {
        this.ctx.moveTo(x, y);
      } else {
        this.ctx.lineTo(x, y);
      }
    }
    
    this.ctx.closePath();
    this.ctx.fill();
  }

  createRippleEffect(element: HTMLElement) {
    const ripple = document.createElement('div');
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'radial-gradient(circle, rgba(139,92,246,0.3) 0%, rgba(139,92,246,0) 70%)';
    ripple.style.transform = 'scale(0)';
    ripple.style.animation = 'rippleAnimation 1s ease-out forwards';
    ripple.style.pointerEvents = 'none';
    ripple.style.zIndex = '1000';

    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 2;
    ripple.style.width = size + 'px';
    ripple.style.height = size + 'px';
    ripple.style.left = (rect.left + rect.width / 2 - size / 2) + 'px';
    ripple.style.top = (rect.top + rect.height / 2 - size / 2) + 'px';

    document.body.appendChild(ripple);

    setTimeout(() => {
      if (document.body.contains(ripple)) {
        document.body.removeChild(ripple);
      }
    }, 1000);
  }

  showSuccessMessage(message = 'Login Berhasil!', duration = 3000) {
    const successDiv = document.createElement('div');
    successDiv.innerHTML = `
      <div style="
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 20px;
        font-size: clamp(14px, 4vw, 18px);
        font-weight: bold;
        text-align: center;
        z-index: 10000;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        animation: successMessageAnimation 3s ease forwards;
        border: 2px solid rgba(255,255,255,0.2);
      ">
        <div style="margin-bottom: 10px; font-size: 24px;">🎉</div>
        ${message}
      </div>
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
      if (document.body.contains(successDiv)) {
        document.body.removeChild(successDiv);
      }
    }, duration);
  }

  slideOutLoginForm(formElement: HTMLElement, direction = 'left') {
    const translateX = direction === 'left' ? '-100%' : '100%';
    
    formElement.style.transition = 'transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.8s ease';
    formElement.style.transform = `translateX(${translateX})`;
    formElement.style.opacity = '0';
  }

  playLoginSuccessAnimation(options: any = {}) {
    if (this.isAnimating) {
      console.warn('🚫 Animation already in progress, skipping');
      return;
    }
    
    console.log('🎬 Starting playLoginSuccessAnimation with options:', options);
    this.isAnimating = true;
    
    const {
      loginButton = null,
      loginForm = null,
      showParticles = true,
      showRipple = true,
      showSuccessMessage = true,
      successMessage = 'Login Berhasil!',
      slideDirection = 'left',
      onComplete = null
    } = options;

    if (showRipple && loginButton) {
      console.log('💫 Creating ripple effect...');
      this.createRippleEffect(loginButton);
    }

    if (showParticles && loginButton) {
      console.log('✨ Creating particles...');
      const rect = loginButton.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      
      console.log('🎯 Button position:', { centerX, centerY, rect });
      
      this.initCanvas();
      this.createParticles(centerX, centerY, 30);
      this.animateParticles();
    }

    if (showSuccessMessage) {
      console.log('💬 Scheduling success message...');
      setTimeout(() => {
        console.log('💬 Showing success message now');
        this.showSuccessMessage(successMessage);
      }, 500);
    }

    if (loginForm) {
      console.log('📱 Scheduling form slide out...');
      setTimeout(() => {
        console.log('📱 Sliding form out now');
        this.slideOutLoginForm(loginForm, slideDirection);
      }, 1500);
    }

    setTimeout(() => {
      console.log('🏁 Animation sequence complete');
      this.isAnimating = false;
      if (onComplete) {
        onComplete();
      }
    }, 4000);
  }

  cleanup() {
    if (this.canvas && document.body.contains(this.canvas)) {
      document.body.removeChild(this.canvas);
    }
    this.particles = [];
    this.isAnimating = false;
  }
}

export { LoginSuccessAnimation };