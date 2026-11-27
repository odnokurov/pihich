@extends('layout')
@section('title', 'О нас')
@section('style', 'about')
@section('content')
<section class="branches-banner-section container">
    <div class="branches-banner-container">
        <h2 class="branches-banner-title">FlexClub</h2>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">16</div>
                <div class="stat-text">квалифицированных<br>тренеров и инструкторов</div>
            </div>

            <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-text">постоянных клиентов, которые<br>доверяют нам свое здоровье и эмоции</div>
            </div>
        </div>
    </div>
</section>

<section class="principles-section">
    <div class="container">
        <div class="principles-card">
            <h2 class="principles-title">Наши ключевые принципы</h2>

            <div class="principles-grid">
                <div class="principle-item">
                    <a class="principle-icon">
                        <img src="{{ asset('images/svg/principle-1.svg') }}" alt="">
                    </a>
                    <p class="principle-text">
                        Серьезная подготовка<br>
                        к работе и<br>
                        систематическое<br>
                        внедрение новейших<br>
                        программ от мировых<br>
                        лидеров
                    </p>
                </div>

                <div class="principle-item">
                    <a class="principle-icon">
                        <img src="{{ asset('images/svg/principle-2.svg') }}" alt="">
                    </a>
                    <p class="principle-text">
                        Постоянный<br>
                        профессиональный<br>
                        рост тренерского<br>
                        состава через<br>
                        наставничество и<br>
                        обмен опытом
                    </p>
                </div>

                <div class="principle-item">
                    <a class="principle-icon">
                        <img src="{{ asset('images/svg/principle-3.svg') }}" alt="">
                    </a>
                    <p class="principle-text">
                        Создание дружеской<br>
                        атмосферы и внимательный<br>
                        потенциал каждого<br>
                        члена команды
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="priorities-section">
    <div class="container">
        <h2 class="section-title priorities-title">Наши приоритеты — качество, безопасность<br>и комфорт для каждого клиента</h2>

        <div class="priorities-grid">
            <div class="priority-card">
                <img src="{{ asset('images/about/priorities-1.png') }}" alt="Профессионально оборудованные тренировочные зоны" class="priority-image">
                <p class="priority-text">Профессионально оборудованные тренировочные зоны с современным инвентарём</p>
            </div>

            <div class="priority-card">
                <img src="{{ asset('images/about/priorities-2.png') }}" alt="Разнообразные программы для гармоничного развития тела" class="priority-image">
                <p class="priority-text">Разнообразные программы для гармоничного развития тела</p>
            </div>

            <div class="priority-card">
                <img src="{{ asset('images/about/priorities-3.png') }}" alt="Индивидуальный подход и ориентированность на конкретные цели клиента" class="priority-image">
                <p class="priority-text">Индивидуальный подход и ориентированность на конкретные цели клиента</p>
            </div>

            <div class="priority-card">
                <img src="{{ asset('images/about/priorities-4.png') }}" alt="Доброжелательная атмосфера и хорошая музыка для полного погружения" class="priority-image">
                <p class="priority-text">Доброжелательная атмосфера и хорошая музыка для полного погружения</p>
            </div>
        </div>
    </div>
</section>
@endsection
