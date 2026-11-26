<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-logo-column">
                <div class="footer-logo">FlexClub</div>
                <div class="footer-subtitle">Тренажерные залы в Томске</div>
            </div>

            <nav class="footer-nav">
                <a href="{{ route('trainers') }}">Тренеры</a>
                <a href="{{ route('plans') }}">Абонемент</a>
                <a href="{{ route('about') }}">О нас</a>
                <a href="{{ route('dashboard') }}">Личный кабинет</a>
                <a href="{{ route('gyms') }}">Залы</a>
            </nav>

            <div class="footer-social">
                <a href="https://ok.ru/" class="social-icon" aria-label="Одноклассники">
                    <img src="{{ asset('images/svg/odnoklassniki.svg') }}" alt="Одноклассники">
                </a>
                <a href="https://vk.com/" class="social-icon" aria-label="ВКонтакте">
                    <img src="{{ asset('images/svg/vk.svg') }}" alt="ВКонтакте">
                </a>
                <a href="https://max.ru/" class="social-icon" aria-label="MAX">
                    <img src="{{ asset('images/svg/max.svg') }}" alt="MAX">
                </a>
            </div>
        </div>

        <div class="footer-divider"></div>

        <div class="footer-bottom">
            <div class="copyright">
                Copyright {{ now()->year }} Тренажерный зал FlexClub
            </div>
        </div>
    </div>
</footer>
