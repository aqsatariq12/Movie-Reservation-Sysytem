<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CineBook — Book Your Movie Experience</title>
    <meta name="description" content="Discover movies, explore showtimes, and plan your next cinema experience with CineBook.">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cinebook-page">

    {{-- Navigation --}}
    <header class="cinebook-header">
        <div class="cinebook-container cinebook-navbar">
            <a href="{{ route('home') }}" class="cinebook-logo">
                <span class="cinebook-logo-icon">C</span>
                <span>CineBook</span>
            </a>

            <nav class="cinebook-nav" aria-label="Main navigation">
                <a href="#now-showing">Movies</a>
                <a href="#showtimes">Showtimes</a>
                <a href="#coming-soon">Coming Soon</a>
            </nav>

            <div class="cinebook-auth">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="cinebook-login">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="cinebook-login">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="cinebook-button cinebook-button-small">
                                Sign up
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <main>
        {{-- Hero section --}}
        
{{-- Hero section --}}
<section class="cinebook-hero">
    <div class="cinebook-container cinebook-hero-inner">
        <div class="cinebook-hero-content">

            <span class="cinebook-eyebrow">
                01 · CUSTOMER / HOME · NOW SHOWING
            </span>

            <div class="cinebook-location-label">
                <span class="cinebook-live-dot"></span>
                ONLY AT CINEBOOK DOWNTOWN
            </div>

            <h1>The Last Horizon</h1>

            <div class="cinebook-hero-meta">
                <span>2026</span>
                <span>·</span>
                <span>PG-13</span>
                <span>·</span>
                <span>124 min</span>
                <span>·</span>
                <span>Sci-Fi / Drama</span>
            </div>

            <p>
                Beyond the edge of the known universe, one signal
                leads an astronaut back to everything she thought
                she had lost.
            </p>

            <div class="cinebook-hero-actions">
                <a href="#showtimes" class="cinebook-button">
                    Find seats <span aria-hidden="true">→</span>
                </a>

                <a href="#trailer"
                   class="cinebook-button cinebook-button-outline">
                    <span aria-hidden="true">▷</span>
                    Watch trailer
                </a>
            </div>

            <div class="cinebook-hero-showtime">
                <span>Tonight</span>
                <span>·</span>
                <span>Screen 1</span>
                <span>·</span>
                <span>19:30</span>
                <span>·</span>
                <strong>From $10</strong>
            </div>

        </div>

        
    </div>
