// assets/js/ui.js  — Toast, AuthModal, AuthState, утилиты

// ─── Toast ────────────────────────────────────────────────────────────────────
const Toast = {
    _box: null,
    _get() {
        if (!this._box) {
            this._box = Object.assign(document.createElement('div'), { className: 'toast-box' });
            document.body.appendChild(this._box);
        }
        return this._box;
    },
    show(type, title, msg = '', ms = 4500) {
        const icons = { success: '✓', error: '✕', info: '◆' };
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerHTML = `<span class="toast-ico">${icons[type]||'●'}</span>
            <div><div class="toast-ttl">${title}</div>${msg ? `<div class="toast-msg">${msg}</div>` : ''}</div>`;
        this._get().appendChild(t);
        setTimeout(() => { t.classList.add('toast-out'); setTimeout(() => t.remove(), 320); }, ms);
    },
    success: (t, m) => Toast.show('success', t, m),
    error:   (t, m) => Toast.show('error', t, m),
    info:    (t, m) => Toast.show('info', t, m),
};

// ─── AuthModal ────────────────────────────────────────────────────────────────
const AuthModal = {
    _el: null,

    init() {
        const el = document.createElement('div');
        el.className = 'amodal-overlay';
        el.id = 'authModal';
        el.innerHTML = `
        <div class="amodal" role="dialog" aria-modal="true">
          <button class="amodal-close" id="amodalClose">✕</button>

          <div class="amodal-brand">
            <div class="amodal-logo-ico">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <span>Safe<strong>Peak</strong></span>
          </div>

          <div class="amodal-tabs">
            <button class="amodal-tab active" data-tab="login">Войти</button>
            <button class="amodal-tab" data-tab="register">Регистрация</button>
          </div>

          <!-- LOGIN -->
          <div id="aTabLogin">
            <p class="amodal-sub">Добро пожаловать обратно</p>
            <form id="loginForm" class="amodal-form" novalidate>
              <div class="fg"><label class="fl">Email</label>
                <input class="fi" type="email" name="email" placeholder="your@email.com" required></div>
              <div class="fg"><label class="fl">Пароль</label>
                <input class="fi" type="password" name="password" placeholder="Ваш пароль" required></div>
              <button class="btn btn-full" type="submit" id="loginBtn">Войти</button>
            </form>
            <p class="amodal-switch">Нет аккаунта?
              <button class="link-btn" data-tab="register">Зарегистрироваться</button></p>
          </div>

          <!-- REGISTER -->
          <div id="aTabRegister" style="display:none">
            <p class="amodal-sub">Создайте аккаунт в SafePeak</p>
            <form id="registerForm" class="amodal-form" novalidate>
              <div class="fg"><label class="fl">ФИО</label>
                <input class="fi" type="text" name="name" placeholder="Иванов Иван Иванович" required></div>
              <div class="fg"><label class="fl">Email</label>
                <input class="fi" type="email" name="email" placeholder="your@email.com" required></div>
              <div class="fg"><label class="fl">Телефон <small>(обязательно для связи)</small></label>
                <input class="fi" type="tel" name="phone" placeholder="+7 (___) ___-__-__" required></div>
              <div class="fg"><label class="fl">Пароль</label>
                <input class="fi" type="password" name="password" placeholder="Минимум 8 символов + цифра" required></div>
              <button class="btn btn-full" type="submit" id="registerBtn">Создать аккаунт</button>
            </form>
            <p class="amodal-switch">Уже есть аккаунт?
              <button class="link-btn" data-tab="login">Войти</button></p>
          </div>
        </div>`;
        document.body.appendChild(el);
        this._el = el;

        el.addEventListener('click', e => { if (e.target === el) this.close(); });
        el.querySelector('#amodalClose').addEventListener('click', () => this.close());
        el.querySelectorAll('[data-tab]').forEach(b => b.addEventListener('click', () => this.tab(b.dataset.tab)));
        document.addEventListener('keydown', e => { if (e.key === 'Escape') this.close(); });

        document.getElementById('loginForm').addEventListener('submit',   e => this._login(e));
        document.getElementById('registerForm').addEventListener('submit', e => this._register(e));
    },

    open(tab = 'login') { this.tab(tab); this._el.classList.add('open'); document.body.style.overflow = 'hidden'; },
    close()             { this._el.classList.remove('open'); document.body.style.overflow = ''; },

    tab(t) {
        this._el.querySelectorAll('.amodal-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === t));
        document.getElementById('aTabLogin').style.display    = t === 'login'    ? '' : 'none';
        document.getElementById('aTabRegister').style.display = t === 'register' ? '' : 'none';
    },

    async _login(e) {
        e.preventDefault();
        const f = e.target, btn = document.getElementById('loginBtn');
        const email = f.email.value.trim(), password = f.password.value;
        if (!email || !password) { Toast.error('Ошибка', 'Заполните все поля'); return; }
        btn.disabled = true; btn.innerHTML = '<span class="spin"></span> Входим...';
        const { ok, data } = await API.auth.login({ email, password });
        btn.disabled = false; btn.textContent = 'Войти';
        if (ok && data.success) {
            AuthState.set(data.user); this.close();
            Toast.success('Добро пожаловать!', `Рады видеть вас, ${data.user.name}`);
            window.dispatchEvent(new CustomEvent('auth:in', { detail: data.user }));
        } else {
            Toast.error('Ошибка входа', data.error || 'Что-то пошло не так');
        }
    },

    async _register(e) {
        e.preventDefault();
        const f = e.target, btn = document.getElementById('registerBtn');
        const p = {
            name:     f.name.value.trim(),
            email:    f.email.value.trim(),
            password: f.password.value,
            phone:    f.phone.value.trim()
        };
        if (!p.name || !p.email || !p.password || !p.phone) {
            Toast.error('Ошибка', 'Заполните все поля, включая телефон');
            return;
        }
        btn.disabled = true; btn.innerHTML = '<span class="spin"></span> Создаём...';
        const { ok, data } = await API.auth.register(p);
        btn.disabled = false; btn.textContent = 'Создать аккаунт';
        if (ok && data.success) {
            AuthState.set(data.user); this.close();
            Toast.success('Аккаунт создан!', `Добро пожаловать, ${data.user.name}!`);
            window.dispatchEvent(new CustomEvent('auth:in', { detail: data.user }));
        } else {
            Toast.error('Ошибка', data.error || 'Что-то пошло не так');
        }
    },
};

// ─── AuthState ────────────────────────────────────────────────────────────────
const AuthState = {
    user: null,
    set(u)     { this.user = u; this._sync(); },
    clear()    { this.user = null; this._sync(); },
    loggedIn() { return !!this.user; },

    _sync() {
        const u = this.user;
        document.querySelectorAll('[data-guest]').forEach(el => el.style.display = u ? 'none' : '');
        document.querySelectorAll('[data-authed]').forEach(el => {
            if (u) {
                el.style.display = el.classList.contains('nav-user-wrap') ? 'flex' : '';
            } else {
                el.style.display = 'none';
            }
        });
        document.querySelectorAll('[data-uname]').forEach(el => el.textContent = u ? u.name : '');
        document.querySelectorAll('[data-uinit]').forEach(el => el.textContent = u ? u.name.charAt(0).toUpperCase() : '');
    },

    async check() {
        const { ok, data } = await API.auth.check();
        if (ok && data.authenticated) { this.set(data.user); window.dispatchEvent(new CustomEvent('auth:restore', { detail: data.user })); }
        else this.clear();
    },

    async logout() {
        await API.auth.logout();
        this.clear();
        Toast.info('До свидания!', 'Вы вышли из аккаунта');
        window.dispatchEvent(new Event('auth:out'));
        if (location.pathname.includes('profile')) location.href = 'index.html';
    },
};

// ─── Scroll fade-up ───────────────────────────────────────────────────────────
function initFadeUp() {
    const els = document.querySelectorAll('.fade-up');
    if (!els.length) return;
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    els.forEach(el => io.observe(el));
}

// ─── Sticky nav ───────────────────────────────────────────────────────────────
function initNav() {
    const nav = document.querySelector('.site-header');
    if (nav) window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 20), { passive: true });

    const burger    = document.getElementById('burger');
    const mobileNav = document.getElementById('mobile-nav');
    if (burger && mobileNav) {
        burger.addEventListener('click', () => {
            const open = mobileNav.classList.toggle('open');
            burger.classList.toggle('open', open);
            burger.setAttribute('aria-expanded', String(open));
        });
    }

    const avatarBtn  = document.getElementById('avatarBtn');
    const avatarDrop = document.getElementById('avatarDrop');
    if (avatarBtn && avatarDrop) {
        avatarBtn.addEventListener('click', e => {
            e.stopPropagation();
            const v = avatarDrop.style.display === 'block';
            avatarDrop.style.display = v ? 'none' : 'block';
        });
        document.addEventListener('click', () => { if (avatarDrop) avatarDrop.style.display = 'none'; });
    }

    document.getElementById('nav-login-btn')?.addEventListener('click', e => { e.preventDefault(); AuthModal.open('login'); });
    document.getElementById('mob-login-btn')?.addEventListener('click', e => { e.preventDefault(); AuthModal.open('login'); });
    document.getElementById('logout-btn')?.addEventListener('click', () => AuthState.logout());
    document.getElementById('logout-btn-mob')?.addEventListener('click', () => AuthState.logout());
}

// ─── link-btn style helper ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.link-btn').forEach(b => {
        b.style.cssText = 'background:none;border:none;color:var(--accent);cursor:pointer;font:inherit;text-decoration:underline;padding:0;';
    });
});