</section>


        {{-- Showtime discovery --}}
        <section class="cinebook-section" id="showtimes">
            <div class="cinebook-container">
                <div class="cinebook-section-heading">
                    <div>
                        <span class="cinebook-eyebrow">PLAN YOUR VISIT</span>
                        <h2>Find your showtime</h2>
                        <p>Choose a date and discover what's playing.</p>
                    </div>
                </div>

                <div class="cinebook-showtime-panel">
                    <div class="cinebook-date-list" aria-label="Choose a date">
                        <button type="button" class="cinebook-date is-selected">
                            <span>Today</span>
                            <strong>{{ now()->format('d') }}</strong>
                            <small>{{ now()->format('M') }}</small>
                        </button>

                        <button type="button" class="cinebook-date">
                            <span>Tomorrow</span>
                            <strong>{{ now()->addDay()->format('d') }}</strong>
                            <small>{{ now()->addDay()->format('M') }}</small>
                        </button>

                        <button type="button" class="cinebook-date">
                            <span>{{ now()->addDays(2)->format('D') }}</span>
                            <strong>{{ now()->addDays(2)->format('d') }}</strong>
                            <small>{{ now()->addDays(2)->format('M') }}</small>
                        </button>

                        <button type="button" class="cinebook-date">
                            <span>{{ now()->addDays(3)->format('D') }}</span>
                            <strong>{{ now()->addDays(3)->format('d') }}</strong>
                            <small>{{ now()->addDays(3)->format('M') }}</small>
                        </button>
                    </div>

                    <div class="cinebook-showtime-bottom">
                        <div>
                            <strong>Ready for movie night?</strong>
                            <p>Explore the movies currently on screen.</p>
                        </div>

                        <a href="#now-showing" class="cinebook-button">
                            Explore movies <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Now showing --}}
        <section class="cinebook-section cinebook-section-soft" id="now-showing">
            <div class="cinebook-container">
                <div class="cinebook-section-heading cinebook-heading-row">
                    <div>
                        <span class="cinebook-eyebrow">ON THE BIG SCREEN</span>
                        <h2>Now Showing</h2>
                        <p>Find your next favourite movie.</p>
                    </div>

                    <a href="#showtimes" class="cinebook-text-link">
                        View showtimes <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="cinebook-movie-grid">
                    @php
                        $movies = [
                            ['image' => 'movie-1.png', 'title' => 'Featured Movie 1', 'genre' => 'Drama'],
                            ['image' => 'movie-2.png', 'title' => 'Featured Movie 2', 'genre' => 'Adventure'],
                            ['image' => 'movie-3.png', 'title' => 'Featured Movie 3', 'genre' => 'Action'],
                            ['image' => 'movie-4.png', 'title' => 'Featured Movie 4', 'genre' => 'Thriller'],
                            ['image' => 'movie-5.png', 'title' => 'Featured Movie 5', 'genre' => 'Sci-Fi'],
                        ];
                    @endphp

                    @foreach ($movies as $movie)
                        <article class="cinebook-movie-card">
                            <a href="#showtimes" class="cinebook-poster-link"
                               aria-label="Explore showtimes for {{ $movie['title'] }}">
                                <img
                                    class="cinebook-poster"
                                    src="{{ asset('images/cinebook/' . $movie['image']) }}"
                                    alt="{{ $movie['title'] }} movie poster"
                                    loading="lazy"
                                >
                                <span class="cinebook-poster-overlay">
                                    View showtimes <span aria-hidden="true">→</span>
                                </span>
                            </a>

                            <div class="cinebook-movie-info">
                                <div>
                                    <h3>{{ $movie['title'] }}</h3>
                                    <p>{{ $movie['genre'] }}</p>
                                </div>
                                <a href="#showtimes" class="cinebook-arrow-link"
                                   aria-label="View showtimes for {{ $movie['title'] }}">↗</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Coming soon --}}
        <section class="cinebook-section" id="coming-soon">
            <div class="cinebook-container">
                <div class="cinebook-section-heading cinebook-heading-row">
                    <div>
                        <span class="cinebook-eyebrow">COMING TO THE CINEMA</span>
                        <h2>Coming Soon</h2>
                        <p>Keep an eye out for your next big-screen adventure.</p>
                    </div>
                </div>

                <div class="cinebook-coming-panel">
                    <div class="cinebook-coming-item">
                        <span class="cinebook-coming-number">01</span>
                        <div>
                            <h3>New stories are on the way</h3>
                            <p>Check back soon for upcoming releases and announcements.</p>
                        </div>
                        <span class="cinebook-coming-icon" aria-hidden="true">✦</span>
                    </div>

                    <div class="cinebook-coming-item">
                        <span class="cinebook-coming-number">02</span>
                        <div>
                            <h3>Plan your next movie night</h3>
                            <p>Discover what's currently showing while you wait.</p>
                        </div>
                        <a href="#now-showing" class="cinebook-text-link">Explore →</a>
                    </div>

                    <div class="cinebook-coming-item">
                        <span class="cinebook-coming-number">03</span>
                        <div>
                            <h3>Your next favourite film awaits</h3>
                            <p>Find a movie that makes your cinema visit memorable.</p>
                        </div>
                        <a href="#showtimes" class="cinebook-text-link">Find a show →</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Genres --}}
        <section class="cinebook-section cinebook-section-soft">
            <div class="cinebook-container cinebook-genres">
                <div>
                    <span class="cinebook-eyebrow">FIND YOUR MOOD</span>
                    <h2>Explore by genre</h2>
                    <p>What kind of story are you in the mood for?</p>
                </div>

                <div class="cinebook-genre-list">
                    <a href="#now-showing">Action <span>↗</span></a>
                    <a href="#now-showing">Adventure <span>↗</span></a>
                    <a href="#now-showing">Comedy <span>↗</span></a>
                    <a href="#now-showing">Drama <span>↗</span></a>
                    <a href="#now-showing">Sci-Fi <span>↗</span></a>
                    <a href="#now-showing">Thriller <span>↗</span></a>
                </div>
            </div>
        </section>

        {{-- Cinema call to action --}}
        <section class="cinebook-section">
            <div class="cinebook-container">
                <div class="cinebook-cinema-banner">
                    <div>
                        <span class="cinebook-eyebrow">MAKE IT A MOVIE NIGHT</span>
                        <h2>Big stories. Big screen. Great memories.</h2>
                        <p>Your next cinema experience starts with CineBook.</p>
                    </div>

                    <a href="#now-showing" class="cinebook-button cinebook-button-light">
                        Discover movies <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="cinebook-footer">
        <div class="cinebook-container">
            <div class="cinebook-footer-main">
                <a href="{{ route('home') }}" class="cinebook-logo">
                    <span class="cinebook-logo-icon">C</span>
                    <span>CineBook</span>
                </a>

                <p>Your next great cinema experience starts here.</p>

                <nav class="cinebook-footer-links" aria-label="Footer navigation">
                    <a href="#now-showing">Movies</a>
                    <a href="#showtimes">Showtimes</a>
                    <a href="#coming-soon">Coming Soon</a>
                </nav>
            </div>

            <div class="cinebook-footer-bottom">
                <span>© {{ now()->year }} CineBook. All rights reserved.</span>
                <span>Made for movie lovers.</span>
            </div>
        </div>
    </footer>

</body>
</html>